# 0004 — Integraciones: enchufes listos y pendientes antes del primer cobro

- **Estado:** aceptada
- **Fecha:** 2026-11-15

## Contexto
`src/Integration/CLAUDE.md` define cómo se conecta el RCE con proveedores externos (pagos, bonos y boletas). Hoy no se integra ningún proveedor: solo se dejan los "enchufes" para que, cuando partamos, conectar uno sea crear su adaptador y activarlo.

## Lo que queda listo
- **Contratos** (`Integration/Payments/Contract/`), sin dependencias del framework:
  - `PaymentGatewayInterface` para cobrar: Webpay, POS de Transbank, Klap, POS manual;
  - `VoucherProviderInterface` para bonos: IMED, Snabb, folio manual;
  - `TaxDocumentIssuerInterface` para boletas del SII, con el emisor por elegir;
  - `ReceivesCallbacksInterface` para webhooks y retornos;
  - los tipos que usan: `Clp` (enteros), `IdempotencyKey`, solicitudes y resultados, y `ProviderUnavailableException`.
- **Máquina de estados** `payment_attempt` (Symfony Workflow): `started → pending_confirmation → confirmed | rejected | expired`, sobre `Domain/PaymentAttempt`, que todavía no tiene tabla.
- **`GatewayRegistry`**: entrega el adaptador por nombre, solo si el tenant lo tiene activo. Por ahora `NoActiveProviders` deja todos inactivos.
- **Circuit breaker** (`CircuitBreakerInterface`): Ganesha con estrategia por tasa, estado en Valkey y umbrales en `CIRCUIT_BREAKER_*`.
- **Locks:** `symfony/lock` sobre Valkey (`LOCK_DSN`).
- **Valkey 9.1** en Docker, la misma versión por defecto de **Memorystore for Valkey** (GCP), que es lo que se usa en producción.
- **Fichas de adaptadores** (`Adapter/Webpay`, `Adapter/TransbankPos`, `Adapter/Imed`, `Adapter/Klap`): contrato, nombre, qué documentación existe, flujo esperado y tests obligatorios. Webpay y Klap tienen documentación pública; el POS de Transbank también, pero requiere la máquina para probar; IMED la entrega solo con convenio. Las demás carpetas de adaptadores quedan vacías.
- **Reglas:** la caja solo usa `Contract/` y `Application/` de Integration (Deptrac, `deptrac.modules.yaml`); los adaptadores solo conocen `Contract/` (`deptrac.layers.yaml`); un adaptador no usa a otro (`scripts/check-adapters.sh` en `make rules`).

## Pendientes (resolver antes del primer cobro real)
1. **Outbox por tenant.** Messenger usa `doctrine://default` (Main). La regla de guardar el `PaymentAttempt` y el mensaje en la misma transacción obliga a que el outbox viva en la **base del tenant**, y a que el worker cambie al tenant correcto (`SwitchDbEvent`) antes de procesar cada mensaje. Va con su propia ADR.
2. **Reintentos:** `retry_strategy` por transporte con inmediato, +1 min y +5 min. El "inmediato" no sale con el multiplicador estándar; probablemente requiere una estrategia propia.
3. **`failure_transport`, alertas y visor de mensajes** (`Integration/Messaging`).
4. **Configuración de proveedores por tenant:** tabla en la base del tenant (migración de tenant) con credenciales cifradas (sodium) y la clave de cifrado en Secret Manager. Reemplaza `NoActiveProviders`.
5. **Mapeo y tabla de `PaymentAttempt`** (XML en `Infrastructure/`, migración de tenant) e idempotencia única por `idempotency_key`.
6. **Auditoría de cada cambio de estado:** quién o qué lo causó, cuándo y la respuesta del proveedor. Depende de `Platform/Audit`, que todavía no existe.
7. **Webhooks:** `UI/WebhookController` (`POST /webhooks/{provider}`) toca el firewall (`Platform/Security`), así que requiere confirmación. Además, la URL de retorno es por subdominio del tenant.
8. **Value object `Run`:** los contratos de bonos reciben el RUN como texto normalizado. Cambiar el tipo cuando exista `Run` en Platform.
9. **POS de Transbank:** la documentación (revisada el 2026-11-15) resuelve la conexión: un agente local de Transbank en el computador de recepción (websocket en `localhost:8090`) y la pantalla de caja le habla con JavaScript. El servidor no habla con la máquina. **Queda pendiente** cómo se respalda un "aprobado" que llega desde el navegador (conciliación con el reporte de Transbank, permisos y auditoría). Ver `Adapter/TransbankPos/CLAUDE.md`. El RCE usa el puerto 8180 en Docker para no chocar con el agente.
10. **Memorystore en producción:** crear la instancia con Valkey 9.1 (no se puede bajar de versión después) y configurar `REDIS_URL` y `LOCK_DSN` con su IP privada.

## Consecuencias
- Agregar un proveedor no toca la caja: se crea su adaptador, se le da su nombre y su tag, y se activa en el tenant.
- Si en Docker cambia la versión de Valkey, hay que cambiar también la de Memorystore, y al revés.
