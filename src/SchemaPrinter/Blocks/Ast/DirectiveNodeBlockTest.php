<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Ast;

use Closure;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\Parser;
use LastDragon_ru\LaraASP\Core\Observer\Dispatcher;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Events\DirectiveUsed;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Events\Event;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Settings;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Settings\DefaultSettings;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Ast\DirectiveNodeBlock
 */
class DirectiveNodeBlockTest extends TestCase {
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
        DirectiveNode $node,
    ): void {
        $actual = (string) (new DirectiveNodeBlock(new Dispatcher(), $settings, $level, $used, $node));
        $parsed = Parser::directive($actual);

        self::assertEquals($expected, $actual);
        self::assertInstanceOf(DirectiveNode::class, $parsed);
    }

    /**
     * @covers ::__toString
     */
    public function testToStringEvent(): void {
        $spy        = Mockery::spy(static fn(Event $event) => null);
        $node       = Parser::directive('@test');
        $settings   = new DefaultSettings();
        $dispatcher = new Dispatcher();

        $dispatcher->attach(Closure::fromCallable($spy));

        self::assertNotNull(
            (string) (new DirectiveNodeBlock($dispatcher, $settings, 0, 0, $node)),
        );

        $spy
            ->shouldHaveBeenCalled()
            ->withArgs(static function (Event $event) use ($node): bool {
                return $event instanceof DirectiveUsed
                    && $event->name === $node->name->value;
            })
            ->once();
        $spy
            ->shouldHaveBeenCalled()
            ->once();
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{string, Settings, int, int, DirectiveNode}>
     */
    public function dataProviderToString(): array {
        return [
            'without arguments'           => [
                '@directive',
                new DefaultSettings(),
                0,
                0,
                Parser::directive('@directive'),
            ],
            'without arguments (level)'   => [
                '@directive',
                new DefaultSettings(),
                0,
                0,
                Parser::directive('@directive'),
            ],
            'with arguments (short)'      => [
                '@directive(a: "a", b: "b")',
                new DefaultSettings(),
                0,
                0,
                Parser::directive('@directive(a: "a", b: "b")'),
            ],
            'with arguments (long)'       => [
                <<<'STRING'
                @directive(
                    b: "b"
                    a: "a"
                )
                STRING,
                new DefaultSettings(),
                0,
                120,
                Parser::directive('@directive(b: "b", a: "a")'),
            ],
            'with arguments (normalized)' => [
                '@directive(a: "a", b: "b")',
                new class() extends DefaultSettings {
                    public function isNormalizeArguments(): bool {
                        return true;
                    }
                },
                0,
                0,
                Parser::directive('@directive(b: "b", a: "a")'),
            ],
            'with arguments (indent)'      => [
                <<<'STRING'
                @directive(
                        b: "b"
                        a: "a"
                    )
                STRING,
                new DefaultSettings(),
                1,
                120,
                Parser::directive('@directive(b: "b", a: "a")'),
            ],
        ];
    }
    // </editor-fold>
}
