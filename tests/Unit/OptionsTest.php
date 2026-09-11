<?php

declare(strict_types=1);

namespace CodexAgent\Tests\Unit;

use CodexAgent\Options;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Options::class)]
final class OptionsTest extends TestCase
{
    public function test_it_builds_expected_cli_flags(): void
    {
        $options = new Options(
            model: 'gpt-5',
            cwd: '/tmp/project',
            addDirs: ['/tmp/project', '/tmp/shared'],
            sandbox: 'workspace-write',
            outputSchema: 'json',
            reasoningEffort: 'medium',
            debug: true,
        );

        $this->assertSame([
            '--json',
            '--model',
            'gpt-5',
            '--cd',
            '/tmp/project',
            '--add-dir',
            '/tmp/project',
            '--add-dir',
            '/tmp/shared',
            '--sandbox',
            'workspace-write',
            '--output-schema',
            'json',
            '-c',
            'model_reasoning_effort=medium',
            '--debug',
        ], $options->toCliArgs());
    }
}
