<?php

declare(strict_types=1);

namespace PHPForge\Debug;

use InvalidArgumentException;

/**
 * Describes a panel from captured data through metadata constants and a single presentation method.
 *
 * Extensions own all titles, icons, columns, and content. The host owns their rendering. Subclasses supply non-empty
 * metadata constants and build the view from the selected capture, never from live services.
 */
abstract class Panel
{
    /**
     * @var string Host-interpreted icon identifier supplied by the extension.
     */
    protected const string ICON = '';

    /**
     * @var string Stable identifier associating the panel with its captured data.
     */
    protected const string ID = '';

    /**
     * @var string Human-readable panel title used in navigation.
     */
    protected const string TITLE = '';

    /**
     * Builds a semantic view from the selected capture without consulting live services.
     *
     * @param array<string, mixed> $data Provider-owned captured data, decoded by the integration when necessary.
     *
     * @return PanelView Panel content, metrics, and activity described for the host frontend.
     */
    abstract public function present(array $data): PanelView;

    /**
     * Returns the icon identifier declared by the extension.
     *
     * @throws InvalidArgumentException If the icon identifier is empty.
     *
     * @return string Host-interpreted icon identifier.
     */
    final public function icon(): string
    {
        return self::required(static::ICON, 'ICON');
    }

    /**
     * Returns the stable panel identifier declared by the extension.
     *
     * @throws InvalidArgumentException If the panel identifier is empty.
     *
     * @return string Identifier used to associate the panel with captured data.
     */
    final public function id(): string
    {
        return self::required(static::ID, 'ID');
    }

    /**
     * Returns the navigation title declared by the extension.
     *
     * @throws InvalidArgumentException If the panel title is empty.
     *
     * @return string Human-readable panel title.
     */
    final public function name(): string
    {
        return self::required(static::TITLE, 'TITLE');
    }

    /**
     * Rejects an empty metadata value and identifies the missing definition field.
     *
     * @param string $value Metadata value to validate.
     * @param string $field Constant name included in the validation error.
     *
     * @throws InvalidArgumentException If the metadata value is empty.
     *
     * @return string Unmodified metadata value.
     */
    private static function required(string $value, string $field): string
    {
        if ($value === '') {
            throw new InvalidArgumentException(
                "A panel definition must declare {$field}.",
            );
        }
        return $value;
    }
}
