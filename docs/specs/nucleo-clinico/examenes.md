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

## Exigencias legales (verificadas)

- **Resultados de exámenes, interconsultas y derivaciones** son parte del contenido mínimo de la ficha (Decreto 41 art. 6 c). Ver [marco-legal-nucleo-clinico.md](../../normas/marco-legal-nucleo-clinico.md).

## Por diseñar

1. **Catálogo de exámenes** asociado a la prestación (código FONASA de 7 dígitos) y **perfiles** (paquetes de exámenes).
2. **La orden:** prioridad, indicación clínica, diagnóstico asociado y estados (solicitada, tomada, con resultado, anulada).
3. **Resultados como valores:** examen, valor, unidad, rango de referencia y marca de anormal, además del PDF. Revisar si conviene LOINC.
4. **Resultados críticos:** a quién se avisa y registro de que el médico lo vio.
5. **Resultados externos** que trae el paciente (adjunto con fecha y laboratorio).
6. **Informes de imágenes.**
7. **Integración con laboratorio e imágenes** (HL7 v2 o FHIR): un centro con laboratorio propio lo pedirá antes de la Fase 5.
8. **Un resultado recibido no se modifica:** si el laboratorio corrige, llega uno nuevo y ambos quedan visibles.
9. **Interconsultas y derivaciones:** ver [bloques-faltantes.md](bloques-faltantes.md).
