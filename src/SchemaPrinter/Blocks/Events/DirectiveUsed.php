<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Events;

/**
 * @internal
 */
class DirectiveUsed implements Event {
    public function __construct(
        public string $name,
    ) {
        // empty
    }
}
