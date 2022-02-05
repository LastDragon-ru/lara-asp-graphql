<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks;

use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Misc\DirectiveResolver;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Misc\PrinterSettings;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\SchemaPrinter\TestSettings;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\TestCase;

use function mb_strlen;

/**
 * @internal
 * @coversDefaultClass \LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Blocks\Property
 */
class PropertyTest extends TestCase {
    /**
     * @covers ::__toString
     * @covers ::getLength
     * @covers ::getLevel
     * @covers ::getUsed
     */
    public function testToString(): void {
        $name      = 'name';
        $used      = 123;
        $level     = 2;
        $space     = '  ';
        $separator = ':';
        $content   = 'abc abcabc abcabc abcabc abc';
        $settings  = (new TestSettings())->setSpace($space);
        $settings  = new PrinterSettings($this->app->make(DirectiveResolver::class), $settings);
        $block     = new class($settings, $level, $used, $content) extends Block {
            public function __construct(
                PrinterSettings $settings,
                int $level,
                int $used,
                protected string $content,
            ) {
                parent::__construct($settings, $level, $used);
            }

            protected function content(): string {
                return $this->content;
            }
        };
        $property  = new class($settings, $name, $block, $separator) extends Property {
            public function __construct(
                PrinterSettings $settings,
                string $name,
                Block $block,
                private string $separator,
            ) {
                parent::__construct($settings, $name, $block);
            }

            public function getUsed(): int {
                return parent::getUsed();
            }

            public function getLevel(): int {
                return parent::getLevel();
            }

            protected function getSeparator(): string {
                return $this->separator;
            }
        };
        $expected  = "{$name}{$separator}{$space}{$content}";

        self::assertEquals($used, $property->getUsed());
        self::assertEquals($level, $property->getLevel());
        self::assertEquals($expected, (string) $property);
        self::assertEquals(mb_strlen($expected), mb_strlen((string) $property));
        self::assertEquals(mb_strlen($expected), $property->getLength());
    }
}