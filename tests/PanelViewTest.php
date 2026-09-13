<?php

declare(strict_types=1);

namespace PHPForge\Debug\Tests;

use InvalidArgumentException;
use PHPForge\Debug\{ColumnStyle, PanelView, Tone};
use PHPForge\Debug\Exception\Message;
use PHPForge\Debug\Tests\Provider\{InlineScalarProvider, LinkTargetProvider};
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Unit tests for the validated shapes {@see PanelView} exports to the host renderer.
 *
 * {@see InlineScalarProvider} and {@see LinkTargetProvider} for test case data providers.
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
                [
                    'kind' => 'table',
                    'headers' => [],
                    'rows' => [],
                    'styles' => [],
                    'collapsible' => false,
                    'filterable' => false,
                ],
            ],
            $view->blocks(),
            'Every presentation hint must stay opt-in.',
        );
    }

    public function testForgedSqlAndTraceValuesSurviveTheInlineRebuild(): void
    {
        self::assertSame(
            [
                [
                    'kind' => 'paragraph',
                    'content' => [
                        ['kind' => 'text', 'value' => 'SELECT 1', 'style' => 'sql'],
                        ['kind' => 'trace', 'frames' => [['file' => '/app/x.php']]],
                    ],
                    'tone' => null,
                ],
            ],
            PanelView::create()
                ->paragraph(
                    ['kind' => 'text', 'value' => 'SELECT 1', 'style' => 'sql'],
                    ['kind' => 'trace', 'frames' => [['file' => '/app/x.php']]],
                )
                ->blocks(),
            'Both new inline values must survive the rebuild unchanged.',
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
        self::assertSame(
            ['kind' => 'link', 'label' => 'View full phpinfo', 'href' => '/debug/php-info', 'external' => false],
            PanelView::link('View full phpinfo', '/debug/php-info'),
            'Links must stay in the same browsing context by default.',
        );
        self::assertSame(
            ['kind' => 'link', 'label' => 'Docs', 'href' => 'https://example.test/d', 'external' => true],
            PanelView::link('Docs', 'https://example.test/d', true),
            'A new browsing context must be requested explicitly.',
        );
        self::assertSame(
            ['kind' => 'text', 'value' => 'SELECT 1', 'style' => 'sql'],
            PanelView::sql('SELECT 1'),
            'Statement highlighting must be requested explicitly.',
        );
        self::assertSame(
            ['kind' => 'trace', 'frames' => [['file' => '/app/x.php', 'line' => 7], ['0' => 'bare']]],
            PanelView::trace([['file' => '/app/x.php', 'line' => 7], ['bare']]),
            'Frames must travel as captured fields, with keys normalized to strings.',
        );
    }

    #[DataProviderExternal(LinkTargetProvider::class, 'accepted')]
    public function testLinkTargetsWithoutAnExecutableSchemeAreAccepted(string $href): void
    {
        self::assertSame(
            [
                [
                    'kind' => 'overview',
                    'fields' => [
                        [
                            'label' => 'Target',
                            'value' => ['kind' => 'link', 'label' => 'Open', 'href' => $href, 'external' => false],
                        ],
                    ],
                    'compact' => false,
                ],
            ],
            PanelView::create()->overview(['Target' => PanelView::link('Open', $href)])->blocks(),
            'An accepted target must travel unmodified.',
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

    /**
     * @param array{kind: 'text', value: string, style: 'plain'} $expected
     */
    #[DataProviderExternal(InlineScalarProvider::class, 'plainText')]
    public function testScalarContentBecomesPlainInlineText(mixed $value, array $expected): void
    {
        self::assertSame(
            [['kind' => 'paragraph', 'content' => [$expected], 'tone' => null]],
            PanelView::create()->paragraph($value)->blocks(),
            'Conversion must keep the literal and the plain style.',
        );
        self::assertSame(
            [
                [
                    'kind' => 'overview',
                    'fields' => [['label' => 'Field', 'value' => $expected]],
                    'compact' => false,
                ],
            ],
            PanelView::create()->overview(['Field' => $value])->blocks(),
            'Overview fields must convert identically.',
        );
    }

    public function testStringKeyedSpreadContentStaysAList(): void
    {
        $parts = ['first' => 'A', 'second' => PanelView::code('B')];

        $content = [PanelView::text('A'), PanelView::code('B')];

        self::assertSame(
            [['kind' => 'paragraph', 'content' => $content, 'tone' => null]],
            PanelView::create()->paragraph(...$parts)->blocks(),
            'String keys must not reach the paragraph content.',
        );
        self::assertSame(
            [['kind' => 'paragraph', 'content' => $content, 'tone' => Tone::WARNING]],
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

        self::assertSame(
            [
                [
                    'kind' => 'table',
                    'headers' => ['Key', 'Value', 'Hits'],
                    'rows' => [[PanelView::text('home'), PanelView::text('cached'), PanelView::text('3')]],
                    'styles' => [0 => ColumnStyle::MONOSPACE, 2 => ColumnStyle::NUMBER],
                    'collapsible' => false,
                    'filterable' => false,
                ],
            ],
            $view->blocks(),
            'Every styled column must survive, not only the first.',
        );
    }

    public function testThrowInvalidArgumentExceptionForAssociativeParagraph(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            Message::PARAGRAPH_CONTENT_INVALID->getMessage(),
        );

        PanelView::create()->emptyState('Empty', ['first' => 'A']);
    }

    #[DataProviderExternal(LinkTargetProvider::class, 'normalized')]
    public function testThrowInvalidArgumentExceptionForBrowserNormalizedLinkTarget(string $href): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            Message::LINK_TARGET_NORMALIZED->getMessage(),
        );

        PanelView::link(
            'Open',
            $href,
        );
    }

    #[DataProviderExternal(LinkTargetProvider::class, 'rejected')]
    public function testThrowInvalidArgumentExceptionForExecutableLinkTarget(string $href, string $scheme): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            Message::LINK_TARGET_SCHEME_INVALID->getMessage($scheme),
        );

        PanelView::link(
            'Open',
            $href,
        );
    }

    public function testThrowInvalidArgumentExceptionForForgedExecutableLink(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            Message::LINK_TARGET_SCHEME_INVALID->getMessage('javascript'),
        );

        PanelView::create()
            ->paragraph(
            [
                'kind' => 'link',
                'label' => 'Open',
                'href' => 'javascript:alert(1)',
                'external' => false,
            ],
        );
    }

    public function testThrowInvalidArgumentExceptionForForgedInlineValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            Message::INLINE_CONTENT_INVALID->getMessage('array'),
        );

        PanelView::create()->paragraph(['kind' => 'text']);
    }

    public function testThrowInvalidArgumentExceptionForNonArrayTraceFrame(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            Message::TRACE_FRAME_INVALID->getMessage(),
        );

        PanelView::trace(['not a frame']);
    }

    public function testThrowInvalidArgumentExceptionForNonColumnStyle(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            Message::COLUMN_STYLE_INVALID->getMessage(ColumnStyle::class),
        );

        PanelView::create()->table(['One'], [['a']], styles: [0 => 'pill']);
    }

    public function testThrowInvalidArgumentExceptionForNonInlineObject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            Message::INLINE_CONTENT_INVALID->getMessage('stdClass'),
        );

        PanelView::create()->paragraph(new stdClass());
    }

    public function testThrowInvalidArgumentExceptionForNonListRow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            Message::TABLE_ROW_WIDTH_INVALID->getMessage(),
        );

        PanelView::create()->table(['One'], ['not a row']);
    }

    public function testThrowInvalidArgumentExceptionForNonStringHeader(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            Message::TABLE_HEADER_INVALID->getMessage(),
        );

        PanelView::create()->table([1], [[1]]);
    }

    public function testThrowInvalidArgumentExceptionForRowWidthMismatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            Message::TABLE_ROW_WIDTH_INVALID->getMessage(),
        );

        PanelView::create()->table(['One'], [[]]);
    }

    public function testThrowInvalidArgumentExceptionForUnknownStyledColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            Message::COLUMN_STYLE_KEY_INVALID->getMessage(),
        );

        PanelView::create()->table(['One'], [['a']], styles: [1 => ColumnStyle::PILL]);
    }

    public function testThrowInvalidArgumentExceptionForUnparsableLinkTarget(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            Message::LINK_TARGET_UNPARSABLE->getMessage(),
        );

        PanelView::link(
            'Open',
            'http://:80',
        );
    }
}
