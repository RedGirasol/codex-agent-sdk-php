# Spec de la fase 1: codex-agent-sdk-php

## Objetivo

Crear un paquete PHP reusable para invocar OpenAI Codex mediante `codex exec --json`, con una API similar a la del SDK de Claude y una capa de transporte abstracta entre el runtime y el cliente.

## Scope

### Incluye

- `Options` para construir flags del CLI
- `Transport` + `ProcessTransport` + `FakeTransport`
- `Client` con flujo `send()` / `receive()` / `query()` / `ask()`
- excepciones package-specific
- helper function `CodexAgent\query()`
- tests unitarios para comportamiento estable

### Excluye

- app-server multi-turn JSON-RPC
- permisos avanzados del CLI
- presupuestos o filas de contexto complejas
- compatibilidad 1:1 con el protocolo de Claude

## Contrato de API

### Options

Debe soportar los siguientes campos mínimos:

- `model: ?string`
- `cwd: ?string`
- `addDirs: list<string>`
- `sandbox: ?string`
- `outputSchema: ?string`
- `reasoningEffort: ?string`
- `cliPath: ?string`
- `env: array<string, string>`
- `debug: bool`

Debe producir flags del estilo:

```php
new Options(
    model: 'gpt-5',
    cwd: '/tmp/project',
    addDirs: ['/tmp/project', '/tmp/shared'],
    sandbox: 'workspace-write',
    outputSchema: 'json',
    reasoningEffort: 'medium',
    debug: true,
);
```

como:

```php
[
  '--json',
  '--model', 'gpt-5',
  '--cd', '/tmp/project',
  '--add-dir', '/tmp/project',
  '--add-dir', '/tmp/shared',
  '--sandbox', 'workspace-write',
  '--output-schema', 'json',
  '-c', 'model_reasoning_effort=medium',
  '--debug',
]
```

### Transport

La interfaz debe cumplir:

```php
interface Transport
{
    public function start(): void;
    public function write(array $payload): void;
    public function read(): ?array;
    public function endInput(): void;
    public function close(): int;
}
```

### FakeTransport

Debe permitir:

- recibir una cola predefinida de eventos
- `read()` devolver cada evento en orden
- no depender del binario real
- ser usado para pruebas unitarias e integración

### Client

Debe permitir:

```php
$client = new Client(new Options(...), $transport);

$client->send('hola');
foreach ($client->receive() as $event) {
    // manejar evento
}

foreach ($client->query('hola') as $event) {
    // manejar evento
}
```

El comportamiento clave:

- `query()` llama a `send()` y luego `receive()`
- `receive()` detiene la iteración en el primer `type === 'result'`
- `query()` es un alias de flujo de una sola pregunta

## Criterio de aceptación de la fase 1

La fase 1 estará terminada cuando:

1. el paquete se puede instalar con Composer como biblioteca local o vía path
2. `Options::toCliArgs()` respeta el mapping acordado
3. `FakeTransport` puede devolver eventos de prueba determinísticamente
4. `Client::query()` itera como `send + receive`
5. `phpunit` corre con al menos 2 tests verdes
6. el paquete no requiere que `codex` exista en el sistema para ejecutar pruebas

## Fase 2 siguiente

La fase siguiente será la de `ProcessTransport` real con lectura robusta de streams y validación del formato JSON esperado del CLI, y luego la preparación del adapter para `agent-runner-bridge`.
