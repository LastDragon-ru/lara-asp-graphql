<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SortBy\Operators;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Laravel\Scout\Builder as ScoutBuilder;
use LastDragon_ru\LaraASP\Core\Utils\Cast;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Handler;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeProvider;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\OperatorUnsupportedBuilder;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Builders\Clause;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Builders\Eloquent\Builder as EloquentHandler;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Builders\Query\Builder as QueryHandler;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Builders\Scout\Builder as ScoutHandler;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Types\Direction;
use Nuwave\Lighthouse\Execution\Arguments\Argument;

class OrderBy extends BaseOperator {
    public function __construct(
        private EloquentHandler $eloquent,
        private QueryHandler $query,
        private ScoutHandler $scout,
    ) {
        parent::__construct();
    }

    public static function getName(): string {
        return 'orderBy';
    }

    public function getFieldType(TypeProvider $provider, string $type): ?string {
        return $provider->getType(Direction::class);
    }

    public function getFieldDescription(): string {
        return 'Property clause.';
    }

    public function call(Handler $handler, object $builder, Property $property, Argument $argument): object {
        $direction = Cast::toString($argument->value);
        $clauses   = [new Clause($property->getPath(), $direction)];

        if ($builder instanceof EloquentBuilder) {
            $this->eloquent->handle($builder, $clauses);
        } elseif ($builder instanceof QueryBuilder) {
            $this->query->handle($builder, $clauses);
        } elseif ($builder instanceof ScoutBuilder) {
            $this->scout->handle($builder, $clauses);
        } else {
            throw new OperatorUnsupportedBuilder($this, $builder);
        }

        return $builder;
    }
}
