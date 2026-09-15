<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

use PHPForge\Debug\Tone;

/**
 * Represents a paragraph of inline values, optionally carrying a callout tone.
 */
final readonly class ParagraphBlock implements Block
{
    /**
     * @param list<Inline> $content Inline values in display order.
     * @param Tone|null $tone Callout tone interpreted by the host frontend, or `null` for an ordinary paragraph.
     */
    public function __construct(public array $content, public Tone|null $tone) {}
}
