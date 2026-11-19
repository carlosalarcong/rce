# Documentos clínicos

- **Módulo:** `Clinical/ClinicalDocument`
- **Cobertura de la NT 231 (NT820):** **ninguna.** La norma no define consentimientos, licencias ni informes.
- **Estado:** borrador, 2026-11-17

## Lo que sí viene de la NT820 (de otros capítulos)

Todo documento lleva la identificación del paciente (Cap. II), del profesional (Cap. III 4.1-4.2) y del establecimiento (Cap. III 4.4). El nombre social se usa en los documentos de trato; el nombre legal en los documentos legales (p. 49).

## Exigencias legales (verificadas)

Detalle y fuentes en [marco-legal-nucleo-clinico.md](../../normas/marco-legal-nucleo-clinico.md).

| Documento | Qué exige la norma |
|---|---|
| Todo documento de la ficha | Lleva el **número de la ficha** (Decreto 41 art. 6). Se conserva 15 años desde el último registro |
| Consentimiento informado | **Por escrito** en cirugías, procedimientos invasivos y de riesgo relevante; en lo demás puede ser verbal, pero la aceptación o el rechazo siempre se anotan en la ficha. Contenido: estado de salud, diagnóstico, alternativas, riesgos y pronóstico. Si el paciente no puede, decide su representante legal. Los menores tienen derecho a ser oídos. Se puede firmar electrónicamente sin firma avanzada (Ley 20.584 arts. 10, 14-16) |
| Rechazo de tratamiento y alta voluntaria | Quedan en la ficha (Decreto 41 art. 6 d) |
| Formulario GES | Formulario oficial de la Superintendencia, firmado por prestador y paciente. Ver [diagnosticos.md](diagnosticos.md) |
| Licencia médica | **Electrónica por regla general**, emitida a través de un **operador con convenio** (Imed, Medipass), y solo por profesionales inscritos en el Registro Nacional de Prestadores Individuales (DS 3/1984; Ley 21.746). El RCE se integra con el operador (`Integration/Lme`) |
| Recetas | Ver [receta-e-indicaciones.md](receta-e-indicaciones.md) |
| Copia de la ficha | Gratuita, completa y en formato estructurado y portable (Ley 20.584 art. 13) |
| Firma | Ninguna norma revisada exige firma electrónica avanzada para estos documentos (Ley 19.799) |

## Por diseñar

1. **Tipos de documento:** certificado, informe médico, consentimiento informado, rechazo de tratamiento, formulario GES, licencia médica (vía operador), interconsulta, epicrisis o resumen de atención.
2. **Plantillas** que se llenan con datos del paciente y del profesional.
3. **Consentimiento informado:** catálogo de procedimientos que lo exigen por escrito, quién firma (paciente o representante), firma electrónica y versión del texto que se mostró.
4. **Guardar el PDF firmado** tal como se entregó; nunca regenerarlo.
5. **Anulación:** un documento firmado no se modifica; se anula y se emite otro que referencia al anterior.
6. **Documentos que trae el paciente:** adjunto con origen y fecha.
7. **Copia de la ficha completa** para el paciente o un tribunal, en formato estructurado (FHIR) y legible (PDF).
8. **Cifrado de adjuntos** y registro de cada acceso.
