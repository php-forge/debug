<?php

declare(strict_types=1);

namespace PHPForge\Debug\Tests;

use PHPForge\Debug\{ColumnStyle, PanelView, Tone};
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for immutable fluent composition in {@see PanelView}.
 */
final class FluentPanelViewTest extends TestCase
{
    public function testDefinitionKeepsEveryContentOptionAndOrder(): void
    {
        $badge = PanelView::badge('Ready', Tone::SUCCESS);

        $nested = PanelView::create()
            ->heading('Nested')
            ->paragraph('Nested text');

        $view = PanelView::create()
            ->summary(' hits', 3)
            ->toolbar('Hits', 3)
            ->active(false)
            ->overview(['Driver' => 'redis', 'State' => $badge, 'Null' => null, 'Ratio' => 1.5], compact: true)
            ->heading('Entries', section: true)
            ->table(
                ['Key', 'Value'],
                [
                    ['home', true],
                    [$badge, PanelView::value(1)],
                ],
                collapsible: true,
                styles: [0 => ColumnStyle::MONOSPACE],
            )
            ->callout(Tone::WARNING, 'State: ', $badge, 0)
            ->emptyState('Empty', 'Plain', ['Mixed ', PanelView::code('x')])
            ->disclosure('Raw', '<data>')
            ->group('Nested group', $nested);

        self::assertSame(
            [
                [
                    'kind' => 'overview',
                    'fields' => [
                        ['label' => 'Driver', 'value' => PanelView::text('redis')],
                        ['label' => 'State', 'value' => $badge],
                        ['label' => 'Null', 'value' => PanelView::text('null')],
                        ['label' => 'Ratio', 'value' => PanelView::text('1.5')],
                    ],
                    'compact' => true,
                ],
                [
                    'kind' => 'heading',
                    'title' => 'Entries',
                    'section' => true,
                ],
                [
                    'kind' => 'table',
                    'headers' => ['Key', 'Value'],
                    'rows' => [
                        [PanelView::text('home'), PanelView::text('true')],
                        [$badge, PanelView::value(1)],
                    ],
                    'styles' => [0 => ColumnStyle::MONOSPACE],
                    'collapsible' => true,
                    'filterable' => false,
                ],
                [
                    'kind' => 'paragraph',
                    'content' => [PanelView::text('State: '), $badge, PanelView::text('0')],
                    'tone' => Tone::WARNING,
                ],
                [
                    'kind' => 'emptyState',
                    'title' => 'Empty',
                    'paragraphs' => [
                        ['kind' => 'paragraph', 'content' => [PanelView::text('Plain')], 'tone' => null],
                        [
                            'kind' => 'paragraph',
                            'content' => [PanelView::text('Mixed '), PanelView::code('x')],
                            'tone' => null,
                        ],
                    ],
                ],
                [
                    'kind' => 'disclosure',
                    'title' => 'Raw',
                    'content' => '<data>',
                ],
                [
                    'kind' => 'group',
                    'label' => 'Nested group',
                    'content' => $nested,
                ],
            ],
            $view->blocks(),
            'Content, options, and ordering must survive fluent composition.',
        );
        self::assertFalse(
            $view->isActive(),
            'Navigation visibility must remain explicit.',
        );
    }

    public function testDefinitionNeverMutatesAnEarlierView(): void
    {
        $base = PanelView::create();
        $nested = PanelView::create()
            ->summary(' ignored', 9)
            ->toolbar('Ignored', 8)
            ->active(false);

        $view = $base
            ->summary(' hits', 3)
            ->toolbar('Hits', 3)
            ->group('Nested group', $nested);

        self::assertSame(
            [],
            $base->blocks(),
            'A reusable base view must keep no content.',
        );
        self::assertSame(
            [],
            $base->summaryMetrics(),
            'A reusable base view must keep no metrics.',
        );
        self::assertTrue(
            $base->isActive(),
            'A reusable base view must keep its own activity flag.',
        );
        self::assertCount(
            1,
            $view->summaryMetrics(),
            'Nested metrics must not reach the root summary.',
        );
        self::assertCount(
            1,
            $view->toolbarMetrics(),
            'Nested metrics must not reach the root toolbar.',
        );
        self::assertTrue(
            $view->isActive(),
            'Nested activity must not override the root flag.',
        );
    }

    public function testNumericOverviewKeysBecomeExplicitLabels(): void
    {
        $view = PanelView::create()->overview([0 => 'zero']);

        self::assertSame(
            [
                [
                    'kind' => 'overview',
                    'fields' => [['label' => '0', 'value' => PanelView::text('zero')]],
                    'compact' => false,
                ],
            ],
            $view->blocks(),
            'Numeric labels must be converted, not dropped.',
        );
    }

    public function testVariadicContentKeepsOrderAndSupportsUnpacking(): void
    {
        $badge = PanelView::badge('Ready', Tone::INFO);

        $parts = ['First ', $badge, ' last'];

        $view = PanelView::create()
            ->paragraph(...$parts)
            ->paragraph()
            ->emptyState('Empty', 'One', 'Two');

        self::assertSame(
            [
                [
                    'kind' => 'paragraph',
                    'content' => [PanelView::text('First '), $badge, PanelView::text(' last')],
                    'tone' => null,
                ],
                ['kind' => 'paragraph', 'content' => [], 'tone' => null],
                [
                    'kind' => 'emptyState',
                    'title' => 'Empty',
                    'paragraphs' => [
                        ['kind' => 'paragraph', 'content' => [PanelView::text('One')], 'tone' => null],
                        ['kind' => 'paragraph', 'content' => [PanelView::text('Two')], 'tone' => null],
                    ],
                ],
            ],
            $view->blocks(),
            'Unpacked and empty argument lists must both be accepted.',
        );
        self::assertSame(
            [['kind' => 'paragraph', 'content' => [PanelView::text('A'), PanelView::text('B')], 'tone' => null]],
            PanelView::create()->paragraph(first: 'A', second: 'B')->blocks(),
            'Named arguments must not leak keys into the content list.',
        );
    }
}
