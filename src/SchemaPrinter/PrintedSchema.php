<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Introspection;
use GraphQL\Type\Schema;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Block;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Contracts\PrintedSchema as PrintedSchemaContract;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Misc\DirectiveResolver;

use function array_diff_key;

/**
 * @internal
 */
class PrintedSchema implements PrintedSchemaContract {
    public function __construct(
        protected DirectiveResolver $resolver,
        protected Schema $schema,
        protected Block $block,
    ) {
        // empty
    }

    public function __toString(): string {
        return (string) $this->block;
    }

    /**
     * @inheritDoc
     */
    public function getUsedTypes(): array {
        return $this->block->getUsedTypes();
    }

    /**
     * @return array<string, string>
     */
    public function getUnusedTypes(): array {
        return array_diff_key($this->getTypes(), $this->getUsedTypes());
    }

    /**
     * @inheritDoc
     */
    public function getUsedDirectives(): array {
        return $this->block->getUsedDirectives();
    }

    /**
     * @inheritDoc
     */
    public function getUnusedDirectives(): array {
        return array_diff_key($this->getDirectives(), $this->getUsedDirectives());
    }

    /**
     * @return array<string, string>
     */
    protected function getTypes(): array {
        // Collect
        $types = [];
        $map   = $this->schema->getTypeMap();

        foreach ($map as $type) {
            if ($this->isType($type)) {
                $types[$type->name] = $type->name;
            }
        }

        // Remove standard types
        unset($types[$this->schema->getQueryType()?->name]);
        unset($types[$this->schema->getMutationType()?->name]);
        unset($types[$this->schema->getSubscriptionType()?->name]);

        // Return
        return $types;
    }

    protected function isType(Type $type): bool {
        return !Introspection::isIntrospectionType($type);
    }

    /**
     * @return array<string, string>
     */
    protected function getDirectives(): array {
        $directives = [];

        foreach ($this->resolver->getDefinitions() as $directive) {
            $directives[$directive->name] = $directive->name;
        }

        return $directives;
    }
}
