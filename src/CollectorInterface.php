<?php

declare(strict_types=1);

namespace PHPForge\Debug;

/**
 * Defines request-scoped diagnostic capture without a framework or debugger dependency.
 *
 * The host coordinates collection through startup, capture, and shutdown.
 */
interface CollectorInterface
{
    /**
     * Returns provider-owned JSON data, or `null` for no capture.
     *
     * The host validates serializability before persistence. Providers must omit secrets before returning data.
     * An empty array is a capture, not absence. Nested values must survive strict JSON encoding within 500 levels.
     *
     * @return array<string, mixed>|null Captured payload, or `null` when no capture is available.
     */
    public function capture(): array|null;

    /**
     * Returns the stable identifier used to associate captured data with its panel.
     *
     * @return string Provider-owned capture identifier.
     */
    public function id(): string;

    /**
     * Stops request-scoped collection and releases its state and instrumentation.
     */
    public function shutdown(): void;

    /**
     * Starts request-scoped collection before the observed application work.
     */
    public function startup(): void;
}
