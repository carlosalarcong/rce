# Diagnósticos según la NT 231 (NT820)

- **Módulo:** `Clinical/Diagnosis`
- **Norma:** NT 231 "Estándares de Información en Salud" (conocida como NT820), aprobada por el Decreto Exento 9/2023, Capítulo V 3 "Diagnóstico clínico" (p. 177)
- **Cobertura:** completa para lo que la norma define.
- **Estado:** borrador, 2026-11-17

**Definición:** el diagnóstico presuntivo inicial o confirmado que hace el profesional según el relato, el examen físico y los exámenes complementarios.

## Datos

| Dato | Campo NT820 | Largo | Exigencia | Valores |
|---|---|---|---|---|
| Término clínico escrito por el médico | `DiagnosticoTextoLibre` | 200 | Obligatorio | Texto libre |
| Sistema de codificación | `TipoCodDiagnosticaCodigo` | — | Lista | 1 CIE-10 · 2 CIE-11 |
| Código | `Codigo` | 200 | CIE-10: 4 caracteres, sin punto. CIE-11: mínimo 4 | Del catálogo correspondiente |
| URI de la entidad | `URLEntidad` | 300 | Obligatorio solo en CIE-11 | URI de la OMS |
| Estado | `DiagnosticoCodigo` | — | (sin exigencia explícita) | 1 Presuntivo (hipótesis) · 2 Confirmado |

## Reglas para el RCE

1. **Siempre texto libre y código juntos.** El médico escribe con sus palabras y elige el código. El texto no se reemplaza por la glosa del catálogo.
2. **Se guarda el sistema de codificación** de forma explícita y la **versión del catálogo** (p. 22). El `CLAUDE.md` exige CIE-10; el campo de sistema deja abierta la puerta a CIE-11 sin cambiar la estructura.
3. **Presuntivo o confirmado** en cada diagnóstico. Pasar de presuntivo a confirmado, o cambiar el código, es una **enmienda**: queda visible el diagnóstico anterior, quién lo cambió, cuándo y por qué.
4. El diagnóstico pertenece a una **atención**. Si después se transforma en un problema crónico del paciente (lista de problemas), eso está por definir.
5. **Formato del código CIE-10 (decidido 2026-11-17):** siempre 4 caracteres, sin punto. No se aceptan códigos de 3.
6. Catálogos CIE-10 (y CIE-11 a futuro) en la base principal (`Platform/Catalog`), de solo lectura y versionados.

## Ambigüedades de la norma

| # | Tema | Qué dice | Resolución |
|---|---|---|---|
| D1 | Formato CIE-10 | "4 caracteres", sin decir si va con punto | **Decidido: como dice la norma, 4 caracteres sin punto** (`J459`). Se puede mostrar con punto en pantalla; al enviar por FHIR se agrega el punto |
| D2 | Categorías de 3 caracteres | Hay códigos CIE-10 sin cuarto carácter; la norma no dice qué hacer | **Decidido: solo códigos de 4.** Los de 3 sin subdivisión se completan en el catálogo hasta 4 caracteres (posiblemente con una `X`, como `I10X`; falta confirmar con el catálogo CIE-10 del DEIS). Si un código tiene subdivisiones, el médico debe elegir una |
| D3 | CIE-11 | La norma la admite; el `CLAUDE.md` exige CIE-10 | Solo CIE-10 en el MVP; la estructura lo permite después |
| D4 | SNOMED CT | La pizarra lo menciona, pero la NT820 solo pide CIE-10 o CIE-11 para el diagnóstico | Revisar la tabla del Capítulo I (p. 35-37) antes de decidir |

## Exigencias legales (verificadas)

Detalle y fuentes en [marco-legal-nucleo-clinico.md](../../normas/marco-legal-nucleo-clinico.md).

- **GES** (Ley 19.966 art. 24; Circular IF/N°469): al **confirmar** un diagnóstico GES, el prestador informa al paciente y deja constancia en el **Formulario de Constancia de Información al Paciente GES**, firmado por prestador y paciente (puede ser electrónico). En los oncológicos, un formulario por cada etapa. No se altera y se conserva 15 años.
- **Enfermedades de notificación obligatoria** (DS 7/2019): el médico notifica a la SEREMI de inmediato ante la sospecha o en 24 horas desde la confirmación, según la enfermedad. El establecimiento nombra un delegado de epidemiología.

## Por diseñar

1. **Catálogo CIE-10 oficial** del DEIS (versión OMS), de solo lectura y versionado.
2. **Diagnóstico principal y secundarios:** uno solo principal por atención, obligatorio.
3. **Estado y fechas:** activo o resuelto, desde cuándo.
4. **Lista de problemas del paciente:** lo que sigue activo entre atenciones.
5. **Lateralidad** cuando corresponde (derecha, izquierda, bilateral).
6. **GES:** relación entre CIE-10 y problema de salud GES (con decreto, sexo y edad), aviso al confirmar, y el formulario de constancia con firma del paciente. En oncológicos, la etapa.
7. **Notificación obligatoria:** catálogo de enfermedades con su plazo (inmediata o 24 h), aviso al médico al diagnosticar y formulario para la SEREMI. Ver si los privados pueden usar EpiVigila.
