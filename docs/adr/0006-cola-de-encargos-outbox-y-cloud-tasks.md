# 0006 — Cola de encargos: outbox por tenant y Google Cloud Tasks

- **Estado:** aceptada
- **Fecha:** 2026-11-16

## Contexto
- Las llamadas a proveedores (Webpay, IMED) y otros trabajos lentos no pueden bloquear al usuario ni perderse si el proveedor está caído (`src/Integration/CLAUDE.md`).
- El mensaje debe guardarse **en la misma transacción** que el cambio de negocio que lo origina (outbox), y eso obliga a que viva en la **base del tenant**.
- En producción se usa **Google Cloud Tasks** (decisión de Carlos), no un worker siempre encendido.

## Decisión
1. **Bandeja (outbox) en cada tenant:** tabla `outbox_message`, creada con una migración de tenant. El transporte de Messenger `outbox://` escribe en ella por la conexión `tenant`, la misma del `TenantEntityManager`: se confirma o se deshace con la transacción de negocio. Los mensajes que van ahí implementan `OutboxMessageInterface`.
2. **Aviso ("timbre"):** al terminar la petición o el comando (`PendingTaskNotifier`), se pide a la cola que llame a `POST /_tasks/outbox/{tenant}/{id}`.
   - GCP: `CloudTasksQueue` crea una tarea HTTP en Cloud Tasks (librería `google/cloud-tasks`, transporte REST, sin extensión grpc), firmada con un token OIDC de una cuenta de servicio.
   - Local: `LocalTaskQueue` guarda el aviso en Valkey y el contenedor `tasks` (`rce:tasks:local-runner`) lo entrega por HTTP con un token compartido.
3. **Proceso (`ProcessOutboxMessage`):**
   - conecta la base del tenant;
   - toma el encargo de forma atómica (`UPDATE … WHERE status = 'pending' … RETURNING`), así un aviso repetido no lo procesa dos veces;
   - verifica que el encargo sea de ese tenant;
   - lo entrega a su handler de Messenger y lo marca como hecho.
4. **Reintentos los decide el RCE**, no Cloud Tasks: al tiro, +1 min y +5 min (4 intentos). Después queda `failed`, con log `critical` y solo la clase del error (nunca el mensaje, que podría traer datos del paciente). Un error `Unrecoverable…` no se reintenta. Cloud Tasks solo reintenta si `/_tasks` no responde (503 ante fallas de infraestructura).
5. **Red de seguridad (`SweepOutbox`):** cada minuto, `POST /_tasks/sweep` recorre todas las clínicas. Vuelve a avisar los encargos cuyo aviso se perdió (más de 2 min) y devuelve a pendiente los tomados por un proceso que murió (más de 10 min). Lo llama Cloud Scheduler en GCP y el simulador en local.
6. **Seguridad de `/_tasks`:** sin sesión ni CSRF. Solo se acepta con token verificado:
   - GCP: `GoogleOidcTaskVerifier` exige firma de Google, audiencia = la URL del servicio y correo = la cuenta de servicio;
   - local: `LocalTaskVerifier`.

   El resolver por subdominio de Hakam excluye `/_tasks`, porque el tenant llega en la URL firmada.

## Por qué no un emulador de Cloud Tasks en local
El emulador de la comunidad (`aertje/cloud-tasks-emulator`) solo habla gRPC (obligaría a compilar la extensión `grpc` solo para desarrollo) y no publica versiones fijas. El simulador propio entrega por HTTP con la misma forma que Cloud Tasks y prueba el mismo código del RCE: solo cambia quién toca el timbre.

## Configuración en GCP (al desplegar)
- **Cola de Cloud Tasks** (por ejemplo, `outbox`) en la región del servicio. Reintentos de transporte: `min-backoff` 10 s, `max-backoff` 300 s, `max-attempts` 10. Son solo para cuando el servicio no responde.
- **Cuenta de servicio** de la cola con `roles/run.invoker` sobre el servicio de Cloud Run. La cuenta del servicio necesita `roles/cloudtasks.enqueuer` y `roles/iam.serviceAccountUser` sobre la de la cola.
- **Cloud Scheduler:** job cada minuto, `POST {URL}/_tasks/sweep`, con token OIDC de la misma cuenta de servicio y la misma audiencia.
- **Variables:** `TASKS_BASE_URL` y `TASKS_AUDIENCE` (la URL del servicio), `CLOUD_TASKS_PROJECT`, `CLOUD_TASKS_LOCATION`, `CLOUD_TASKS_QUEUE` y `CLOUD_TASKS_SERVICE_ACCOUNT`. `TASKS_LOCAL_TOKEN` no se usa en prod.
- **Migración de tenant** `Version20261116033456` en todas las clínicas: `tenant:migrations:migrate migrate`.
- **Diagnóstico:** `bin/console rce:outbox:ping <tenant>` deja un encargo que recorre toda la cadena.

## Consecuencias
- No hay worker siempre encendido en GCP: Cloud Run atiende `/_tasks` y escala solo.
- Todo mensaje que deba sobrevivir a fallas implementa `OutboxMessageInterface` y se despacha **dentro del contexto de un tenant**; sin tenant activo, el transporte lanza una excepción.
- Los encargos fallidos quedan en `outbox_message` con `status = failed`: el visor de mensajes (pendiente) los lista y permite reprocesarlos.
- Pendiente: limpieza periódica de los encargos `done` antiguos.
