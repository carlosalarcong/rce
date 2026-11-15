# Adapter/TransbankPos — POS de Transbank (máquina física)

**Estado:** enchufe listo, sin implementar.

- **Contrato:** `PaymentGatewayInterface`.
- **Nombre:** `transbank_pos`. **Tag:** `#[AutoconfigureTag('payment.gateway')]`.
- **Flujo esperado:** `startPayment()` manda el monto a la máquina y devuelve `PaymentNextStep::WaitForTerminal`. El paciente pasa la tarjeta. `confirm()` consulta el resultado y puede devolver `Pending` mientras el paciente no termina. `refund()` hace la anulación.
- **Decisión pendiente antes de implementar (docs/adr/0004):** el POS está conectado al computador de recepción, no al servidor en GCP. Hay que definir cómo llega el servidor a la máquina: una solución en la nube de Transbank o un agente local en el computador de recepción. Esto se verifica con Transbank.
- **Tests obligatorios:** camino feliz, rechazo, timeout, paciente que no termina (expira), doble envío del mismo cobro.
