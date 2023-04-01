<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Builder\Directives;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\ListTypeNode;
use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\NonNullTypeNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\Parser;
use Illuminate\Container\Container;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder as ScoutBuilder;
use LastDragon_ru\LaraASP\GraphQL\Builder\BuilderInfo;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Handler;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Operator;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Scope;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\Client\ConditionEmpty;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\Client\ConditionTooManyOperators;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\Client\ConditionTooManyProperties;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\HandlerInvalidConditions;
use LastDragon_ru\LaraASP\GraphQL\Builder\Manipulator;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use LastDragon_ru\LaraASP\GraphQL\Builder\Sources\InterfaceFieldArgumentSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Sources\ObjectFieldArgumentSource;
use LastDragon_ru\LaraASP\GraphQL\Exceptions\NotImplemented;
use LastDragon_ru\LaraASP\GraphQL\Utils\ArgumentFactory;
use Nuwave\Lighthouse\Execution\Arguments\ArgumentSet;
use Nuwave\Lighthouse\Pagination\PaginateDirective;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\Directives\AllDirective;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Directives\BuilderDirective;
use Nuwave\Lighthouse\Schema\Directives\RelationDirective;
use Nuwave\Lighthouse\Scout\SearchDirective;
use ReflectionFunction;
use ReflectionNamedType;
use stdClass;

use function array_keys;
use function array_map;
use function count;
use function is_a;
use function is_array;
use function reset;

abstract class HandlerDirective extends BaseDirective implements Handler {
    public function __construct(
        private ArgumentFactory $factory,
        private DirectiveLocator $directives,
    ) {
        // empty
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    /**
     * @return class-string<Scope>
     */
    abstract public static function getScope(): string;

    protected function getFactory(): ArgumentFactory {
        return $this->factory;
    }

    protected function getDirectives(): DirectiveLocator {
        return $this->directives;
    }
    // </editor-fold>

    // <editor-fold desc="Handle">
    // =========================================================================
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     *
     * @param EloquentBuilder<Model>|QueryBuilder $builder
     *
     * @return EloquentBuilder<Model>|QueryBuilder
     */
    public function handleBuilder($builder, mixed $value): EloquentBuilder|QueryBuilder {
        return $this->handleAnyBuilder($builder, $value);
    }

    public function handleScoutBuilder(ScoutBuilder $builder, mixed $value): ScoutBuilder {
        return $this->handleAnyBuilder($builder, $value);
    }

    /**
     * @template T of object
     *
     * @param T $builder
     *
     * @return T
     */
    protected function handleAnyBuilder(object $builder, mixed $value): object {
        if ($value !== null && $this->definitionNode instanceof InputValueDefinitionNode) {
            $argument   = $this->getFactory()->getArgument($this->definitionNode, $value);
            $isList     = $this->definitionNode->type instanceof ListTypeNode;
            $conditions = $isList && is_array($argument->value)
                ? $argument->value
                : [$argument->value];

            foreach ($conditions as $condition) {
                if ($condition instanceof ArgumentSet) {
                    $builder = $this->handle($builder, new Property(), $condition);
                } else {
                    throw new HandlerInvalidConditions($this);
                }
            }
        }

        return $builder;
    }

    /**
     * @template T of object
     *
     * @param T $builder
     *
     * @return T
     */
    public function handle(object $builder, Property $property, ArgumentSet $conditions): object {
        // Empty?
        if (count($conditions->arguments) === 0) {
            return $builder;
        }

        // Valid?
        if (count($conditions->arguments) !== 1) {
            throw new ConditionTooManyProperties(array_keys($conditions->arguments));
        }

        // Call
        return $this->call($builder, $property, $conditions);
    }

    /**
     * @template T of object
     *
     * @param T $builder
     *
     * @return T
     */
    protected function call(object $builder, Property $property, ArgumentSet $operator): object {
        // Arguments?
        if (count($operator->arguments) > 1) {
            throw new ConditionTooManyOperators(
                array_keys($operator->arguments),
            );
        }

        // Operator & Value
        $op    = null;
        $value = null;

        foreach ($operator->arguments as $name => $argument) {
            $operators = [];

            foreach ($argument->directives as $directive) {
                if ($directive instanceof Operator) {
                    $operators[] = $directive;
                }
            }

            $property = $property->getChild($name);
            $value    = $argument;
            $op       = reset($operators);

            if (count($operators) > 1) {
                throw new ConditionTooManyOperators(
                    array_map(
                        static function (Operator $operator): string {
                            return $operator::getName();
                        },
                        $operators,
                    ),
                );
            }
        }

        // Operator?
        if (!$op || !$value) {
            throw new ConditionEmpty();
        }

        // Return
        return $op->call($this, $builder, $property, $value);
    }
    // </editor-fold>

    // <editor-fold desc="Manipulate">
    // =========================================================================
    public function manipulateArgDefinition(
        DocumentAST &$documentAST,
        InputValueDefinitionNode &$argDefinition,
        FieldDefinitionNode &$parentField,
        ObjectTypeDefinitionNode|InterfaceTypeDefinitionNode &$parentType,
    ): void {
        // Converted?
        /** @var Manipulator $manipulator */
        $manipulator = Container::getInstance()->make(Manipulator::class, [
            'document'    => $documentAST,
            'builderInfo' => $this->getBuilderInfo($parentField),
        ]);

        if ($this->isTypeName($manipulator->getNodeTypeName($argDefinition))) {
            return;
        }

        // Argument
        $argInfo             = $parentType instanceof InterfaceTypeDefinitionNode
            ? new InterfaceFieldArgumentSource($manipulator, $parentType, $parentField, $argDefinition)
            : new ObjectFieldArgumentSource($manipulator, $parentType, $parentField, $argDefinition);
        $argDefinition->type = $this->getArgDefinitionType($manipulator, $documentAST, $argInfo);

        // Interfaces
        $interfaces   = $manipulator->getNodeInterfaces($parentType);
        $fieldName    = $manipulator->getNodeName($parentField);
        $argumentName = $manipulator->getNodeName($argDefinition);

        foreach ($interfaces as $interface) {
            // Field?
            $field = $manipulator->getNodeField($interface, $fieldName);

            if (!$field) {
                continue;
            }

            // Argument?
            $argument = $manipulator->getNodeAttribute($field, $argumentName);

            if ($argument === null) {
                continue;
            }

            // Directive? (no need to update type here)
            if ($manipulator->getNodeDirective($argument, self::class) !== null) {
                continue;
            }

            // Update
            if ($argument instanceof InputValueDefinitionNode) {
                $argument->type = $argDefinition->type;
            } else {
                throw new NotImplemented($argument::class);
            }
        }
    }

    /**
     * Should return `true` if `$name` is already converted.
     */
    abstract protected function isTypeName(string $name): bool;

    abstract protected function getArgDefinitionType(
        Manipulator $manipulator,
        DocumentAST $document,
        ObjectFieldArgumentSource|InterfaceFieldArgumentSource $argument,
    ): ListTypeNode|NamedTypeNode|NonNullTypeNode;

    /**
     * @param class-string<Operator> $operator
     */
    protected function getArgumentTypeDefinitionNode(
        Manipulator $manipulator,
        DocumentAST $document,
        ObjectFieldArgumentSource|InterfaceFieldArgumentSource $argument,
        string $operator,
    ): ListTypeNode|NamedTypeNode|NonNullTypeNode|null {
        $type       = null;
        $definition = $manipulator->isPlaceholder($argument->getArgument())
            ? $manipulator->getPlaceholderTypeDefinitionNode($argument->getField())
            : $argument->getTypeDefinition();

        if ($definition) {
            $operator = $manipulator->getOperator(static::getScope(), $operator);
            $node     = $manipulator->getTypeSource($definition);
            $type     = $operator->getFieldType($manipulator, $node);
            $type     = Parser::typeReference($type);
        }

        return $type;
    }

    protected function getBuilderInfo(FieldDefinitionNode $field): BuilderInfo {
        // Scout?
        $scout      = false;
        $directives = $this->getDirectives();

        foreach ($field->arguments as $argument) {
            if ($directives->associatedOfType($argument, SearchDirective::class)->isNotEmpty()) {
                $scout = true;
                break;
            }
        }

        if ($scout) {
            return new BuilderInfo('Scout', ScoutBuilder::class);
        }

        // Builder?
        $directive = $directives->associatedOfType($field, AllDirective::class)->first()
            ?? $directives->associatedOfType($field, PaginateDirective::class)->first()
            ?? $directives->associatedOfType($field, BuilderDirective::class)->first()
            ?? $directives->associatedOfType($field, RelationDirective::class)->first();

        if ($directive) {
            $info = null;
            $type = null;

            if ($directive instanceof PaginateDirective) {
                $type = $this->getBuilderType($directive, [
                    'resolver' => Paginator::class,
                    'builder'  => null,
                ]);
            } elseif ($directive instanceof AllDirective) {
                $type = $this->getBuilderType($directive, [
                    'builder' => null,
                ]);
            } elseif ($directive instanceof BuilderDirective) {
                $type = $this->getBuilderType($directive, [
                    'method' => null,
                ]);
            } else {
                // empty
            }

            if ($type && is_a($type, QueryBuilder::class, true)) {
                $info = new BuilderInfo('Query', QueryBuilder::class);
            } elseif ($type && is_a($type, Paginator::class, true)) {
                $info = new BuilderInfo('Paginator', Paginator::class);
            } else {
                $info = new BuilderInfo('', EloquentBuilder::class);
            }

            return $info;
        }

        // Collection?
        $type = $field->type instanceof NonNullTypeNode
            ? $field->type->type
            : $field->type;
        $info = $type instanceof ListTypeNode
            ? new BuilderInfo('Collection', Collection::class)
            : new BuilderInfo('Object', stdClass::class);

        return $info;
    }

    /**
     * @param array<string, ?class-string> $arguments
     */
    private function getBuilderType(BaseDirective $directive, array $arguments): ?string {
        $type = null;

        foreach ($arguments as $argument => $class) {
            if ($directive->directiveHasArgument($argument)) {
                $resolver = $directive->getResolverFromArgument($argument);
                $type     = $class;

                if (!$type) {
                    $type = (new ReflectionFunction($resolver))->getReturnType();
                    $type = $type instanceof ReflectionNamedType
                        ? $type->getName()
                        : null;
                }

                break;
            }
        }

        return $type;
    }
    // </editor-fold>
}
