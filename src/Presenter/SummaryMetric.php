<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents one panel summary metric whose label follows its inline value.
 */
final readonly class SummaryMetric
{
    /**
     * @param string $label Suffix displayed after the metric value, including any intended spacing.
     * @param Inline $value Metric value in its inline presentation.
     */
    public function __construct(public string $label, public Inline $value) {}
}
