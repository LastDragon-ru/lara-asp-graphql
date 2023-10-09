<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Builder\Traits;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use LastDragon_ru\LaraASP\GraphQL\Builder\Sources\InterfaceFieldArgumentSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Sources\InterfaceFieldSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Sources\ObjectFieldArgumentSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Sources\ObjectFieldSource;
use LastDragon_ru\LaraASP\GraphQL\Utils\AstManipulator;

trait WithSource {
    protected function getFieldSource(
        AstManipulator $manipulator,
        ObjectTypeDefinitionNode|InterfaceTypeDefinitionNode $type,
        FieldDefinitionNode $field,
    ): ObjectFieldSource|InterfaceFieldSource {
        return $type instanceof InterfaceTypeDefinitionNode
            ? new InterfaceFieldSource($manipulator, $type, $field)
            : new ObjectFieldSource($manipulator, $type, $field);
    }

    protected function getFieldArgumentSource(
        AstManipulator $manipulator,
        ObjectTypeDefinitionNode|InterfaceTypeDefinitionNode $type,
        FieldDefinitionNode $field,
        InputValueDefinitionNode $argument,
    ): ObjectFieldArgumentSource|InterfaceFieldArgumentSource {
        return $type instanceof InterfaceTypeDefinitionNode
            ? new InterfaceFieldArgumentSource($manipulator, $type, $field, $argument)
            : new ObjectFieldArgumentSource($manipulator, $type, $field, $argument);
    }
}