# Integration/Messaging — Cola de encargos (outbox + Cloud Tasks)

Decisión completa en `docs/adr/0006`. En simple: el trabajo lento o con terceros se **anota** en una bandeja de la clínica, junto con el cambio que lo origina, y lo procesa una llamada HTTP firmada que dispara la cola, con reintentos.

## Cómo usarla
1. El mensaje implementa `Application/OutboxMessageInterface`: lleva solo ids y lo mínimo, nunca datos clínicos innecesarios.
2. Se despacha con `MessageBusInterface` **dentro del contexto de un tenant** y, si corresponde, dentro de la misma transacción del cambio de negocio (TenantEntityManager). Sin tenant activo, falla.
3. Su handler (`#[AsMessageHandler]`) debe ser **idempotente**: la cola puede entregar el mismo encargo más de una vez en casos raros.
4. Un error que no tiene sentido reintentar se lanza como `UnrecoverableMessageHandlingException`.

## Piezas
| Pieza | Qué hace |
|---|---|
| `Infrastructure/Outbox/OutboxTransport` (`outbox://`) | Guarda el mensaje en `outbox_message` del tenant activo |
| `Infrastructure/Outbox/PendingTaskNotifier` | Al terminar la petición o el comando, avisa a la cola |
| `Application/ProcessOutboxMessage` | `/_tasks/outbox/{tenant}/{id}`: toma el encargo de forma atómica, lo procesa, reintenta (al tiro, +1 y +5 min) o lo marca fallido |
| `Application/SweepOutbox` | `/_tasks/sweep`, cada minuto: reenvía avisos perdidos y libera encargos de procesos muertos |
| `Infrastructure/Tasks/CloudTasksQueue` + `GoogleOidcTaskVerifier` | GCP (`when@prod` en `config/services.yaml`) |
| `Infrastructure/Tasks/LocalTaskQueue` + `LocalTaskVerifier` + contenedor `tasks` | Local: simulador de Cloud Tasks y Cloud Scheduler sobre Valkey |
| `UI/TaskController` | Entrada `/_tasks`: solo con token verificado; 503 si falla la infraestructura |
| `rce:outbox:ping <tenant>` | Diagnóstico de toda la cadena |

## Reglas
- Nunca guardar el mensaje de un error: solo su clase. Puede traer datos del paciente.
- Nunca procesar un encargo en otra clínica: `ProcessOutboxMessage` lo verifica.
- `/_tasks` no usa sesión ni CSRF, y no se expone en menús ni en enlaces.
- Pendiente: visor de mensajes (listar `failed`, reprocesar, alertas) y limpieza de `done` antiguos.
