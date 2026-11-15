# Adapter/TransbankPos — POS de Transbank (máquina física)

**Estado:** enchufe listo, sin implementar.

- **Contrato:** `PaymentGatewayInterface`.
- **Nombre:** `transbank_pos`. **Tag:** `#[AutoconfigureTag('payment.gateway')]`.

## Documentación (revisada el 2026-11-15)
- **Pública:** "POS Integrado" en https://transbankdevelopers.cl/producto/posintegrado.
- **Cómo se conecta:**
  - La máquina va por USB o serial al **computador de recepción** y tiene su propia salida a internet (RJ45).
  - En ese computador se instala el **agente de Transbank**: un programa local en Node.js que abre un websocket en `localhost:8090`. El repositorio original, `TransbankDevelopers/transbank-pos-sdk-web-agent`, está archivado; el vigente es `transbank-pos-sdk-web-agent2`.
  - La pantalla de caja, en el navegador, habla con el agente usando la librería JavaScript `transbank-pos-sdk-web-js`.
- **El servidor (GCP) no habla con la máquina.** No hay opción en la nube documentada.
- Para probar hace falta una máquina física. No hay librería para PHP (no se necesita: el lado servidor no toca el POS).
- El agente usa el puerto **8090**. Por eso el RCE en Docker usa 8180.

## Flujo esperado (definir el detalle al implementar)
1. `startPayment()` crea el intento y devuelve `PaymentNextStep::WaitForTerminal`.
2. La pantalla de caja (Stimulus) manda el monto al agente; el paciente pasa la tarjeta.
3. La pantalla envía el resultado del POS al servidor y `confirm()` lo registra (`Confirmed` con código de autorización, `Rejected` o `Pending`).
4. `refund()`: la anulación también se hace en la máquina, desde la pantalla de caja.

## Decisión pendiente (docs/adr/0004)
**El resultado llega desde el navegador, y el navegador no es de confianza.** A diferencia de Webpay, el servidor no puede preguntarle a Transbank si el cobro existe. Antes de implementar hay que decidir cómo se respalda un "aprobado":
- conciliación con el reporte de ventas o liquidación de Transbank, que marca diferencias;
- y quién puede registrar un cobro (permiso de cajero y auditoría de cada intento).

**Tests obligatorios:** camino feliz, rechazo, timeout del agente, agente no instalado o apagado, paciente que no termina (expira), doble envío del mismo cobro, resultado sin código de autorización.
