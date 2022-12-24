<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Builder\Types;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Operator;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeDefinition;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\TypeDefinitionFieldAlreadyDefined;
use LastDragon_ru\LaraASP\GraphQL\Builder\Manipulator;
use LastDragon_ru\LaraASP\GraphQL\Exceptions\TypeDefinitionUnknown;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;

use function count;

abstract class InputObject implements TypeDefinition {
    public function __construct() {
        // empty
    }

    abstract protected function getScope(): string;

    abstract protected function getTypeDescription(
        Manipulator $manipulator,
        string $name,
        string $type,
        bool $nullable = null,
    ): string;

    /**
     * @inheritDoc
     */
    public function getTypeDefinitionNode(
        Manipulator $manipulator,
        string $name,
        ?string $type,
        ?bool $nullable,
    ): ?TypeDefinitionNode {
        // Type?
        if (!$type) {
            return null;
        }

        // Supported?
        $node        = $manipulator->getTypeDefinitionNode($type);
        $isSupported = $node instanceof InputObjectTypeDefinitionNode
            || $node instanceof ObjectTypeDefinitionNode
            || $node instanceof InputObjectType
            || $node instanceof ObjectType;

        if (!$isSupported) {
            return null;
        }

        // Logical
        $description = $this->getTypeDescription($manipulator, $name, $type, $nullable);
        $operators   = $this->getTypeOperators($manipulator, $name, $type, $nullable);
        $definition  = Parser::inputObjectTypeDefinition(
            <<<DEF
            """
            {$description}
            """
            input {$name} {
                """
                If you see this probably something wrong. Please contact to developer.
                """
                dummy: ID

                {$manipulator->getOperatorsFields($operators, $name)}
            }
            DEF,
        );

        // Add searchable fields
        $fields = $node instanceof InputObjectType || $node instanceof ObjectType
            ? $node->getFields()
            : $node->fields;

        foreach ($fields as $field) {
            // Name should be unique (may conflict with Type's operators)
            $fieldName = $manipulator->getNodeName($field);

            if (isset($definition->fields[$fieldName])) {
                throw new TypeDefinitionFieldAlreadyDefined($fieldName);
            }

            // Convertable?
            if (!$this->isConvertable($manipulator, $field)) {
                continue;
            }

            // Determine type
            $fieldType     = $manipulator->getNodeTypeName($field);
            $fieldNullable = $manipulator->isNullable($field);
            $fieldTypeNode = null;

            try {
                $fieldTypeNode = $manipulator->getTypeDefinitionNode($field);
            } catch (TypeDefinitionUnknown $exception) {
                if ($manipulator->hasTypeOperators($this->getScope(), $fieldType)) {
                    $fieldTypeNode = $manipulator->getScalarTypeDefinitionNode($fieldType);
                } else {
                    throw $exception;
                }
            }

            // Convertable?
            if (!$this->isConvertable($manipulator, $fieldTypeNode)) {
                continue;
            }

            // Add
            $fieldDefinition = $this->getFieldDefinition($manipulator, $field, $fieldTypeNode, $fieldNullable);

            if ($fieldDefinition) {
                $definition->fields[] = $fieldDefinition;
            }
        }

        // Remove dummy
        unset($definition->fields[0]);

        // Empty?
        if (count($definition->fields) === 0) {
            return null;
        }

        // Return
        return $definition;
    }

    /**
     * @return array<Operator>
     */
    protected function getTypeOperators(
        Manipulator $manipulator,
        string $name,
        string $type,
        ?bool $nullable,
    ): array {
        return [];
    }

    protected function isConvertable(
        Manipulator $manipulator,
        InputValueDefinitionNode|FieldDefinitionNode|InputObjectField|FieldDefinition|TypeDefinitionNode|Type $node,
    ): bool {
        // Union?
        if ($manipulator->isUnion($node)) {
            return false;
        }

        // Resolver?
        if ($manipulator->getNodeDirective($node, FieldResolver::class)) {
            return false;
        }

        // Ok
        return true;
    }

    protected function getFieldDefinition(
        Manipulator $manipulator,
        InputValueDefinitionNode|FieldDefinitionNode|InputObjectField|FieldDefinition $field,
        TypeDefinitionNode|Type $fieldType,
        ?bool $fieldNullable,
    ): InputValueDefinitionNode|null {
        $name              = $manipulator->getNodeName($field);
        $builder           = $manipulator->getBuilderInfo()->getBuilder();
        [$operator, $type] = $this->getFieldOperator($manipulator, $field, $fieldType, $fieldNullable) ?? [null, null];

        if (!$type || !$operator || !$operator->isBuilderSupported($builder)) {
            return null;
        }

        $fieldDescription = $this->getFieldDescription($manipulator, $field, $fieldType, $fieldNullable);
        $fieldDefinition  = $manipulator->getOperatorField($operator, $type, $name, $fieldNullable, $fieldDescription);

        return Parser::inputValueDefinition($fieldDefinition);
    }

    /**
     * @return array{Operator, string}|null
     */
    abstract protected function getFieldOperator(
        Manipulator $manipulator,
        InputValueDefinitionNode|FieldDefinitionNode|InputObjectField|FieldDefinition $field,
        TypeDefinitionNode|Type $fieldType,
        ?bool $fieldNullable,
    ): ?array;

    /**
     * @template T of Operator
     *
     * @param class-string<T> $directive
     *
     * @return ?T
     */
    protected function getFieldDirectiveOperator(
        string $directive,
        Manipulator $manipulator,
        InputValueDefinitionNode|FieldDefinitionNode|InputObjectField|FieldDefinition $field,
        InputObjectTypeDefinitionNode|ObjectTypeDefinitionNode|InputObjectType|ObjectType $fieldType,
        ?bool $fieldNullable,
    ): ?Operator {
        // Directive?
        $operator = null;
        $builder  = $manipulator->getBuilderInfo()->getBuilder();
        $nodes    = [$field, $fieldType];

        foreach ($nodes as $node) {
            $operator = $manipulator->getNodeDirective(
                $node,
                $directive,
                static function (Operator $operator) use ($builder): bool {
                    return $operator->isBuilderSupported($builder);
                },
            );

            if ($operator) {
                break;
            }
        }

        // Return
        return $operator;
    }

    protected function getFieldDescription(
        Manipulator $manipulator,
        InputValueDefinitionNode|FieldDefinitionNode|InputObjectField|FieldDefinition $field,
        TypeDefinitionNode|Type $fieldType,
        ?bool $fieldNullable,
    ): string|null {
        $description = null;

        if ($field instanceof InputValueDefinitionNode) {
            $description = $field->description->value ?? null;
        } elseif ($field instanceof InputObjectField) {
            $description = $field->description;
        } else {
            // empty
        }

        return $description;
    }
}
