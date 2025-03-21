<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Stream\Directives;

use Closure;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\HasFieldsType;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Laravel\Scout\Builder as ScoutBuilder;
use LastDragon_ru\LaraASP\Core\Application\ContainerResolver;
use LastDragon_ru\LaraASP\Core\Utils\Cast;
use LastDragon_ru\LaraASP\Eloquent\ModelHelper;
use LastDragon_ru\LaraASP\GraphQL\Builder\BuilderInfo;
use LastDragon_ru\LaraASP\GraphQL\Builder\BuilderInfoDetector;
use LastDragon_ru\LaraASP\GraphQL\Builder\Context;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\BuilderInfoProvider;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Enhancer;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\ManipulatorFactory;
use LastDragon_ru\LaraASP\GraphQL\Builder\Sources\InterfaceFieldArgumentSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Sources\InterfaceFieldSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Sources\ObjectFieldArgumentSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Sources\ObjectFieldSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Sources\ObjectSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Traits\WithSource;
use LastDragon_ru\LaraASP\GraphQL\Package;
use LastDragon_ru\LaraASP\GraphQL\PackageConfig;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByDirective;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Definitions\SortByDirective;
use LastDragon_ru\LaraASP\GraphQL\Stream\Contracts\FieldArgumentDirective;
use LastDragon_ru\LaraASP\GraphQL\Stream\Contracts\StreamFactory;
use LastDragon_ru\LaraASP\GraphQL\Stream\Definitions\StreamLimitDirective;
use LastDragon_ru\LaraASP\GraphQL\Stream\Definitions\StreamOffsetDirective;
use LastDragon_ru\LaraASP\GraphQL\Stream\Exceptions\ArgumentMissed;
use LastDragon_ru\LaraASP\GraphQL\Stream\Exceptions\BuilderInvalid;
use LastDragon_ru\LaraASP\GraphQL\Stream\Exceptions\BuilderUnsupported;
use LastDragon_ru\LaraASP\GraphQL\Stream\Exceptions\Client\ArgumentsMutuallyExclusive;
use LastDragon_ru\LaraASP\GraphQL\Stream\Exceptions\FieldIsNotList;
use LastDragon_ru\LaraASP\GraphQL\Stream\Exceptions\FieldIsSubscription;
use LastDragon_ru\LaraASP\GraphQL\Stream\Exceptions\FieldIsUnion;
use LastDragon_ru\LaraASP\GraphQL\Stream\Exceptions\KeyUnknown;
use LastDragon_ru\LaraASP\GraphQL\Stream\StreamValue;
use LastDragon_ru\LaraASP\GraphQL\Stream\Types\Stream as StreamType;
use LastDragon_ru\LaraASP\GraphQL\Utils\AstManipulator;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Directives\RenameDirective;
use Nuwave\Lighthouse\Schema\ResolverProvider;
use Nuwave\Lighthouse\Schema\RootType;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Scout\ScoutEnhancer;
use Nuwave\Lighthouse\Scout\SearchDirective;
use Nuwave\Lighthouse\Support\Contracts\Directive as DirectiveContract;
use Nuwave\Lighthouse\Support\Contracts\FieldManipulator;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Nuwave\Lighthouse\Support\Contracts\ProvidesResolver;
use Nuwave\Lighthouse\Validation\ValidateDirective;
use Override;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;

use function array_key_exists;
use function array_key_first;
use function class_exists;
use function count;
use function explode;
use function gettype;
use function is_a;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function json_encode;
use function reset;
use function uksort;

use const JSON_THROW_ON_ERROR;

class Directive extends BaseDirective implements FieldResolver, FieldManipulator, BuilderInfoProvider {
    use WithSource;

    final public const string Name          = 'Stream';
    final public const string Settings      = Package::Name.'.stream';
    final public const string ArgSearchable = 'searchable';
    final public const string ArgSortable   = 'sortable';
    final public const string ArgBuilder    = 'builder';
    final public const string ArgLimit      = 'limit';
    final public const string ArgKey        = 'key';

    /**
     * @param StreamFactory<object> $streamFactory
     */
    public function __construct(
        protected readonly ContainerResolver $container,
        protected readonly PackageConfig $config,
        protected readonly BuilderInfoDetector $detector,
        protected readonly StreamFactory $streamFactory,
        protected readonly StreamType $streamType,
        protected readonly ProvidesResolver $provider,
        protected readonly ManipulatorFactory $manipulatorFactory,
    ) {
        // empty
    }

    // <editor-fold desc="Directive">
    // =========================================================================
    #[Override]
    public static function definition(): string {
        $name          = DirectiveLocator::directiveName(static::class);
        $builder       = self::Name.'Builder';
        $argSearchable = self::ArgSearchable;
        $argSortable   = self::ArgSortable;
        $argBuilder    = self::ArgBuilder;
        $argLimit      = self::ArgLimit;
        $argKey        = self::ArgKey;

        return <<<GRAPHQL
            """
            Splits list of items into the chunks and returns one chunk specified
            by an offset or a cursor.
            """
            directive @{$name}(
                """
                Overrides default searchable status.
                """
                {$argSearchable}: Boolean

                """
                Overrides default sortable status.
                """
                {$argSortable}: Boolean

                """
                Overrides default builder. Useful if the standard detection
                algorithm doesn't fit/work. By default, the directive will use
                the field and its type to determine the Builder to query.
                """
                {$argBuilder}: {$builder}

                """
                Overrides default limit.
                """
                {$argLimit}: Int

                """
                Overrides default unique key. Useful if the standard detection
                algorithm doesn't fit/work. By default, the directive will use
                the name of field with `ID!` type.
                """
                {$argKey}: String
            ) on FIELD_DEFINITION

            """
            Explicit builder. Only one of fields allowed.
            """
            input {$builder} {
                """
                The class name of the model to query.
                """
                model: String

                """
                The reference to a function that provides a Builder instance.
                """
                builder: String

                """
                The relation name to query.
                """
                relation: String
            }
        GRAPHQL;
    }
    //</editor-fold>

    // <editor-fold desc="FieldManipulator">
    // =========================================================================
    #[Override]
    public function manipulateFieldDefinition(
        DocumentAST &$documentAST,
        FieldDefinitionNode &$fieldDefinition,
        ObjectTypeDefinitionNode|InterfaceTypeDefinitionNode &$parentType,
    ): void {
        // Prepare
        $manipulator = $this->manipulatorFactory->create($documentAST);
        $source      = $this->getFieldSource($manipulator, $parentType, $fieldDefinition);
        $config      = $this->config->getInstance();

        // Updated?
        if ($this->streamType->is($source->getTypeName())) {
            return;
        }

        // Subscription?
        if ($source->getParent()->getTypeName() === RootType::SUBSCRIPTION) {
            throw new FieldIsSubscription($source);
        }

        // Field is a list?
        if (!$source->isList()) {
            throw new FieldIsNotList($source);
        }

        // Union?
        if ($source->isUnion()) {
            throw new FieldIsUnion($source);
        }

        // Builder?
        $this->detector->getFieldBuilderInfo($documentAST, $parentType, $fieldDefinition);

        // Searchable?
        $searchable = Cast::toBool($this->directiveArgValue(self::ArgSearchable) ?? $config->stream->search->enabled);

        if ($searchable) {
            $this->addArgument(
                $manipulator,
                $source,
                SearchByDirective::class,
                $config->stream->search->name,
                $manipulator::Placeholder,
            );
        }

        // Sortable?
        $sortable = Cast::toBool($this->directiveArgValue(self::ArgSortable) ?? $config->stream->sort->enabled);

        if ($sortable) {
            $this->addArgument(
                $manipulator,
                $source,
                SortByDirective::class,
                $config->stream->sort->name,
                $manipulator::Placeholder,
            );
        }

        // Limit
        $this->addArgument(
            $manipulator,
            $source,
            StreamLimitDirective::class,
            $config->stream->limit->name,
            $manipulator::Placeholder,
            null,
            $this->directiveArgValue(self::ArgLimit) !== null
                ? [StreamLimitDirective::ArgDefault => $this->directiveArgValue(self::ArgLimit)]
                : [],
        );

        // Offset
        $this->addArgument(
            $manipulator,
            $source,
            StreamOffsetDirective::class,
            $config->stream->offset->name,
            $manipulator::Placeholder,
        );

        // @validate (required to validation)
        $manipulator->addDirective(
            $fieldDefinition,
            ValidateDirective::class,
        );

        // Update type
        $type = $manipulator->getType(StreamType::class, $source, new Context());
        $type = Parser::typeReference("{$type}!");

        $manipulator->setFieldType(
            $parentType,
            $fieldDefinition,
            $type,
        );

        // Key? (required)
        $this->getArgKey($manipulator, $source);
    }

    /**
     * @param class-string<DirectiveContract> $directive
     * @param array<string, mixed>            $arguments
     */
    protected function addArgument(
        AstManipulator $manipulator,
        ObjectFieldSource|InterfaceFieldSource $field,
        string $directive,
        string $name,
        string $type,
        ?string $value = null,
        array $arguments = [],
    ): void {
        // Arguments with directive already exists?
        $argument = $manipulator->findArgument(
            $field->getField(),
            static function (mixed $argument) use ($manipulator, $directive): bool {
                return $manipulator->getDirective($argument, $directive) !== null;
            },
        );

        if ($argument !== null && $arguments !== []) {
            // todo(graphql/@stream): Move to AstManipulator + check definition.
            $directiveNode = $manipulator->getDirective($argument, $directive);
            $directiveNode = $directiveNode !== null
                ? $manipulator->getDirectiveNode($directiveNode)
                : null;

            if ($directiveNode !== null) {
                foreach ($arguments as $argName => $argValue) {
                    $argNode  = $manipulator->getArgument($directiveNode, $argName);
                    $argValue = json_encode($argValue, JSON_THROW_ON_ERROR);

                    if ($argNode !== null) {
                        $argNode->value = Parser::valueLiteral($argValue);
                    } else {
                        $directiveNode->arguments[] = Parser::argument("{$argName}: {$argValue}");
                    }
                }
            }
        }

        if ($argument !== null && !$manipulator->isDeprecated($argument)) {
            return;
        }

        // Nope
        $manipulator->addDirective(
            $manipulator->addArgument($field->getObject(), $field->getField(), $name, $type, $value),
            $directive,
            $arguments,
        );
    }
    // </editor-fold>

    // <editor-fold desc="BuilderInfoProvider">
    // =========================================================================
    #[Override]
    public function getBuilderInfo(TypeSource $source): ?BuilderInfo {
        // Field?
        $field = null;

        if ($source instanceof ObjectFieldArgumentSource || $source instanceof InterfaceFieldArgumentSource) {
            $field = $source->getParent();
        } elseif ($source instanceof ObjectFieldSource || $source instanceof InterfaceFieldSource) {
            $field = $source;
        } else {
            // empty
        }

        if ($field === null) {
            return null;
        }

        // Resolver?
        $resolver = $this->getResolver($field);

        if ($resolver === null) {
            return null;
        }

        // Type
        $type = null;

        try {
            $type = is_array($resolver)
                ? (new ReflectionClass($resolver[0]))->getMethod($resolver[1])->getReturnType()
                : (new ReflectionFunction($resolver))->getReturnType();
            $type = $type instanceof ReflectionNamedType
                ? $type->getName()
                : null;
            $type = $type !== null && class_exists($type)
                ? $type
                : null;

            // Scout?
            if ($type !== null && !is_a($type, ScoutBuilder::class, true)) {
                $scout = $field->hasArgument(
                    static function (mixed $argument, AstManipulator $manipulator): bool {
                        return $manipulator->getDirective($argument, SearchDirective::class) !== null;
                    },
                );

                if ($scout) {
                    /** @see ScoutEnhancer */
                    $type = is_a($type, EloquentBuilder::class, true)
                        ? ScoutBuilder::class
                        : null;
                }
            }

            // Not supported?
            if ($type !== null && !$this->streamFactory->isSupported($type)) {
                throw new BuilderUnsupported($source, $type);
            }
        } catch (ReflectionException) {
            // empty
        }

        if ($type !== null) {
            $type = BuilderInfo::create($type);
        }

        return $type;
    }
    // </editor-fold>

    // <editor-fold desc="FieldResolver">
    // =========================================================================
    /**
     * @return callable(mixed, array<string, mixed>, GraphQLContext, ResolveInfo): StreamValue
     */
    #[Override]
    public function resolveField(FieldValue $fieldValue): callable {
        return function (mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): StreamValue {
            // Offset
            $manipulator = $this->manipulatorFactory->create(new DocumentAST());
            $source      = (new ObjectSource($manipulator, $info->parentType))->getField($info->fieldDefinition);
            $offset      = $this->getFieldValue(StreamOffsetDirective::class, $manipulator, $source, $info, $args);

            // Builder
            $resolver = $this->getResolver($source);
            $builder  = $resolver !== null && is_callable($resolver)
                ? $resolver($root, $args, $context, $info)
                : null;
            $builder  = is_object($builder)
                ? $this->getBuilder($builder, $root, $args, $context, $info)
                : null;

            if (!is_object($builder)) {
                throw new BuilderInvalid($source, gettype($builder));
            } elseif (!$this->streamFactory->isSupported($builder)) {
                throw new BuilderUnsupported($source, $builder::class);
            } else {
                // ok
            }

            // Stream
            $key    = $this->getArgKey($manipulator, $source);
            $limit  = $this->getFieldValue(StreamLimitDirective::class, $manipulator, $source, $info, $args);
            $stream = $this->streamFactory->create($builder, $key, $limit, $offset);
            $stream = new StreamValue($stream);

            return $stream;
        };
    }

    /**
     * @param array<string, mixed> $args
     */
    protected function getBuilder(
        object $builder,
        mixed $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $info,
    ): object {
        // Scout?
        if ($builder instanceof EloquentBuilder) {
            $enhancer = new class ($info->argumentSet, $builder) extends ScoutEnhancer {
                #[Override]
                public function enhanceEloquentBuilder(): ScoutBuilder {
                    return parent::enhanceEloquentBuilder();
                }
            };

            if ($enhancer->canEnhanceBuilder()) {
                $builder = $enhancer->enhanceEloquentBuilder();
            }
        }

        // Lighthouse's enhancer is slower because it processes all nested fields
        // of the input value, and then we need to recreate Argument from the
        // plain value. So we are skipping it if possible.
        $lighthouse = false;
        $filter     = static function (object $directive): bool {
            return !($directive instanceof Enhancer)
                && !($directive instanceof Offset)
                && !($directive instanceof Limit)
                && !($directive instanceof SearchDirective);
        };

        foreach ($info->argumentSet->arguments as $argument) {
            if ($argument->directives->contains(static fn ($directive) => !$filter($directive))) {
                foreach ($argument->directives as $directive) {
                    if ($directive instanceof Enhancer) {
                        $builder = $directive->enhance($builder, $argument);
                    }
                }
            } else {
                $lighthouse = true;
            }
        }

        // Not possible?
        if (
            $lighthouse
            && (
                $builder instanceof EloquentBuilder
                || $builder instanceof EloquentRelation
                || $builder instanceof QueryBuilder
                || $builder instanceof ScoutBuilder
            )
        ) {
            $builder = $info->enhanceBuilder($builder, [], $root, $args, $context, $info, $filter);
        }

        // Return
        return $builder;
    }

    /**
     * @return Closure(mixed, array<string, mixed>, GraphQLContext, ResolveInfo):mixed|array{class-string, string}|null
     */
    protected function getResolver(ObjectFieldSource|InterfaceFieldSource $source): Closure|array|null {
        $resolver = null;
        $builder  = (array) $this->directiveArgValue(self::ArgBuilder);

        if ($builder !== []) {
            if (count($builder) > 1) {
                // empty
            } elseif (isset($builder['builder'])) {
                $resolver = is_string($builder['builder'])
                    ? $this->getResolverClass($builder['builder'])
                    : null;
            } elseif (isset($builder['model'])) {
                $resolver = is_string($builder['model'])
                    ? $this->getResolverModel($builder['model'])
                    : null;
            } elseif (isset($builder['relation'])) {
                $resolver = is_string($builder['relation'])
                    ? $this->getResolverRelation($source->getParent()->getTypeName(), $builder['relation'])
                    : null;
            } else {
                // empty
            }
        } else {
            $parent   = $source->getParent()->getTypeName();
            $resolver = $this->getResolverQuery($parent, $source->getName());

            if ($resolver === null) {
                $resolver = RootType::isRootType($parent)
                    ? $this->getResolverModel(
                        $this->streamType->getOriginalTypeName($source->getTypeName()),
                    )
                    : $this->getResolverRelation($parent, $source->getName());
            }
        }

        return $resolver;
    }

    /**
     * @return Closure(mixed, array<string, mixed>, GraphQLContext, ResolveInfo): EloquentBuilder<EloquentModel>|null
     */
    protected function getResolverRelation(string $model, string $relation): ?Closure {
        $class    = $this->namespaceModelClass($model);
        $resolver = null;

        if ((new ModelHelper($class))->isRelation($relation)) {
            $resolver = static function (mixed $root) use ($class, $relation): EloquentBuilder {
                // In runtime, we cannot guarantee that the `$root` is the
                // expected model. So we are checking it explicitly and return
                // the empty Builder if the model is wrong.
                return $root instanceof $class
                    ? (new ModelHelper($root))->getRelation($relation)->getQuery()
                    : (new ModelHelper($class))->getRelation($relation)->getQuery()->whereRaw('0 = 1');
            };
        }

        return $resolver;
    }

    /**
     * @return array{class-string, string}|null
     */
    protected function getResolverQuery(string $type, string $field): ?array {
        // We are mimicking to default Lighthouse resolver resolution, thus
        // custom implementations may not work.
        if (!($this->provider instanceof ResolverProvider)) {
            return null;
        }

        // Determine class
        $method   = '__invoke';
        $value    = new class($type, $field) extends FieldValue {
            /**
             * @noinspection             PhpMissingParentConstructorInspection
             * @phpstan-ignore-next-line constructor.missingParentCall (no need to call parent `__construct`)
             */
            public function __construct(
                private readonly string $typeName,
                private readonly string $fieldName,
            ) {
                // no need to call parent
            }

            #[Override]
            public function getParentName(): string {
                return $this->typeName;
            }

            #[Override]
            public function getFieldName(): string {
                return $this->fieldName;
            }
        };
        $helper   = new class() extends ResolverProvider {
            /**
             * @return class-string|null
             */
            public function getResolverClass(ResolverProvider $provider, FieldValue $value, string $method): ?string {
                return $provider->findResolverClass($value, $method);
            }
        };
        $class    = $helper->getResolverClass($this->provider, $value, $method);
        $resolver = $class !== null ? [$class, $method] : null;

        return $resolver;
    }

    /**
     * @return Closure(mixed,array<string,mixed>,GraphQLContext,ResolveInfo):EloquentBuilder<covariant EloquentModel>
     */
    protected function getResolverModel(string $model): Closure {
        $class    = $this->namespaceModelClass($model);
        $resolver = static function () use ($class): EloquentBuilder {
            return $class::query();
        };

        return $resolver;
    }

    /**
     * @return array{class-string, string}
     */
    protected function getResolverClass(string $class): array {
        [$class, $method] = explode('@', $class, 2) + [null, null];
        $class            = $this->namespaceClassName($class ?? '');
        $resolver         = [$class, $method ?? '__invoke'];

        return $resolver;
    }
    // </editor-fold>

    // <editor-fold desc="Arguments">
    // =========================================================================
    protected function getArgKey(
        AstManipulator $manipulator,
        ObjectFieldSource|InterfaceFieldSource $source,
    ): string {
        // Explicit?
        $key = $this->directiveArgValue(self::ArgKey);

        if ($key !== null) {
            if (!is_string($key) || $key === '') {
                throw new KeyUnknown($source);
            }

            return $key;
        }

        // Search for field with `ID!` type
        $type  = $this->streamType->getOriginalTypeName($source->getTypeName());
        $type  = $manipulator->getTypeDefinition($type);
        $field = null;

        if (
            $type instanceof HasFieldsType
            || $type instanceof InterfaceTypeDefinitionNode
            || $type instanceof ObjectTypeDefinitionNode
        ) {
            $field = $manipulator->findField($type, static function (mixed $field) use ($manipulator): bool {
                return !$manipulator->isList($field)
                    && !$manipulator->isNullable($field)
                    && $manipulator->getTypeName($field) === Type::ID;
            });
        }

        // Key
        if ($field !== null) {
            $rename = $manipulator->getDirective($field, RenameDirective::class);
            $key    = $rename?->attributeArgValue() ?? $manipulator->getName($field);
        }

        // Found?
        if ($key === null) {
            throw new KeyUnknown($source);
        }

        // Return
        return $key;
    }

    /**
     * @template T
     *
     * @param class-string<DirectiveContract&FieldArgumentDirective<T>> $directive
     * @param array<string, mixed>                                      $args
     *
     * @return T
     */
    protected function getFieldValue(
        string $directive,
        AstManipulator $manipulator,
        ObjectFieldSource $source,
        ResolveInfo $info,
        array $args,
    ): mixed {
        // Collect
        $arguments = $manipulator->findArguments($source->getField(), static fn (): bool => true);
        $instances = [];
        $values    = [];

        foreach ($arguments as $name => $argument) {
            // Directive?
            $instance = $manipulator->getDirective($argument, $directive);

            if ($instance === null) {
                continue;
            }

            // Value
            $instances[$name] = $instance;

            if (array_key_exists($name, $args)) {
                $values[] = $name;
            }
        }

        // Value
        $argument = null;
        $instance = null;

        if (count($values) > 1) {
            throw new ArgumentsMutuallyExclusive($source, $values);
        } elseif (count($instances) === 0) {
            throw new ArgumentMissed($source, $directive);
        } elseif (count($values) === 1) {
            $argument = reset($values);
            $instance = $instances[$argument];
        } else {
            uksort($instances, static function (mixed $a, mixed $b) use ($manipulator, $arguments): int {
                return $manipulator->isDeprecated($arguments[$a]) <=> $manipulator->isDeprecated($arguments[$b]);
            });

            $argument = array_key_first($instances);
            $instance = $instances[$argument];
        }

        return $instance->getFieldArgumentValue($info, $args[$argument] ?? null);
    }
    //</editor-fold>
}
