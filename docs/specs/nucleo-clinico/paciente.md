# Paciente según la NT 231 (NT820)

- **Módulo:** `Clinical/Patient`
- **Norma:** NT 231 "Estándares de Información en Salud" (conocida como NT820), aprobada por el Decreto Exento 9/2023, Capítulo II "Estándares de datos de la persona" (p. 42-93) y Capítulo IV para la dirección (p. 157-173)
- **Estado:** borrador, 2026-11-17

"Persona" es quien solicita y recibe atención. Si viene un acompañante, se registra su relación o parentesco con el paciente (p. 42).

## 1. Identificación

### Nombre (p. 49-50)

| Dato | Campo NT820 | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Nombres | `Nombre` | Texto | 150 | Obligatorio (al menos uno) |
| Primer apellido | `PrimerApellido` | Texto | 120 | Obligatorio |
| Segundo apellido | `SegundoApellido` | Texto | 120 | Opcional (ver ambigüedad A3) |
| Nombre social | `NombreSocial` | Texto | 120 | Opcional |

- Acepta mayúsculas, minúsculas, tildes, ñ, apóstrofo y diéresis.
- **Nombre social** (Circular 34/2011): si existe, se usa en el trato, la ficha, el brazalete, las recetas y las interconsultas. El nombre legal se mantiene para los documentos legales.
- Un cambio de nombre aprobado por el Registro Civil se reconoce. Es una enmienda, no una sobrescritura.

### Número de identificación (p. 51-56)

Se usa el RUN si la persona lo tiene. Solo si no lo tiene, se usa **un** otro identificador, en el orden de la tabla.

| Código | Tipo de identificador | Campo | Tipo | Largo |
|---|---|---|---|---|
| 1 | RUN | `Run` + `Digito Verificador` | Numérico + alfanumérico | 9 + 1 |
| 2 | RUN materno o del progenitor (recién nacido sin inscribir, transitorio) | `Run` + `Digito Verificador` | Numérico + alfanumérico | 9 + 1 |
| 3 | Folio del comprobante de parto | `OtraIdentificacion` | Alfanumérico | 100 |
| 4 | Pasaporte | `OtraIdentificacion` | Alfanumérico | 100 |
| 5 | Documento del país de origen | `OtraIdentificacion` | Alfanumérico | 100 |
| 6 | Identificador FONASA (provisorio, con forma de RUN) | `OtraIdentificacion` | Alfanumérico | 100 |

Reglas de la norma:
- Siempre que se usan datos nominales, hay que registrar algún identificador (p. 52).
- El RUN del progenitor y el folio de parto son **transitorios**: se reemplazan al inscribir al recién nacido. En partos múltiples se sugiere registrar más de uno (p. 52).
- El pasaporte vencido no es barrera de acceso; se registra el número vigente, que cambia al renovarlo (p. 55).
- El identificador FONASA se vincula después con el documento definitivo para no perder la trazabilidad (p. 56).

**El paciente no siempre tiene RUN.** El registro debe funcionar igual de bien en estos casos:

| Caso | Cómo se identifica según la norma | Qué cuidar |
|---|---|---|
| Chileno o extranjero con RUN | Código 1, RUN propio | — |
| Recién nacido sin inscribir, con RUN de la madre o del progenitor | Código 2 | Es **transitorio**: al inscribirlo se agrega su RUN y el anterior queda como historial. En gemelos o mellizos el RUN de la madre se repite, así que **no puede ser único** |
| Recién nacido sin RUN propio ni de la madre | Código 3, folio del comprobante de parto | Es un identificador **distinto** del código 2, también transitorio. En partos múltiples la norma sugiere registrar los dos (código 2 y código 3) para distinguir a cada hijo (p. 52) |
| Extranjero con pasaporte | Código 4 | Alfanumérico, no se valida como RUN. Cambia al renovar el pasaporte. Se guarda el **país que lo emitió** |
| Extranjero con documento de su país | Código 5 (DNI, cédula, etc.) | Alfanumérico. Se guarda el **país que lo emitió** (ver abajo) |
| Extranjero indocumentado o con visa en trámite | Código 6, identificador provisorio de FONASA | Tiene forma de RUN. Se vincula después con el documento definitivo |
| Sin ningún documento ni identificador FONASA | **La norma no lo cubre** | Igual hay que poder atenderlo y registrarlo. Pendiente en ADR 0007 (junto con los pacientes sin identificar, NN) |

**País que emitió el documento:** la NT 231 no tiene este campo, pero hace falta porque dos países pueden emitir el mismo número. Lo exige el perfil FHIR de paciente del Minsal (`MINSAL Paciente` v0.4.9, guía NID): la extensión `PaisEmisor` va en `identifier.type` y es obligatoria (1..1) en los identificadores RUN, RUN materno, pasaporte, documento del país de origen y NIP. Fuente: https://build.fhir.org/ig/Minsal-CL/NID/StructureDefinition-MINSALPaciente.html (es una guía en construcción, puede cambiar).

**Decisión (2026-11-17): el RUN admite hasta 9 dígitos** más el dígito verificador. Se sigue la tabla de detalle de la norma (p. 53). La regla vale para los tipos con forma de RUN (1, 2 y 6). Los tipos 3, 4 y 5 son alfanuméricos de hasta 100 caracteres y no pasan por el validador de RUN.

En el RCE:
- El RUN usa el value object `Run` (valida el dígito verificador y guarda `12345678-K`). La norma lo separa en dos campos solo para el intercambio.
- **Pendiente (decisión 1 y ADR 0007):** cómo se guardan varios identificadores con su historial, la unicidad, la fusión de pacientes duplicados y el número de ficha del Decreto 41. Ver `docs/revisiones/2026-11-17-identidad-paciente.md` en la rama `arquitecto`.

## 2. Datos demográficos

| Dato | Campo NT820 | Tipo | Largo | Exigencia | Valores |
|---|---|---|---|---|---|
| Fecha de nacimiento (p. 57) | `FechaNacimiento` | Numérico | 10 | Obligatorio | `DD-MM-AAAA` (en la base, tipo fecha) |
| Sexo biológico (p. 59-61) | `SexobiologicoGlosa` | Texto | 15 | Obligatorio | 1 Hombre · 2 Mujer · 3 Intersexual · 93 No informado · 99 Desconocido |
| Identidad de género (p. 62-64) | `GeneroGlosa` | Texto | 25 | Obligatorio | 1 Masculino · 2 Femenina · 4 Transgénero masculino · 5 Transgénero femenina · 6 No binarie · 7 Otra · 8 No revelado |
| Nacionalidad (p. 65-66) | `NacionalidadCodigo` | Numérico | — | Obligatorio | ISO 3166-1 numérico |
| País de origen (p. 67-68) | `PaisOrigenCodigo` | Numérico | — | Obligatorio | ISO 3166-1 numérico |

Reglas:
- **Sexo 93 ("No informado")** se usa cuando el Registro Civil cambió el sexo registral. En ese caso **la identidad de género es obligatoria** (p. 60).
- El sexo biológico lo registra quien atiende el parto, por observación. "Intersexual" no es un tercer sexo (p. 59).
- La identidad de género es **autodeclarada** y sensible: requiere privacidad y protocolo local (p. 62).
- El código 3 de identidad de género no existe en la norma. No se reutiliza.
- Si una de las nacionalidades es la chilena, se registra la chilena (p. 65).
- **La edad no se guarda en el paciente:** se calcula a la fecha de cada atención. Ver [evolucion-clinica.md](evolucion-clinica.md).

## 3. Situación de la persona

| Dato | Campo NT820 | Tipo | Largo | Exigencia | Valores |
|---|---|---|---|---|---|
| Estado civil (p. 69-70) | `EstadoCivilGlosa` | Numérico | — | Obligatorio | 1 Soltero(a) · 2 Casado(a) · 3 Viudo(a) · 4 Divorciado(a) · 5 Separado(a) judicialmente · 6 Conviviente civil · 99 Desconocido |
| Pertenencia a pueblo indígena (p. 71-73) | `PuebloIndigenaPertenencia` | Texto | 2 | Obligatorio | Sí / No |
| Pueblo indígena | `PuebloIndigenaCodigoGlosa` | Texto | 25 | Condicional: si la pertenencia es Sí | 1 Mapuche · 2 Aymara · 3 Rapa Nui · 4 Lickanantay · 5 Quechua · 6 Colla · 7 Diaguita · 8 Kawésqar · 9 Yagán · 11 Chango · 10 Otro (texto libre) |
| Afrodescendiente (p. 74-75) | `PuebloAfroPertenenciaGlosa` | Texto | 2 | Obligatorio | Sí / No |
| Religión (p. 76-77) | `ReligionCultosGlosa` | Texto | 30 | Opcional | Lista en el xlsx del DEIS |
| Situación de discapacidad (p. 78-79) | `DiscapacidadSituacion` | Texto | 2 | Obligatorio | Sí / No |

Reglas:
- El pueblo indígena se pregunta en dos pasos: "¿Se considera perteneciente a algún pueblo indígena u originario?" y, si dice Sí, "¿A cuál?". En "Otro" no se anotan pueblos de otros países (p. 72).
- La discapacidad **no exige** la credencial de SENADIS (p. 78).
- La valoración IVADEC (origen y nivel) se aplica "en el contexto clínico que corresponda". No es un dato fijo del paciente; probablemente queda fuera del MVP privado.
- Pueblo indígena, afrodescendencia, religión y discapacidad son **datos sensibles**.

## 4. Instrucción y trabajo

| Dato | Campo NT820 | Tipo | Largo | Exigencia | Valores |
|---|---|---|---|---|---|
| Nivel de instrucción (p. 80-82) | `NivelInstruccionGlosa` | Texto | 50 | Obligatorio | 1 Preescolar · 2 Especial · 3 Básica · 4 Media · 5 Superior · 6 Sin instrucción · 97 No recuerda · 98 No responde |
| Último curso aprobado | `UltimoCursoAprobado` | — | — | Junto al nivel | 0 a 8 |
| Condición de actividad (p. 83) | `CondActividadGlosa` | Texto | 150 | Opcional | 1 Inactivo · 2 Activo · 3 Cesante · 99 Ignorado |
| Ocupación (p. 84-85) | `OcupacionGlosa` | Texto | 300 | Opcional | CIUO-08, grandes grupos 1-10, 99 |
| Ocupación detallada (p. 86) | `OcupacionDetalladaGlosa` | Texto | 300 | Opcional | CIUO-08 de 4 dígitos (xlsx del DEIS) |

## 5. Previsión (p. 87-89)

| Dato | Campo NT820 | Tipo | Largo | Exigencia | Valores |
|---|---|---|---|---|---|
| Previsión | `PrevisionGlosa` | Texto | 150 | Obligatorio | 1 FONASA · 2 ISAPRE · 3 CAPREDENA · 4 DIPRECA · 5 SISA · 96 Ninguna · 99 Desconocido |
| Tramo FONASA (Cap. III 2.3, p. 101) | `TramoFonasaGlosa` | Texto | 20 | Condicional: si es FONASA | A, B, C, D (el A no tiene libre elección) |

- La previsión **cambia en el tiempo**: se guarda con fecha de vigencia y se conserva el historial.
- Los accidentes laborales (Ley 16.744) no son previsión; van por atención.
- La caja (`Ambulatory/Billing`) consulta la previsión vigente a través de la capa `Application` de `Clinical/Patient`.

## 6. Dirección (p. 90 y Capítulo IV)

| Dato | Campo NT820 | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Región | `RegionCodigo` | Numérico | 2 | Opcional |
| Provincia | `ProvinciaCodigo` | Numérico | 2 | Opcional |
| Comuna | `ComunaCodigo` | Texto | 5 | **Obligatorio** |
| Área urbano-rural | `LimiteUrbanoCensalCodigo` | Texto | 2 | Opcional |
| Tipo de vía | `ViadeDireccionCodigo` | Texto | 2 | Opcional |
| Nombre de la vía | `NombredeVia` | Texto | 100 | **Obligatorio** |
| Número | `NumerodeDireccion` | Numérico | 10 | **Obligatorio** (admite "S/N") |
| Complemento | `ComplementoDireccion` | Texto | 100 | Condicional |

- La región y la provincia se deducen de la comuna (catálogo DPA versionado en `Platform/Catalog`).
- La norma dice "Numérico" para el número, pero exige aceptar "S/N" (p. 167): en el RCE se trata como texto corto.

## 7. Contacto (p. 91-93)

| Dato | Campo NT820 | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Teléfono móvil | `TelefonoMovil` | Numérico | 9 | Al menos uno de los tres (decisión A5) |
| Teléfono fijo | `TelefonoFijo` | Numérico | 9 | Opcional |
| Otro teléfono | `TelefonoOtro` | Numérico | 9 | Opcional |
| De quién es el otro teléfono | `TipoOtroTelefonoCodigo` | Numérico | — | Opcional: 1 Trabajo · 2 Pareja · 3 Familiar · 4 Tutor o cuidador · 5 Vecino · 98 No responde |
| Correo | `CorreoElectronico` | Alfanumérico | 50 | Opcional, `texto@dominio` |

- **Decisión (2026-11-17): se exige al menos un teléfono**, cualquiera de los tres. La pantalla pide primero el celular, porque sirve para los recordatorios. Así no queda sin registrar quien solo tiene teléfono fijo.
- Los teléfonos van con 9 dígitos y sin `+56`.

## 8. Antecedentes y alergias

La NT820 los ubica en la **anamnesis remota** (Cap. V 2.2, p. 175-176), sin campos ni listas de valores. Orden de la norma:

1. mórbidos (médicos, quirúrgicos, traumatismos);
2. gineco-obstétricos;
3. hábitos (tabaco, alcohol, drogas);
4. uso de medicamentos;
5. **alergias**;
6. sociales y personales;
7. familiares;
8. inmunizaciones;
9. transfusiones.

En el RCE:
- Los antecedentes son del **paciente**, no de una atención: se arrastran entre consultas.
- **Las alergias** se muestran siempre como alerta visible (en la ficha, en la receta y junto al nombre en todas las listas), y alimentan `DecisionSupport`.
- Como la norma no da estructura, **falta definir** cómo se codifica una alergia (texto libre, catálogo de fármacos o SNOMED CT). Esa definición habría que informarla al DEIS (p. 21-22).

## Ambigüedades de la norma que tocan al paciente

| # | Tema | Qué dice la norma | Resolución |
|---|---|---|---|
| A1 | Pasaporte | En el resumen es código 3; en el detalle es 4 y el folio de parto es 3 (p. 44 vs. 54) | Usar el detalle |
| A2 | Largo del RUN | Máximo 99999999 en el resumen; 999999999 (9 dígitos) en el detalle (p. 44 vs. 53) | **Decidido: hasta 9 dígitos.** Al programar hay que alinear la caja de pagos, que hoy acepta solo 8 (`src/Integration/Payments/Contract/StartVoucherSale.php`, `CancelVoucher.php`) |
| A3 | Segundo apellido | "Obligatorio si tiene dos apellidos" en el resumen; "Opcional" en el detalle (p. 43 vs. 50) | Opcional en el sistema; la pantalla lo pide |
| A4 | Campos `...Glosa` | Muchos campos apuntan al texto aunque la lista tiene código | Guardar código + versión; exportar según lo pida el destino |
| A5 | Teléfono | "Al menos 1 teléfono" en el resumen; móvil obligatorio en el detalle (p. 47 vs. 91) | **Decidido: al menos uno.** Se sigue el resumen para no dejar fuera a quien no tiene celular |
| A6 | Nombre del campo de pueblo indígena | `PuebloIndigenaCodigoGlosa` en el resumen y `PuebloIndigenaPertenenciaGlosa` en el detalle | Solo afecta la exportación |

## Exigencias legales (verificadas)

Detalle y fuentes en [marco-legal-nucleo-clinico.md](../../normas/marco-legal-nucleo-clinico.md).

- **Contenido mínimo de la identificación** (Decreto 41 art. 6 a): nombre, tipo y número de documento, sexo, fecha de nacimiento, domicilio, teléfonos, ocupación, **representante legal o apoderado** y sistema de salud. Debe estar **actualizada** y se verifica en cada atención (art. 7).
- **Número de ficha, fecha de creación y prestador** (Decreto 41 art. 6 b).
- **Decisiones del paciente** que van en la ficha (Decreto 41 art. 6 d): rechazos de tratamiento, alta voluntaria y requerimientos por convicciones religiosas, étnicas o culturales.
- **Datos personales** (Ley 21.719, desde 01-12-2026): para atender no se necesita consentimiento del paciente (art. 16 bis e); la ficha **no se suprime** aunque el paciente lo pida (art. 7); una corrección pedida por el paciente se hace como enmienda (art. 6).
- **Quién puede ver la ficha** (Ley 20.584 art. 13): lista cerrada de ocho casos. Base para los permisos.

## Por diseñar

1. **Identificadores y número de ficha** (decisión 1, rama `arquitecto`).
2. **Antecedentes con estructura**, separados como en la anamnesis remota: mórbidos, quirúrgicos, gineco-obstétricos, hábitos, medicamentos en uso, familiares, inmunizaciones y transfusiones.
3. **Alergias completas:** sustancia (¿qué catálogo?), tipo de reacción, gravedad, quién la informó, fecha, y la diferencia entre "sin alergias conocidas" y "no consultado".
4. **Embarazo y lactancia vigentes:** cambian lo que se puede recetar.
5. **Grupo sanguíneo.**
6. **Representante legal o apoderado** (exigido por el Decreto 41): quién es, relación, documento y desde cuándo. Firma consentimientos cuando el paciente no puede.
7. **Contacto de emergencia**, distinto del representante.
8. **Fallecimiento:** fecha, para no agendar ni enviar recordatorios.
9. **Ficha confidencial:** acceso más restringido (salud mental, VIH, funcionarios del mismo centro).
10. **Pacientes sin ningún documento y sin identificar (NN)**, y **fusión de fichas duplicadas** (ADR de identidad del paciente).
11. **Búsqueda de pacientes y aviso de posibles duplicados**, sin mostrar el RUN completo de otras personas.
12. **Copia de la ficha para el paciente:** completa, gratuita y en formato estructurado y portable (Ley 20.584 art. 13).
13. **Historia de un sistema anterior:** cargar la historia de un centro que se cambia al RCE (fichas antiguas y PDF escaneados), sin mezclarla con los registros nuevos.
