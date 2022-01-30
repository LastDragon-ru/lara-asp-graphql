<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Types;

use GraphQL\Type\Definition\ObjectType;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\BlockSettings;

/**
 * @internal
 */
class RootOperationTypeDefinitionBlock extends TypeBlock {
    public function __construct(
        BlockSettings $settings,
        int $level,
        int $used,
        private OperationType $operation,
        ObjectType $type,
    ) {
        parent::__construct($settings, $level, $used, $type);
    }

    public function getOperation(): OperationType {
        return $this->operation;
    }

    protected function content(): string {
        $content = parent::content();
        $content = "{$this->getOperation()}:{$this->space()}{$content}";

        return $content;
    }
}
