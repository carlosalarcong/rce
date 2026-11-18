# Documentos clínicos

- **Módulo:** `Clinical/ClinicalDocument`
- **Cobertura de la NT 231 (NT820):** **ninguna.** La norma no define consentimientos, licencias ni informes.
- **Estado:** borrador, 2026-11-17

## Lo que sí viene de la NT820 (de otros capítulos)

Todo documento lleva la identificación del paciente (Cap. II), del profesional (Cap. III 4.1-4.2) y del establecimiento (Cap. III 4.4). El nombre social se usa en los documentos de trato; el nombre legal en los documentos legales (p. 49).

## Otras normas que aplican

| Documento | Norma | Verificado |
|---|---|---|
| Todo documento de la ficha lleva el número identificador de la ficha | Decreto 41, art. 6 | Sí (en la revisión de identidad) |
| Consentimiento informado | Ley 20.584 | No: falta leer el artículo y sus requisitos de forma |
| Licencia médica electrónica (LME) | Normativa de COMPIN y SUSESO; integración en `Integration/Lme` | No |
| Recetas | Ver [receta-e-indicaciones.md](receta-e-indicaciones.md) | No |

## Reglas del RCE que ya aplican

- Un documento firmado no se modifica: toda corrección es un documento nuevo que referencia al anterior.
- Los adjuntos van **cifrados** y cada acceso se audita.
- Retención mínima de 15 años.
