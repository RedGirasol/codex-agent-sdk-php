# Codex Agent SDK for PHP

PHP client for the OpenAI Codex CLI, designed around `codex exec --json` and intended to mirror the ergonomics of the Claude PHP SDK without pretending both protocols are compatible.

## Scope of v1

This package focuses on a single-turn execution model, which is enough for the PSM use case:

- one request per invocation
- JSON output from `codex exec --json`
- no multi-turn RPC session server
- no unsupported permission/Budget features to fake

## Goals

- Keep a simple `Client` API similar to `ClaudeAgent\Client`
- Support CLI argument mapping via `Options`
- Allow fake transports in tests
- Keep the implementation decoupled from the concrete Codex runtime

## Example

```php
<?php

use CodexAgent\Client;
use CodexAgent\Options;

$client = new Client(new Options(
    model: 'gpt-5',
    cwd: '/path/to/project',
    addDirs: ['/path/to/project'],
    reasoningEffort: 'medium',
));

foreach ($client->query('Explícame el estado actual del proyecto.') as $event) {
    echo json_encode($event, JSON_PRETTY_PRINT), PHP_EOL;
}
```

## Important differences vs Claude SDK

This package is intentionally narrow:

- no multi-turn app server / JSON-RPC loop
- no `permissionMode` concept in the same way
- no `maxBudgetUsd` enforcement in the CLI layer
- `model_reasoning_effort` is passed as a CLI config flag when available

## Planned package layout

- `Options` — CLI flags and config
- `Transport` — abstraction for the underlying command runner
- `ProcessTransport` — `proc_open` implementation
- `FakeTransport` — deterministic test transport
- `Client` — request/response stream wrapper
- `Exception/*` — package-specific errors

## Status

This repo is the v1 foundation for the multi-provider agent work described in the PSM plan.
