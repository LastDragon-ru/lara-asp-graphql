<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks;


use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Misc\PrinterSettings;

/**
 * @internal
 *
 * @template TBlock of Block
 */
class Property extends Block implements Named {
    /**
     * @param TBlock $block
     */
    public function __construct(
        PrinterSettings $settings,
        private string $name,
        private Block $block,
    ) {
        parent::__construct($settings, $block->getLevel(), $block->getUsed());
    }

    public function getName(): string {
        return $this->name;
    }

    public function isMultiline(): bool {
        return $this->getBlock()->isMultiline() || parent::isMultiline();
    }

    /**
     * @return TBlock
     */
    protected function getBlock(): Block {
        return $this->block;
    }

    protected function getSeparator(): string {
        return ':';
    }

    protected function content(): string {
        $block   = $this->addUsed($this->getBlock());
        $content = "{$this->getName()}{$this->getSeparator()}{$this->space()}{$block}";

        return $content;
    }
}