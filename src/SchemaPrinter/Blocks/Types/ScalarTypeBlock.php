<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Types;

use GraphQL\Type\Definition\ScalarType;
use LastDragon_ru\LaraASP\Core\Observer\Dispatcher;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Settings;

/**
 * @internal
 *
 * @extends TypeDefinitionBlock<ScalarType>
 */
class ScalarTypeBlock extends TypeDefinitionBlock {
    public function __construct(
        Dispatcher $dispatcher,
        Settings $settings,
        int $level,
        int $used,
        ScalarType $type,
    ) {
        parent::__construct($dispatcher, $settings, $level, $used, $type);
    }

    protected function body(int $used): string {
        return "scalar{$this->space()}{$this->getName()}";
    }
}
