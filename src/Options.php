<?php

declare(strict_types=1);

namespace CodexAgent;

final class Options
{
    /**
     * @param list<string>|null $addDirs
     * @param array<string, string> $env
     */
    public function __construct(
        public ?string $model = null,
        public ?string $cwd = null,
        public array $addDirs = [],
        public ?string $sandbox = null,
        public ?string $approvalPolicy = null,
        public ?string $outputSchema = null,
        public ?string $reasoningEffort = null,
        public ?string $cliPath = null,
        public array $env = [],
        public bool $debug = false,
    ) {
    }

    /**
     * @return list<string>
     */
    public function toCliArgs(): array
    {
        $args = ['--json'];

        if ($this->model !== null) {
            $args[] = '--model';
            $args[] = $this->model;
        }

        if ($this->cwd !== null) {
            $args[] = '--cd';
            $args[] = $this->cwd;
        }

        foreach ($this->addDirs as $dir) {
            $args[] = '--add-dir';
            $args[] = $dir;
        }

        if ($this->sandbox !== null) {
            $args[] = '--sandbox';
            $args[] = $this->sandbox;
        }

        // Sin esto, un comando que pide aprobación se queda esperando una
        // respuesta en stdin que nunca llega: el CLI corre sin terminal.
        if ($this->approvalPolicy !== null) {
            $args[] = '-c';
            $args[] = 'approval_policy='.$this->approvalPolicy;
        }

        if ($this->outputSchema !== null) {
            $args[] = '--output-schema';
            $args[] = $this->outputSchema;
        }

        if ($this->reasoningEffort !== null) {
            $args[] = '-c';
            $args[] = 'model_reasoning_effort=' . $this->reasoningEffort;
        }

        if ($this->debug) {
            $args[] = '--debug';
        }

        return $args;
    }
}
