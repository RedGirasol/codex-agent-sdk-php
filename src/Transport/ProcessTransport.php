<?php

declare(strict_types=1);

namespace CodexAgent\Transport;

use CodexAgent\Exception\CliNotFoundException;
use CodexAgent\Exception\ProcessException;

final class ProcessTransport implements Transport
{
    /** @var resource|null */
    private $process = null;

    /** @var array<int, resource> */
    private array $pipes = [];

    private string $stdoutBuffer = '';
    private string $stderrBuffer = '';
    private bool $stdinClosed = false;

    /**
     * @param list<string> $args
     * @param array<string, string> $env
     */
    public function __construct(
        private readonly array $args,
        private readonly ?string $cwd = null,
        private readonly array $env = [],
        private readonly ?string $cliPath = null,
        private readonly bool $useExecSubcommand = true,
    ) {
    }

    public function start(): void
    {
        if (!function_exists('proc_open')) {
            throw new ProcessException('proc_open is disabled in this PHP environment.');
        }

        $binary = $this->cliPath ?? self::locateCli();

        if (!@is_file($binary) || !@is_executable($binary)) {
            throw new ProcessException("Codex CLI not found or not executable: {$binary}");
        }

        $cmd = $this->useExecSubcommand
            ? array_merge([$binary, 'exec'], $this->args)
            : array_merge([$binary], $this->args);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($cmd, $descriptors, $pipes, $this->cwd, array_merge(getenv(), $this->env));
        if (!is_resource($process)) {
            throw new ProcessException('Unable to launch codex CLI: ' . $binary);
        }

        $this->process = $process;
        $this->pipes = $pipes;

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        stream_set_blocking($pipes[0], true);
    }

    public static function locateCli(): string
    {
        $binaryName = PHP_OS_FAMILY === 'Windows' ? 'codex.exe' : 'codex';
        $path = (string) (getenv('PATH') ?: '');
        $separator = PHP_OS_FAMILY === 'Windows' ? ';' : ':';

        foreach (explode($separator, $path) as $dir) {
            if ($dir === '') {
                continue;
            }

            $candidate = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $binaryName;
            if (@is_file($candidate) && @is_executable($candidate)) {
                return $candidate;
            }
        }

        $home = (string) (getenv('HOME') ?: '');
        $fallbacks = array_filter([
            $home !== '' ? $home . '/.local/bin/codex' : null,
            '/usr/local/bin/codex',
            '/opt/homebrew/bin/codex',
        ]);

        foreach ($fallbacks as $candidate) {
            if (@is_file($candidate) && @is_executable($candidate)) {
                return $candidate;
            }
        }

        throw new CliNotFoundException(
            'No se encontró el binario `codex` en PATH. Instálalo con el instalador oficial de OpenAI Codex o pasa una ruta explícita en Options::$cliPath.'
        );
    }

    public function write(array $payload): void
    {
        if ($this->stdinClosed || !isset($this->pipes[0])) {
            throw new ProcessException('The Codex transport is not active.');
        }

        $line = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        $written = 0;
        $length = strlen($line);

        while ($written < $length) {
            $bytes = fwrite($this->pipes[0], substr($line, $written));
            if ($bytes === false) {
                throw new ProcessException('Writing to codex stdin failed.');
            }
            $written += $bytes;
        }
    }

    public function writeRaw(string $text): void
    {
        if ($this->stdinClosed || !isset($this->pipes[0])) {
            throw new ProcessException('The Codex transport is not active.');
        }

        $written = 0;
        $length = strlen($text);

        while ($written < $length) {
            $bytes = fwrite($this->pipes[0], substr($text, $written));
            if ($bytes === false) {
                throw new ProcessException('Writing to codex stdin failed.');
            }
            $written += $bytes;
        }
    }

    public function read(): ?array
    {
        while (true) {
            $newlinePos = strpos($this->stdoutBuffer, "\n");
            if ($newlinePos !== false) {
                $line = trim(substr($this->stdoutBuffer, 0, $newlinePos));
                $this->stdoutBuffer = substr($this->stdoutBuffer, $newlinePos + 1);

                if ($line === '') {
                    continue;
                }

                $decoded = json_decode($line, true);
                if (!is_array($decoded)) {
                    $this->stderrBuffer .= "[stdout no-json] {$line}\n";
                    continue;
                }

                return $decoded;
            }

            if (!$this->pump()) {
                $tail = trim($this->stdoutBuffer);
                $this->stdoutBuffer = '';

                if ($tail === '') {
                    return null;
                }

                $decoded = json_decode($tail, true);
                if (!is_array($decoded)) {
                    $this->stderrBuffer .= "[stdout no-json] {$tail}\n";
                    return null;
                }

                return $decoded;
            }
        }
    }

    public function endInput(): void
    {
        if (!$this->stdinClosed && isset($this->pipes[0])) {
            fclose($this->pipes[0]);
            unset($this->pipes[0]);
            $this->stdinClosed = true;
        }
    }

    public function close(): int
    {
        if ($this->process === null) {
            return 0;
        }

        $this->endInput();
        $status = proc_close($this->process);
        $this->process = null;

        return $status;
    }

    public function pid(): ?int
    {
        if ($this->process === null) {
            return null;
        }

        $status = proc_get_status($this->process);

        return $status !== false && $status['pid'] > 0 ? $status['pid'] : null;
    }

    /** @return bool false when stdout has reached EOF */
    private function pump(): bool
    {
        $stdout = $this->pipes[1] ?? null;
        $stderr = $this->pipes[2] ?? null;

        if ($stdout === null || feof($stdout)) {
            $this->drainStderr();
            return false;
        }

        $read = [$stdout];
        if ($stderr !== null && !feof($stderr)) {
            $read[] = $stderr;
        }

        $write = $except = [];
        $ready = @stream_select($read, $write, $except, 0, 100000);
        if ($ready === false) {
            return false;
        }
        if ($ready === 0) {
            return true;
        }

        foreach ($read as $stream) {
            $chunk = fread($stream, 65536);
            if ($chunk === false || $chunk === '') {
                continue;
            }

            if ($stream === $stdout) {
                $this->stdoutBuffer .= $chunk;
            } else {
                $this->stderrBuffer .= $chunk;
            }
        }

        return true;
    }

    private function drainStderr(): void
    {
        $stderr = $this->pipes[2] ?? null;
        if ($stderr === null) {
            return;
        }

        while (($chunk = fread($stderr, 65536)) !== false && $chunk !== '') {
            $this->stderrBuffer .= $chunk;
        }
    }
}
