# CLAUDE.md — Integration

## Propósito
Conectar el RCE con proveedores externos (pagos, bonos, boletas, FHIR, HL7 v2, LME) de forma que:
- la caja y los módulos clínicos no conozcan a ningún proveedor concreto
- agregar un proveedor nuevo sea crear un adaptador y activarlo, sin tocar nada más
- una caída del proveedor nunca bloquee al usuario ni duplique cobros o bonos

## Estructura
src/Integration/
├── Payments/
│   ├── Contract/
│   │   ├── PaymentGatewayInterface.php      # startPayment(), confirm(), refund()
│   │   ├── VoucherProviderInterface.php     # issueVoucher(), validate(), cancel()
│   │   └── TaxDocumentIssuerInterface.php   # issue(), void()
│   ├── Domain/
│   │   ├── PaymentAttempt.php               # intento con estado, provider, idempotency_key, montos en enteros
│   │   └── PaymentStatus.php                # enum de estados
│   ├── Application/
│   │   ├── GatewayRegistry.php              # entrega el adaptador según proveedor y configuración del tenant
│   │   └── HandleProviderCallback.php       # procesa webhooks de cualquier proveedor
│   ├── Infrastructure/                      # repositorios Doctrine, configuración por tenant
│   ├── UI/
│   │   └── WebhookController.php            # ruta única: POST /webhooks/{provider}
│   └── Adapter/
│       ├── ManualPos/                       # MVP
│       ├── ManualVoucher/                   # MVP (folio de bono a mano)
│       ├── Webpay/                          # Fase 2 (Webpay Plus de Transbank)
│       ├── TransbankPos/                    # POS físico de Transbank
│       ├── Imed/                            # Fase 2
│       ├── Snabb/                           # cuando corresponda
│       └── Klap/                            # opcional
├── Messaging/       # outbox, reintentos, visor de mensajes
├── Fhir/
├── Hl7v2/
├── Lme/
└── MinsalRecipe/

## Dependencias (Deptrac las hace cumplir)
- La caja (`Ambulatory`, `Inpatient`) puede usar **solo** `Payments/Contract` y `Payments/Application`: el "menú". Nunca un adaptador ni el dominio de Integration.
- `Contract/` no depende de nada: ni Symfony, ni Doctrine, ni otras capas. Los tags `AutoconfigureTag` van en el adaptador, no en la interfaz.
- `Adapter/` solo conoce `Contract/` (y el framework). No usa `Domain/` ni `Application/`.
- Un adaptador no usa a otro: lo revisa `make rules` (`scripts/check-adapters.sh`).

## Contratos
- `PaymentGatewayInterface`: cobrar dinero (Webpay, Klap, POS manual).
- `VoucherProviderInterface`: emitir o validar bonos (IMED, SNABB/BonoWeb, folio manual).
- `TaxDocumentIssuerInterface`: emitir boletas (facturador electrónico).
- Cada adaptador implementa uno de estos contratos y se registra con un tag:
  `#[AutoconfigureTag('payment.gateway')]`, `#[AutoconfigureTag('payment.voucher_provider')]`, `#[AutoconfigureTag('payment.tax_issuer')]`.
- Cada adaptador expone un nombre único (`webpay`, `transbank_pos`, `imed`, `manual_pos`, ...), en minúsculas, dígitos y `_`. Se usa en el registro, la configuración y la ruta del webhook.
- Si recibe webhooks o retornos, implementa además `ReceivesCallbacksInterface`.
- Timeout o caída: lanzar `ProviderUnavailableException` (reintentable, cuenta para el circuit breaker). Un rechazo de negocio no es una excepción: es un resultado con estado de rechazo.
- Montos con `Clp` (enteros) y operaciones con `IdempotencyKey`.
- Los RUN van como texto normalizado mientras no exista el value object `Run` (docs/adr/0004).

## Configuración por tenant
- Qué proveedores tiene activos cada centro y sus credenciales se guardan en la base del tenant.
- Las credenciales van cifradas. Nunca en `.env`, nunca en logs.
- Un proveedor no activo para el tenant no puede usarse aunque su adaptador exista. `GatewayRegistry` lo hace cumplir con `ActiveProvidersInterface`. Mientras no exista la configuración por tenant, `NoActiveProviders` deja todos inactivos.
- Los adaptadores piden sus credenciales a `Contract/ProviderCredentialsInterface`. En prod, `NoProviderCredentials` no entrega ninguna hasta que exista la configuración por tenant. En dev y test, `SandboxProviderCredentials` entrega las **públicas** de los ambientes de prueba (`config/services.yaml`).
- Pruebas contra ambientes reales de proveedores: grupo `external`, con `make test-external`. No corren en `make check` ni en la CI.

## Máquina de estados (Symfony Workflow)
`payment_attempt` en `config/packages/workflow.yaml`, sobre `Domain/PaymentAttempt`:

`started → pending_confirmation → confirmed | rejected | expired`

En la pantalla: Iniciado → Pendiente de confirmación → Confirmado | Rechazado | Expirado (`PaymentStatus::label()`).
- Solo se avanza a estados válidos. Nunca retroceder ni saltarse estados.
- Todo cambio de estado se audita (quién o qué lo causó, cuándo, respuesta del proveedor).

## Reglas de resiliencia (obligatorias)
- **No bloquear al usuario:** la web crea el `PaymentAttempt` en `INICIADO`, guarda el mensaje en el outbox en la misma transacción y responde. La llamada externa la hace un worker.
- **Timeouts:** 3–5 s si se llama desde la web; más largos solo en workers.
- **Reintentos con backoff:** inmediato, +1 min, +5 min (`retry_strategy` por transporte).
- **Circuit breaker por proveedor:** tras fallas repetidas, dejar de llamarlo un tiempo y dejar los procesos en cola. Usar `CircuitBreakerInterface` (Ganesha sobre Valkey; umbrales en `CIRCUIT_BREAKER_*`).
- **Idempotencia:** cada operación tiene `idempotency_key` única. Un doble clic, un reintento o un webhook repetido se detecta como "ya procesado" y no duplica nada.
- **Locks en Redis** en toda operación de cobro: `symfony/lock` con `LOCK_DSN`, sobre Valkey (Memorystore for Valkey en GCP).
- **Webhooks:** validar firma u origen y luego consultar al proveedor para confirmar el estado antes de aceptarlo.
- **Mensajes fallidos:** van al `failure_transport`, generan alerta y se reprocesan desde el visor de mensajes.

## Prohibido
- Guardar datos de tarjetas. Los pagos se hacen solo en la plataforma del proveedor.
- Llamar a un proveedor desde un controller sin pasar por la cola.
- Usar float para montos (siempre enteros en pesos).
- Que un adaptador dependa de otro adaptador.

## Cómo agregar un proveedor nuevo
1. Crear `Adapter/{Proveedor}/` con la clase que implementa el contrato.
2. Darle su nombre único y su tag.
3. Si recibe webhooks, implementar la validación de firma en el adaptador.
4. Tests: camino feliz, rechazo, timeout, webhook repetido, webhook con firma inválida.
5. Activarlo en la configuración del tenant. No se toca la caja ni otros módulos.
