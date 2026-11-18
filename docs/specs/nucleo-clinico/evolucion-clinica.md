# Evolución clínica según la NT 231 (NT820)

- **Módulo:** `Clinical/Encounter`
- **Norma:** NT 231 "Estándares de Información en Salud" (conocida como NT820), aprobada por el Decreto Exento 9/2023, Capítulo V "Estándares de proceso asistencial" (p. 174-187) y Capítulo II 2.2 para la edad (p. 58)
- **Cobertura:** parcial. La norma define la anamnesis, el tipo de consulta y la edad. **No define** el examen físico, los signos vitales ni la estructura de la nota de evolución.
- **Estado:** borrador, 2026-11-17

La norma pide que los registros clínicos cumplan criterios de **oportunidad y completitud** (p. 174).

## 1. Anamnesis (p. 175-176)

| AnamnesisCodigo | AnamnesisGlosa | Dónde vive en el RCE |
|---|---|---|
| 1 | Próxima | En la **atención**: es lo que le pasa hoy |
| 2 | Remota | En el **paciente** (antecedentes): se arrastra entre atenciones. Ver [paciente.md](paciente.md#8-antecedentes-y-alergias) |

- **Próxima:** relato cronológico y ordenado de lo que le ocurre al paciente, incluidos los síntomas que ya no tiene. Si hay varios problemas, va un párrafo por problema. Debe ser objetiva.
- La norma no da campos, tipos ni largos: es texto libre.
- Incluye el **motivo de consulta** dentro de la definición de anamnesis.

## 2. Tipo de consulta (p. 178-182)

Cada atención se marca como **nueva** o **de control**.

| Atención | Campo NT820 | Tipo | Largo | Exigencia | Valores |
|---|---|---|---|---|---|
| Consulta médica general | `ConsultaMedGeneralGLosa` | Texto | 50 | Obligatoria en APS | 1 Primera consulta o nueva · 2 Control o seguimiento |
| Consulta médica de especialidad | `ConsultaMedEspecialidadGlosa` | Texto | 100 | Obligatoria en consultas médicas | 1 Nueva de especialidad · 2 Control de especialidad |
| Consulta odontológica de especialidad | `ConsultaOdontoloEspecialidadGlosa` | Texto | 100 | Obligatoria donde hay odontología | 1 Nueva · 2 Control |
| Consulta de urgencia (p. 183) | `ConsultaUrgenciaGlosa` | Texto | 300 | Obligatoria en urgencia | Sin lista. Fuera del MVP (`Inpatient/Emergency`) |

Reglas:
- **Nueva** es la primera atención de un episodio. Toda primera atención que viene de una interconsulta es nueva para esa especialidad, aunque el paciente ya se haya atendido antes en ella (p. 180).
- **Control** son las atenciones siguientes por el mismo problema.
- La consulta médica general incluye procedimientos mínimos: presión arterial, otoscopia, peso y talla (p. 178).
- La agenda (`Ambulatory/Scheduling`) puede proponer el tipo al reservar, pero quien atiende lo confirma en la atención.

## 3. Otros tipos de atención (p. 184-187)

Solo definiciones, sin código. Sirven como catálogo de tipos de atención por profesión:

- consulta médica abreviada (repetir receta, leer exámenes): **no cuenta como consulta**;
- consultoría en salud (equipo de especialidad con APS);
- consulta de enfermera(o), matrón(a), nutricionista (ingreso y control), psicólogo(a), fonoaudiólogo(a), terapeuta ocupacional, tecnólogo médico, asistente social;
- teleconsulta de especialidad: homologable a la presencial (`Ambulatory/Telemedicine`).

Falta asignarles código: la norma no lo da, así que lo define el RCE y se informa al DEIS (p. 21-22).

## 4. Edad en la atención (p. 58)

La edad **se calcula** con la fecha de nacimiento y la fecha de la atención. No se guarda en el paciente.

| Unidad | Campo NT820 | Rango | Cuándo se usa |
|---|---|---|---|
| Años | `EdadAño` | 0 en adelante | Desde 1 año |
| Meses | `EdadMes` | 0-11 | Lactantes |
| Días | `EdadDia` | 0-29 | Recién nacidos |
| Horas | `EdadHora` | 0-23 | Recién nacidos |
| Minutos | `EdadMinuto` | 1-59 | Recién nacidos |

- La edad sí queda **fija en la atención** como dato calculado: los informes (REM) piden la edad al momento del evento.
- La fecha de la atención se guarda en UTC; la edad se calcula con la fecha en `America/Santiago`.

## 5. Leyes previsionales por atención (Cap. III 2.5, p. 105-106)

Exigencia: opcional "cuando corresponde". Se marcan en la atención, no en el paciente:
Ley de urgencia, GES, Ricarte Soto, accidente laboral, escolar o de tránsito. Afectan la caja y GES.

## Lo que la NT820 no define y el RCE necesita

- **Examen físico** y **signos vitales** (presión, pulso, temperatura, saturación, peso, talla). Hacen falta para `DecisionSupport` (rangos por edad). Habría que definir unidades y rangos, y revisar si la tabla de terminologías del Capítulo I (p. 35-37) recomienda LOINC o SNOMED CT.
- **Estructura de la nota** (por ejemplo SOAP) y el **plan**.
- **Firma** de la atención: quién, cuándo y con qué cierra la nota. Después de firmar, toda corrección es una enmienda (decisión 2, pendiente).
- **Borrador** de una nota a medio escribir: no es registro clínico hasta que se firma (ver `docs/specs/navegacion-por-areas.md`, idea A).
