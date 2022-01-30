<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Types;

use GraphQL\Type\Definition\ScalarType;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Block;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\BlockSettings;

/**
 * @internal
 *
 * @extends DefinitionBlock<ScalarType>
 */
class ScalarTypeDefinitionBlock extends DefinitionBlock {
    public function __construct(
        BlockSettings $settings,
        int $level,
        int $used,
        ScalarType $definition,
    ) {
        parent::__construct($settings, $level, $used, $definition);
    }

    protected function type(): string|null {
        return 'scalar';
    }

    protected function body(int $used): Block|string|null {
        return null;
    }

    protected function fields(int $used): Block|string|null {
        return null;
    }
}
