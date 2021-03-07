<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators;

use LastDragon_ru\LaraASP\GraphQL\SearchBy\OperatorNegationable;

class In extends BaseOperator implements OperatorNegationable {
    public function getName(): string {
        return 'in';
    }

    protected function getDescription(): string {
        return 'Within a set of values.';
    }

    /**
     * @inheritdoc
     */
    public function getDefinition(array $map, string $scalar, bool $nullable): string {
        return parent::getDefinition($map, "[{$scalar}!]", true);
    }
}
