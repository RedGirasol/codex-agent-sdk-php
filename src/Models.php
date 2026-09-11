<?php

declare(strict_types=1);

namespace CodexAgent;

/**
 * Los modelos válidos no son un valor fijo en el código: dependen de la cuenta
 * (ChatGPT vs API key) y cambian sin aviso. El CLI ya los resuelve y los deja
 * en `$CODEX_HOME/models_cache.json` en cada sesión — leerlos de ahí evita
 * adivinar un slug y descubrir el error minutos después, a medio turno.
 */
final class Models
{
    /**
     * @return list<array{slug: string, display_name: string, description: string}>
     */
    public static function available(?string $codexHome = null): array
    {
        $path = self::cachePath($codexHome);

        if (!@is_file($path)) {
            return [];
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        $decoded = json_decode($contents, true);

        if (!is_array($decoded) || !is_array($decoded['models'] ?? null)) {
            return [];
        }

        $models = [];

        foreach ($decoded['models'] as $model) {
            if (!is_array($model) || !is_string($model['slug'] ?? null)) {
                continue;
            }

            $models[] = [
                'slug' => $model['slug'],
                'display_name' => is_string($model['display_name'] ?? null) ? $model['display_name'] : $model['slug'],
                'description' => is_string($model['description'] ?? null) ? $model['description'] : '',
            ];
        }

        return $models;
    }

    /**
     * @return list<string>
     */
    public static function slugs(?string $codexHome = null): array
    {
        return array_values(array_map(
            static fn (array $model): string => $model['slug'],
            self::available($codexHome),
        ));
    }

    public static function isValid(string $slug, ?string $codexHome = null): bool
    {
        return in_array($slug, self::slugs($codexHome), true);
    }

    private static function cachePath(?string $codexHome): string
    {
        $home = $codexHome
            ?? self::stringEnv('CODEX_HOME')
            ?? (self::stringEnv('HOME') !== null ? self::stringEnv('HOME') . '/.codex' : null)
            ?? '';

        return rtrim($home, '/') . '/models_cache.json';
    }

    private static function stringEnv(string $name): ?string
    {
        $value = getenv($name);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
