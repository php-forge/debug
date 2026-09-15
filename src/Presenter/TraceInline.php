<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

use InvalidArgumentException;
use PHPForge\Debug\Exception\PanelViewMessage;

use function is_array;

/**
 * Represents the captured source frames of a call site, which the host renders through its own frame renderer.
 *
 * Frames travel as captured data, never as markup, so the host keeps ownership of the source-link format.
 */
final readonly class TraceInline implements Inline
{
    /**
     * @var list<array<string, mixed>> Captured frames in call order, each an array of frame fields.
     */
    public array $frames;

    /**
     * @param array<array-key, mixed> $frames Captured frames in call order, each an array of frame fields.
     *
     * @throws InvalidArgumentException if a frame is not an array of fields.
     */
    public function __construct(array $frames)
    {
        $captured = [];

        foreach ($frames as $frame) {
            if (is_array($frame) === false) {
                throw new InvalidArgumentException(
                    PanelViewMessage::TRACE_FRAME_INVALID->getMessage(),
                );
            }

            $fields = [];

            foreach ($frame as $key => $value) {
                $fields[(string) $key] = $value;
            }

            $captured[] = $fields;
        }

        $this->frames = $captured;
    }
}
