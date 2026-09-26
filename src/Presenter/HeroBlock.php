<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents the header identifying the subject a panel describes, such as the signed-in user.
 *
 * The host draws it as the lead card of the panel: the mark and title with their subtitle, the status badge whose tone
 * also colors the card accent, and one row of metrics under them.
 */
final readonly class HeroBlock implements Block
{
    /**
     * @param string $mark Short text drawn as an avatar before the title, such as initials, or `''` to omit it.
     * @param string $title Subject name, also announced as the accessible name of the header; the host escapes it.
     * @param string $subtitle Qualifier shown under the title, or `''` to omit it; the host escapes it.
     * @param BadgeInline|null $status Status shown opposite the title, or `null` for a neutral header without one.
     * @param list<FactEntry> $metrics Label and value pairs of the metric row in display order.
     */
    public function __construct(
        public string $mark,
        public string $title,
        public string $subtitle,
        public BadgeInline|null $status,
        public array $metrics,
    ) {}
}
