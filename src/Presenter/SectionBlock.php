<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

use PHPForge\Debug\PanelView;

/**
 * Represents a titled section wrapping the content of a child view.
 */
final readonly class SectionBlock implements Block
{
    /**
     * @param string $mark Short glyph shown before the title, such as `::` or `//`.
     * @param string $title Section title announced as its accessible name.
     * @param int|null $count Tally shown at the end of the title, or `null` to omit it.
     * @param PanelView $content Child view contributing only its ordered content blocks.
     */
    public function __construct(
        public string $mark,
        public string $title,
        public int|null $count,
        public PanelView $content,
    ) {}
}
