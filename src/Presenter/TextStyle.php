<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Semantic presentation styles for inline text, mapped to presentation by the host, never raw CSS.
 */
enum TextStyle: string
{
    /**
     * Presents the text as source code.
     */
    case CODE = 'code';

    /**
     * Presents ordinary text without additional treatment, and is the default for every inline text.
     */
    case PLAIN = 'plain';

    /**
     * Presents text the host may clamp behind its standard expand control.
     */
    case PREVIEW = 'preview';

    /**
     * Presents the text as a highlighted SQL statement.
     */
    case SQL = 'sql';

    /**
     * Presents the text with emphasis, such as a headline metric value.
     */
    case STRONG = 'strong';
}
