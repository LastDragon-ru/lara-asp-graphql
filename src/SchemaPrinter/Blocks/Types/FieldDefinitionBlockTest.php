<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Types;

use GraphQL\Language\Parser;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Contracts\Settings;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Misc\DirectiveResolver;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Misc\PrinterSettings;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\SchemaPrinter\TestSettings;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\TestCase;

/**
 * @internal
 * @coversDefaultClass \LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Types\FieldDefinitionBlock
 */
class FieldDefinitionBlockTest extends TestCase {
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
        FieldDefinition $definition,
    ): void {
        $settings = new PrinterSettings($this->app->make(DirectiveResolver::class), $settings);
        $actual   = (string) (new FieldDefinitionBlock($settings, $level, $used, $definition));

        Parser::fieldDefinition($actual);

        self::assertEquals($expected, $actual);
    }

    /**
     * @covers ::__toString
     */
    public function testStatistics(): void {
        $settings   = new TestSettings();
        $settings   = new PrinterSettings($this->app->make(DirectiveResolver::class), $settings);
        $definition = FieldDefinition::create([
            'name'    => 'A',
            'type'    => new NonNull(
                new ObjectType([
                    'name' => 'A',
                ]),
            ),
            'astNode' => Parser::fieldDefinition('a: A @a'),
        ]);
        $block      = new FieldDefinitionBlock($settings, 0, 0, $definition);

        self::assertNotEmpty((string) $block);
        self::assertEquals(['A' => 'A'], $block->getUsedTypes());
        self::assertEquals(['@a' => '@a'], $block->getUsedDirectives());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{string, Settings, int, int, FieldDefinition}>
     */
    public function dataProviderToString(): array {
        $settings = (new TestSettings())
            ->setNormalizeArguments(false);

        return [
            'without args'         => [
                <<<'STRING'
                """
                Description
                """
                test: Test!
                @a
                STRING,
                $settings->setPrintDirectives(true),
                0,
                0,
                FieldDefinition::create([
                    'name'        => 'test',
                    'type'        => new NonNull(
                        new ObjectType([
                            'name' => 'Test',
                        ]),
                    ),
                    'astNode'     => Parser::fieldDefinition('test: Test! @a'),
                    'description' => 'Description',
                ]),
            ],
            'with args (short)'    => [
                <<<'STRING'
                """
                Description
                """
                test(a: [String!] = ["aaaaaaaaaaaaaaaaaaaaaaaaaa"], b: Int): Test!
                STRING,
                $settings,
                0,
                0,
                FieldDefinition::create([
                    'name'        => 'test',
                    'type'        => new NonNull(
                        new ObjectType([
                            'name' => 'Test',
                        ]),
                    ),
                    'args'        => [
                        'a' => [
                            'type'         => new ListOfType(new NonNull(Type::string())),
                            'defaultValue' => [
                                'aaaaaaaaaaaaaaaaaaaaaaaaaa',
                            ],
                        ],
                        'b' => [
                            'type' => Type::int(),
                        ],
                    ],
                    'description' => 'Description',
                ]),
            ],
            'with args (long)'     => [
                <<<'STRING'
                test(
                    b: Int

                    """
                    Description
                    """
                    a: String! = "aaaaaaaaaaaaaaaaaaaaaaaaaa"
                ): Test!
                STRING,
                $settings,
                0,
                0,
                FieldDefinition::create([
                    'name' => 'test',
                    'type' => new NonNull(
                        new ObjectType([
                            'name' => 'Test',
                        ]),
                    ),
                    'args' => [
                        'b' => [
                            'type' => Type::int(),
                        ],
                        'a' => [
                            'type'         => new NonNull(Type::string()),
                            'description'  => 'Description',
                            'defaultValue' => 'aaaaaaaaaaaaaaaaaaaaaaaaaa',
                        ],
                    ],
                ]),
            ],
            'with args normalized' => [
                <<<'STRING'
                test(a: String, b: Int): Test!
                STRING,
                $settings->setNormalizeArguments(true),
                0,
                0,
                FieldDefinition::create([
                    'name' => 'test',
                    'type' => new NonNull(
                        new ObjectType([
                            'name' => 'Test',
                        ]),
                    ),
                    'args' => [
                        'b' => [
                            'type' => Type::int(),
                        ],
                        'a' => [
                            'type' => Type::string(),
                        ],
                    ],
                ]),
            ],
            'indent'               => [
                <<<'STRING'
                test(
                        a: String
                        b: Int
                    ): Test!
                STRING,
                $settings->setNormalizeArguments(true),
                1,
                120,
                FieldDefinition::create([
                    'name' => 'test',
                    'type' => new NonNull(
                        new ObjectType([
                            'name' => 'Test',
                        ]),
                    ),
                    'args' => [
                        'b' => [
                            'type' => Type::int(),
                        ],
                        'a' => [
                            'type' => Type::string(),
                        ],
                    ],
                ]),
            ],
        ];
    }
    // </editor-fold>
}