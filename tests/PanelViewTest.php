<?php

declare(strict_types=1);

namespace PHPForge\Debug\Tests;

use InvalidArgumentException;
use PHPForge\Debug\{ColumnStyle, PanelView, Tone};
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Unit tests for the validated shapes {@see PanelView} exports to the host renderer.
 */
final class PanelViewTest extends TestCase
{
    public function testDefaultsPreserveNonIntrusivePresentation(): void
    {
        $view = PanelView::create()
            ->heading('Title')
            ->overview([])
            ->table([], []);

        self::assertTrue(
            PanelView::create()->isActive(),
            'A described panel must be active by default.',
        );
        self::assertSame(
            [
                ['kind' => 'heading', 'title' => 'Title', 'section' => false],
                ['kind' => 'overview', 'fields' => [], 'compact' => false],
                ['kind' => 'table', 'headers' => [], 'rows' => [], 'styles' => [], 'collapsible' => false],
            ],
            $view->blocks(),
            'Every presentation hint must stay opt-in.',
        );
    }

    public function testInlineFactoriesDescribeContentStyleAndTone(): void
    {
        self::assertSame(
            ['kind' => 'text', 'value' => '<value>', 'style' => 'plain'],
            PanelView::text('<value>'),
            'Text must stay unescaped and unstyled.',
        );
        self::assertSame(
            ['kind' => 'text', 'value' => 'v', 'style' => 'strong'],
            PanelView::strong('v'),
            'Emphasis must remain semantic.',
        );
        self::assertSame(
            ['kind' => 'text', 'value' => 'v', 'style' => 'code'],
            PanelView::code('v'),
            'Source code must remain semantic.',
        );
        self::assertSame(
            ['kind' => 'text', 'value' => 'v', 'style' => 'preview'],
            PanelView::preview('v'),
            'Clamping must be requested explicitly.',
        );
        self::assertSame(
            ['kind' => 'badge', 'label' => 'shared', 'tone' => Tone::INFO],
            PanelView::badge('shared', Tone::INFO),
            'Badges must retain their text and tone.',
        );
        self::assertSame(
            ['kind' => 'badge', 'label' => 'b', 'tone' => Tone::MUTED],
            PanelView::badge('b'),
            'Badges must default to a muted tone.',
        );
        self::assertSame(
            ['kind' => 'value', 'value' => ['id' => 1], 'typeOnly' => false],
            PanelView::value(['id' => 1]),
            'Diagnostic values must not be flattened.',
        );
        self::assertSame(
            ['kind' => 'value', 'value' => null, 'typeOnly' => true],
            PanelView::value(null, typeOnly: true),
            'Type-only presentation must be explicit.',
        );
    }

    public function testMetricsAndFieldsShareOneLabelAndValueShape(): void
    {
        $view = PanelView::create()
            ->summary(' prop', 1)
            ->summary('', 2.5, emphasized: false)
            ->toolbar('Hits', 3)
            ->overview(['Driver' => 'redis']);

        self::assertSame(
            [
                ['label' => ' prop', 'value' => ['kind' => 'text', 'value' => '1', 'style' => 'strong']],
                ['label' => '', 'value' => ['kind' => 'text', 'value' => '2.5', 'style' => 'plain']],
            ],
            $view->summaryMetrics(),
            'Emphasis must travel as the inline text style.',
        );
        self::assertSame(
            [['label' => 'Hits', 'value' => ['kind' => 'text', 'value' => '3', 'style' => 'plain']]],
            $view->toolbarMetrics(),
            'Toolbar metrics must stay separate from the summary.',
        );
        self::assertSame(
            [
                [
                    'kind' => 'overview',
                    'fields' => [
                        ['label' => 'Driver', 'value' => ['kind' => 'text', 'value' => 'redis', 'style' => 'plain']],
                    ],
                    'compact' => false,
                ],
            ],
            $view->blocks(),
            'Overview fields must reuse the metric shape.',
        );
    }

    public function testThrowInvalidArgumentExceptionForAssociativeParagraph(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Debug panel paragraphs must be scalars, inline values, or lists of them.',
        );

        PanelView::create()->emptyState('Empty', ['first' => 'A']);
    }

    public function testThrowInvalidArgumentExceptionForForgedInlineValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Debug panel inline content must be a scalar, null, or a PanelView inline value. Got array.',
        );

        PanelView::create()->paragraph(['kind' => 'text']);
    }

    public function testThrowInvalidArgumentExceptionForNonColumnStyle(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Debug panel column styles must be ' . ColumnStyle::class . ' cases.',
        );

        PanelView::create()->table(['One'], [['a']], styles: [0 => 'pill']);
    }

    public function testThrowInvalidArgumentExceptionForNonInlineObject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Debug panel inline content must be a scalar, null, or a PanelView inline value. Got stdClass.',
        );

        PanelView::create()->paragraph(new stdClass());
    }

    public function testThrowInvalidArgumentExceptionForNonListRow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Debug panel table rows must be lists whose width matches the headers.',
        );

        PanelView::create()->table(['One'], ['not a row']);
    }

    public function testThrowInvalidArgumentExceptionForNonStringHeader(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Debug panel table headers must be plain strings.',
        );

        PanelView::create()->table([1], [[1]]);
    }

    public function testThrowInvalidArgumentExceptionForRowWidthMismatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Debug panel table rows must be lists whose width matches the headers.',
        );

        PanelView::create()->table(['One'], [[]]);
    }

    public function testThrowInvalidArgumentExceptionForUnknownStyledColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Debug panel column styles must be keyed by an existing column index.',
        );

        PanelView::create()->table(['One'], [['a']], styles: [1 => ColumnStyle::PILL]);
    }
}
