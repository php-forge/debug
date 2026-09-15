<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

use PHPForge\Debug\PanelView;

/**
 * Represents a labeled group wrapping the content of a child view.
 */
final readonly class GroupBlock implements Block
{
    /**
     * @param string $label Accessible group label.
     * @param PanelView $content Child view contributing only its ordered content blocks.
     */
    public function __construct(public string $label, public PanelView $content) {}
}
