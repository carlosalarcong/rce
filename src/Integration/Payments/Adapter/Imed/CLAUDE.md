# Adapter/Imed — Bono electrónico IMED

**Estado:** enchufe listo, sin implementar. Se implementa en la Fase 2.

- **Contrato:** `VoucherProviderInterface` (+ `ReceivesCallbacksInterface` si IMED avisa por webhook).
- **Nombre:** `imed`. **Tag:** `#[AutoconfigureTag('payment.voucher_provider')]`.

## Documentación (revisada el 2026-11-15)
- **No hay documentación pública de programación.** `ayuda.imed.cl` publica solo requisitos de red e instalación del bono electrónico (TLS 1.2, puertos y Autentia para la huella), no la API.
- La integración se **pide a IMED**: el centro necesita convenio, y para bonos Fonasa, su Código de Venta Prestador (CVP). IMED entrega un "kit de integración" y credenciales.
- Otros sistemas clínicos chilenos ya lo hacen (por ejemplo, Nicemed), así que existe una vía para software de terceros.
- **Antes de implementar:** conseguir el kit con IMED, guardar aquí qué documentos entregaron y ajustar el flujo de abajo.

## Flujo esperado (suposición; confirmar con el kit)
1. `validate()`: con el RUN del paciente, su previsión (Fonasa o Isapre), el profesional y las prestaciones, IMED responde si tiene cobertura y cuánto copaga. No emite nada.
2. `issueVoucher()`: emite el bono y devuelve folio, monto cubierto y copago.
3. `cancel()`: anula el bono, con motivo.

## Reglas
- Credenciales por centro, en la configuración del tenant, cifradas.
- **Datos sensibles:** viajan el RUN y la previsión del paciente. Nunca en logs ni en mensajes de error.
- **Tests obligatorios:** camino feliz, sin cobertura, timeout, emisión repetida con la misma llave (no duplica el bono), anulación de un bono ya anulado.
