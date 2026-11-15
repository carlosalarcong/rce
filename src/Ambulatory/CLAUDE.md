# Ambulatory

Atención ambulatoria privada (MVP): Scheduling, Reception, Telemedicine, Billing (caja ambulatoria).

- Usa `Clinical` y `Platform` solo por su capa `Application` o por eventos.
- `Billing` es la caja ambulatoria, **separada** de la hospitalaria. Lo compartido (medios de pago) va en `Integration`.
- Dinero en enteros (pesos), nunca float.
