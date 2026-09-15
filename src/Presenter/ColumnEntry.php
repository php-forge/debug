<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

use PHPForge\Debug\PanelView;

/**
 * Represents one titled column of a card body.
 */
final readonly class ColumnEntry
{
    /**
     * @param string $title Column heading announced as its accessible name.
     * @param PanelView $content Child view contributing only its ordered content blocks.
     */
    public function __construct(public string $title, public PanelView $content) {}
}
