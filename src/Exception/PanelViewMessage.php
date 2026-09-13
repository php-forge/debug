<?php

declare(strict_types=1);

namespace PHPForge\Debug\Exception;

use function sprintf;

/**
 * Exception message templates authored by this package.
 *
 * Use {@see PanelViewPanelViewMessage::getMessage()} to format a template with `sprintf()` arguments.
 */
enum PanelViewMessage: string
{
    /**
     * Indicates that a column style is not a {@see \PHPForge\Debug\ColumnStyle} case.
     *
     * Format: "Debug panel column styles must be %s cases."
     */
    case COLUMN_STYLE_INVALID = 'Debug panel column styles must be %s cases.';

    /**
     * Indicates that a column style is keyed by a column the table does not declare.
     *
     * Format: "Debug panel column styles must be keyed by an existing column index."
     */
    case COLUMN_STYLE_KEY_INVALID = 'Debug panel column styles must be keyed by an existing column index.';

    /**
     * Indicates that an inline value is neither a scalar, `null`, nor a shape this class produces.
     *
     * Format: "Debug panel inline content must be a scalar, null, or a PanelView inline value. Got %s."
     */
    case INLINE_CONTENT_INVALID = 'Debug panel inline content must be a scalar, null, or a PanelView inline value. '
        . 'Got %s.';

    /**
     * Indicates that a link target carries characters a browser strips while resolving a URL.
     *
     * Format: "Debug panel links must not carry control characters or surrounding spaces, because a browser strips
     * them while resolving the target."
     */
    case LINK_TARGET_NORMALIZED = 'Debug panel links must not carry control characters or surrounding spaces, because '
        . 'a browser strips them while resolving the target.';

    /**
     * Indicates that a link target declares a scheme the host must not follow.
     *
     * Format: "Debug panel links must be relative or use the http, https, or mailto scheme. Got %s."
     */
    case LINK_TARGET_SCHEME_INVALID = 'Debug panel links must be relative or use the http, https, or mailto scheme. '
        . 'Got %s.';

    /**
     * Indicates that a link target cannot be parsed as a URL.
     *
     * Format: "Debug panel links must be a target a browser can resolve."
     */
    case LINK_TARGET_UNPARSABLE = 'Debug panel links must be a target a browser can resolve.';

    /**
     * Indicates that paragraph content is neither a scalar, an inline value, nor a list of them.
     *
     * Format: "Debug panel paragraphs must be scalars, inline values, or lists of them."
     */
    case PARAGRAPH_CONTENT_INVALID = 'Debug panel paragraphs must be scalars, inline values, or lists of them.';

    /**
     * Indicates that a column heading is not a plain string.
     *
     * Format: "Debug panel table headers must be plain strings."
     */
    case TABLE_HEADER_INVALID = 'Debug panel table headers must be plain strings.';

    /**
     * Indicates that a table row is not a list as wide as the headers.
     *
     * Format: "Debug panel table rows must be lists whose width matches the headers."
     */
    case TABLE_ROW_WIDTH_INVALID = 'Debug panel table rows must be lists whose width matches the headers.';

    /**
     * Indicates that a captured trace frame is not an array of frame fields.
     *
     * Format: "Debug panel trace frames must be arrays of frame fields."
     */
    case TRACE_FRAME_INVALID = 'Debug panel trace frames must be arrays of frame fields.';

    /**
     * Formats the message without changing diagnostic argument values.
     *
     * @param int|string ...$argument Values to insert into the message template.
     *
     * @return string Formatted exception message.
     */
    public function getMessage(int|string ...$argument): string
    {
        return sprintf($this->value, ...$argument);
    }
}
