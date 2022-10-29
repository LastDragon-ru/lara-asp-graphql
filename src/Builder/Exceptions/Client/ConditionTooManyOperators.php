<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\Client;

use Throwable;

use function implode;
use function sprintf;

class ConditionTooManyOperators extends ClientException {
    /**
     * @param array<string> $operators
     */
    public function __construct(
        protected array $operators,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Only one operator allowed, found: `%s`.',
            implode('`, `', $this->getOperators()),
        ), $previous);
    }

    /**
     * @return array<string>
     */
    public function getOperators(): array {
        return $this->operators;
    }
}