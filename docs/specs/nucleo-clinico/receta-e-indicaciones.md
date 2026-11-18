# Receta e indicaciones

- **Módulo:** `Clinical/Prescription`
- **Cobertura de la NT 231 (NT820):** **ninguna.** La norma no estandariza medicamentos, dosis ni recetas. Solo los nombra entre los "datos clínicos básicos" (indicaciones de tratamiento, p. 174).
- **Estado:** borrador, 2026-11-17

## Lo que sí viene de la NT820 (de otros capítulos)

| Dato de la receta | De dónde sale | Archivo |
|---|---|---|
| Identificación del paciente (nombre, nombre social, RUN u otro identificador, edad) | Cap. II | [paciente.md](paciente.md) |
| Alergias (alerta visible en la receta) | Cap. V 2.2, anamnesis remota | [paciente.md](paciente.md#8-antecedentes-y-alergias) |
| Profesional que prescribe: identificación, título, especialidad, registro (RNPI) | Cap. III 4.1-4.2 (p. 114-131) | Vive en `Platform/Identity`, no aquí |
| Establecimiento: código DEIS, dirección | Cap. III 4.4 (p. 152-156) | Vive en `Platform/Tenancy` |
| Diagnóstico asociado | Cap. V 3 | [diagnosticos.md](diagnosticos.md) |

## Normas que hay que revisar (no verificadas)

Ninguna de estas se ha leído para este proyecto. Hay que confirmar cuál aplica y qué exige antes de diseñar:

- Reglamento de farmacias y recetas (tipos de receta: simple, retenida, cheque) y el de estupefacientes y psicotrópicos.
- Normativa de **receta electrónica** del Minsal (integración `Integration/MinsalRecipe`).
- Terminología de fármacos: **TFC / CENS Pharma** (lo menciona la pizarra). Falta verificar si es obligatoria y cómo se licencia.

## Reglas del RCE que ya aplican

- La receta muestra las **alergias** como alerta visible.
- `DecisionSupport` revisa alergias, interacciones, dosis máximas y duplicidad cuando se redacta (`PrescriptionDrafted`).
- Una receta emitida no se modifica: se anula y se emite otra, dejando las dos visibles.
