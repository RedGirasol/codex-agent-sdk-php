<?php

declare(strict_types=1);

namespace CodexAgent;

use CodexAgent\Transport\ProcessTransport;
use CodexAgent\Transport\Transport;

final class Client
{
    private Transport $transport;

    public function __construct(
        private readonly Options $options = new Options(),
        ?Transport $transport = null,
    ) {
        $this->transport = $transport ?? new ProcessTransport(
            args: $this->options->toCliArgs(),
            cwd: $this->options->cwd,
            env: $this->options->env,
            cliPath: $this->options->cliPath,
        );
    }

    /**
     * `codex exec` no habla el protocolo JSON-por-línea de Claude: lee TODO
     * stdin como el prompt en texto plano y no arranca hasta ver EOF. Sin
     * cerrar stdin aquí, el CLI se queda para siempre en "Reading prompt from
     * stdin..." — 0% CPU, sin eventos, sin llamadas a la API.
     */
    public function send(string $prompt): void
    {
        $this->transport->start();
        $this->transport->writeRaw($prompt);
        $this->transport->endInput();
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    public function receive(): \Generator
    {
        while (true) {
            $event = $this->transport->read();
            if ($event === null) {
                return;
            }

            yield $event;

            if (in_array($event['type'] ?? null, ['result', 'turn.completed', 'turn.failed'], true)) {
                return;
            }
        }
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    public function query(string $prompt): \Generator
    {
        $this->send($prompt);

        yield from $this->receive();
    }

    /**
     * Alias for query().
     *
     * @return \Generator<int, array<string, mixed>>
     */
    public function ask(string $prompt): \Generator
    {
        yield from $this->query($prompt);
    }

    public function close(): int
    {
        return $this->transport->close();
    }

    /**
     * PID del proceso `codex`, disponible solo despu\u00e9s de `send()`/`query()`.
     * Es lo \u00fanico que permite matar un CLI colgado desde afuera.
     */
    public function pid(): ?int
    {
        return $this->transport->pid();
    }
}
