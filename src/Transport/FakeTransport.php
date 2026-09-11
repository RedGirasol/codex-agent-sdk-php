<?php

declare(strict_types=1);

namespace CodexAgent\Transport;

final class FakeTransport implements Transport
{
    /**
     * @var list<array<string, mixed>>
     */
    private array $queue;

    private int $index = 0;

    /**
     * @param list<array<string, mixed>> $queue
     */
    public function __construct(array $queue = [])
    {
        $this->queue = $queue;
    }

    public function start(): void
    {
    }

    public function write(array $payload): void
    {
        $this->lastWritten = $payload;
    }

    public function writeRaw(string $text): void
    {
        $this->lastWrittenRaw = $text;
    }

    public function read(): ?array
    {
        if ($this->index >= count($this->queue)) {
            return null;
        }

        return $this->queue[$this->index++];
    }

    public function endInput(): void
    {
    }

    public function close(): int
    {
        return 0;
    }

    public function pid(): ?int
    {
        return null;
    }

    /**
     * @var array<string, mixed>|null
     */
    private ?array $lastWritten = null;

    /**
     * @return array<string, mixed>|null
     */
    public function lastWritten(): ?array
    {
        return $this->lastWritten;
    }

    private ?string $lastWrittenRaw = null;

    public function lastWrittenRaw(): ?string
    {
        return $this->lastWrittenRaw;
    }
}
