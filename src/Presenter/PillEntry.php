<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents one status pill.
 */
final readonly class PillEntry
{
    /**
     * @param string $label Subject of the pill, such as an extension name; the host escapes it.
     * @param string $state Short state text shown after the label, such as `on` or a version.
     * @param bool $enabled Whether the subject is active, selecting the on or off presentation.
     */
    public function __construct(public string $label, public string $state, public bool $enabled) {}
}
