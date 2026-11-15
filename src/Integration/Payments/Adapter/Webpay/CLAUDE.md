# Adapter/Webpay — Webpay Plus (Transbank)

**Estado:** enchufe listo, sin implementar. Se implementa en la Fase 2.

- **Contrato:** `PaymentGatewayInterface` + `ReceivesCallbacksInterface` (recibe el retorno del navegador).
- **Nombre:** `webpay`. **Tag:** `#[AutoconfigureTag('payment.gateway')]`.

## Documentación (revisada el 2026-11-15)
- **Pública y completa:** https://transbankdevelopers.cl (Webpay Plus, API REST).
- **Librería oficial para PHP:** `transbank/transbank-sdk` (Packagist; 5.1.0 a junio de 2025, pide PHP 8.2+). Agregarla es una dependencia de Composer: **preguntar antes**. Alternativa: llamar la API REST directo con `symfony/http-client`.
- **Ambiente de integración** con credenciales de prueba públicas: se puede desarrollar y probar **sin contrato**.

## Flujo esperado (confirmar en la documentación al implementar)
1. `startPayment()` crea la transacción con orden de compra (≤ 26 caracteres), monto y URL de retorno. Devuelve `PaymentNextStep::Redirect`, con la URL de Webpay y el token en `redirectFields` (`token_ws`).
2. El paciente paga en la página de Webpay y vuelve a la URL de retorno con el token.
3. `verifyCallback()` y `referenceFrom()` toman ese token. **No se acepta el estado del retorno:** se llama a `confirm()`, que hace el *commit* en Webpay y entrega el estado real.
4. `refund()` hace la anulación o reversa, total o parcial.

## Reglas
- Credenciales distintas por centro y por ambiente (integración y producción), en la configuración del tenant, cifradas. Nunca en `.env`.
- Revisar en la documentación: códigos de respuesta, plazo para hacer el commit y reglas de anulación.
- **Tests obligatorios:** camino feliz, rechazo, timeout, retorno repetido, retorno con token inválido, commit fuera de plazo.
