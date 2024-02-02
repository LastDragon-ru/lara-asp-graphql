<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Complex;

use Closure;
use GraphQL\Language\DirectiveLocation;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use LastDragon_ru\LaraASP\Eloquent\ModelHelper;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\BuilderPropertyResolver;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Context;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Handler;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeProvider;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\OperatorUnsupportedBuilder;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByOperatorFieldDirective;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Exceptions\OperatorInvalidArgumentValue;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Operator;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use Nuwave\Lighthouse\Execution\Arguments\ArgumentSet;
use Override;

use function array_merge;
use function is_a;
use function reset;

class Relationship extends Operator {
    public function __construct(
        protected readonly SearchByOperatorFieldDirective $field,
        BuilderPropertyResolver $resolver,
    ) {
        parent::__construct($resolver);
    }

    // <editor-fold desc="Directive">
    // =========================================================================
    /**
     * @inheritDoc
     */
    #[Override]
    protected static function getDirectiveLocations(): array {
        return array_merge(parent::getDirectiveLocations(), [
            DirectiveLocation::FIELD_DEFINITION,
        ]);
    }
    // </editor-fold>

    // <editor-fold desc="Operator">
    // =========================================================================
    #[Override]
    public static function getName(): string {
        return 'relation';
    }

    #[Override]
    public function getFieldType(TypeProvider $provider, TypeSource $source, Context $context): string {
        return $provider->getType(RelationshipType::class, $source, $context);
    }

    #[Override]
    public function getFieldDescription(): string {
        return 'Relationship condition.';
    }

    #[Override]
    public function isAvailable(string $builder, Context $context): bool {
        return is_a($builder, EloquentBuilder::class, true);
    }

    #[Override]
    public function call(
        Handler $handler,
        object $builder,
        Property $property,
        Argument $argument,
        Context $context,
    ): object {
        // Supported?
        if (!($builder instanceof EloquentBuilder)) {
            throw new OperatorUnsupportedBuilder($this, $builder);
        }

        // ArgumentSet?
        if (!($argument->value instanceof ArgumentSet)) {
            throw new OperatorInvalidArgumentValue($this, ArgumentSet::class, $argument->value);
        }

        // Possible variants:
        // * where              = whereHas
        // * where + count      = whereHas
        // * where + exists     = whereHas
        // * where + notExists  = doesntHave

        // Conditions
        $relation  = (new ModelHelper($builder))->getRelation($property->getName());
        $has       = $argument->value->arguments['where'] ?? null;
        $hasCount  = $argument->value->arguments['count'] ?? null;
        $notExists = (bool) ($argument->value->arguments['notExists']->value ?? false);

        // Build
        $alias    = $relation->getRelationCountHash(false);
        $count    = 1;
        $operator = '>=';

        if ($hasCount instanceof Argument) {
            $query    = $builder->getQuery()->newQuery();
            $query    = $this->field->call($handler, $query, new Property(), $hasCount, $context);
            $where    = reset($query->wheres);
            $count    = $where['value'] ?? $count;
            $operator = $where['operator'] ?? $operator;
        } elseif ($notExists) {
            $count    = 1;
            $operator = '<';
        } else {
            // empty
        }

        // Build
        $this->build(
            $builder,
            $property,
            $operator,
            $count,
            static function (EloquentBuilder $builder) use ($context, $relation, $handler, $alias, $has): void {
                if (!$alias || $alias === $relation->getRelationCountHash(false)) {
                    $alias = $builder->getModel()->getTable();
                }

                if ($has instanceof Argument && $has->value instanceof ArgumentSet) {
                    $handler->handle($builder, new Property($alias), $has->value, $context);
                }
            },
        );

        // Return
        return $builder;
    }

    /**
     * @template TBuilder of EloquentBuilder<EloquentModel>
     *
     * @param TBuilder                $builder
     * @param Closure(TBuilder): void $closure
     */
    protected function build(
        EloquentBuilder $builder,
        Property $property,
        string $operator,
        int $count,
        Closure $closure,
    ): void {
        $builder->whereHas($property->getName(), $closure, $operator, $count);
    }
}
