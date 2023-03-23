<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Testing;

use GraphQL\Type\Schema;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
use Nuwave\Lighthouse\Schema\AST\ASTBuilder;
use Nuwave\Lighthouse\Schema\AST\ASTCache;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\SchemaBuilder;
use Nuwave\Lighthouse\Schema\Source\SchemaSourceProvider;
use Nuwave\Lighthouse\Schema\TypeRegistry;

use function spl_object_hash;

class SchemaBuilderWrapper extends SchemaBuilder {
    protected ?SchemaBuilder $current = null;

    /**
     * @var array<class-string, object>
     */
    protected array $singletons = [];

    /**
     * @noinspection             PhpMissingParentConstructorInspection
     * @phpstan-ignore-next-line no need to call parent `__construct`
     */
    public function __construct(
        protected SchemaBuilder $builder,
    ) {
        // no need to call parent
    }

    protected function getSchemaBuilder(): SchemaBuilder {
        return $this->current ?? $this->builder;
    }

    public function schema(): Schema {
        return $this->getSchemaBuilder()->schema();
    }

    public function setSchema(?SchemaSourceProvider $provider): void {
        // Origins
        $container = Container::getInstance();

        if (!$this->singletons) {
            $this->singletons = [
                ASTCache::class   => $container->make(ASTCache::class),
                ASTBuilder::class => $container->make(ASTBuilder::class),
            ];
        }

        // Build
        $builder = null;

        if ($provider) {
            $types      = $container->make(TypeRegistry::class);
            $dispatcher = $container->make(EventsDispatcher::class);
            $directives = $container->make(DirectiveLocator::class);
            $astCache   = $container->make(SchemaCache::class, ['key' => spl_object_hash($provider)]);
            $astBuilder = new ASTBuilder($directives, $provider, $dispatcher, $astCache);
            $builder    = new SchemaBuilder($types, $astBuilder);

            $container->instance(ASTCache::class, $astCache);
            $container->instance(ASTBuilder::class, $astBuilder);
        } else {
            foreach ($this->singletons as $abstract => $instance) {
                $container->instance($abstract, $instance);
            }
        }

        // Set
        $this->current = $builder;
    }
}
