<?php

declare(strict_types=1);

namespace CodexAgent;

/**
 * Small convenience wrapper for one-shot Codex requests.
 *
 * @return \Generator<int, array<string, mixed>>
 */
function query(string $prompt, ?Options $options = null): \Generator
{
    $client = new Client($options ?? new Options());

    yield from $client->query($prompt);
}
