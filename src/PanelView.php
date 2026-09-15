<?php

declare(strict_types=1);

namespace PHPForge\Debug;

use InvalidArgumentException;
use PHPForge\Debug\Exception\PanelViewMessage;

use function array_is_list;
use function array_values;
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
 * The finished description is a tree of `PHPForge\Debug\Presenter` value objects. The host reads it through
 * {@see self::summaryMetrics()}, {@see self::toolbarMetrics()}, {@see self::blocks()}, and {@see self::isActive()},
 * then narrows each value with `instanceof` over the sealed {@see Block} and {@see Inline} unions.
 */
final readonly class PanelView
{
    /**
     * @param list<Presenter\SummaryMetric> $summary Summary metrics in display order.
     * @param list<Presenter\Block> $blocks Panel content blocks in display order.
     * @param list<Presenter\ToolbarMetric> $toolbar Toolbar metrics in display order, separate from the summary.
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
     * @return Presenter\BadgeInline Inline badge accepted by every content method.
     */
    public static function badge(string $label, Tone $tone = Tone::MUTED): Presenter\BadgeInline
    {
        return new Presenter\BadgeInline($label, $tone);
    }

    /**
     * Returns the content blocks for the host renderer.
     *
     * @return list<Presenter\Block> Validated content blocks in display order.
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
     * Appends a card describing one entity, optionally split into titled columns of its own content.
     *
     * @param string $id Anchor the host emits so other blocks can link to the card, or `''` to omit it.
     * @param string $icon Host icon key shown before the title, or `''` to omit it.
     * @param string $title Entity name shown as the card heading; the host escapes it.
     * @param string $subtitle Qualifier shown under the title, or `''` to omit it.
     * @param array<array-key, mixed> $meta Inline values or scalars shown beside the title, such as counts.
     * @param Presenter\ColumnEntry ...$columns Entries produced by {@see self::column()}.
     *
     * @throws InvalidArgumentException if a meta value is not an accepted inline value.
     *
     * @return self New view with the card appended.
     */
    public function card(
        string $id,
        string $icon,
        string $title,
        string $subtitle,
        array $meta,
        Presenter\ColumnEntry ...$columns
    ): self {
        $inline = [];

        foreach ($meta as $value) {
            $inline[] = self::inline($value);
        }

        return $this->append(
            new Presenter\CardBlock(
                $id,
                $icon,
                $title,
                $subtitle,
                $inline,
                array_values($columns),
            ),
        );
    }

    /**
     * Creates inline text presented as source code.
     *
     * @param string $value Text content; the host escapes it.
     *
     * @return Presenter\TextInline Inline text accepted by every content method.
     */
    public static function code(string $value): Presenter\TextInline
    {
        return new Presenter\TextInline($value, Presenter\TextStyle::CODE);
    }

    /**
     * Creates one titled column of a card body.
     *
     * @param string $title Column heading announced as its accessible name.
     * @param self $content Child view contributing only its ordered content blocks.
     *
     * @return Presenter\ColumnEntry Column entry accepted by {@see self::card()}.
     */
    public static function column(string $title, self $content): Presenter\ColumnEntry
    {
        return new Presenter\ColumnEntry($title, $content);
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
        return $this->append(new Presenter\DisclosureBlock($title, $content));
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

        return $this->append(new Presenter\EmptyStateBlock($title, $content));
    }

    /**
     * Creates one compact label and value pair of a fact strip.
     *
     * @param string $label Name of the fact; the host escapes it.
     * @param string $value Recorded value; the host escapes it.
     *
     * @return Presenter\FactEntry Fact entry accepted by {@see self::facts()}.
     */
    public static function fact(string $label, string $value): Presenter\FactEntry
    {
        return new Presenter\FactEntry($label, $value);
    }

    /**
     * Appends a compact strip of label and value pairs.
     *
     * @param Presenter\FactEntry ...$facts Entries produced by {@see self::fact()}.
     *
     * @return self New view with the fact strip appended.
     */
    public function facts(Presenter\FactEntry ...$facts): self
    {
        return $this->append(new Presenter\FactsBlock(array_values($facts)));
    }

    /**
     * Creates one typed file entry of a file list.
     *
     * @param string $type Short kind label shown as a pill, such as `.css`; the host escapes it.
     * @param string $name File name or URL; the host escapes it.
     * @param Tone $tone Semantic tone interpreted by the host frontend.
     *
     * @return Presenter\FileEntry File entry accepted by {@see self::files()}.
     */
    public static function file(string $type, string $name, Tone $tone = Tone::MUTED): Presenter\FileEntry
    {
        return new Presenter\FileEntry($type, $name, $tone);
    }

    /**
     * Appends a list of typed file names.
     *
     * @param Presenter\FileEntry ...$files Entries produced by {@see self::file()}.
     *
     * @return self New view with the file list appended.
     */
    public function files(Presenter\FileEntry ...$files): self
    {
        return $this->append(new Presenter\FilesBlock(array_values($files)));
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
        return $this->append(new Presenter\GroupBlock($label, $content));
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
        return $this->append(new Presenter\HeadingBlock($title, $section));
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
     * @return Presenter\LinkInline Inline link accepted by every content method.
     */
    public static function link(string $label, string $href, bool $external = false): Presenter\LinkInline
    {
        return new Presenter\LinkInline($label, $href, $external);
    }

    /**
     * Appends a labeled strip of navigation links.
     *
     * @param string $label Text introducing the strip, such as `Depends on 2`; the host escapes it.
     * @param Presenter\LinkInline ...$links Inline values produced by {@see self::link()}.
     *
     * @return self New view with the link strip appended.
     */
    public function links(string $label, Presenter\LinkInline ...$links): self
    {
        return $this->append(new Presenter\LinksBlock($label, array_values($links)));
    }

    /**
     * Appends a vendor-grouped package manifest.
     *
     * @param string $label Vendor prefix the packages share, shown as the group heading.
     * @param Presenter\PackageEntry ...$packages Entries produced by {@see self::package()}.
     *
     * @return self New view with the manifest appended.
     */
    public function manifest(string $label, Presenter\PackageEntry ...$packages): self
    {
        return $this->append(new Presenter\ManifestBlock($label, array_values($packages)));
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
            $fields[] = new Presenter\FieldEntry((string) $label, self::inline($value));
        }

        return $this->append(new Presenter\OverviewBlock($fields, $compact));
    }

    /**
     * Creates one package entry of a manifest.
     *
     * @param string $name Package name; the host escapes it.
     * @param string $version Resolved version; the host escapes it.
     *
     * @return Presenter\PackageEntry Package entry accepted by {@see self::manifest()}.
     */
    public static function package(string $name, string $version): Presenter\PackageEntry
    {
        return new Presenter\PackageEntry($name, $version);
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
     * @return Presenter\PillEntry Pill entry accepted by {@see self::pills()}.
     */
    public static function pill(string $label, string $state, bool $enabled): Presenter\PillEntry
    {
        return new Presenter\PillEntry($label, $state, $enabled);
    }

    /**
     * Appends a strip of status pills.
     *
     * @param Presenter\PillEntry ...$pills Entries produced by {@see self::pill()}.
     *
     * @return self New view with the pill strip appended.
     */
    public function pills(Presenter\PillEntry ...$pills): self
    {
        return $this->append(new Presenter\PillsBlock(array_values($pills)));
    }

    /**
     * Creates inline text the host may clamp behind its standard expand control.
     *
     * @param string $value Text content; the host escapes it.
     *
     * @return Presenter\TextInline Inline text accepted by every content method.
     */
    public static function preview(string $value): Presenter\TextInline
    {
        return new Presenter\TextInline($value, Presenter\TextStyle::PREVIEW);
    }

    /**
     * Creates one headline readout card.
     *
     * @param string $label Metric name shown above the value; the host escapes it.
     * @param string $value Headline value; the host escapes it.
     * @param string $caption Qualifier shown under the value, or `''` to omit it.
     *
     * @return Presenter\ReadoutEntry Readout entry accepted by {@see self::readouts()}.
     */
    public static function readout(string $label, string $value, string $caption = ''): Presenter\ReadoutEntry
    {
        return new Presenter\ReadoutEntry($label, $value, $caption);
    }

    /**
     * Appends a row of headline readout cards.
     *
     * @param Presenter\ReadoutEntry ...$readouts Entries produced by {@see self::readout()}.
     *
     * @return self New view with the readout row appended.
     */
    public function readouts(Presenter\ReadoutEntry ...$readouts): self
    {
        return $this->append(new Presenter\ReadoutsBlock(array_values($readouts)));
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
        return $this->append(new Presenter\SectionBlock($mark, $title, $count, $content));
    }

    /**
     * Creates inline text the host highlights as an SQL statement.
     *
     * @param string $value Statement text; the host escapes it.
     *
     * @return Presenter\TextInline Inline text accepted by every content method.
     */
    public static function sql(string $value): Presenter\TextInline
    {
        return new Presenter\TextInline($value, Presenter\TextStyle::SQL);
    }

    /**
     * Creates one headline stat tile.
     *
     * @param string $icon Host icon key shown above the value.
     * @param string $label Metric name shown under the value; the host escapes it.
     * @param string $value Headline value; the host escapes it.
     * @param Tone $tone Semantic tone interpreted by the host frontend, or {@see Tone::MUTED} to keep its own accent.
     *
     * @return Presenter\StatEntry Stat entry accepted by {@see self::stats()}.
     */
    public static function stat(string $icon, string $label, string $value, Tone $tone = Tone::MUTED): Presenter\StatEntry
    {
        return new Presenter\StatEntry($icon, $label, $value, $tone);
    }

    /**
     * Appends a strip of headline stat tiles.
     *
     * @param Presenter\StatEntry ...$stats Entries produced by {@see self::stat()}.
     *
     * @return self New view with the stat strip appended.
     */
    public function stats(Presenter\StatEntry ...$stats): self
    {
        return $this->append(new Presenter\StatsBlock(array_values($stats)));
    }

    /**
     * Creates emphasized inline text.
     *
     * @param string $value Text content; the host escapes it.
     *
     * @return Presenter\TextInline Inline text accepted by every content method.
     */
    public static function strong(string $value): Presenter\TextInline
    {
        return new Presenter\TextInline($value, Presenter\TextStyle::STRONG);
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
        $metric = new Presenter\SummaryMetric(
            $label,
            $emphasized ? self::strong((string) $value) : self::text((string) $value),
        );

        return new self([...$this->summary, $metric], $this->blocks, $this->toolbar, $this->active);
    }

    /**
     * Returns the summary metrics for the host renderer.
     *
     * @return list<Presenter\SummaryMetric> Validated summary metrics in display order.
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
        return $this->append(
            new Presenter\TableBlock(
                $headers,
                self::cells($rows),
                $styles,
                $collapsible,
                $filterable,
            ),
        );
    }

    /**
     * Creates plain inline text.
     *
     * @param string $value Text content; the host escapes it.
     *
     * @return Presenter\TextInline Inline text accepted by every content method.
     */
    public static function text(string $value): Presenter\TextInline
    {
        return new Presenter\TextInline($value, Presenter\TextStyle::PLAIN);
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
        $metric = new Presenter\ToolbarMetric($label, (string) $value);

        return new self($this->summary, $this->blocks, [...$this->toolbar, $metric], $this->active);
    }

    /**
     * Returns the toolbar metrics for the host renderer.
     *
     * @return list<Presenter\ToolbarMetric> Validated toolbar metrics in display order.
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
     * @return Presenter\TraceInline Inline trace accepted by every content method.
     */
    public static function trace(array $frames): Presenter\TraceInline
    {
        return new Presenter\TraceInline($frames);
    }

    /**
     * Creates a captured diagnostic value the host formats and escapes.
     *
     * @param mixed $value Diagnostic value, preserved without conversion.
     * @param bool $typeOnly Whether to show only the value's type instead of its contents.
     *
     * @return Presenter\ValueInline Inline value accepted by every content method.
     */
    public static function value(mixed $value, bool $typeOnly = false): Presenter\ValueInline
    {
        return new Presenter\ValueInline($value, $typeOnly);
    }

    /**
     * Appends a content block while retaining metrics and activity.
     *
     * @param Presenter\Block $block Content block placed after the existing blocks.
     *
     * @return self New view containing the additional block.
     */
    private function append(Presenter\Block $block): self
    {
        return new self($this->summary, [...$this->blocks, $block], $this->toolbar, $this->active);
    }

    /**
     * Converts table rows to inline cells, rejecting rows that are not lists.
     *
     * @param array<array-key, mixed> $rows Rows to normalize.
     *
     * @throws InvalidArgumentException if a row is not a list or a cell is not an accepted inline value.
     *
     * @return list<list<Presenter\Inline>> Normalized rows in display order.
     */
    private static function cells(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            if (is_array($row) === false || array_is_list($row) === false) {
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
     * Retains inline values and converts scalars and `null` to text.
     *
     * @param mixed $value Inline value or plain value to normalize.
     *
     * @throws InvalidArgumentException if the value is not an accepted inline input.
     *
     * @return Presenter\Inline Normalized inline value.
     */
    private static function inline(mixed $value): Presenter\Inline
    {
        return match (true) {
            $value instanceof Presenter\Inline => $value,
            $value === null => self::text('null'),
            $value === true => self::text('true'),
            $value === false => self::text('false'),
            is_string($value) => self::text($value),
            is_int($value), is_float($value) => self::text((string) $value),
            default => throw self::unsupportedInline($value),
        };
    }

    /**
     * Builds a paragraph block from ordered inline inputs.
     *
     * @param array<array-key, mixed> $content Inline values or plain values in display order.
     * @param Tone|null $tone Callout tone, or `null` for an ordinary paragraph.
     *
     * @throws InvalidArgumentException if an item is not an accepted inline value.
     *
     * @return Presenter\ParagraphBlock Normalized paragraph block.
     */
    private static function paragraphBlock(array $content, Tone|null $tone): Presenter\ParagraphBlock
    {
        $inline = [];

        foreach ($content as $value) {
            $inline[] = self::inline($value);
        }

        return new Presenter\ParagraphBlock($inline, $tone);
    }

    /**
     * Builds one paragraph from a scalar, a single inline value, or a list of inline values.
     *
     * @param mixed $paragraph Paragraph description.
     *
     * @throws InvalidArgumentException if the description is neither an inline value nor a list of them.
     *
     * @return Presenter\ParagraphBlock Normalized paragraph block.
     */
    private static function paragraphOf(mixed $paragraph): Presenter\ParagraphBlock
    {
        if (is_array($paragraph) === false) {
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
