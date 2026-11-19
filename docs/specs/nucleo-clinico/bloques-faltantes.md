# Bloques que faltan en el núcleo clínico

- **Estado:** borrador, 2026-11-19

Los seis bloques del núcleo clínico (paciente, evolución, diagnósticos, receta, exámenes y documentos) no cubren estas cuatro cosas, que un centro ambulatorio necesita. Hay que decidir si cada una es un bloque nuevo o parte de uno existente.

## 1. Interconsultas y derivaciones

- **Por qué:** son parte del contenido mínimo de la ficha (Decreto 41 art. 6 c). Además, toda primera atención que viene de una interconsulta es una **consulta nueva** para esa especialidad (NT 231, Cap. V 4.2).
- **Por diseñar:**
  - quién deriva, a qué especialidad o profesional, el motivo, el diagnóstico y la prioridad;
  - estados: solicitada, agendada, atendida, respondida, rechazada;
  - la **respuesta** (contrarreferencia) del especialista;
  - derivación interna (mismo centro, se conecta con la agenda) o externa (documento impreso);
  - en GES, la derivación cuenta para los plazos.
- **Dónde podría vivir:** `Clinical/Order` (como un tipo de orden) o un submódulo propio.

## 2. Vacunas

- **Qué dice la norma:** registran en el Registro Nacional de Inmunizaciones (RNI) los públicos y los privados **en convenio** con la SEREMI. No se encontró obligación para un privado sin convenio. Ver [marco-legal-nucleo-clinico.md](../../normas/marco-legal-nucleo-clinico.md), sección 8.
- **Por diseñar:**
  - registro de vacuna aplicada: vacuna, lote, dosis, fecha, vía, sitio y quién la aplicó;
  - antecedente de vacunas puestas en otro lugar (anamnesis remota);
  - integración con el RNI solo para centros con convenio.

## 3. Procedimientos en la consulta

- **Por qué:** curaciones, infiltraciones, suturas, toma de muestras, electrocardiogramas. El Decreto 41 pide registrar los procedimientos (art. 6 c), y se cobran con su código FONASA.
- **Por diseñar:**
  - catálogo de procedimientos asociado a la prestación (código FONASA);
  - registro: qué se hizo, quién, cuándo, hallazgos y complicaciones;
  - consentimiento por escrito cuando es invasivo o de riesgo (Ley 20.584 art. 14);
  - aviso a la caja para el cobro.

## 4. Atención de otros profesionales

- **Por qué:** enfermería, matrona, nutrición, psicología, kinesiología, fonoaudiología y terapia ocupacional atienden en centros ambulatorios. La NT 231 define estos tipos de consulta (Cap. V 5) y el Decreto 41 incluye las hojas de enfermería (art. 6 c).
- **Por diseñar:**
  - qué registra cada profesión (secciones y campos);
  - qué puede ver y hacer cada una (por ejemplo, quién puede recetar o diagnosticar con CIE-10);
  - el plan de cuidados de enfermería.
- **Dónde podría vivir:** como tipos de atención dentro de `Clinical/Encounter`, con secciones configurables por profesión.

## Lo que cruza todos los bloques

Detalle legal en [marco-legal-nucleo-clinico.md](../../normas/marco-legal-nucleo-clinico.md).

1. **Vista de la ficha:** línea de tiempo, alertas visibles (alergias), resumen del paciente.
2. **Permisos:** recepción no ve lo clínico; cada profesión ve y hace lo suyo; fichas confidenciales. La lista de quién puede acceder está en la Ley 20.584 art. 13.
3. **Registro de accesos:** quién vio cada ficha y cuándo (Decreto 41 art. 9).
4. **Conservación:** 15 años desde el último registro; eliminación con acta ante notario en privados (Decreto 41 arts. 11-12).
5. **Copia al paciente** en formato estructurado y portable (Ley 20.584 art. 13).
6. **Interoperabilidad:** obligatoria para todos los prestadores (Ley 21.668), con reglamento pendiente. Base probable: perfiles FHIR del Minsal.
7. **Contrato de encargo con cada clínica** y evaluación de impacto de cada clínica (Ley 21.719, desde 01-12-2026). Es tema de negocio y contrato, pero el RCE debe dar lo que la clínica necesita para hacerla.
8. **Respaldo en un segundo lugar y restauración** (Decreto 41 art. 8).
9. **Cierre de un centro:** ninguna norma lo regula; definir la devolución de datos (Ley 21.719 art. 15 bis) y quién custodia las fichas.
