<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Types;

use GraphQL\Language\Parser;
use GraphQL\Type\Definition\EnumValueDefinition;
use LastDragon_ru\LaraASP\Core\Observer\Dispatcher;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Settings;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\SchemaPrinter\TestSettings;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversDefaultClass \LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Types\EnumValueDefinitionBlock
 */
class EnumValueDefinitionBlockTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__toString
     *
     * @dataProvider dataProviderToString
     */
    public function testToString(
        string $expected,
        Settings $settings,
        int $level,
        int $used,
        EnumValueDefinition $type,
    ): void {
        $actual = (string) (new EnumValueDefinitionBlock(new Dispatcher(), $settings, $level, $used, $type));

        Parser::enumValueDefinition($actual);

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{string, Settings, int, int, EnumValueDefinition}>
     */
    public function dataProviderToString(): array {
        $settings = new TestSettings();

        return [
            'value'                      => [
                <<<'STRING'
                A
                STRING,
                $settings,
                0,
                0,
                new EnumValueDefinition([
                    'name'  => 'A',
                    'value' => 'A',
                ]),
            ],
            'indent'                     => [
                <<<'STRING'
                A
                STRING,
                $settings,
                1,
                0,
                new EnumValueDefinition([
                    'name'  => 'A',
                    'value' => 'A',
                ]),
            ],
            'description and directives' => [
                <<<'STRING'
                """
                Description
                """
                A
                @a
                STRING,
                $settings->setPrintDirectives(true),
                0,
                0,
                new EnumValueDefinition([
                    'name'        => 'A',
                    'value'       => 'A',
                    'astNode'     => Parser::enumValueDefinition('A @a'),
                    'description' => 'Description',
                ]),
            ],
        ];
    }
    // </editor-fold>
}
