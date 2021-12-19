<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Printer\Blocks;

use LastDragon_ru\LaraASP\GraphQL\Printer\Settings;
use LastDragon_ru\LaraASP\GraphQL\Printer\Settings\DefaultSettings;
use Mockery;
use PHPUnit\Framework\TestCase;
use function mb_strlen;

/**
 * @coversDefaultClass \LastDragon_ru\LaraASP\GraphQL\Printer\Blocks\Block
 */
class BlockTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::getContent
     */
    public function testGetContent(): void {
        $content = 'content';
        $block   = Mockery::mock(BlockTest__Block::class, [new DefaultSettings()]);
        $block->shouldAllowMockingProtectedMethods();
        $block->makePartial();
        $block
            ->shouldReceive('serialize')
            ->once()
            ->andReturn($content);

        self::assertEquals($content, $block->getContent());
        self::assertEquals($content, $block->getContent());
    }

    /**
     * @covers ::getLength
     */
    public function testGetLength(): void {
        $content = 'content';
        $length  = mb_strlen($content);
        $block   = Mockery::mock(BlockTest__Block::class, [new DefaultSettings()]);
        $block->shouldAllowMockingProtectedMethods();
        $block->makePartial();
        $block
            ->shouldReceive('serialize')
            ->once()
            ->andReturn($content);

        self::assertEquals($length, $block->getLength());
        self::assertEquals($length, $block->getLength());
    }

    /**
     * @covers ::isMultiline
     *
     * @dataProvider dataProviderIsMultiline
     */
    public function testIsMultiline(bool $expected, Settings $settings, string $content): void {
        $block = Mockery::mock(BlockTest__Block::class, [$settings]);
        $block->shouldAllowMockingProtectedMethods();
        $block->makePartial();
        $block
            ->shouldReceive('serialize')
            ->once()
            ->andReturn($content);

        self::assertEquals($expected, $block->isMultiline());
        self::assertEquals($expected, $block->isMultiline());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{bool, Settings, string}>
     */
    public function dataProviderIsMultiline(): array {
        return [
            'single short line' => [
                false,
                new DefaultSettings(),
                'short line',
            ],
            'single long line'  => [
                true,
                new class() extends DefaultSettings {
                    public function getLineLength(): int {
                        return 5;
                    }
                },
                'long line',
            ],
            'multi line'        => [
                true,
                new DefaultSettings(),
                "multi\nline",
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
class BlockTest__Block extends Block {
    public function __construct(Settings $settings, int $level = 0) {
        parent::__construct($settings, $level);
    }

    public function getContent(): string {
        return parent::getContent();
    }

    public function getLength(): int {
        return parent::getLength();
    }

    public function isMultiline(): bool {
        return parent::isMultiline();
    }

    protected function serialize(): string {
        return '';
    }

    protected function isNormalized(): bool {
        return false;
    }
}
