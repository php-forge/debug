<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents one package of a vendor manifest.
 */
final readonly class PackageEntry
{
    /**
     * @param string $name Package name; the host escapes it.
     * @param string $version Resolved version; the host escapes it.
     */
    public function __construct(public string $name, public string $version) {}
}
