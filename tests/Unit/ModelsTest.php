<?php

declare(strict_types=1);

namespace CodexAgent\Tests\Unit;

use CodexAgent\Models;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Models::class)]
final class ModelsTest extends TestCase
{
    private string $home;

    protected function setUp(): void
    {
        $this->home = sys_get_temp_dir() . '/codex-agent-sdk-models-test-' . uniqid();
        mkdir($this->home, 0777, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->home . '/models_cache.json');
        @rmdir($this->home);
    }

    public function test_it_reads_the_slugs_from_the_cli_cache(): void
    {
        file_put_contents($this->home . '/models_cache.json', json_encode([
            'fetched_at' => '2026-09-11T19:37:47Z',
            'models' => [
                ['slug' => 'gpt-5.6-sol', 'display_name' => 'GPT-5.6-Sol', 'description' => 'Reliable agentic workhorse.'],
                ['slug' => 'gpt-5.5', 'display_name' => 'GPT-5.5', 'description' => 'Previous generation.'],
            ],
        ]));

        $models = Models::available($this->home);

        $this->assertCount(2, $models);
        $this->assertSame('gpt-5.6-sol', $models[0]['slug']);
        $this->assertSame(['gpt-5.6-sol', 'gpt-5.5'], Models::slugs($this->home));
        $this->assertTrue(Models::isValid('gpt-5.6-sol', $this->home));
        $this->assertFalse(Models::isValid('gpt-5', $this->home));
    }

    public function test_it_returns_an_empty_list_when_the_cache_is_missing(): void
    {
        $this->assertSame([], Models::available($this->home));
        $this->assertSame([], Models::slugs($this->home));
        $this->assertFalse(Models::isValid('gpt-5.6-sol', $this->home));
    }

    public function test_it_returns_an_empty_list_when_the_cache_is_malformed(): void
    {
        file_put_contents($this->home . '/models_cache.json', 'not json');

        $this->assertSame([], Models::available($this->home));
    }
}
