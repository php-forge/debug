<?php

declare(strict_types=1);

namespace PHPForge\Debug\Tests;

use InvalidArgumentException;
use PHPForge\Debug\{ColumnStyle, PanelView, Tone};
use PHPForge\Debug\Exception\PanelViewMessage;
use PHPForge\Debug\Presenter\{
    BadgeInline,
    FieldEntry,
    HeadingBlock,
    LinkInline,
    OverviewBlock,
    ParagraphBlock,
    SummaryMetric,
    TableBlock,
    TextInline,
    TextStyle,
    ToolbarMetric,
    ValueInline,
};
use PHPForge\Debug\Tests\Provider\InlineScalarProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Unit tests for the value objects {@see PanelView} exports to the host renderer.
 *
 * {@see InlineScalarProvider} for test case data providers.
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
        self::assertEquals(
            [
                new HeadingBlock('Title', false),
                new OverviewBlock([], false),
                new TableBlock([], [], [], false, false),
            ],
            $view->blocks(),
            'Every presentation hint must stay opt-in.',
        );
    }

    public function testInlineFactoriesDescribeContentStyleAndTone(): void
    {
        self::assertEquals(
            new TextInline('<value>', TextStyle::PLAIN),
            PanelView::text('<value>'),
            'Text must stay unescaped and unstyled.',
        );
        self::assertEquals(
            new TextInline('v', TextStyle::STRONG),
            PanelView::strong('v'),
            'Emphasis must remain semantic.',
        );
        self::assertEquals(
            new TextInline('v', TextStyle::CODE),
            PanelView::code('v'),
            'Source code must remain semantic.',
        );
        self::assertEquals(
            new TextInline('v', TextStyle::PREVIEW),
            PanelView::preview('v'),
            'Clamping must be requested explicitly.',
        );
        self::assertEquals(
            new TextInline('SELECT 1', TextStyle::SQL),
            PanelView::sql('SELECT 1'),
            'Statement highlighting must be requested explicitly.',
        );
        self::assertEquals(
            new BadgeInline('shared', Tone::INFO),
            PanelView::badge('shared', Tone::INFO),
            'Badges must retain their text and tone.',
        );
        self::assertEquals(
            new BadgeInline('b', Tone::MUTED),
            PanelView::badge('b'),
            'Badges must default to a muted tone.',
        );
        self::assertEquals(
            new ValueInline(['id' => 1], false),
            PanelView::value(['id' => 1]),
            'Diagnostic values must not be flattened.',
        );
        self::assertEquals(
            new ValueInline(null, true),
            PanelView::value(null, typeOnly: true),
            'Type-only presentation must be explicit.',
        );
        self::assertEquals(
            new LinkInline('View full phpinfo', '/debug/php-info', false),
            PanelView::link('View full phpinfo', '/debug/php-info'),
            'Links must stay in the same browsing context by default.',
        );
        self::assertEquals(
            new LinkInline('Docs', 'https://example.test/d', true),
            PanelView::link('Docs', 'https://example.test/d', true),
            'A new browsing context must be requested explicitly.',
        );
        self::assertSame(
            [['file' => '/app/x.php', 'line' => 7], ['bare']],
            PanelView::trace([['file' => '/app/x.php', 'line' => 7], ['bare']])->frames,
            'Frames must travel as captured fields, with keys normalized to strings.',
        );
    }

    public function testInlineValuesTravelThroughContentWithoutRebuilding(): void
    {
        $sql = PanelView::sql('SELECT 1');
        $trace = PanelView::trace([['file' => '/app/x.php']]);

        $blocks = PanelView::create()
            ->paragraph($sql, $trace)
            ->blocks();

        $block = $blocks[0] ?? null;

        self::assertInstanceOf(
            ParagraphBlock::class,
            $block,
            'Content must reach the host as a paragraph.',
        );

        $content = $block->content;

        self::assertSame(
            $sql,
            $content[0] ?? null,
            'The statement must travel as the same object.',
        );
        self::assertSame(
            $trace,
            $content[1] ?? null,
            'The trace must travel as the same object.',
        );
    }

    public function testMetricsAndFieldsCarryTheirLabelsAndValues(): void
    {
        $view = PanelView::create()
            ->summary(' prop', 1)
            ->summary('', 2.5, emphasized: false)
            ->toolbar('Hits', 3)
            ->overview(['Driver' => 'redis']);

        self::assertEquals(
            [
                new SummaryMetric(' prop', new TextInline('1', TextStyle::STRONG)),
                new SummaryMetric('', new TextInline('2.5', TextStyle::PLAIN)),
            ],
            $view->summaryMetrics(),
            'Emphasis must travel as the inline text style.',
        );
        self::assertEquals(
            [new ToolbarMetric('Hits', '3')],
            $view->toolbarMetrics(),
            'Toolbar metrics must stay separate from the summary.',
        );
        self::assertEquals(
            [new OverviewBlock([new FieldEntry('Driver', new TextInline('redis', TextStyle::PLAIN))], false)],
            $view->blocks(),
            'Overview fields must carry their label beside the inline value.',
        );
    }

    #[DataProviderExternal(InlineScalarProvider::class, 'plainText')]
    public function testScalarContentBecomesPlainInlineText(mixed $value, TextInline $expected): void
    {
        self::assertEquals(
            [new ParagraphBlock([$expected], null)],
            PanelView::create()->paragraph($value)->blocks(),
            'Conversion must keep the literal and the plain style.',
        );
        self::assertEquals(
            [new OverviewBlock([new FieldEntry('Field', $expected)], false)],
            PanelView::create()->overview(['Field' => $value])->blocks(),
            'Overview fields must convert identically.',
        );
    }

    public function testStringKeyedSpreadContentStaysAList(): void
    {
        $parts = ['first' => 'A', 'second' => PanelView::code('B')];

        $content = [new TextInline('A', TextStyle::PLAIN), new TextInline('B', TextStyle::CODE)];

        self::assertEquals(
            [new ParagraphBlock($content, null)],
            PanelView::create()->paragraph(...$parts)->blocks(),
            'String keys must not reach the paragraph content.',
        );
        self::assertEquals(
            [new ParagraphBlock($content, Tone::WARNING)],
            PanelView::create()->callout(Tone::WARNING, ...$parts)->blocks(),
            'String keys must not reach the callout content.',
        );
    }

    public function testTableKeepsEveryStyledColumnUnderItsIndex(): void
    {
        $view = PanelView::create()
            ->table(
                ['Key', 'Value', 'Hits'],
                [['home', 'cached', 3]],
                styles: [0 => ColumnStyle::MONOSPACE, 2 => ColumnStyle::NUMBER],
            );

        self::assertEquals(
            [
                new TableBlock(
                    ['Key', 'Value', 'Hits'],
                    [
                        [
                            new TextInline('home', TextStyle::PLAIN),
                            new TextInline('cached', TextStyle::PLAIN),
                            new TextInline('3', TextStyle::PLAIN),
                        ],
                    ],
                    [0 => ColumnStyle::MONOSPACE, 2 => ColumnStyle::NUMBER],
                    false,
                    false,
                ),
            ],
            $view->blocks(),
            'Every styled column must survive, not only the first.',
        );
    }

    public function testThrowInvalidArgumentExceptionForArrayContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            PanelViewMessage::INLINE_CONTENT_INVALID->getMessage('array'),
        );

        PanelView::create()->paragraph(['kind' => 'text']);
    }

    public function testThrowInvalidArgumentExceptionForAssociativeParagraph(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            PanelViewMessage::PARAGRAPH_CONTENT_INVALID->getMessage(),
        );

        PanelView::create()->emptyState('Empty', ['first' => 'A']);
    }

    public function testThrowInvalidArgumentExceptionForAssociativeRow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            PanelViewMessage::TABLE_ROW_WIDTH_INVALID->getMessage(),
        );

        PanelView::create()->table(['One'], [['first' => 'a']]);
    }

    public function testThrowInvalidArgumentExceptionForNonInlineObject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            PanelViewMessage::INLINE_CONTENT_INVALID->getMessage('stdClass'),
        );

        PanelView::create()->paragraph(new stdClass());
    }

    public function testThrowInvalidArgumentExceptionForNonListRow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            PanelViewMessage::TABLE_ROW_WIDTH_INVALID->getMessage(),
        );

        PanelView::create()->table(['One'], ['not a row']);
    }
}
