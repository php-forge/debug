<?php

declare(strict_types=1);

namespace PHPForge\Debug;

/**
 * Semantic table column styles mapped to presentation by the host, never raw CSS.
 */
enum ColumnStyle: string
{
    /**
     * Presents short machine names that must stay on one line, such as keys or class names.
     */
    case IDENTIFIER = 'identifier';

    /**
     * Presents code-like text whose character alignment matters, such as file paths.
     */
    case MONOSPACE = 'monospace';

    /**
     * Presents counts and measurements so that digits align across rows.
     */
    case NUMBER = 'number';

    /**
     * Presents long serialized data the host may clamp behind its standard expand control.
     */
    case PAYLOAD = 'payload';

    /**
     * Presents short status words as compact pills; plain text in the column is wrapped for the reader.
     */
    case PILL = 'pill';

    /**
     * Presents ordinary prose without additional treatment, and is the default for every column.
     */
    case PLAIN = 'plain';
}
