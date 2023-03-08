<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Builder;

use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use Illuminate\Container\Container;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Operator;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeProvider;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\FakeTypeDefinitionIsNotFake;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\FakeTypeDefinitionUnknown;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\TypeDefinitionImpossibleToCreateType;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\TypeDefinitionInvalidTypeName;
use LastDragon_ru\LaraASP\GraphQL\Utils\AstManipulator;
use Nuwave\Lighthouse\Pagination\PaginateDirective;
use Nuwave\Lighthouse\Pagination\PaginationType;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\TypeRegistry;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function implode;
use function mb_strlen;
use function mb_substr;

class Manipulator extends AstManipulator implements TypeProvider {
    /**
     * @var array<string, Operators>
     */
    private array $operators = [];

    public function __construct(
        DirectiveLocator $directives,
        DocumentAST $document,
        TypeRegistry $types,
        private BuilderInfo $builderInfo,
    ) {
        parent::__construct($directives, $document, $types);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    public function getBuilderInfo(): BuilderInfo {
        return $this->builderInfo;
    }
    // </editor-fold>

    // <editor-fold desc="TypeProvider">
    // =========================================================================
    public function getType(string $definition, ?TypeSource $type = null): string {
        // Exists?
        $name = $definition::getTypeName($this, $this->getBuilderInfo(), $type);

        if ($this->isTypeDefinitionExists($name)) {
            return $name;
        }

        // Fake
        $this->addFakeTypeDefinition($name);

        // Create new
        $instance = Container::getInstance()->make($definition);
        $node     = $instance->getTypeDefinitionNode($this, $name, $type);

        if (!$node) {
            throw new TypeDefinitionImpossibleToCreateType($definition, $type);
        }

        if ($name !== $this->getNodeName($node)) {
            throw new TypeDefinitionInvalidTypeName($definition, $name, $this->getNodeName($node));
        }

        // Save
        $this->removeFakeTypeDefinition($name);
        $this->addTypeDefinition($node);

        // Return
        return $name;
    }
    // </editor-fold>

    // <editor-fold desc="Operators">
    // =========================================================================
    public function addOperators(Operators $operators): static {
        $this->operators[$operators->getScope()] = $operators;

        return $this;
    }

    /**
     * @template T of Operator
     *
     * @param class-string<T> $operator
     *
     * @return T
     */
    public function getOperator(string $scope, string $operator): Operator {
        return Container::getInstance()->make($operator);
    }

    /**
     * Method doesn't check Builder!
     */
    public function hasTypeOperators(string $scope, TypeSource $type): bool {
        return (bool) ($this->operators[$scope] ?? null)?->hasOperators($type->getTypeName());
    }

    /**
     * @return list<Operator>
     */
    public function getTypeOperators(string $scope, TypeSource $type): array {
        $operators = $this->operators[$scope] ?? null;
        $operators = $operators && $operators->hasOperators($type->getTypeName())
            ? $operators->getOperators($type->getTypeName(), (bool) $type->isNullable())
            : [];
        $operators = array_filter($operators, function (Operator $operator): bool {
            return $operator->isBuilderSupported($this->getBuilderInfo()->getBuilder());
        });
        $operators = array_values($operators);

        return $operators;
    }

    public function getOperatorField(
        Operator $operator,
        TypeSource $type,
        ?string $field,
        ?string $description = null,
    ): string {
        $type        = $operator->getFieldType($this, $type);
        $field       = $field ?: $operator::getName();
        $directive   = $operator->getFieldDirective() ?? $operator::getDirectiveName();
        $directive   = $directive instanceof DirectiveNode
            ? Printer::doPrint($directive)
            : $directive;
        $description = $description ?: $operator->getFieldDescription();

        return <<<DEF
            """
            {$description}
            """
            {$field}: {$type}
            {$directive}
        DEF;
    }

    /**
     * @param array<Operator> $operators
     */
    public function getOperatorsFields(array $operators, TypeSource $type): string {
        return implode(
            "\n",
            array_map(
                function (Operator $operator) use ($type): string {
                    return $this->getOperatorField($operator, $type, null);
                },
                $operators,
            ),
        );
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =================================================================================================================
    protected function addFakeTypeDefinition(string $name): void {
        $this->addTypeDefinition(
            Parser::inputObjectTypeDefinition(
                <<<DEF
                """
                Fake type to prevent circular dependency infinite loop.
                """
                input {$name} {
                    fake: Boolean! = true
                }
                DEF,
            ),
        );
    }

    protected function removeFakeTypeDefinition(string $name): void {
        // Possible?
        $fake = $this->getTypeDefinitionNode($name);

        if (!($fake instanceof InputObjectTypeDefinitionNode)) {
            throw new FakeTypeDefinitionUnknown($name);
        }

        if (count($fake->fields) !== 1 || $this->getNodeName($fake->fields[0]) !== 'fake') {
            throw new FakeTypeDefinitionIsNotFake($name);
        }

        // Remove
        $this->removeTypeDefinition($name);
    }

    public function getPlaceholderTypeDefinitionNode(
        FieldDefinitionNode|FieldDefinition $field,
    ): TypeDefinitionNode|Type|null {
        $node     = null;
        $paginate = $this->getNodeDirective($field, PaginateDirective::class);

        if ($paginate) {
            $type       = $this->getNodeTypeName($this->getTypeDefinitionNode($field));
            $pagination = (new class() extends PaginateDirective {
                public function getPaginationType(PaginateDirective $directive): PaginationType {
                    return $directive->paginationType();
                }
            })->getPaginationType($paginate);

            if ($pagination->isPaginator()) {
                $type = mb_substr($type, 0, -mb_strlen('Paginator'));
            } elseif ($pagination->isSimple()) {
                $type = mb_substr($type, 0, -mb_strlen('SimplePaginator'));
            } elseif ($pagination->isConnection()) {
                $type = mb_substr($type, 0, -mb_strlen('Connection'));
            } else {
                // empty
            }

            if ($type) {
                $node = $this->getTypeDefinitionNode($type);
            }
        } else {
            $node = $this->getTypeDefinitionNode($field);
        }

        return $node;
    }
    // </editor-fold>
}
