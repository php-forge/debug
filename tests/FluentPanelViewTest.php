<?php

declare(strict_types=1);

namespace PHPForge\Debug\Tests;

use PHPForge\Debug\{ColumnStyle, PanelView, Tone};
use PHPForge\Debug\Presenter\{
    DisclosureBlock,
    EmptyStateBlock,
    FieldEntry,
    GroupBlock,
    HeadingBlock,
    OverviewBlock,
    ParagraphBlock,
    TableBlock,
};
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

        self::assertEquals(
            [
                new OverviewBlock(
                    [
                        new FieldEntry('Driver', PanelView::text('redis')),
                        new FieldEntry('State', $badge),
                        new FieldEntry('Null', PanelView::text('null')),
                        new FieldEntry('Ratio', PanelView::text('1.5')),
                    ],
                    true,
                ),
                new HeadingBlock('Entries', true),
                new TableBlock(
                    ['Key', 'Value'],
                    [
                        [PanelView::text('home'), PanelView::text('true')],
                        [$badge, PanelView::value(1)],
                    ],
                    [0 => ColumnStyle::MONOSPACE],
                    true,
                    false,
                ),
                new ParagraphBlock(
                    [PanelView::text('State: '), $badge, PanelView::text('0')],
                    Tone::WARNING,
                ),
                new EmptyStateBlock(
                    'Empty',
                    [
                        new ParagraphBlock([PanelView::text('Plain')], null),
                        new ParagraphBlock([PanelView::text('Mixed '), PanelView::code('x')], null),
                    ],
                ),
                new DisclosureBlock('Raw', '<data>'),
                new GroupBlock('Nested group', $nested),
            ],
            $view->blocks(),
            'Content, options, and ordering must survive fluent composition.',
        );
    }

    public function testDefinitionNeverMutatesAnEarlierView(): void
    {
        $base = PanelView::create();
        $nested = PanelView::create()
            ->summary(' ignored', 9)
            ->toolbar('Ignored', 8);

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
    }

    public function testNumericOverviewKeysBecomeExplicitLabels(): void
    {
        $view = PanelView::create()->overview([0 => 'zero']);

        self::assertEquals(
            [new OverviewBlock([new FieldEntry('0', PanelView::text('zero'))], false)],
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

        self::assertEquals(
            [
                new ParagraphBlock([PanelView::text('First '), $badge, PanelView::text(' last')], null),
                new ParagraphBlock([], null),
                new EmptyStateBlock(
                    'Empty',
                    [
                        new ParagraphBlock([PanelView::text('One')], null),
                        new ParagraphBlock([PanelView::text('Two')], null),
                    ],
                ),
            ],
            $view->blocks(),
            'Unpacked and empty argument lists must both be accepted.',
        );
        self::assertEquals(
            [new ParagraphBlock([PanelView::text('A'), PanelView::text('B')], null)],
            PanelView::create()->paragraph(first: 'A', second: 'B')->blocks(),
            'Named arguments must not leak keys into the content list.',
        );
    }
}
