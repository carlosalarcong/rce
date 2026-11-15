# Integration

Integraciones externas: Messaging (outbox, cola, visor), Fhir, Hl7v2, Imed, Webpay, Sii, Lme, MinsalRecipe. También lo compartido entre cajas (medios de pago).

- Puede usar `Clinical` y `Platform`. Nadie de abajo depende de Integration.
- Los clientes externos van en `Infrastructure/`; nunca poner datos de pacientes en logs de integración.
