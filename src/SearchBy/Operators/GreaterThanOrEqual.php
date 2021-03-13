<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\ComparisonOperator;

class GreaterThanOrEqual extends BaseOperator implements ComparisonOperator {
    public function getName(): string {
        return 'gte';
    }

    protected function getDescription(): string {
        return 'Greater than or equal to (`>=`).';
    }

    public function apply(
        EloquentBuilder|QueryBuilder $builder,
        string $property,
        mixed $value,
        bool $not,
    ): EloquentBuilder|QueryBuilder {
        return $builder->where($property, '>=', $value);
    }
}
