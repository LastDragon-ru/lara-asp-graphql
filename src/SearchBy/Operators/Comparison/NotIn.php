<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Contracts\ComparisonOperator;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Contracts\TypeProvider;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\BaseOperator;

class NotIn extends BaseOperator implements ComparisonOperator {
    public static function getName(): string {
        return 'notIn';
    }

    public function getFieldDescription(): string {
        return 'Outside a set of values.';
    }

    public function getFieldType(TypeProvider $provider, string $type): string {
        return "[{$type}!]";
    }

    public function apply(
        EloquentBuilder|QueryBuilder $builder,
        string $property,
        mixed $value,
    ): EloquentBuilder|QueryBuilder {
        return $builder->whereNotIn($property, $value);
    }
}
