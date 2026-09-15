<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents one toolbar metric shown independently of the panel summary.
 */
final readonly class ToolbarMetric
{
    /**
     * @param string $label Toolbar metric label; the host escapes it.
     * @param string $value Metric value as plain text; the host escapes it.
     */
    public function __construct(public string $label, public string $value) {}
}
