<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

use InvalidArgumentException;
use PHPForge\Debug\Exception\PanelViewMessage;

use function in_array;
use function parse_url;
use function strpbrk;
use function strtolower;
use function trim;

/**
 * Represents an inline navigation link the host renders as an anchor.
 *
 * Only relative targets and the `http`, `https`, and `mailto` schemes are accepted, so a captured value can never turn
 * into an executable target. Characters a browser strips while resolving a URL are rejected first, because they move
 * the scheme: a tab, a line break, or a surrounding space turns `" javascript:alert(1)"` into an executable target
 * after the check.
 */
final readonly class LinkInline implements Inline
{
    /**
     * @param string $label Link text; the host escapes it.
     * @param string $href Relative target, or an absolute `http`, `https`, or `mailto` URL.
     * @param bool $external Whether the host opens the target in a new browsing context.
     *
     * @throws InvalidArgumentException if the target carries characters a browser strips, cannot be parsed, or
     * declares a scheme other than `http`, `https`, or `mailto`.
     */
    public function __construct(public string $label, public string $href, public bool $external)
    {
        if (strpbrk($href, "\t\n\r") !== false || trim($href, "\x00..\x20") !== $href) {
            throw new InvalidArgumentException(
                PanelViewMessage::LINK_TARGET_NORMALIZED->getMessage(),
            );
        }

        $parts = parse_url($href);

        if ($parts === false) {
            throw new InvalidArgumentException(
                PanelViewMessage::LINK_TARGET_UNPARSABLE->getMessage(),
            );
        }

        $scheme = $parts['scheme'] ?? null;

        if ($scheme !== null && in_array(strtolower($scheme), ['http', 'https', 'mailto'], true) === false) {
            throw new InvalidArgumentException(
                PanelViewMessage::LINK_TARGET_SCHEME_INVALID->getMessage($scheme),
            );
        }
    }
}
