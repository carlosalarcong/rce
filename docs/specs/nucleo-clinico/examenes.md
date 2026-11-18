# Exámenes (órdenes y resultados)

- **Módulo:** `Clinical/Order`
- **Cobertura de la NT 231 (NT820):** **ninguna.** La norma menciona los "exámenes complementarios" como dato clínico básico (p. 174), pero no los estandariza.
- **Estado:** borrador, 2026-11-17

## Lo que sí viene de la NT820 (de otros capítulos)

| Dato | De dónde sale | Archivo |
|---|---|---|
| Identificación del paciente | Cap. II | [paciente.md](paciente.md) |
| Profesional que solicita | Cap. III 4.1-4.2 | `Platform/Identity` |
| Establecimiento que solicita | Cap. III 4.4 | `Platform/Tenancy` |
| Diagnóstico o hipótesis que justifica el examen | Cap. V 3 | [diagnosticos.md](diagnosticos.md) |
| Código de la prestación (7 dígitos, como texto) | Cap. III 2.4, arancel FONASA (p. 103-104) | `Platform/Catalog`; el precio lo usa la caja |

## Por revisar (no verificado)

- Si la tabla de terminologías del Capítulo I (p. 35-37) recomienda **LOINC** para exámenes de laboratorio o **SNOMED CT** para procedimientos.
- Cómo llegan los resultados de laboratorios externos (HL7 v2 o FHIR): es Fase 5, `Integration/`.

## Reglas del RCE que ya aplican

- Un resultado recibido no se modifica. Si el laboratorio corrige, llega un resultado nuevo que reemplaza al anterior y ambos quedan visibles.
- Los resultados son datos de salud: sensibles y auditados.
