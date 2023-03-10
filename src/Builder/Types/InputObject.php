<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Builder\Types;

use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Operator;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeDefinition;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\TypeDefinitionFieldAlreadyDefined;
use LastDragon_ru\LaraASP\GraphQL\Builder\Manipulator;
use LastDragon_ru\LaraASP\GraphQL\Builder\Sources\InputFieldSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Sources\InputSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Sources\ObjectFieldSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Sources\ObjectSource;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;

use function count;
use function trim;

abstract class InputObject implements TypeDefinition {
    public function __construct() {
        // empty
    }

    abstract protected function getScope(): string;

    abstract protected function getTypeDescription(
        Manipulator $manipulator,
        string $name,
        InputSource|ObjectSource $node,
    ): string;

    /**
     * @inheritDoc
     */
    public function getTypeDefinitionNode(
        Manipulator $manipulator,
        string $name,
        ?TypeSource $node,
    ): ?TypeDefinitionNode {
        // Source?
        if (!($node instanceof InputSource) && !($node instanceof ObjectSource)) {
            return null;
        }

        // Type
        $description = $this->getTypeDescription($manipulator, $name, $node);
        $operators   = $this->getTypeOperators($manipulator, $name, $node);
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

                {$manipulator->getOperatorsFields($operators, $node)}
            }
            DEF,
        );

        // Add searchable fields
        $type   = $node->getType();
        $fields = $type instanceof InputObjectType || $type instanceof ObjectType
            ? $type->getFields()
            : $type->fields;

        foreach ($fields as $field) {
            // Name should be unique (may conflict with Type's operators)
            $fieldName = $manipulator->getNodeName($field);

            if (isset($definition->fields[$fieldName])) {
                throw new TypeDefinitionFieldAlreadyDefined($fieldName);
            }

            // Field & Type
            $fieldSource = $node->getField($field);

            if (!$this->isFieldConvertable($manipulator, $fieldSource)) {
                continue;
            }

            // Add
            $fieldDefinition = $this->getFieldDefinition($manipulator, $fieldSource);

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
        InputSource|ObjectSource $node,
    ): array {
        return [];
    }

    protected function isFieldConvertable(
        Manipulator $manipulator,
        InputFieldSource|ObjectFieldSource $field,
    ): bool {
        // Union?
        if ($manipulator->isUnion($field->getType())) {
            return false;
        }

        // Resolver?
        if ($manipulator->getNodeDirective($field->getField(), FieldResolver::class)) {
            return false;
        }

        // Ok
        return true;
    }

    protected function getFieldDefinition(
        Manipulator $manipulator,
        InputFieldSource|ObjectFieldSource $field,
    ): InputValueDefinitionNode|null {
        [$operator, $type] = $this->getFieldOperator($manipulator, $field) ?? [null, null];

        if ($operator === null || !$operator->isBuilderSupported($manipulator->getBuilderInfo()->getBuilder())) {
            return null;
        }

        if ($type === null) {
            $fieldType = $manipulator->getTypeDefinitionNode($field->getType());

            if ($fieldType instanceof InputObjectTypeDefinitionNode || $fieldType instanceof InputObjectType) {
                $type = new InputSource($manipulator, $fieldType);
            } elseif ($fieldType instanceof ObjectTypeDefinitionNode || $fieldType instanceof ObjectType) {
                $type = new ObjectSource($manipulator, $fieldType);
            } else {
                // empty
            }
        }

        if ($type === null) {
            return null;
        }

        $fieldName       = $manipulator->getNodeName($field->getField());
        $fieldDesc       = $this->getFieldDescription($manipulator, $field);
        $fieldDefinition = $manipulator->getOperatorField($operator, $type, $fieldName, $fieldDesc);

        return Parser::inputValueDefinition($fieldDefinition);
    }

    /**
     * @return array{Operator, ?TypeSource}|null
     */
    abstract protected function getFieldOperator(
        Manipulator $manipulator,
        InputFieldSource|ObjectFieldSource $field,
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
        InputFieldSource|ObjectFieldSource $field,
    ): ?Operator {
        // Directive?
        $operator = null;
        $builder  = $manipulator->getBuilderInfo()->getBuilder();
        $nodes    = [$field->getField(), $manipulator->getTypeDefinitionNode($field->getType())];

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
        ObjectFieldSource|InputFieldSource $field,
    ): string|null {
        $description = null;

        if ($field instanceof InputFieldSource) {
            $description = $field->getField()->description;
        }

        if ($description instanceof StringValueNode) {
            $description = $description->value;
        }

        if ($description) {
            $description = trim($description) ?: null;
        }

        return $description;
    }
}
