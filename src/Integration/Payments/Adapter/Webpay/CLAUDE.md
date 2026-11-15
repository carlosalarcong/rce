# Adapter/Webpay — Webpay Plus (Transbank)

**Estado:** enchufe listo, sin implementar. Se implementa en la Fase 2.

- **Contrato:** `PaymentGatewayInterface` + `ReceivesCallbacksInterface` (recibe el retorno del navegador).
- **Nombre:** `webpay`. **Tag:** `#[AutoconfigureTag('payment.gateway')]`.
- **Flujo esperado** (verificar en la documentación oficial antes de implementar):
  1. `startPayment()` crea la transacción con orden de compra (≤ 26 caracteres), monto y URL de retorno. Devuelve `PaymentNextStep::Redirect`, con la URL de Webpay y el token en `redirectFields` (`token_ws`).
  2. El paciente paga en la página de Webpay y vuelve a la URL de retorno con el token.
  3. `verifyCallback()` y `referenceFrom()` toman ese token. **No se acepta el estado del retorno:** se llama a `confirm()`, que hace el *commit* en Webpay y entrega el estado real.
  4. `refund()` hace la anulación o reversa, total o parcial.
- **Ambientes:** integración (pruebas) y producción, con credenciales distintas por centro. Van en la configuración del tenant, cifradas; nunca en `.env`.
- **Leer antes de implementar:** documentación de Webpay Plus en transbankdevelopers.cl: códigos de respuesta, plazos para hacer commit y reglas de anulación.
- **Tests obligatorios:** camino feliz, rechazo, timeout, retorno repetido, retorno con token inválido, commit fuera de plazo.
