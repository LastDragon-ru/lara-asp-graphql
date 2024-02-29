<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Builder\Directives;

use Exception;
use GraphQL\Language\AST\Node;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\AST;
use LastDragon_ru\LaraASP\Core\Utils\Cast;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\TypeDefinitionIsNotScalarExtension;
use LastDragon_ru\LaraASP\GraphQL\Builder\Scalars\Internal;
use LastDragon_ru\LaraASP\GraphQL\Exceptions\TypeDefinitionAlreadyDefined;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\TestCase;
use Nuwave\Lighthouse\Events\BuildSchemaString;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

use function is_string;

/**
 * @internal
 */
#[CoversClass(SchemaDirective::class)]
final class SchemaDirectiveTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testInvoke(): void {
        $directive = new SchemaDirective__Directive();
        $actual    = $directive(new BuildSchemaString(''));
        $class     = self::getGraphQLStringValue(Internal::class);
        $expected  = "scalar SchemaDirective @scalar(class: {$class}) @schemaDirective__";

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dataProviderManipulateTypeDefinition
     */
    public function testManipulateTypeDefinition(Exception|string $expected, string $schema, string $type): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $directive                          = new SchemaDirective__Directive();
        $document                           = DocumentAST::fromSource($schema);
        $root                               = Parser::scalarTypeDefinition($directive(new BuildSchemaString($schema)));
        $document->types['SchemaDirective'] = $root;

        $directive->manipulateTypeDefinition($document, $root);

        $type = $document->types[$type] ?? null;

        self::assertNotNull($type);
        self::assertFalse(isset($document->types['SchemaDirective']));
        self::assertFalse(isset($document->typeExtensions['SchemaDirective']));

        if (is_string($expected)) {
            $this->assertGraphQLPrintableEquals($expected, $type);
        }
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    private static function getGraphQLStringValue(string $string): string {
        $string = Cast::to(Node::class, AST::astFromValue($string, Type::string()));
        $string = Printer::doPrint($string);

        return $string;
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{Exception|string, string, string}>
     */
    public static function dataProviderManipulateTypeDefinition(): array {
        $string  = self::getGraphQLStringValue(StringType::class);
        $default = self::getGraphQLStringValue(Internal::class);

        return [
            'Scalar'                 => [
                new TypeDefinitionAlreadyDefined('TestScalar'),
                <<<GRAPHQL
                type Query {
                    test: String! @mock
                }

                scalar TestScalar
                @scalar(class: {$string})

                extend scalar TestScalar
                @test
                GRAPHQL,
                'TestScalar',
            ],
            'Scalar (no definition)' => [
                <<<GRAPHQL
                scalar TestScalar
                @scalar(
                    class: {$default}
                )
                @test
                GRAPHQL,
                <<<'GRAPHQL'
                type Query {
                    test: String! @mock
                }

                extend scalar TestScalar
                @test
                GRAPHQL,
                'TestScalar',
            ],
            'Not a scalar extension' => [
                new TypeDefinitionIsNotScalarExtension('TestScalar', 'extend enum TestScalar'),
                <<<'GRAPHQL'
                type Query {
                    test: String! @mock
                }

                extend enum TestScalar
                @test
                GRAPHQL,
                'TestScalar',
            ],
            'Unsupported'            => [
                <<<'GRAPHQL'
                union MyUnion =
                    | A
                    | B
                GRAPHQL,
                <<<'GRAPHQL'
                type Query {
                    test: String! @mock
                }

                type A {
                    a: String!
                }

                type B {
                    b: String!
                }

                union MyUnion = A | B

                extend union MyUnion
                @test
                GRAPHQL,
                'MyUnion',
            ],
        ];
    }
    // </editor-fold>
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class SchemaDirective__Directive extends SchemaDirective {
    #[Override]
    protected function getNamespace(): string {
        return 'Test';
    }
}
