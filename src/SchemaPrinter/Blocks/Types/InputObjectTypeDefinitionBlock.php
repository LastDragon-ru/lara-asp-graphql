<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Types;

use GraphQL\Type\Definition\InputObjectType;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Block;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Misc\PrinterSettings;

use function mb_strlen;

/**
 * @internal
 *
 * @extends DefinitionBlock<InputObjectType>
 */
class InputObjectTypeDefinitionBlock extends DefinitionBlock {
    public function __construct(
        PrinterSettings $settings,
        int $level,
        int $used,
        InputObjectType $definition,
    ) {
        parent::__construct($settings, $level, $used, $definition);
    }

    protected function type(): string|null {
        return 'input';
    }

    protected function body(int $used): Block|string|null {
        return null;
    }

    protected function fields(int $used): Block|string|null {
        $definition = $this->getDefinition();
        $space      = $this->space();
        $fields     = $this->addUsed(
            new InputFieldsDefinitionList(
                $this->getSettings(),
                $this->getLevel(),
                $used + mb_strlen($space),
                $definition->getFields(),
            ),
        );

        return $fields;
    }
}
