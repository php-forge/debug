<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

use InvalidArgumentException;
use PHPForge\Debug\ColumnStyle;
use PHPForge\Debug\Exception\PanelViewMessage;

use function count;
use function is_int;
use function is_string;

/**
 * Represents a table whose columns share a semantic style.
 *
 * The constructor rejects a heading that is not plain text, a row whose width differs from the headers, and a style
 * that does not address a declared column, so an inconsistent table cannot exist.
 */
final readonly class TableBlock implements Block
{
    /**
     * @var list<string> Plain-text column headings in display order.
     */
    public array $headers;

    /**
     * @var array<int, ColumnStyle> Semantic column styles keyed by column index.
     */
    public array $styles;

    /**
     * @param array<array-key, mixed> $headers Plain-text column headings in display order.
     * @param list<list<Inline>> $rows Rows of inline values in display order.
     * @param array<array-key, mixed> $styles {@see ColumnStyle} cases keyed by column index.
     * @param bool $collapsible Whether the host may collapse the table.
     * @param bool $filterable Whether to request the host's in-place row filter for the table.
     *
     * @throws InvalidArgumentException if a heading, a row width, or a column style is invalid.
     */
    public function __construct(
        array $headers,
        public array $rows,
        array $styles,
        public bool $collapsible,
        public bool $filterable,
    ) {
        $columns = [];

        foreach ($headers as $header) {
            if (is_string($header) === false) {
                throw new InvalidArgumentException(
                    PanelViewMessage::TABLE_HEADER_INVALID->getMessage(),
                );
            }

            $columns[] = $header;
        }

        $width = count($columns);

        foreach ($rows as $row) {
            if (count($row) !== $width) {
                throw new InvalidArgumentException(
                    PanelViewMessage::TABLE_ROW_WIDTH_INVALID->getMessage(),
                );
            }
        }

        $declared = [];

        foreach ($styles as $column => $style) {
            if (is_int($column) === false || $column < 0 || $column >= $width) {
                throw new InvalidArgumentException(
                    PanelViewMessage::COLUMN_STYLE_KEY_INVALID->getMessage(),
                );
            }

            if ($style instanceof ColumnStyle === false) {
                throw new InvalidArgumentException(
                    PanelViewMessage::COLUMN_STYLE_INVALID->getMessage(ColumnStyle::class),
                );
            }

            $declared[$column] = $style;
        }

        $this->headers = $columns;
        $this->styles = $declared;
    }
}
