<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SortBy\Operators;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Laravel\Scout\Builder as ScoutBuilder;
use LastDragon_ru\LaraASP\Core\Utils\Cast;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Handler;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeProvider;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\OperatorUnsupportedBuilder;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Builders\Eloquent\Builder as EloquentHandler;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Builders\Query\Builder as QueryHandler;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Builders\Scout\Builder as ScoutHandler;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Types\Direction;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use Override;

class Field extends BaseOperator {
    public function __construct(
        private EloquentHandler $eloquent,
        private QueryHandler $query,
        private ScoutHandler $scout,
    ) {
        parent::__construct();
    }

    #[Override]
    public static function getName(): string {
        return 'field';
    }

    #[Override]
    public function getFieldType(TypeProvider $provider, TypeSource $source): string {
        return $provider->getType(Direction::class, $source);
    }

    #[Override]
    public function getFieldDescription(): string {
        return 'Property clause.';
    }

    #[Override]
    public function call(Handler $handler, object $builder, Property $property, Argument $argument): object {
        $direction = Cast::toString($argument->value);

        if ($builder instanceof EloquentBuilder) {
            $this->eloquent->handle($builder, $property, $direction);
        } elseif ($builder instanceof QueryBuilder) {
            $this->query->handle($builder, $property, $direction);
        } elseif ($builder instanceof ScoutBuilder) {
            $this->scout->handle($builder, $property, $direction);
        } else {
            throw new OperatorUnsupportedBuilder($this, $builder);
        }

        return $builder;
    }
}
