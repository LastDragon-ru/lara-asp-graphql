<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Ast;

use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\Directive;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\BlockList;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Settings;
use Traversable;

use function array_filter;
use function json_encode;

/**
 * @internal
 * @extends BlockList<DirectiveNodeBlock>
 */
class DirectiveNodeList extends BlockList {
    /**
     * @param Traversable<DirectiveNode>|array<DirectiveNode> $directives
     */
    public function __construct(
        Settings $settings,
        int $level,
        int $used,
        Traversable|array|null $directives,
        string|null $deprecationReason = null,
    ) {
        parent::__construct($settings, $level, $used);

        $deprecated   = Directive::DEPRECATED_NAME;
        $directives ??= [];

        if ($deprecationReason) {
            // todo(graphql): Is there a better way to create directive node?
            if ($deprecationReason !== Directive::DEFAULT_DEPRECATION_REASON) {
                $reason = json_encode($deprecationReason);
                $this[] = $this->block(Parser::directive("@{$deprecated}(reason: {$reason})"));
            } else {
                $this[] = $this->block(Parser::directive("@{$deprecated}"));
            }
        }

        foreach ($directives as $directive) {
            if ($deprecationReason && $directive->name->value === $deprecated) {
                continue;
            }

            $this[] = $this->block($directive);
        }
    }

    protected function isAlwaysMultiline(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function getBlocks(): array {
        $filter = $this->getSettings()->getDirectiveFilter();
        $blocks = parent::getBlocks();

        if ($filter !== null) {
            $blocks = array_filter($blocks, static function (DirectiveNodeBlock $block) use ($filter): bool {
                return $filter->isAllowedDirective($block->getNode());
            });
        }

        return $blocks;
    }

    private function block(DirectiveNode $directive,): DirectiveNodeBlock {
        return new DirectiveNodeBlock(
            $this->getSettings(),
            $this->getLevel(),
            $this->getUsed(),
            $directive,
        );
    }
}
