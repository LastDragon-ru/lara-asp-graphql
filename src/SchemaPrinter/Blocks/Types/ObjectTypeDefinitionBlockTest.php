<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Types;

use GraphQL\Language\Parser;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Contracts\Settings;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Misc\DirectiveResolver;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Misc\PrinterSettings;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\SchemaPrinter\TestSettings;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\TestCase;

/**
 * @internal
 * @covers \LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Types\ObjectTypeDefinitionBlock
 */
class ObjectTypeDefinitionBlockTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderToString
     */
    public function testToString(
        string $expected,
        Settings $settings,
        int $level,
        int $used,
        ObjectType $definition,
    ): void {
        $settings = new PrinterSettings($this->app->make(DirectiveResolver::class), $settings);
        $actual   = (string) (new ObjectTypeDefinitionBlock($settings, $level, $used, $definition));

        Parser::objectTypeDefinition($actual);

        self::assertEquals($expected, $actual);
    }

    public function testStatistics(): void {
        $settings   = new TestSettings();
        $settings   = new PrinterSettings($this->app->make(DirectiveResolver::class), $settings);
        $definition = new ObjectType([
            'name'       => 'A',
            'fields'     => [
                'b' => [
                    'name'    => 'b',
                    'type'    => new ObjectType([
                        'name' => 'B',
                    ]),
                    'args'    => [
                        'c' => [
                            'type'    => new ObjectType([
                                'name' => 'C',
                            ]),
                            'astNode' => Parser::inputValueDefinition('c: C @c'),
                        ],
                    ],
                    'astNode' => Parser::fieldDefinition('b: B @b'),
                ],
            ],
            'interfaces' => [
                new InterfaceType([
                    'name' => 'D',
                ]),
            ],
            'astNode'    => Parser::objectTypeDefinition('type A @a'),
        ]);
        $block      = new ObjectTypeDefinitionBlock($settings, 0, 0, $definition);

        self::assertNotEmpty((string) $block);
        self::assertEquals(['B' => 'B', 'C' => 'C', 'D' => 'D'], $block->getUsedTypes());
        self::assertEquals(['@a' => '@a', '@b' => '@b', '@c' => '@c'], $block->getUsedDirectives());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{string, Settings, int, int, ObjectType}>
     */
    public function dataProviderToString(): array {
        $settings = (new TestSettings())
            ->setNormalizeFields(false)
            ->setNormalizeInterfaces(false)
            ->setAlwaysMultilineArguments(false)
            ->setAlwaysMultilineInterfaces(false);

        return [
            'description + directives'                    => [
                <<<'STRING'
                """
                Description
                """
                type Test
                @a
                STRING,
                $settings
                    ->setPrintDirectives(true),
                0,
                0,
                new ObjectType([
                    'name'        => 'Test',
                    'astNode'     => Parser::objectTypeDefinition('type Test @a'),
                    'description' => 'Description',
                ]),
            ],
            'description + directives + fields'           => [
                <<<'STRING'
                """
                Description
                """
                type Test
                @a
                {
                    c: C

                    """
                    Description
                    """
                    b(b: Int): B

                    a(a: Int): A
                }
                STRING,
                $settings->setPrintDirectives(true),
                0,
                0,
                new ObjectType([
                    'name'        => 'Test',
                    'astNode'     => Parser::objectTypeDefinition('type Test @a'),
                    'description' => 'Description',
                    'fields'      => [
                        [
                            'name' => 'c',
                            'type' => new ObjectType([
                                'name' => 'C',
                            ]),
                        ],
                        [
                            'name'        => 'b',
                            'type'        => new ObjectType([
                                'name' => 'B',
                            ]),
                            'args'        => [
                                'b' => [
                                    'type' => Type::int(),
                                ],
                            ],
                            'description' => 'Description',
                        ],
                        [
                            'name' => 'a',
                            'type' => new ObjectType([
                                'name' => 'A',
                            ]),
                            'args' => [
                                'a' => [
                                    'type' => Type::int(),
                                ],
                            ],
                        ],
                    ],
                ]),
            ],
            'fields'                                      => [
                <<<'STRING'
                type Test {
                    a: String
                }
                STRING,
                $settings,
                0,
                0,
                new ObjectType([
                    'name'   => 'Test',
                    'fields' => [
                        [
                            'name' => 'a',
                            'type' => Type::string(),
                        ],
                    ],
                ]),
            ],
            'implements + directives + fields'            => [
                <<<'STRING'
                type Test implements B & A
                @a
                {
                    a: String
                }
                STRING,
                $settings->setPrintDirectives(true),
                0,
                0,
                new ObjectType([
                    'name'       => 'Test',
                    'astNode'    => Parser::objectTypeDefinition('type Test @a'),
                    'fields'     => [
                        [
                            'name' => 'a',
                            'type' => Type::string(),
                        ],
                    ],
                    'interfaces' => [
                        new ObjectType(['name' => 'B']),
                        new ObjectType(['name' => 'A']),
                    ],
                ]),
            ],
            'implements(multiline) + directives + fields' => [
                <<<'STRING'
                type Test
                implements
                    & B
                    & A
                @a
                {
                    a: String
                }
                STRING,
                $settings->setPrintDirectives(true),
                0,
                120,
                new ObjectType([
                    'name'       => 'Test',
                    'astNode'    => Parser::objectTypeDefinition('type Test @a'),
                    'fields'     => [
                        [
                            'name' => 'a',
                            'type' => Type::string(),
                        ],
                    ],
                    'interfaces' => [
                        new ObjectType(['name' => 'B']),
                        new ObjectType(['name' => 'A']),
                    ],
                ]),
            ],
            'implements(multiline) + fields'              => [
                <<<'STRING'
                type Test
                implements
                    & B
                    & A
                {
                    a: String
                }
                STRING,
                $settings,
                0,
                120,
                new ObjectType([
                    'name'       => 'Test',
                    'fields'     => [
                        [
                            'name' => 'a',
                            'type' => Type::string(),
                        ],
                    ],
                    'interfaces' => [
                        new ObjectType(['name' => 'B']),
                        new ObjectType(['name' => 'A']),
                    ],
                ]),
            ],
            'implements + fields'                         => [
                <<<'STRING'
                type Test implements B & A {
                    a: String
                }
                STRING,
                $settings,
                0,
                0,
                new ObjectType([
                    'name'       => 'Test',
                    'fields'     => [
                        [
                            'name' => 'a',
                            'type' => Type::string(),
                        ],
                    ],
                    'interfaces' => [
                        new ObjectType(['name' => 'B']),
                        new ObjectType(['name' => 'A']),
                    ],
                ]),
            ],
            'implements(normalized) + fields'             => [
                <<<'STRING'
                type Test
                implements
                    & A
                    & B
                {
                    a: String
                }
                STRING,
                $settings->setNormalizeInterfaces(true),
                0,
                120,
                new ObjectType([
                    'name'       => 'Test',
                    'fields'     => [
                        [
                            'name' => 'a',
                            'type' => Type::string(),
                        ],
                    ],
                    'interfaces' => [
                        new ObjectType(['name' => 'B']),
                        new ObjectType(['name' => 'A']),
                    ],
                ]),
            ],
            'indent'                                      => [
                <<<'STRING'
                type Test
                    implements
                        & A
                        & B
                    {
                        a: String
                    }
                STRING,
                $settings->setNormalizeInterfaces(true),
                1,
                120,
                new ObjectType([
                    'name'       => 'Test',
                    'fields'     => [
                        [
                            'name' => 'a',
                            'type' => Type::string(),
                        ],
                    ],
                    'interfaces' => [
                        new ObjectType(['name' => 'B']),
                        new ObjectType(['name' => 'A']),
                    ],
                ]),
            ],
            'implements always multiline'                 => [
                <<<'STRING'
                type Test
                implements
                    & B
                {
                    a: String
                }
                STRING,
                $settings
                    ->setAlwaysMultilineInterfaces(true),
                0,
                0,
                new ObjectType([
                    'name'       => 'Test',
                    'fields'     => [
                        [
                            'name' => 'a',
                            'type' => Type::string(),
                        ],
                    ],
                    'interfaces' => [
                        new ObjectType(['name' => 'B']),
                    ],
                ]),
            ],
            'args always multiline'                       => [
                <<<'STRING'
                type Test {
                    """
                    Description
                    """
                    b(
                        b: Int
                    ): B

                    a(
                        a: Int
                    ): A
                }
                STRING,
                $settings->setAlwaysMultilineArguments(true),
                0,
                0,
                new ObjectType([
                    'name'   => 'Test',
                    'fields' => [
                        [
                            'name'        => 'b',
                            'type'        => new ObjectType([
                                'name' => 'B',
                            ]),
                            'args'        => [
                                'b' => [
                                    'type' => Type::int(),
                                ],
                            ],
                            'description' => 'Description',
                        ],
                        [
                            'name' => 'a',
                            'type' => new ObjectType([
                                'name' => 'A',
                            ]),
                            'args' => [
                                'a' => [
                                    'type' => Type::int(),
                                ],
                            ],
                        ],
                    ],
                ]),
            ],
        ];
    }
    // </editor-fold>
}
