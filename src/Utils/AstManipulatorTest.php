<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Utils;

use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\TestCase;
use Nuwave\Lighthouse\Schema\AST\ASTBuilder;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\Directives\AllDirective;
use Nuwave\Lighthouse\Schema\SchemaBuilder;
use Nuwave\Lighthouse\Schema\TypeRegistry;
use Nuwave\Lighthouse\Support\Contracts\Directive;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

use function array_keys;
use function array_map;

/**
 * @internal
 */
#[CoversClass(AstManipulator::class)]
class AstManipulatorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testGetNodeInterfaces(): void {
        // Object
        $types       = $this->app->make(TypeRegistry::class);
        $manipulator = $this->getManipulator(
        /** @lang GraphQL */
            <<<'GRAPHQL'
            interface InterfaceA {
                id: ID!
            }

            interface InterfaceB implements InterfaceA & InterfaceC {
                id: ID!
            }

            type ObjectA implements InterfaceA & InterfaceB {
                id: ID!
            }
            GRAPHQL,
        );
        $interface   = new InterfaceType([
            'name'       => 'InterfaceC',
            'interfaces' => [
                static function (): InterfaceType {
                    return new InterfaceType([
                        'name'   => 'InterfaceD',
                        'fields' => [
                            'id' => [
                                'type' => Type::nonNull(Type::id()),
                            ],
                        ],
                    ]);
                },
            ],
            'fields'     => [
                'id' => [
                    'type' => Type::nonNull(Type::id()),
                ],
            ],
        ]);

        $types->register($interface);
        $types->register(
            new ObjectType([
                'name'       => 'ObjectB',
                'interfaces' => [$interface],
                'fields'     => [
                    'id' => [
                        'type' => Type::nonNull(Type::id()),
                    ],
                ],
            ]),
        );

        // Object
        $object = $manipulator->getTypeDefinitionNode('ObjectA');

        self::assertInstanceOf(ObjectTypeDefinitionNode::class, $object);
        self::assertEquals(
            [
                'InterfaceA',
                'InterfaceB',
                'InterfaceC',
                'InterfaceD',
            ],
            array_keys(
                $manipulator->getNodeInterfaces($object),
            ),
        );

        // ObjectType
        $objectType = $manipulator->getTypeDefinitionNode('ObjectB');

        self::assertInstanceOf(ObjectType::class, $objectType);
        self::assertEquals(
            [
                'InterfaceC',
                'InterfaceD',
            ],
            array_keys(
                $manipulator->getNodeInterfaces($objectType),
            ),
        );

        // Interface
        $interface = $manipulator->getTypeDefinitionNode('InterfaceB');

        self::assertInstanceOf(InterfaceTypeDefinitionNode::class, $interface);
        self::assertEquals(
            [
                'InterfaceA',
                'InterfaceC',
                'InterfaceD',
            ],
            array_keys(
                $manipulator->getNodeInterfaces($interface),
            ),
        );

        // InterfaceType
        $interfaceType = $manipulator->getTypeDefinitionNode('InterfaceC');

        self::assertInstanceOf(InterfaceType::class, $interfaceType);
        self::assertEquals(
            [
                'InterfaceD',
            ],
            array_keys(
                $manipulator->getNodeInterfaces($interfaceType),
            ),
        );
    }

    public function testGetNodeDirectives(): void {
        // Types
        $types = $this->app->make(TypeRegistry::class);

        $types->register(new CustomScalarType([
            'name' => 'CustomScalar',
        ]));

        // Directives
        $locator = $this->app->make(DirectiveLocator::class);

        $locator->setResolved('aDirective', AstManipulatorTest_ADirective::class);
        $locator->setResolved('bDirective', AstManipulatorTest_BDirective::class);
        $locator->setResolved('cDirective', AstManipulatorTest_CDirective::class);

        // Schema
        $this->useGraphQLSchema(
        /** @lang GraphQL */
            <<<'GRAPHQL'
            extend scalar Int @aDirective @bDirective
            scalar CustomScalar @bDirective @cDirective
            extend scalar CustomScalar @aDirective

            type Query {
                test(arg: String @aDirective @cDirective): Test @all @bDirective
            }

            type Test {
                id: ID!
            }
            GRAPHQL,
        );

        // Prepare
        $map         = static function (Directive $directive): string {
            return $directive::class;
        };
        $manipulator = $this->getManipulator();

        // Another class
        self::assertEquals(
            [
                // empty
            ],
            array_map(
                $map,
                $manipulator->getNodeDirectives(
                    $manipulator->getTypeDefinitionNode('CustomScalar'),
                    stdClass::class,
                ),
            ),
        );

        // Scalar node
        self::assertEquals(
            [
                AstManipulatorTest_BDirective::class,
                AstManipulatorTest_CDirective::class,
            ],
            array_map(
                $map,
                $manipulator->getNodeDirectives(
                    $manipulator->getTypeDefinitionNode('CustomScalar'),
                    Directive::class,
                ),
            ),
        );

        // Type
        self::assertEquals(
            [
                // Not supported by Lighthouse yet :(
            ],
            array_map(
                $map,
                $manipulator->getNodeDirectives(
                    Type::int(),
                    Directive::class,
                ),
            ),
        );

        // Field
        $schema   = $this->app->make(SchemaBuilder::class)->schema();
        $query    = $schema->getQueryType();
        $field    = $manipulator->getNodeField($query, 'test');
        $expected = [
            AllDirective::class,
            AstManipulatorTest_BDirective::class,
        ];

        self::assertInstanceOf(FieldDefinition::class, $field);
        self::assertNotNull($field->astNode);
        self::assertEquals(
            $expected,
            array_map(
                $map,
                $manipulator->getNodeDirectives(
                    $field,
                    Directive::class,
                ),
            ),
        );
        self::assertEquals(
            $expected,
            array_map(
                $map,
                $manipulator->getNodeDirectives(
                    $field->astNode,
                    Directive::class,
                ),
            ),
        );

        // Argument
        $argument = $manipulator->getNodeArgument($field, 'arg');
        $expected = [
            AstManipulatorTest_ADirective::class,
            AstManipulatorTest_CDirective::class,
        ];

        self::assertInstanceOf(Argument::class, $argument);
        self::assertNotNull($argument->astNode);
        self::assertEquals(
            $expected,
            array_map(
                $map,
                $manipulator->getNodeDirectives(
                    $argument,
                    Directive::class,
                ),
            ),
        );
        self::assertEquals(
            $expected,
            array_map(
                $map,
                $manipulator->getNodeDirectives(
                    $argument->astNode,
                    Directive::class,
                ),
            ),
        );
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected function getManipulator(string $schema = null): AstManipulator {
        $document    = $schema
            ? DocumentAST::fromSource($schema)
            : $this->app->make(ASTBuilder::class)->documentAST();
        $manipulator = $this->app->make(AstManipulator::class, [
            'document' => $document,
        ]);

        return $manipulator;
    }
    // </editor-fold>
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class AstManipulatorTest_ADirective implements Directive {
    public static function definition(): string {
        return 'directive @astManipulatorTest_A on OBJECT | SCALAR';
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class AstManipulatorTest_BDirective implements Directive {
    public static function definition(): string {
        return 'directive @astManipulatorTest_B on OBJECT | SCALAR';
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class AstManipulatorTest_CDirective implements Directive {
    public static function definition(): string {
        return 'directive @astManipulatorTest_C on OBJECT | SCALAR';
    }
}
