# Adapter/Webpay — Webpay Plus (Transbank)

**Estado:** implementado (`WebpayGateway`), **inactivo** hasta que exista la configuración de proveedores por tenant (`NoActiveProviders`, `NoProviderCredentials`).

- **Contrato:** `PaymentGatewayInterface` + `ReceivesCallbacksInterface` (recibe el retorno del navegador).
- **Nombre:** `webpay`. **Tag:** `#[AutoconfigureTag('payment.gateway')]`.
- **Librería:** `transbank/transbank-sdk` 5.x (oficial; usa Guzzle). API REST v1.2.

## Documentación (revisada el 2026-11-15)
- https://transbankdevelopers.cl (Webpay Plus, API REST).
- Ambiente de integración con credenciales públicas: se prueba sin contrato.

## Cómo funciona
1. `startPayment()` crea la transacción (`buy_order` = `session_id` = orden de compra, ≤ 26 caracteres) y devuelve `PaymentNextStep::Redirect` con la URL de Webpay y `token_ws` en `redirectFields`.
2. El paciente paga en Webpay y vuelve a la URL de retorno con:
   - `token_ws`: retorno normal;
   - `TBK_TOKEN`: el paciente anuló en Webpay;
   - ningún token: el formulario venció. `verifyCallback()` devuelve false y el intento expira solo.
3. **El retorno nunca se acepta tal cual:** `confirm()` hace el *commit*. Si Transbank responde 422 (ya confirmada, anulada o abandonada), consulta `status()` y devuelve el estado real. Así, un retorno repetido no cobra dos veces.
4. Estados de Transbank:
   - `AUTHORIZED` con `response_code` 0 → `Confirmed`;
   - `INITIALIZED` → `Pending`;
   - `FAILED`, `REVERSED` o rechazo → `Rejected`;
   - desconocido → `Pending` (nunca se confirma).
5. Si `ConfirmPayment::$expectedAmount` no coincide con el monto de Transbank, se lanza una excepción: nunca se confirma un monto distinto.
6. `refund()` anula o reversa. Un rechazo de Transbank (4xx) es un resultado `refunded: false`, no una excepción.

## Reglas
- Timeout por llamada: `WEBPAY_TIMEOUT_SECONDS` (10 s; la llaman workers). La librería trae 10 minutos por defecto: nunca usar ese valor.
- Red caída, timeout o 5xx → `ProviderUnavailableException` (reintentable). 4xx en `create` → error de configuración, no se reintenta.
- Credenciales por tenant con `ProviderCredentialsInterface` (`commerce_code`, `api_key`, `production`). En dev y test: las públicas de integración (`SandboxProviderCredentials`).
- Para auditoría se guardan `status`, `response_code`, `buy_order`, `authorization_code`, `payment_type_code`, `installments_number`, `accounting_date` y `transaction_date`. **Nunca `card_detail`.**

## Tests
- Unitarios con Transbank simulado: `tests/Unit/Integration/Payments/Adapter/Webpay/`.
- Contra el ambiente de integración real: `make test-external`. No corre en la CI.

## Pendiente
- El controlador del retorno (`POST/GET /webhooks/webpay`) toca `Platform/Security`: requiere confirmación (docs/adr/0004).
- Activación por centro y credenciales cifradas (tabla en la base del tenant).
