<?php

declare(strict_types=1);

namespace CodexAgent\Transport;

interface Transport
{
    /**
     * Starts the transport if needed.
     */
    public function start(): void;

    /**
     * Sends a payload to the CLI.
     *
     * @param array<string, mixed> $payload
     */
    public function write(array $payload): void;

    /**
     * `codex exec` reads the whole prompt as raw text from stdin — it is not
     * the JSON-per-line protocol `write()` assumes. Without this, the prompt
     * has to go through `write()`'s JSON envelope, which the CLI just reads
     * back as literal prompt text instead of parsing it.
     */
    public function writeRaw(string $text): void;

    /**
     * Reads the next event from the CLI.
     *
     * @return array<string, mixed>|null
     */
    public function read(): ?array;

    /**
     * Ends the input stream if supported.
     */
    public function endInput(): void;

    /**
     * Closes the process if running.
     */
    public function close(): int;

    /**
     * PID del proceso del CLI, si ya arrancó. Sin esto no hay forma de matar
     * un CLI colgado desde fuera del propio transporte.
     */
    public function pid(): ?int;
}
