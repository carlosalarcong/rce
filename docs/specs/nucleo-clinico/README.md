# Núcleo clínico según la NT820

Documenta los seis bloques del núcleo clínico (`src/Clinical/`) contra la **Norma Técnica 820 v3.0**. Cada archivo dice qué datos exige la norma para ese bloque, con su campo, tipo, largo, exigencia y lista de valores.

- **Fuente:** `docs/normas/nt820-estandares-de-informacion.md` (resumen de la norma con páginas citadas). Las citas `(p. NN)` son páginas del PDF.
- **Qué no es:** no es el diseño de tablas ni de clases. Cuando un dato depende de una decisión de arquitectura que aún no se cierra (identificadores, enmiendas, auditoría, cifrado), aquí se anota como **pendiente** y no se decide.
- **Estado:** borrador, 2026-11-17.

## Los seis bloques

| Bloque | Módulo | Cobertura de la NT820 | Archivo |
|---|---|---|---|
| Paciente | `Clinical/Patient` | **Completa** (Capítulo II, p. 42-93) | [paciente.md](paciente.md) |
| Evolución clínica | `Clinical/Encounter` | **Parcial** (Cap. V: anamnesis y tipo de consulta; edad calculada) | [evolucion-clinica.md](evolucion-clinica.md) |
| Diagnósticos | `Clinical/Diagnosis` | **Completa para lo que define** (Cap. V 3, p. 177) | [diagnosticos.md](diagnosticos.md) |
| Receta e indicaciones | `Clinical/Prescription` | **No la cubre** | [receta-e-indicaciones.md](receta-e-indicaciones.md) |
| Exámenes | `Clinical/Order` | **No la cubre** | [examenes.md](examenes.md) |
| Documentos | `Clinical/ClinicalDocument` | **No la cubre** | [documentos.md](documentos.md) |

**Hallazgo importante:** la NT820 v3.0 solo estandariza datos de la persona, del sistema de salud, geográficos y tres datos del proceso clínico (anamnesis, diagnóstico y tipo de consulta). Receta, exámenes y documentos se rigen por otras normas. Esos tres archivos dejan anotado qué norma habría que revisar, sin afirmar su contenido.

## Reglas que valen para los seis bloques

Vienen de la NT820 (p. 20-22, 26) y del `CLAUDE.md` del proyecto:

1. **Exigencia de la norma:** *Obligatoria* (siempre presente), *Opcional* (presente, pero puede ir vacía) o *Condicional* (depende de una condición de la persona o de la atención) (p. 26).
2. **Se guarda el código y la versión del catálogo, no el texto.** La norma pide poder decir con qué versión del estándar se registró cada dato (p. 22). Todos los catálogos viven en `Platform/Catalog` con versión y fecha de vigencia.
3. **Los nombres de campo de la norma** (`PrimerApellido`, `SexobiologicoGlosa`…) sirven para exportar e interoperar (REM, FHIR). El código del RCE usa nombres en inglés.
4. **Fechas:** la norma usa `DD-MM-AAAA` para intercambio y pantalla. En la base van como fecha (UTC para instantes) y se muestran en `America/Santiago`.
5. **Si un dato no está en la norma**, quien diseña lo define y lo informa al Equipo de Estándares del DEIS (p. 21-22).
6. **Datos sensibles** (identidad de género, pueblo indígena, religión, discapacidad y todo dato de salud, Ley 19.628): cifrado o acceso restringido, y auditoría de cada acceso. Cómo se cifra está **pendiente** (decisión 4 del orden de la auditoría).
7. **Nada se borra ni se sobrescribe:** toda corrección es una enmienda. Cómo se modela está **pendiente** (decisión 2).

## Pendientes que afectan a todos

- Confirmar si la NT820 v3.0 ya tiene resolución que la apruebe; la tabla de versiones dice "En tramitación" (p. 16).
- Revisar `VocabularioNorma820.xlsx` del DEIS: varias listas (religión, ocupaciones detalladas, comunas, títulos) solo están en extracto.
- La tabla de sistemas de codificación del Capítulo I (p. 35-37), donde aparecerían SNOMED CT y otros, no se pudo leer bien. Hay que verificarla antes de afirmar qué terminologías exige la norma.
