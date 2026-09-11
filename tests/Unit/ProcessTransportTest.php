<?php

declare(strict_types=1);

namespace CodexAgent\Tests\Unit;

use CodexAgent\Transport\ProcessTransport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProcessTransport::class)]
final class ProcessTransportTest extends TestCase
{
    public function test_it_reads_json_events_from_a_real_php_subprocess(): void
    {
        $phpBinary = PHP_BINARY;

        $transport = new ProcessTransport(
            args: ['-r', 'echo json_encode(["type" => "assistant", "content" => "hola"]) . PHP_EOL; echo json_encode(["type" => "result", "status" => "ok"]) . PHP_EOL;'],
            cwd: null,
            env: [],
            cliPath: $phpBinary,
            useExecSubcommand: false,
        );

        $transport->start();

        $first = $transport->read();
        $second = $transport->read();

        $this->assertSame('assistant', $first['type']);
        $this->assertSame('hola', $first['content']);
        $this->assertSame('result', $second['type']);
        $this->assertSame('ok', $second['status']);

        $transport->close();
    }
}
