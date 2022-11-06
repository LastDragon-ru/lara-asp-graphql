<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SortBy\Types;

use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\Parser;
use LastDragon_ru\LaraASP\GraphQL\Builder\BuilderInfo;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeDefinition;
use LastDragon_ru\LaraASP\GraphQL\Builder\Manipulator;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Directives\Directive;

class Direction implements TypeDefinition {
    public function __construct() {
        // empty
    }

    public static function getName(BuilderInfo $builder, string $type = null, bool $nullable = null): string {
        return Directive::Name.'TypeDirection';
    }

    public function getTypeDefinitionNode(
        Manipulator $manipulator,
        string $name,
        string $type = null,
        bool $nullable = null,
    ): ?TypeDefinitionNode {
        $node = null;

        if ($type === null && $nullable === null) {
            $node = Parser::enumTypeDefinition(
            /** @lang GraphQL */
                <<<GRAPHQL
                """
                Sort direction.
                """
                enum {$name} {
                    asc
                    desc
                }
                GRAPHQL,
            );
        }

        return $node;
    }
}
