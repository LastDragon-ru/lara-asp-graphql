<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Testing\Package;

use GraphQL\Type\Schema;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Laravel\Scout\Builder as ScoutBuilder;
use LastDragon_ru\LaraASP\GraphQL\Provider;
use LastDragon_ru\LaraASP\GraphQL\Testing\GraphQLAssertions;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\Data\Models\TestObject;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\Directives\ExposeBuilderDirective;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\Provider as TestProvider;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\SchemaPrinter\LighthouseDirectiveFilter;
use LastDragon_ru\LaraASP\GraphQL\Utils\ArgumentFactory;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\Printer;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\Settings;
use LastDragon_ru\LaraASP\GraphQLPrinter\Testing\TestSettings;
use LastDragon_ru\LaraASP\Serializer\Provider as SerializerProvider;
use LastDragon_ru\LaraASP\Testing\Package\TestCase as PackageTestCase;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use Nuwave\Lighthouse\LighthouseServiceProvider;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Testing\TestingServiceProvider as LighthouseTestingServiceProvider;
use Nuwave\Lighthouse\Validation\ValidationServiceProvider as LighthouseValidationServiceProvider;
use Override;
use ReflectionClass;
use SplFileInfo;

use function mb_substr;

/**
 * @internal
 */
abstract class TestCase extends PackageTestCase {
    use GraphQLAssertions {
        getGraphQLPrinter as private getDefaultGraphQLPrinter;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array {
        return [
            Provider::class,
            TestProvider::class,
            SerializerProvider::class,
            LighthouseServiceProvider::class,
            LighthouseTestingServiceProvider::class,
            LighthouseValidationServiceProvider::class,
        ];
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getEnvironmentSetUp($app): void {
        parent::getEnvironmentSetUp($app);

        $this->setConfig([
            'lighthouse.namespaces.models' => [
                (new ReflectionClass(TestObject::class))->getNamespaceName(),
            ],
        ]);
    }

    protected function getGraphQLPrinter(Settings $settings = null): Printer {
        $settings ??= (new TestSettings())
            ->setDirectiveDefinitionFilter(Container::getInstance()->make(LighthouseDirectiveFilter::class));
        $printer    = $this->getDefaultGraphQLPrinter($settings);

        return $printer;
    }

    protected function getGraphQLArgument(
        string $type,
        mixed $value,
        Schema|SplFileInfo|string $schema = null,
    ): Argument {
        try {
            if ($schema) {
                $this->useGraphQLSchema($schema);
            }

            $factory  = Container::getInstance()->make(ArgumentFactory::class);
            $argument = $factory->getArgument($type, $value);

            return $argument;
        } finally {
            $this->resetGraphQLSchema();
        }
    }

    /**
     * @template M of EloquentModel
     *
     * @param QueryBuilder|EloquentBuilder<M>|ScoutBuilder $builder
     */
    protected function getExposeBuilderDirective(
        QueryBuilder|EloquentBuilder|ScoutBuilder $builder,
    ): ExposeBuilderDirective {
        $directive = new class() extends ExposeBuilderDirective {
            // empty
        };

        $directive::$builder = $builder;
        $directive::$result  = $builder;

        Container::getInstance()->make(DirectiveLocator::class)
            ->setResolved(mb_substr($directive::getName(), 1), $directive::class);

        return $directive;
    }
}
