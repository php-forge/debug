<?php

declare(strict_types=1);

namespace PHPForge\Debug;

use InvalidArgumentException;
use JsonSerializable;

use function count;
use function is_array;
use function is_float;
use function is_int;
use function is_string;

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
 * @phpstan-type BadgeInline array{kind: 'badge', label: string, tone: Tone}
 * @phpstan-type TextInline array{kind: 'text', value: string, style: 'code'|'plain'|'preview'|'strong'}
 * @phpstan-type ValueInline array{kind: 'value', value: mixed, typeOnly: bool}
 * @phpstan-type Inline BadgeInline|TextInline|ValueInline
 * @phpstan-type Pair array{label: string, value: Inline}
 * @phpstan-type TextPair array{label: string, value: TextInline}
 * @phpstan-type EmptyStateBlock array{kind: 'emptyState', title: string, paragraphs: list<ParagraphBlock>}
 * @phpstan-type GroupBlock array{kind: 'group', label: string, content: PanelView}
 * @phpstan-type OverviewBlock array{kind: 'overview', fields: list<Pair>, compact: bool}
 * @phpstan-type ParagraphBlock array{kind: 'paragraph', content: list<Inline>, tone: Tone|null}
 * @phpstan-type TableBlock array{
 *   kind: 'table',
 *   headers: list<string>,
 *   rows: list<list<Inline>>,
 *   styles: array<int, ColumnStyle>,
 *   collapsible: bool
 * }
 * @phpstan-type Block array{
 *   kind: 'disclosure',
 *   title: string,
 *   content: string
 * }|array{kind: 'heading', title: string, section: bool}|EmptyStateBlock|GroupBlock|OverviewBlock|ParagraphBlock|TableBlock
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
        return new self(
            $this->summary,
            $this->blocks,
            $this->toolbar,
            $active,
        );
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
     * @throws InvalidArgumentException If an argument is not an accepted inline value.
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
        return new self(
            [],
            [],
            [],
            true,
        );
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
     * @throws InvalidArgumentException If a paragraph is neither an inline value nor a list of them.
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
     * Appends labeled overview fields, using array keys as labels.
     *
     * Labels are unique because they are array keys; repeat a value under a different label instead.
     *
     * @param array<array-key, mixed> $values Labeled values in display order, each an inline value or a scalar.
     * @param bool $compact Whether to request compact presentation from the host.
     *
     * @throws InvalidArgumentException If a value is not an accepted inline value.
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
     * Appends an ordinary paragraph, converting plain values to text.
     *
     * @param mixed ...$content Ordered factory-produced inline values, scalars, or `null`.
     *
     * @throws InvalidArgumentException If an argument is not an accepted inline value.
     *
     * @return self New view with the paragraph appended.
     */
    public function paragraph(mixed ...$content): self
    {
        return $this->append(self::paragraphBlock($content, null));
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

        return new self(
            [...$this->summary, $metric],
            $this->blocks,
            $this->toolbar,
            $this->active,
        );
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
     *
     * @throws InvalidArgumentException If a header, row width, cell, or column style is invalid.
     *
     * @return self New view with the table appended.
     */
    public function table(array $headers, array $rows, bool $collapsible = false, array $styles = []): self
    {
        $columns = self::headers($headers);

        return $this->append(
            [
                'kind' => 'table',
                'headers' => $columns,
                'rows' => self::rows($rows, count($columns)),
                'styles' => self::styles($styles, count($columns)),
                'collapsible' => $collapsible,
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

        return new self(
            $this->summary,
            $this->blocks,
            [...$this->toolbar, $metric],
            $this->active,
        );
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
        return new self(
            $this->summary,
            [...$this->blocks, $block],
            $this->toolbar,
            $this->active,
        );
    }

    /**
     * Rejects column headings that are not plain strings.
     *
     * @param array<array-key, mixed> $headers Column headings to validate.
     *
     * @throws InvalidArgumentException If a heading is not a string.
     *
     * @return list<string> Validated column headings in display order.
     */
    private static function headers(array $headers): array
    {
        $columns = [];

        foreach ($headers as $header) {
            if (is_string($header) === false) {
                throw new InvalidArgumentException(
                    'Debug panel table headers must be plain strings.',
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
     * @throws InvalidArgumentException If the value is not an accepted inline input.
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
     * @throws InvalidArgumentException If the array was not produced by an inline factory.
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

        $text = $value['value'] ?? null;
        $style = $value['style'] ?? null;

        if ($kind === 'text' && is_string($text) && in_array($style, ['code', 'plain', 'preview', 'strong'], true)) {
            return [
                'kind' => 'text',
                'value' => $text,
                'style' => $style,
            ];
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
     * @throws InvalidArgumentException If an item is not an accepted inline value.
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
     * @throws InvalidArgumentException If the description is neither an inline value nor a list of them.
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
                'Debug panel paragraphs must be scalars, inline values, or lists of them.',
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
     * @throws InvalidArgumentException If a row is not a list or its width differs from the headers.
     *
     * @return list<list<Inline>> Validated rows in display order.
     */
    private static function rows(array $rows, int $columns): array
    {
        $result = [];

        foreach ($rows as $row) {
            if (is_array($row) === false || array_is_list($row) === false || count($row) !== $columns) {
                throw new InvalidArgumentException(
                    'Debug panel table rows must be lists whose width matches the headers.',
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
     * @throws InvalidArgumentException If a key is not an existing column index or a value is not a style.
     *
     * @return array<int, ColumnStyle> Validated column styles.
     */
    private static function styles(array $styles, int $columns): array
    {
        $result = [];

        foreach ($styles as $column => $style) {
            if (is_int($column) === false || $column < 0 || $column >= $columns) {
                throw new InvalidArgumentException(
                    'Debug panel column styles must be keyed by an existing column index.',
                );
            }

            if ($style instanceof ColumnStyle === false) {
                throw new InvalidArgumentException(
                    'Debug panel column styles must be ' . ColumnStyle::class . ' cases.',
                );
            }

            $result[$column] = $style;
        }

        return $result;
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
            'Debug panel inline content must be a scalar, null, or a PanelView inline value. Got '
            . get_debug_type($value) . '.',
        );
    }
}
