<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Scalars;

use Exception;
use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\ValueNode;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(JsonStringType::class)]
class JsonStringTypeTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderSerialize
     */
    public function testSerialize(?Exception $expected, mixed $value): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $scalar = new JsonStringType();
        $actual = $scalar->serialize($value);

        if ($value instanceof JsonStringable) {
            self::assertEquals((string) $value, $actual);
        } else {
            self::assertEquals($value, $actual);
        }
    }

    /**
     * @dataProvider dataProviderParseValue
     */
    public function testParseValue(?Exception $expected, mixed $value): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $scalar = new JsonStringType();
        $actual = $scalar->parseValue($value);

        self::assertIsString($value);
        self::assertEquals($value, $actual);
    }

    /**
     * @dataProvider dataProviderParseLiteral
     */
    public function testParseLiteral(?Exception $expected, Node&ValueNode $value): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $scalar = new JsonStringType();
        $actual = $scalar->parseLiteral($value);

        self::assertInstanceOf(StringValueNode::class, $value);
        self::assertEquals($value->value, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{?Exception, mixed}>
     */
    public static function dataProviderSerialize(): array {
        return [
            'not a string'                => [
                new InvariantViolation('The valid JSON string expected, `123` given.'),
                123,
            ],
            'string but not a valid json' => [
                new InvariantViolation('The valid JSON string expected, `"invalid json"` given.'),
                'invalid json',
            ],
            'string and a valid json'     => [
                null,
                '{"a": 123, "b": {"c": 45}}',
            ],
            JsonStringable::class         => [
                null,
                new class('{"a": 123, "b": {"c": 45}}') implements JsonStringable {
                    public function __construct(
                        private readonly string $json,
                    ) {
                        // empty
                    }

                    public function __toString(): string {
                        return $this->json;
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, array{?Exception, mixed}>
     */
    public static function dataProviderParseValue(): array {
        return [
            'not a string'                => [
                new Error('The valid JSON string expected, `123` given.'),
                123,
            ],
            'string but not a valid json' => [
                new Error('The valid JSON string expected, `"invalid json"` given.'),
                'invalid json',
            ],
            'string and a valid json'     => [
                null,
                '{"a": 123, "b": {"c": 45}}',
            ],
        ];
    }

    /**
     * @return array<string, array{?Exception, Node&ValueNode}>
     */
    public static function dataProviderParseLiteral(): array {
        return [
            'not a string'                => [
                new Error('The `StringValue` value expected, `IntValue` given.'),
                new IntValueNode(['value' => '123']),
            ],
            'string but not a valid json' => [
                new Error('The valid JSON string expected, `"invalid json"` given.'),
                new StringValueNode(['value' => 'invalid json']),
            ],
            'string and a valid json'     => [
                null,
                new StringValueNode(['value' => '{"a": 123, "b": {"c": 45}}']),
            ],
        ];
    }
    // </editor-fold>
}