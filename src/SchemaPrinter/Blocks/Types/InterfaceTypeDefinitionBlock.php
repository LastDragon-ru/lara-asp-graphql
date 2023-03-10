<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Types;

use GraphQL\Type\Definition\InterfaceType;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Misc\PrinterSettings;

/**
 * @internal
 *
 * @extends TypeDefinitionBlock<InterfaceType>
 */
class InterfaceTypeDefinitionBlock extends TypeDefinitionBlock {
    public function __construct(
        PrinterSettings $settings,
        int $level,
        int $used,
        InterfaceType $definition,
    ) {
        parent::__construct($settings, $level, $used, $definition);
    }

    protected function type(): string|null {
        return 'interface';
    }
}