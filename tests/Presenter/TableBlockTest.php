<?php

declare(strict_types=1);

namespace PHPForge\Debug\Tests\Presenter;

use InvalidArgumentException;
use PHPForge\Debug\{ColumnStyle, Tone};
use PHPForge\Debug\Exception\PanelViewMessage;
use PHPForge\Debug\Presenter\BadgeInline;
use PHPForge\Debug\Presenter\TableBlock;
use PHPForge\Debug\Presenter\TextInline;
use PHPForge\Debug\Presenter\TextStyle;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the table consistency {@see TableBlock} enforces over headers, rows, and column styles.
 */
final class TableBlockTest extends TestCase
{
    public function testHeadersRowsAndStylesKeepTheirDeclaredOrder(): void
    {
        $cell = new TextInline('home', TextStyle::PLAIN);
        $badge = new BadgeInline('cached', Tone::INFO);

        $block = new TableBlock(
            ['Key', 'Value'],
            [[$cell, $badge]],
            [0 => ColumnStyle::MONOSPACE],
            true,
            true,
        );

        self::assertSame(
            ['Key', 'Value'],
            $block->headers,
            'Headings must stay in declaration order.',
        );
        self::assertSame(
            [[$cell, $badge]],
            $block->rows,
            'Cells must travel as the same objects.',
        );
        self::assertSame(
            [0 => ColumnStyle::MONOSPACE],
            $block->styles,
            'Styles must stay keyed by their column index.',
        );
        self::assertTrue(
            $block->collapsible,
            'Collapsing must be requested explicitly.',
        );
        self::assertTrue(
            $block->filterable,
            'Filtering must be requested explicitly.',
        );
    }

    public function testThrowInvalidArgumentExceptionForNegativeStyledColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            PanelViewMessage::COLUMN_STYLE_KEY_INVALID->getMessage(),
        );

        new TableBlock(['One'], [], [-1 => ColumnStyle::PILL], false, false);
    }

    public function testThrowInvalidArgumentExceptionForNonColumnStyle(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            PanelViewMessage::COLUMN_STYLE_INVALID->getMessage(ColumnStyle::class),
        );

        new TableBlock(['One'], [], [0 => 'pill'], false, false);
    }

    public function testThrowInvalidArgumentExceptionForNonStringHeader(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            PanelViewMessage::TABLE_HEADER_INVALID->getMessage(),
        );

        new TableBlock([1], [], [], false, false);
    }

    public function testThrowInvalidArgumentExceptionForRowWidthMismatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            PanelViewMessage::TABLE_ROW_WIDTH_INVALID->getMessage(),
        );

        new TableBlock(['One'], [[]], [], false, false);
    }

    public function testThrowInvalidArgumentExceptionForStringStyledColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            PanelViewMessage::COLUMN_STYLE_KEY_INVALID->getMessage(),
        );

        new TableBlock(['One'], [], ['first' => ColumnStyle::PILL], false, false);
    }

    public function testThrowInvalidArgumentExceptionForUnknownStyledColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            PanelViewMessage::COLUMN_STYLE_KEY_INVALID->getMessage(),
        );

        new TableBlock(['One'], [], [1 => ColumnStyle::PILL], false, false);
    }
}
