<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators;

use LastDragon_ru\LaraASP\GraphQL\SearchBy\Manipulator;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\OperatorNegationable;

class IsNull extends BaseOperator implements OperatorNegationable {
    public function getName(): string {
        return 'isNull';
    }

    protected function getDescription(): string {
        return 'IS NULL (value of property not matter)';
    }

    /**
     * @inheritdoc
     */
    public function getDefinition(array $map, string $scalar, bool $nullable): string {
        return parent::getDefinition($map, $map[Manipulator::TYPE_FLAG], true);
    }
}
