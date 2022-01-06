<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Nodes;

use GraphQL\Language\AST\ArgumentNode;
use GraphQL\Language\AST\NodeList;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\BlockList;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Settings;

/**
 * @internal
 */
class Arguments extends BlockList {
    /**
     * @param NodeList<ArgumentNode> $arguments
     */
    public function __construct(
        Settings $settings,
        int $level,
        int $used,
        NodeList $arguments,
    ) {
        parent::__construct($settings, $level, $used);

        foreach ($arguments as $argument) {
            $this[$argument->name->value] = new Value(
                $this->getSettings(),
                $this->getLevel() + 1,
                $this->getUsed(),
                $argument->value,
            );
        }
    }

    protected function getPrefix(): string {
        return '(';
    }

    protected function getSuffix(): string {
        return ')';
    }

    protected function isNormalized(): bool {
        return $this->getSettings()->isNormalizeArguments();
    }
}
