<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents a list of typed file names.
 */
final readonly class FilesBlock implements Block
{
    /**
     * @param list<FileEntry> $files File entries in display order.
     */
    public function __construct(public array $files) {}
}
