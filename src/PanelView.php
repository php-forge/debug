<?php

declare(strict_types=1);

namespace PHPForge\Debug;

use InvalidArgumentException;
use JsonSerializable;
use PHPForge\Debug\Exception\PanelViewMessage;

use function array_key_exists;
use function count;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function parse_url;
use function strpbrk;
use function strtolower;
use function trim;

/**
 * Builds immutable panel descriptions without exposing the host's markup or styles.
 *
 * Every method validates its arguments and returns a new view. Ordinary overview and table values need no wrappers:
 * scalars become text, with literal text for `null`, `true`, and `false`. Styled text, badges, and structured values
 * come from the static factories and are accepted wherever a scalar is accepted.
 *
 * The host reads the result through {@see self::summaryMetrics()}, {@see self::toolbarMetrics()}, {@see self::blocks()},
 * and {@see self::isActive()}. Nothing outside this class can build or alter a shape.
 *
 * Inline values.
 *
 * @phpstan-type BadgeInline array{kind: 'badge', label: string, tone: Tone}
 * @phpstan-type LinkInline array{kind: 'link', label: string, href: string, external: bool}
 * @phpstan-type TextInline array{kind: 'text', value: string, style: 'code'|'plain'|'preview'|'sql'|'strong'}
 * @phpstan-type TraceInline array{kind: 'trace', frames: list<array<string, mixed>>}
 * @phpstan-type ValueInline array{kind: 'value', value: mixed, typeOnly: bool}
 * @phpstan-type Inline BadgeInline|LinkInline|TextInline|TraceInline|ValueInline
 * @phpstan-type Pair array{label: string, value: Inline}
 * @phpstan-type TextPair array{label: string, value: TextInline}
 *
 * Entries of a composite block.
 *
 * @phpstan-type FactEntry array{kind: 'fact', label: string, value: string}
 * @phpstan-type PackageEntry array{kind: 'package', name: string, version: string}
 * @phpstan-type PillEntry array{kind: 'pill', label: string, state: string, enabled: bool}
 * @phpstan-type ReadoutEntry array{kind: 'readout', label: string, value: string, caption: string}
 *
 * Content blocks.
 *
 * @phpstan-type DisclosureBlock array{kind: 'disclosure', title: string, content: string}
 * @phpstan-type EmptyStateBlock array{kind: 'emptyState', title: string, paragraphs: list<ParagraphBlock>}
 * @phpstan-type FactsBlock array{kind: 'facts', facts: list<FactEntry>}
 * @phpstan-type GroupBlock array{kind: 'group', label: string, content: PanelView}
 * @phpstan-type HeadingBlock array{kind: 'heading', title: string, section: bool}
 * @phpstan-type ManifestBlock array{kind: 'manifest', label: string, packages: list<PackageEntry>}
 * @phpstan-type OverviewBlock array{kind: 'overview', fields: list<Pair>, compact: bool}
 * @phpstan-type ParagraphBlock array{kind: 'paragraph', content: list<Inline>, tone: Tone|null}
 * @phpstan-type PillsBlock array{kind: 'pills', pills: list<PillEntry>}
 * @phpstan-type ReadoutsBlock array{kind: 'readouts', readouts: list<ReadoutEntry>}
 * @phpstan-type SectionBlock array{kind: 'section', mark: string, title: string, count: int|null, content: PanelView}
 * @phpstan-type TableBlock array{
 *   kind: 'table',
 *   headers: list<string>,
 *   rows: list<list<Inline>>,
 *   styles: array<int, ColumnStyle>,
 *   collapsible: bool,
 *   filterable: bool
 * }
 * @phpstan-type Block DisclosureBlock|EmptyStateBlock|FactsBlock|GroupBlock|HeadingBlock|ManifestBlock|OverviewBlock
 *   |ParagraphBlock|PillsBlock|ReadoutsBlock|SectionBlock|TableBlock
 */
final readonly class PanelView implements JsonSerializable
{
    /**
     * @param list<Pair> $summary Summary metrics in display order.
     * @param list<Block> $blocks Panel content blocks in display order.
     * @param list<TextPair> $toolbar Toolbar metrics in display order, separate from the summary.
     * @param bool $active Whether the panel is marked active for host navigation.
     */
    private function __construct(
        private array $summary,
        private array $blocks,
        private array $toolbar,
        private bool $active,
    ) {}

    /**
     * Changes the activity flag without altering content or metrics.
     *
     * @param bool $active Whether the panel is marked active for host navigation.
     *
     * @return self New view with the requested activity flag.
     */
    public function active(bool $active): self
    {
        return new self($this->summary, $this->blocks, $this->toolbar, $active);
    }

    /**
     * Creates an inline status label with a semantic tone.
     *
     * @param string $label Badge text; the host escapes it.
     * @param Tone $tone Semantic tone interpreted by the host frontend.
     *
     * @return BadgeInline Inline badge accepted by every content method.
     */
    public static function badge(string $label, Tone $tone = Tone::MUTED): array
    {
        return [
            'kind' => 'badge',
            'label' => $label,
            'tone' => $tone,
        ];
    }

    /**
     * Returns the content blocks for the host renderer.
     *
     * @return list<Block> Validated content blocks in display order.
     */
    public function blocks(): array
    {
        return $this->blocks;
    }

    /**
     * Appends a paragraph with a semantic callout tone.
     *
     * @param Tone $tone Callout tone interpreted by the host frontend.
     * @param mixed ...$content Ordered factory-produced inline values, scalars, or `null`.
     *
     * @throws InvalidArgumentException if an argument is not an accepted inline value.
     *
     * @return self New view with the callout appended.
     */
    public function callout(Tone $tone, mixed ...$content): self
    {
        return $this->append(self::paragraphBlock($content, $tone));
    }

    /**
     * Creates inline text presented as source code.
     *
     * @param string $value Text content; the host escapes it.
     *
     * @return TextInline Inline text accepted by every content method.
     */
    public static function code(string $value): array
    {
        return [
            'kind' => 'text',
            'value' => $value,
            'style' => 'code',
        ];
    }

    /**
     * Creates an active view without content or metrics as the starting point for fluent composition.
     *
     * @return self Empty active view.
     */
    public static function create(): self
    {
        return new self([], [], [], true);
    }

    /**
     * Appends a titled plain-text disclosure.
     *
     * @param string $title Disclosure label.
     * @param string $content Plain-text payload, preserved without formatting.
     *
     * @return self New view with the disclosure appended.
     */
    public function disclosure(string $title, string $content): self
    {
        return $this->append(['kind' => 'disclosure', 'title' => $title, 'content' => $content]);
    }

    /**
     * Appends an explicit empty state whose arguments each describe one paragraph.
     *
     * @param string $title Empty-state heading.
     * @param mixed ...$paragraphs Ordered explanations, each one paragraph: a scalar, a factory-produced inline value,
     * or a list of inline values and scalars.
     *
     * @throws InvalidArgumentException if a paragraph is neither an inline value nor a list of them.
     *
     * @return self New view with the empty state appended.
     */
    public function emptyState(string $title, mixed ...$paragraphs): self
    {
        $content = [];

        foreach ($paragraphs as $paragraph) {
            $content[] = self::paragraphOf($paragraph);
        }

        return $this->append(['kind' => 'emptyState', 'title' => $title, 'paragraphs' => $content]);
    }

    /**
     * Creates one compact label and value pair of a fact strip.
     *
     * @param string $label Name of the fact; the host escapes it.
     * @param string $value Recorded value; the host escapes it.
     *
     * @return FactEntry Fact entry accepted by {@see self::facts()}.
     */
    public static function fact(string $label, string $value): array
    {
        return [
            'kind' => 'fact',
            'label' => $label,
            'value' => $value,
        ];
    }

    /**
     * Appends a compact strip of label and value pairs.
     *
     * @param FactEntry ...$facts Entries produced by {@see self::fact()}.
     *
     * @return self New view with the fact strip appended.
     */
    public function facts(array ...$facts): self
    {
        return $this->append(['kind' => 'facts', 'facts' => array_values($facts)]);
    }

    /**
     * Groups only the child's content; metrics and activity belong to the root view.
     *
     * @param string $label Accessible group label.
     * @param self $content Child view contributing only its ordered content blocks.
     *
     * @return self New view with the group appended and the current metrics and activity preserved.
     */
    public function group(string $label, self $content): self
    {
        return $this->append(['kind' => 'group', 'label' => $label, 'content' => $content]);
    }

    /**
     * Appends an ordinary or section-level heading.
     *
     * @param string $title Heading text.
     * @param bool $section Whether to request section-heading presentation from the host.
     *
     * @return self New view with the heading appended.
     */
    public function heading(string $title, bool $section = false): self
    {
        return $this->append(['kind' => 'heading', 'title' => $title, 'section' => $section]);
    }

    /**
     * Reports whether the panel is marked active for host navigation.
     *
     * @return bool Activity flag.
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Returns the complete description for inspection, fixtures, and diffing.
     *
     * @return array{summary: list<Pair>, blocks: list<Block>, toolbar: list<TextPair>, active: bool} Description.
     */
    public function jsonSerialize(): array
    {
        return [
            'summary' => $this->summary,
            'blocks' => $this->blocks,
            'toolbar' => $this->toolbar,
            'active' => $this->active,
        ];
    }

    /**
     * Creates an inline navigation link the host renders as an anchor.
     *
     * Only relative targets and the `http`, `https`, and `mailto` schemes are accepted, so a captured value can never
     * turn into an executable target.
     *
     * @param string $label Link text; the host escapes it.
     * @param string $href Relative target, or an absolute `http`, `https`, or `mailto` URL.
     * @param bool $external Whether the host opens the target in a new browsing context.
     *
     * @throws InvalidArgumentException if the target declares a scheme the host must not follow.
     *
     * @return LinkInline Inline link accepted by every content method.
     */
    public static function link(string $label, string $href, bool $external = false): array
    {
        return [
            'kind' => 'link',
            'label' => $label,
            'href' => self::target($href),
            'external' => $external,
        ];
    }

    /**
     * Appends a vendor-grouped package manifest.
     *
     * @param string $label Vendor prefix the packages share, shown as the group heading.
     * @param PackageEntry ...$packages Entries produced by {@see self::package()}.
     *
     * @return self New view with the manifest appended.
     */
    public function manifest(string $label, array ...$packages): self
    {
        return $this->append(['kind' => 'manifest', 'label' => $label, 'packages' => array_values($packages)]);
    }

    /**
     * Appends labeled overview fields, using array keys as labels.
     *
     * Labels are unique because they are array keys; repeat a value under a different label instead.
     *
     * @param array<array-key, mixed> $values Labeled values in display order, each an inline value or a scalar.
     * @param bool $compact Whether to request compact presentation from the host.
     *
     * @throws InvalidArgumentException if a value is not an accepted inline value.
     *
     * @return self New view with the overview appended.
     */
    public function overview(array $values, bool $compact = false): self
    {
        $fields = [];

        foreach ($values as $label => $value) {
            $fields[] = ['label' => (string) $label, 'value' => self::inline($value)];
        }

        return $this->append(['kind' => 'overview', 'fields' => $fields, 'compact' => $compact]);
    }

    /**
     * Creates one package entry of a manifest.
     *
     * @param string $name Package name; the host escapes it.
     * @param string $version Resolved version; the host escapes it.
     *
     * @return PackageEntry Package entry accepted by {@see self::manifest()}.
     */
    public static function package(string $name, string $version): array
    {
        return [
            'kind' => 'package',
            'name' => $name,
            'version' => $version,
        ];
    }

    /**
     * Appends an ordinary paragraph, converting plain values to text.
     *
     * @param mixed ...$content Ordered factory-produced inline values, scalars, or `null`.
     *
     * @throws InvalidArgumentException if an argument is not an accepted inline value.
     *
     * @return self New view with the paragraph appended.
     */
    public function paragraph(mixed ...$content): self
    {
        return $this->append(self::paragraphBlock($content, null));
    }

    /**
     * Creates one status pill.
     *
     * @param string $label Subject of the pill, such as an extension name; the host escapes it.
     * @param string $state Short state text shown after the label, such as `on` or a version.
     * @param bool $enabled Whether the subject is active, selecting the on or off presentation.
     *
     * @return PillEntry Pill entry accepted by {@see self::pills()}.
     */
    public static function pill(string $label, string $state, bool $enabled): array
    {
        return [
            'kind' => 'pill',
            'label' => $label,
            'state' => $state,
            'enabled' => $enabled,
        ];
    }

    /**
     * Appends a strip of status pills.
     *
     * @param PillEntry ...$pills Entries produced by {@see self::pill()}.
     *
     * @return self New view with the pill strip appended.
     */
    public function pills(array ...$pills): self
    {
        return $this->append(['kind' => 'pills', 'pills' => array_values($pills)]);
    }

    /**
     * Creates inline text the host may clamp behind its standard expand control.
     *
     * @param string $value Text content; the host escapes it.
     *
     * @return TextInline Inline text accepted by every content method.
     */
    public static function preview(string $value): array
    {
        return [
            'kind' => 'text',
            'value' => $value,
            'style' => 'preview',
        ];
    }

    /**
     * Creates one headline readout card.
     *
     * @param string $label Metric name shown above the value; the host escapes it.
     * @param string $value Headline value; the host escapes it.
     * @param string $caption Qualifier shown under the value, or `''` to omit it.
     *
     * @return ReadoutEntry Readout entry accepted by {@see self::readouts()}.
     */
    public static function readout(string $label, string $value, string $caption = ''): array
    {
        return [
            'kind' => 'readout',
            'label' => $label,
            'value' => $value,
            'caption' => $caption,
        ];
    }

    /**
     * Appends a row of headline readout cards.
     *
     * @param ReadoutEntry ...$readouts Entries produced by {@see self::readout()}.
     *
     * @return self New view with the readout row appended.
     */
    public function readouts(array ...$readouts): self
    {
        return $this->append(['kind' => 'readouts', 'readouts' => array_values($readouts)]);
    }

    /**
     * Appends a titled section wrapping its own content.
     *
     * @param string $mark Short glyph shown before the title, such as `::` or `//`.
     * @param string $title Section title announced as its accessible name.
     * @param self $content Blocks the section wraps.
     * @param int|null $count Optional tally shown at the end of the title, or `null` to omit it.
     *
     * @return self New view with the section appended.
     */
    public function section(string $mark, string $title, self $content, int|null $count = null): self
    {
        return $this->append(
            [
                'kind' => 'section',
                'mark' => $mark,
                'title' => $title,
                'count' => $count,
                'content' => $content,
            ],
        );
    }

    /**
     * Creates inline text the host highlights as an SQL statement.
     *
     * @param string $value Statement text; the host escapes it.
     *
     * @return TextInline Inline text accepted by every content method.
     */
    public static function sql(string $value): array
    {
        return [
            'kind' => 'text',
            'value' => $value,
            'style' => 'sql',
        ];
    }

    /**
     * Creates emphasized inline text.
     *
     * @param string $value Text content; the host escapes it.
     *
     * @return TextInline Inline text accepted by every content method.
     */
    public static function strong(string $value): array
    {
        return [
            'kind' => 'text',
            'value' => $value,
            'style' => 'strong',
        ];
    }

    /**
     * Adds a metric whose label follows its value. Include any intended spacing in the label.
     *
     * @param string $label Suffix displayed after the metric value.
     * @param string|int|float $value Metric value converted to text.
     * @param bool $emphasized Whether to request emphasis for the value.
     *
     * @return self New view with the summary metric appended.
     */
    public function summary(string $label, string|int|float $value, bool $emphasized = true): self
    {
        $metric = [
            'label' => $label,
            'value' => $emphasized ? self::strong((string) $value) : self::text((string) $value),
        ];

        return new self([...$this->summary, $metric], $this->blocks, $this->toolbar, $this->active);
    }

    /**
     * Returns the summary metrics for the host renderer.
     *
     * @return list<Pair> Validated summary metrics in display order.
     */
    public function summaryMetrics(): array
    {
        return $this->summary;
    }

    /**
     * Appends a table whose columns share a semantic style.
     *
     * @param array<array-key, mixed> $headers Plain-text column headings in display order.
     * @param array<array-key, mixed> $rows Rows of inline or plain values in display order.
     * @param bool $collapsible Whether the host may collapse the table.
     * @param array<array-key, mixed> $styles Optional {@see ColumnStyle} cases keyed by column index.
     * @param bool $filterable Whether to request the host's in-place row filter for the table.
     *
     * @throws InvalidArgumentException if a header, row width, cell, or column style is invalid.
     *
     * @return self New view with the table appended.
     */
    public function table(
        array $headers,
        array $rows,
        bool $collapsible = false,
        array $styles = [],
        bool $filterable = false,
    ): self {
        $columns = self::headers($headers);

        return $this->append(
            [
                'kind' => 'table',
                'headers' => $columns,
                'rows' => self::rows($rows, count($columns)),
                'styles' => self::styles($styles, count($columns)),
                'collapsible' => $collapsible,
                'filterable' => $filterable,
            ],
        );
    }


    /**
     * Creates plain inline text.
     *
     * @param string $value Text content; the host escapes it.
     *
     * @return TextInline Inline text accepted by every content method.
     */
    public static function text(string $value): array
    {
        return [
            'kind' => 'text',
            'value' => $value,
            'style' => 'plain',
        ];
    }

    /**
     * Adds a toolbar metric independently of the panel summary.
     *
     * @param string $label Toolbar metric label.
     * @param string|int|float $value Metric value converted to text.
     *
     * @return self New view with the toolbar metric appended.
     */
    public function toolbar(string $label, string|int|float $value): self
    {
        $metric = [
            'label' => $label,
            'value' => self::text((string) $value),
        ];

        return new self($this->summary, $this->blocks, [...$this->toolbar, $metric], $this->active);
    }

    /**
     * Returns the toolbar metrics for the host renderer.
     *
     * @return list<TextPair> Validated toolbar metrics in display order.
     */
    public function toolbarMetrics(): array
    {
        return $this->toolbar;
    }

    /**
     * Creates the captured source frames of a call site, which the host renders through its own frame renderer.
     *
     * Frames travel as captured data, never as markup, so the host keeps ownership of the source-link format.
     *
     * @param array<array-key, mixed> $frames Captured frames in call order, each an array of frame fields.
     *
     * @throws InvalidArgumentException if a frame is not an array of fields.
     *
     * @return TraceInline Inline trace accepted by every content method.
     */
    public static function trace(array $frames): array
    {
        $captured = [];

        foreach ($frames as $frame) {
            if (is_array($frame) === false) {
                throw new InvalidArgumentException(
                    PanelViewMessage::TRACE_FRAME_INVALID->getMessage(),
                );
            }

            $fields = [];

            foreach ($frame as $key => $value) {
                $fields[(string) $key] = $value;
            }

            $captured[] = $fields;
        }

        return [
            'kind' => 'trace',
            'frames' => $captured,
        ];
    }

    /**
     * Creates a captured diagnostic value the host formats and escapes.
     *
     * @param mixed $value Diagnostic value, preserved without conversion.
     * @param bool $typeOnly Whether to show only the value's type instead of its contents.
     *
     * @return ValueInline Inline value accepted by every content method.
     */
    public static function value(mixed $value, bool $typeOnly = false): array
    {
        return [
            'kind' => 'value',
            'value' => $value,
            'typeOnly' => $typeOnly,
        ];
    }

    /**
     * Appends a content block while retaining metrics and activity.
     *
     * @param Block $block Content block placed after the existing blocks.
     *
     * @return self New view containing the additional block.
     */
    private function append(array $block): self
    {
        return new self($this->summary, [...$this->blocks, $block], $this->toolbar, $this->active);
    }

    /**
     * Rejects column headings that are not plain strings.
     *
     * @param array<array-key, mixed> $headers Column headings to validate.
     *
     * @throws InvalidArgumentException if a heading is not a string.
     *
     * @return list<string> Validated column headings in display order.
     */
    private static function headers(array $headers): array
    {
        $columns = [];

        foreach ($headers as $header) {
            if (is_string($header) === false) {
                throw new InvalidArgumentException(
                    PanelViewMessage::TABLE_HEADER_INVALID->getMessage(),
                );
            }

            $columns[] = $header;
        }

        return $columns;
    }

    /**
     * Retains inline values and converts scalars and `null` to text.
     *
     * @param mixed $value Inline value or plain value to normalize.
     *
     * @throws InvalidArgumentException if the value is not an accepted inline input.
     *
     * @return Inline Normalized inline value.
     */
    private static function inline(mixed $value): array
    {
        return match (true) {
            is_array($value) => self::inlineShape($value),
            $value === null => self::text('null'),
            $value === true => self::text('true'),
            $value === false => self::text('false'),
            is_string($value) => self::text($value),
            is_int($value), is_float($value) => self::text((string) $value),
            default => throw self::unsupportedInline($value),
        };
    }

    /**
     * Rebuilds a factory-produced inline value, rejecting every other array.
     *
     * @param array<array-key, mixed> $value Candidate inline value.
     *
     * @throws InvalidArgumentException if the array was not produced by an inline factory.
     *
     * @return Inline Validated inline value.
     */
    private static function inlineShape(array $value): array
    {
        $kind = $value['kind'] ?? null;
        $label = $value['label'] ?? null;
        $tone = $value['tone'] ?? null;

        if ($kind === 'badge' && is_string($label) && $tone instanceof Tone) {
            return ['kind' => 'badge', 'label' => $label, 'tone' => $tone];
        }

        $href = $value['href'] ?? null;
        $external = $value['external'] ?? null;

        if ($kind === 'link' && is_string($label) && is_string($href) && is_bool($external)) {
            return [
                'kind' => 'link',
                'label' => $label,
                'href' => self::target($href),
                'external' => $external,
            ];
        }

        $text = $value['value'] ?? null;
        $style = $value['style'] ?? null;

        if (
            $kind === 'text'
            && is_string($text)
            && in_array($style, ['code', 'plain', 'preview', 'sql', 'strong'], true)
        ) {
            return [
                'kind' => 'text',
                'value' => $text,
                'style' => $style,
            ];
        }

        $frames = $value['frames'] ?? null;

        if ($kind === 'trace' && is_array($frames)) {
            return self::trace($frames);
        }

        $typeOnly = $value['typeOnly'] ?? null;

        if ($kind === 'value' && array_key_exists('value', $value) && is_bool($typeOnly)) {
            return [
                'kind' => 'value',
                'value' => $value['value'],
                'typeOnly' => $typeOnly,
            ];
        }

        throw self::unsupportedInline($value);
    }

    /**
     * Builds a paragraph block from ordered inline inputs.
     *
     * @param array<array-key, mixed> $content Inline values or plain values in display order.
     * @param Tone|null $tone Callout tone, or `null` for an ordinary paragraph.
     *
     * @throws InvalidArgumentException if an item is not an accepted inline value.
     *
     * @return ParagraphBlock Validated paragraph block.
     */
    private static function paragraphBlock(array $content, Tone|null $tone): array
    {
        $inline = [];

        foreach ($content as $value) {
            $inline[] = self::inline($value);
        }

        return [
            'kind' => 'paragraph',
            'content' => $inline,
            'tone' => $tone,
        ];
    }

    /**
     * Builds one paragraph from a string, a single inline value, or a list of inline values.
     *
     * @param mixed $paragraph Paragraph description.
     *
     * @throws InvalidArgumentException if the description is neither an inline value nor a list of them.
     *
     * @return ParagraphBlock Validated paragraph block.
     */
    private static function paragraphOf(mixed $paragraph): array
    {
        if (is_array($paragraph) === false || array_key_exists('kind', $paragraph)) {
            return self::paragraphBlock([$paragraph], null);
        }

        if (array_is_list($paragraph) === false) {
            throw new InvalidArgumentException(
                PanelViewMessage::PARAGRAPH_CONTENT_INVALID->getMessage(),
            );
        }

        return self::paragraphBlock($paragraph, null);
    }

    /**
     * Rejects rows that are not lists of the table's width.
     *
     * @param array<array-key, mixed> $rows Rows to validate.
     * @param int $columns Number of declared columns.
     *
     * @throws InvalidArgumentException if a row is not a list or its width differs from the headers.
     *
     * @return list<list<Inline>> Validated rows in display order.
     */
    private static function rows(array $rows, int $columns): array
    {
        $result = [];

        foreach ($rows as $row) {
            if (is_array($row) === false || array_is_list($row) === false || count($row) !== $columns) {
                throw new InvalidArgumentException(
                    PanelViewMessage::TABLE_ROW_WIDTH_INVALID->getMessage(),
                );
            }

            $cells = [];

            foreach ($row as $value) {
                $cells[] = self::inline($value);
            }

            $result[] = $cells;
        }

        return $result;
    }

    /**
     * Rejects column styles that do not address a declared column.
     *
     * @param array<array-key, mixed> $styles Column styles keyed by column index.
     * @param int $columns Number of declared columns.
     *
     * @throws InvalidArgumentException if a key is not an existing column index or a value is not a style.
     *
     * @return array<int, ColumnStyle> Validated column styles.
     */
    private static function styles(array $styles, int $columns): array
    {
        $result = [];

        foreach ($styles as $column => $style) {
            if (is_int($column) === false || $column < 0 || $column >= $columns) {
                throw new InvalidArgumentException(
                    PanelViewMessage::COLUMN_STYLE_KEY_INVALID->getMessage(),
                );
            }

            if ($style instanceof ColumnStyle === false) {
                throw new InvalidArgumentException(
                    PanelViewMessage::COLUMN_STYLE_INVALID->getMessage(ColumnStyle::class),
                );
            }

            $result[$column] = $style;
        }

        return $result;
    }

    /**
     * Rejects a link target the host must not follow.
     *
     * Characters a browser strips while resolving a URL are rejected first, because they move the scheme: a tab, a line
     * break, or a surrounding space turns `" javascript:alert(1)"` into an executable target after the check.
     *
     * @param string $href Candidate target.
     *
     * @throws InvalidArgumentException if the target carries characters a browser strips, cannot be parsed, or declares
     * a scheme other than `http`, `https`, or `mailto`.
     *
     * @return string Unmodified target.
     */
    private static function target(string $href): string
    {
        if (strpbrk($href, "\t\n\r") !== false || trim($href, "\x00..\x20") !== $href) {
            throw new InvalidArgumentException(
                PanelViewMessage::LINK_TARGET_NORMALIZED->getMessage(),
            );
        }

        $parts = parse_url($href);

        if ($parts === false) {
            throw new InvalidArgumentException(
                PanelViewMessage::LINK_TARGET_UNPARSABLE->getMessage(),
            );
        }

        $scheme = $parts['scheme'] ?? null;

        if ($scheme !== null && in_array(strtolower($scheme), ['http', 'https', 'mailto'], true) === false) {
            throw new InvalidArgumentException(
                PanelViewMessage::LINK_TARGET_SCHEME_INVALID->getMessage($scheme),
            );
        }

        return $href;
    }

    /**
     * Builds the rejection naming the value that cannot be displayed inline.
     *
     * @param mixed $value Rejected value.
     *
     * @return InvalidArgumentException Rejection carrying the received type.
     */
    private static function unsupportedInline(mixed $value): InvalidArgumentException
    {
        return new InvalidArgumentException(
            PanelViewMessage::INLINE_CONTENT_INVALID->getMessage(get_debug_type($value)),
        );
    }
}
