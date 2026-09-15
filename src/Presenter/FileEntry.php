<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

use PHPForge\Debug\Tone;

/**
 * Represents one typed file of a file list.
 */
final readonly class FileEntry
{
    /**
     * @param string $type Short kind label shown as a pill, such as `.css`; the host escapes it.
     * @param string $name File name or URL; the host escapes it.
     * @param Tone $tone Semantic tone interpreted by the host frontend.
     */
    public function __construct(public string $type, public string $name, public Tone $tone) {}
}
