# Adapter/TransbankPos — POS de Transbank (máquina física)

**Estado:** lado del servidor implementado (`TransbankPosGateway`), **inactivo**. Falta la pantalla de caja que le habla a la máquina.

- **Contrato:** `PaymentGatewayInterface`.
- **Nombre:** `transbank_pos`. **Tag:** `#[AutoconfigureTag('payment.gateway')]`.

## Documentación (revisada el 2026-11-15)
- "POS Integrado" en https://transbankdevelopers.cl/producto/posintegrado.
- **Conexión:**
  - La máquina va por USB o serial al computador de recepción.
  - El **agente de Transbank** (`transbank-pos-sdk-web-agent2`, Node.js) corre en ese computador y abre un websocket en `localhost:8090`.
  - La pantalla de caja le habla con la librería JS `transbank-pos-sdk-web-js` (`doSale(monto, ticket)`, `refund(operationNumber)`).
- **El servidor (GCP) no habla con la máquina.** Por eso el RCE en Docker usa el puerto 8180.
- `melisa_prod` usa este mismo esquema (`WidgetsCaja.html.twig`), pero graba lo que manda el navegador sin validar nada. Aquí no.

## Cómo funciona
1. `startPayment()` devuelve `PaymentNextStep::WaitForTerminal`, con la orden de compra como referencia. La pantalla la envía como `ticket` en `doSale()`.
2. La pantalla reenvía la respuesta de la máquina al servidor en `ConfirmPayment::$terminalResponse`.
3. `confirm()`:
   - sin respuesta todavía → `Pending`;
   - `ticket` distinto de la referencia → excepción (respuesta de otra venta);
   - `responseCode` 0 → `Confirmed`. Exige código de autorización y que el monto coincida con `expectedAmount`;
   - otro `responseCode` → `Rejected`.
4. `refund()`: la anulación se hace **en la máquina** (`refund(operationNumber)` desde la pantalla). El servidor lanza `LogicException` hasta que exista ese flujo.

## Reglas
- Para auditoría se guardan los campos de la venta **sin** `last4Digits` ni `accountNumber`, y con `verified_by_provider: false`.
- La fecha de la máquina (`realDate`, hora de Chile) se guarda en UTC.

## Pendiente (docs/adr/0004)
- **La respuesta viene del navegador y Transbank no la confirma.** Hay que definir la conciliación con el reporte de ventas de Transbank, los permisos para registrar cobros y la auditoría.
- **Verificar con una máquina real:**
  - el largo máximo del `ticket` (melisa_prod manda `"0"`; nuestras órdenes tienen hasta 26 caracteres);
  - los nombres exactos de los campos de la respuesta;
  - los códigos de error (melisa_prod trata 1, 14 y 18 como tarjeta, 3 como conexión, 7 y 22 como fallida).
- La pantalla de caja (Stimulus + librería JS de Transbank vía importmap; dependencia nueva).

## Tests
`tests/Unit/Integration/Payments/Adapter/TransbankPos/`: aprobado, rechazado, sin respuesta, sin código de autorización, monto distinto, ticket de otra venta, respuesta repetida y que no se guarden datos de tarjeta.
