<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents an explicit empty state explained by its own paragraphs.
 */
final readonly class EmptyStateBlock implements Block
{
    /**
     * @param string $title Empty-state heading.
     * @param list<ParagraphBlock> $paragraphs Ordered explanations, each one paragraph.
     */
    public function __construct(public string $title, public array $paragraphs) {}
}
