# Adapter/Imed — Bono electrónico IMED

**Estado:** enchufe listo, sin implementar. Se implementa en la Fase 2.

- **Contrato:** `VoucherProviderInterface` (+ `ReceivesCallbacksInterface` si IMED avisa por webhook).
- **Nombre:** `imed`. **Tag:** `#[AutoconfigureTag('payment.voucher_provider')]`.
- **Flujo esperado** (verificar con IMED antes de implementar):
  1. `validate()`: con el RUN del paciente, su previsión (Fonasa o Isapre), el profesional y las prestaciones, IMED responde si tiene cobertura y cuánto copaga. No emite nada.
  2. `issueVoucher()`: emite el bono y devuelve folio, monto cubierto y copago.
  3. `cancel()`: anula el bono, con motivo.
- **Requisitos:** convenio de cada centro con IMED y sus credenciales, en la configuración del tenant, cifradas. La documentación técnica la entrega IMED con el convenio.
- **Datos sensibles:** viajan el RUN y la previsión del paciente. Nunca en logs ni en mensajes de error.
- **Tests obligatorios:** camino feliz, sin cobertura, timeout, emisión repetida con la misma llave (no duplica el bono), anulación de un bono ya anulado.
