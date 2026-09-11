<?php

declare(strict_types=1);

namespace CodexAgent\Tests\Unit;

use CodexAgent\Client;
use CodexAgent\Options;
use CodexAgent\Transport\FakeTransport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Client::class)]
final class ClientTest extends TestCase
{
    public function test_it_reads_events_from_a_fake_transport(): void
    {
        $transport = new FakeTransport([
            ['type' => 'assistant', 'content' => 'hola'],
            ['type' => 'result', 'status' => 'ok'],
        ]);

        $client = new Client(new Options(), $transport);

        $events = iterator_to_array($client->query('hola'));

        $this->assertCount(2, $events);
        $this->assertSame('assistant', $events[0]['type']);
        $this->assertSame('result', $events[1]['type']);
    }
}
