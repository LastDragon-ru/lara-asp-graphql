<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Printer\Blocks;

use LastDragon_ru\LaraASP\GraphQL\Printer\Settings;

/**
 * @internal
 */
class NamedBlock extends Block {
    public function __construct(
        Settings $settings,
        private string $name,
        private Block $block,
        private string $separator = ':',
    ) {
        parent::__construct($settings, $block->getLevel(), $block->getUsed());
    }

    public function getName(): string {
        return $this->name;
    }

    public function isMultiline(): bool {
        return $this->getBlock()->isMultiline() || parent::isMultiline();
    }

    protected function getBlock(): Block {
        return $this->block;
    }

    protected function getSeparator(): string {
        return $this->separator;
    }

    protected function content(): string {
        return "{$this->getName()}{$this->getSeparator()}{$this->space()}{$this->getBlock()}";
    }
}
