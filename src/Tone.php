<?php

declare(strict_types=1);

namespace PHPForge\Debug;

/**
 * Semantic status tones for badges and callout paragraphs, never raw CSS.
 */
enum Tone: string
{
    /**
     * Reports a failure or a condition that breaks the observed operation.
     */
    case DANGER = 'danger';

    /**
     * Highlights a neutral fact worth noticing, such as the origin of a captured value.
     */
    case INFO = 'info';

    /**
     * De-emphasizes a value the reader can usually skip, and is the default badge tone.
     */
    case MUTED = 'muted';

    /**
     * Confirms that the observed operation completed as intended.
     */
    case SUCCESS = 'success';

    /**
     * Flags a degraded but working condition, such as diagnostics the provider could not read.
     */
    case WARNING = 'warning';
}
