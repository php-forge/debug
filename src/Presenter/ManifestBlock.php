<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents a vendor-grouped package manifest.
 */
final readonly class ManifestBlock implements Block
{
    /**
     * @param string $label Vendor prefix the packages share, shown as the group heading.
     * @param list<PackageEntry> $packages Packages in display order.
     */
    public function __construct(public string $label, public array $packages) {}
}
