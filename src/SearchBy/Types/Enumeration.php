<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SearchBy\Types;

use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\Type;
use LastDragon_ru\LaraASP\GraphQL\Builder\BuilderInfo;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeDefinition;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Manipulator;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Directives\Directive;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators;

class Enumeration implements TypeDefinition {
    public function __construct() {
        // empty
    }

    public static function getTypeName(Manipulator $manipulator, BuilderInfo $builder, TypeSource $source): string {
        $directiveName = Directive::Name;
        $builderName   = $builder->getName();
        $typeName      = $source->getTypeName();
        $nullable      = $source->isNullable() ? 'OrNull' : '';

        return "{$directiveName}{$builderName}Enum{$typeName}{$nullable}";
    }

    /**
     * @inheritDoc
     */
    public function getTypeDefinitionNode(
        Manipulator $manipulator,
        string $name,
        TypeSource $source,
    ): TypeDefinitionNode|Type|null {
        // Operators
        $scope     = Directive::class;
        $operators = $manipulator->hasTypeOperators($scope, $source->getTypeName())
            ? $manipulator->getTypeOperators($scope, $source->getTypeName(), $source->isNullable())
            : $manipulator->getTypeOperators($scope, Operators::Enum, $source->isNullable());

        if (!$operators) {
            return null;
        }

        // Definition
        $content    = $manipulator->getOperatorsFields($operators, $source);
        $typeName   = $manipulator->getNodeTypeFullName($source->getType());
        $definition = Parser::inputObjectTypeDefinition(
            <<<DEF
            """
            Available operators for `{$typeName}` (only one operator allowed at a time).
            """
            input {$name} {
                {$content}
            }
            DEF,
        );

        // Return
        return $definition;
    }
}
