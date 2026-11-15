# Adapter/Klap — Klap Checkout (ex Multicaja)

**Estado:** enchufe listo, sin implementar. Opcional.

- **Contrato:** `PaymentGatewayInterface` + `ReceivesCallbacksInterface` (Klap avisa por webhook).
- **Nombre:** `klap`. **Tag:** `#[AutoconfigureTag('payment.gateway')]`.

## Documentación (revisada el 2026-11-15)
- **Pública:** portal https://www.klap.cl/developers, con la API de Pagos documentada en Swagger (https://www.klap.cl/developers/docapi).
- Productos: **Checkout Flex** (el paciente paga en Klap; es el que corresponde) y Checkout Transparente (formulario propio con tokenización de tarjeta; **no usar**, porque acerca datos de tarjeta a nuestro sistema).
- No hay librería oficial para PHP: se llama la API REST con `symfony/http-client`.
- **Credenciales de prueba:** se piden a Klap desde el portal. Para probar de punta a punta piden contrato firmado.

## Flujo esperado (confirmar en la documentación al implementar)
1. `startPayment()` crea una orden de pago y devuelve `PaymentNextStep::Redirect` con la URL de Klap. Según el medio de pago, Klap responde una URL o un JSON.
2. El paciente paga en Klap.
3. Klap llama a nuestro webhook. `verifyCallback()` valida el aviso y `confirm()` **consulta el estado de la orden** en Klap antes de aceptarlo.
4. `refund()`: revisar en la documentación si la API permite reembolsos y en qué plazo.

## Reglas
- Credenciales por centro, en la configuración del tenant, cifradas.
- **Tests obligatorios:** camino feliz, rechazo, timeout, webhook repetido, webhook con firma inválida, orden expirada.
