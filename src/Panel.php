<?php

declare(strict_types=1);

namespace PHPForge\Debug;

use InvalidArgumentException;

/**
 * Describes a panel from captured data through metadata constants and a single presentation method.
 *
 * Extensions own all columns and content and declare the default title and icon; the host owns their rendering and
 * may override the title and icon through its own configuration. Subclasses supply non-empty metadata constants and
 * build the view from the selected capture, never from live services.
 */
abstract class Panel
{
    /**
     * @var string Default host-interpreted icon identifier; the host configuration may override it.
     */
    protected const string ICON = '';

    /**
     * @var string Stable identifier associating the panel with its captured data, validated against the registration.
     */
    protected const string ID = '';

    /**
     * @var string Default human-readable panel title used in navigation; the host configuration may override it.
     */
    protected const string TITLE = '';

    /**
     * Builds a semantic view from the selected capture without consulting live services.
     *
     * @param array<string, mixed> $data Provider-owned captured data, decoded by the integration when necessary.
     *
     * @return PanelView Panel content and metrics described for the host frontend.
     */
    abstract public function present(array $data): PanelView;

    /**
     * Returns the default icon identifier declared by the extension.
     *
     * @throws InvalidArgumentException if the icon identifier is empty.
     *
     * @return string Default icon identifier declared by the extension; the host may override it.
     */
    final public function icon(): string
    {
        return self::required(static::ICON, 'ICON');
    }

    /**
     * Returns the stable panel identifier declared by the extension.
     *
     * @throws InvalidArgumentException if the panel identifier is empty.
     *
     * @return string Identifier associating the panel with captured data, validated against the registration.
     */
    final public function id(): string
    {
        return self::required(static::ID, 'ID');
    }

    /**
     * Returns the default navigation title declared by the extension.
     *
     * @throws InvalidArgumentException if the panel title is empty.
     *
     * @return string Default panel title declared by the extension; the host may override it.
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
     * @throws InvalidArgumentException if the metadata value is empty.
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
