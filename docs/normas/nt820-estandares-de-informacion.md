# Norma Técnica 820 — Estándares de Información de Salud (versión 3.0)

Referencia de trabajo para el RCE. Resume y transcribe las definiciones, estructuras y listas de valores de la norma. No reemplaza al documento original.

> **Numeración de páginas.** Las citas `(p. NNN)` son la página del PDF (1..198). La norma imprime su propio pie "Página N | 196", que va **una página atrás** del PDF (p. ej. PDF p. 60 = "Página 59").

## 1. Qué es y por qué importa

- **Qué es:** el marco normativo de los datos administrativos y clínicos de salud en Chile. Fija la definición semántica de cada dato y su estructura (campo, tipo, largo, exigencia, vocabulario y fuente). Rige tanto para formularios en papel como para soluciones informáticas (p. 15).
- **Emisor:** Departamento de Estadísticas e Información de Salud (DEIS), División de Planificación Sanitaria, Subsecretaría de Salud Pública, Minsal (p. 1-2).
- **Versión y fecha:** 3.0, elaborada el 12-01-2023. En la tabla de versiones figura con decreto "En tramitación", y "sustituirá" a la Resolución Exenta 643 (versión 2.0, 31-12-2016). La versión 1.0 es de 2011, Resolución Exenta N° 820 (p. 16). La versión de 2021 agregó la primera serie de estándares de datos clínicos (p. 15).
- **Alcance:** se aplica a los establecimientos y organismos de salud del territorio nacional, **públicos y privados** (p. 19-20). Cada nueva versión deja sin efecto la anterior (p. 15).
- **Obligatoriedad y adopción (lo que dice la norma):**
  - Los datos de la norma son exigibles desde la captura cuando forman parte del **CMBD** (conjunto mínimo básico de datos) de un formulario, plataforma o sistema. Cuando un dato está en el CMBD, se captura según el estándar vigente (p. 20).
  - "No todos los datos contenidos en esta Norma son obligatorios para todos los registros". La exigencia específica la define quien diseña cada activo de información; la norma fija las exigencias transversales (p. 20, 26).
  - Antes de crear un dato en un formulario o sistema hay que consultar la norma. Si el dato existe, se usa el estándar. Si no existe, quien diseña lo define (semántica, estructura, vocabulario y fuente) y lo envía al Equipo de Estándares del DEIS (p. 21-22).
  - La vigencia rige desde la resolución del Departamento Jurídico del Minsal. El DEIS fija la exigencia para la **tributación** (envío) de datos al Ministerio (p. 22).
  - "El modelamiento de los datos de los sistemas de información deberá permitir la continuidad, renovación y especificar con qué versión del estándar se está trabajando" (p. 22). Para el RCE, esto significa que los catálogos deben guardar su versión.
- **Por qué le importa al RCE:** la norma define los datos mínimos y las listas de valores que debe usar cualquier sistema que capture datos de pacientes, profesionales y establecimientos, y que después informe al Minsal (REM, interoperabilidad, HCC). Diseñar con ella desde el MVP evita tener que homologar datos después.
- **Vocabulario en CSV/XLSX:** la norma publica las tablas en `https://deis.minsal.cl/VocabularioNorma820.xlsx` (p. 26). Las listas largas se remiten a ese archivo.

## 2. Cómo leer las tablas de la norma

Cada dato tiene: **Definición**, **Alcance**, **Estructura** y **Fuente** (p. 24). Además hay dos tablas: una de **categorías** (Código + Glosa) y otra de **estructura** (variable, campo, tipo, largo, exigencia y a veces observación).

### Términos de las tablas (p. 25)

| Término | Significado según la norma |
|---|---|
| Código | Identificador del dato. |
| Variable | Nombre en lenguaje natural del dato que se registra o comparte. |
| Glosa | Nombre del "Dato" (texto de la categoría). |
| Campo | Nombre que se usa para intercambiar, extraer e interoperar. Va en **PascalCase** (p. ej. `PrimerApellido`). |
| Tipo | Característica del dato: numérico, texto, alfanumérico, etc. |
| Largo | Número de caracteres del Código o de la Glosa. |
| Exigencia | Condición de presencia del dato en un formulario o sistema. |
| Observación | Detalle de especificidad del dato. |
| Concepto | Significado de las categorías de un dato. |

### Obligatoriedad de las variables (p. 26)

| Marcador | Definición textual de la norma |
|---|---|
| **Obligatoria** | "Cuando la variable debe estar presente siempre en el registro de salud." |
| **Opcional** | "Es cuando la variable está presente pero su registro no es obligatorio." |
| **Condicional** | "Cuando la presencia de la variable está supeditada a una característica o condición dada, clínica o administrativa de la persona, prestador individual o institucional." |

Notas de lectura:
- En muchas estructuras el **campo apunta a la Glosa** (`SexobiologicoGlosa`, `EstadoCivilGlosa`) aunque la lista tenga código numérico. Para el RCE conviene guardar el **código** y la versión del catálogo, y derivar la glosa.
- Las fechas se definen como "Numérico 10" o "Texto 10" con formato `DD-MM-AAAA`. Es un formato de **intercambio y presentación**. En la base del RCE las fechas van como tipo fecha (UTC para instantes, según el CLAUDE.md del proyecto).
- Hay diferencias entre la tabla resumen de cada capítulo y la tabla de detalle de cada variable. Cuando ocurre, se indica abajo y se da preferencia a la tabla de detalle.

## 3. Capítulo I — Marco conceptual (resumen) (p. 27-41)

- Fundamento legal: Ley 19.937 de Autoridad Sanitaria (art. 5) y DS 136/2004, Reglamento Orgánico del Minsal (art. 27): a la Subsecretaría de Salud Pública le corresponde proponer los estándares de datos, códigos y clasificaciones del sector y velar por su cumplimiento (p. 27-28).
- **Modelo conceptual** (diagrama, p. 31):
  - Ámbitos: contenido/definiciones y herramientas TIC para su uso.
  - Componentes: definiciones, alcances y relaciones de los datos; maestros o índices de personas, prestadores e instalaciones; interoperabilidad.
  - Productos: FIC (Familia de Clasificaciones Internacionales), Norma N° 820, terminologías y maestros.
  - Resultados: información para HSE, HCC (Historia Clínica Compartida), gestión clínica y políticas públicas.
- **Clasificación de estándares** (p. 32-37):
  - a) General: información **administrativa** (identificación de usuarios, prestadores individuales e institucionales, fiscalizadores e instalaciones) e información **clínica** (diagnósticos, procedimientos, laboratorio, medicamentos e insumos).
  - b) Específica por tipo:
    - identificación;
    - comunicaciones (TCP/IP, HTTP, XML, SOAP, Web Services);
    - seguridad (XMLDSIG/PKI, Kerberos, SAML, SSL, HL7 RBAC);
    - mensajería (HL7, DICOM, NCPDP, IEEE/ASTM);
    - representación de datos clínicos (CIE-10, SNOMED CT, LOINC, CIE-O, CIAP, NANDA/NIC/NOC);
    - calidad (acreditación de prestadores, Ley 19.966);
    - internacionales (ISO).
  - c) Lista de sistemas de codificación con su sigla HL7 (p. ej. `LN` LOINC, `CIAP2` CIAP2, y entradas para ICD-10 y SNOMED CT). La tabla está muy degradada en el escaneo: [ilegible en el original en parte, p. 35-37].
- **Tipos de dato según la Ley 19.628** (p. 38): caduco, estadístico, de carácter personal y **sensible**. Los estados de salud son datos sensibles.
- **Mantención** (p. 39): servidor terminológico (información clínica mapeada a clasificaciones estadísticas), maestro de personas, maestro de prestadores (institucionales e individuales, públicos y privados) y maestro de instalaciones.

## 4. Capítulo II — Estándares de datos de la persona (p. 42-93)

"Persona" es la persona natural que solicita y recibe atención (paciente, usuario, beneficiario, afiliado, cotizante). Si es un acompañante, se registra su relación o parentesco con quien solicita la atención (p. 42).

### Tabla resumen del capítulo (p. 43-48)

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Nombres | `Nombre` | Texto | 150 | Obligatorio al menos un nombre |
| Primer Apellido | `PrimerApellido` | Texto | 120 | Obligatorio |
| Segundo Apellido | `SegundoApellido` | Texto | 120 | Obligatorio en quienes poseen dos apellidos (la tabla de detalle dice "Opcional") |
| Nombre Social | `NombreSocial` | Texto | 120 | (sin exigencia en el resumen; "Opcional" en el detalle) |
| RUN | `Run` | Numérico | 9 | Enteros, máximo 99999999 (el detalle dice 999999999) |
| Dígito Verificador | `Digito Verificador` | Alfanumérico | 1 | 0-9 y K mayúscula o minúscula |
| Pasaporte | `TipoIdentificadorCodigo` = 3 (sic, ver nota) | Alfanumérico | 100 | — |
| Folio Comprobante de Parto | `TipoIdentificadorCodigo` = 3 | Numérico | 9 | — |
| Identificador FONASA | `TipoIdentificadorCodigo` = 6 | Numérico | 9 | Enteros, máximo 999999999 |
| N° documento país de origen | `TipoIdentificadorCodigo` = 5 | Alfanumérico | 100 | — |
| Fecha de Nacimiento | `FechaNacimiento` | Numérico | 10 | Obligatorio, DD-MM-AAAA |
| Edad (años/meses/días/horas/minutos) | `EdadAño`, `EdadMes`, `EdadDia`, `EdadHora`, `EdadMinuto` | — | rango | ver 2.2 |
| Sexo Biológico | `SexobiologicoGlosa` | Texto | 15 | Obligatorio |
| Identidad de Género | `GeneroGlosa` | Texto | 25 | Obligatorio |
| Nacionalidad | `NacionalidadCodigo` | Numérico | — | Obligatorio (códigos de países) |
| País de Origen | `PaisOrigenCodigo` | Numérico | — | Obligatorio (códigos de países) |
| Estado Civil | `EstadoCivilGlosa` | Numérico | — | Obligatorio |
| Pertenencia a pueblo indígena | `PuebloIndigenaPertenencia` | Texto | 2 | Obligatorio, SI/NO |
| Pueblo indígena u originario | `PuebloIndigenaCodigoGlosa` | Texto | 25 | Obligatorio si la pertenencia es SI |
| Pertenencia pueblo afrochileno | `PuebloAfroPertenenciaGlos` | Texto | 2 | Obligatorio, SI/NO |
| Religión | `ReligionCultosGlosa` | Texto | 30 | Opcional |
| Situación de discapacidad | `DiscapacidadSituacion` | Texto | 2 | Obligatorio, SI/NO |
| Nivel de instrucción | `NivelInstruccionGlosa` | Texto | 50 | Obligatorio |
| Condición de actividad | `CondActividadGlosa` | Texto | 150 | Opcional |
| Ocupaciones | `OcupacionGlosa` | Texto | 300 | Opcional |
| Ocupaciones detalladas | `OcupacionDetalladaGlosa` | Texto | 300 | Opcional |
| Previsión | `PrevisionGlosa` | Texto | 150 | Obligatoria |
| Región | `RegionCodigo` | Numérico | 2 | Lista de regiones |
| Provincia | `ProvinciaCodigo` | Numérico | 2 | Lista de provincias |
| Comuna | `ComunaCodigo` | Texto | 5 | Lista de comunas |
| Límite urbano censal | `LimiteUrbanoCensalCodigo` | Texto | 2 | Lista |
| Vía de la dirección | `ViadeDireccionCodigo` | Texto | 2 | Lista de vías |
| Nombre de la vía | `NombredeVia` | Texto | 100 | — |
| Número de dirección | `NumerodeDireccion` | Numérico | 10 | — |
| Complemento dirección | `ComplementoDireccion` | Texto | 100 | — |
| Teléfono móvil / fijo / otro | `TelefonoMovil`, `TelefonoFijo`, `TelefonoOtro` | Numérico | 9 | Obligatorio al menos 1 teléfono |
| Tipo otro teléfono | `TipoOtroTelefonoCodigo` | Numérico | — | Opcional |
| Correo electrónico | `CorreoElectronico` | Alfanumérico | 50 | Opcional, formato texto@dominio |

### 1. Identificación de la persona

#### 1.1 Nombre (p. 49-50)
- **Definición:** el nombre legal inscrito en el Registro Civil. Se compone de primer apellido, segundo apellido y uno o dos nombres propios. Es el que se registra en los trámites legales.
- **Nombre social:** en personas trans o de género no conforme, cuando el nombre social no coincide con la cédula, se usa el nombre social en el trato, la ficha clínica, el brazalete, las recetas, las interconsultas, etc. (Circular N° 34 de 13-09-2011). Se guarda junto al legal para contactos futuros.
- **Alcance:** los cambios de nombre aprobados por el Registro Civil se reconocen en la Red. El nombre legal se contrasta con el documento de identidad.

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Nombres | `Nombre` | Texto | 150 | Obligatorio |
| Primer Apellido | `PrimerApellido` | Texto | 120 | Obligatorio |
| Segundo Apellido | `SegundoApellido` | Texto | 120 | Opcional |
| Nombre Social | `NombreSocial` | Texto | 120 | Opcional |

- **Validación:** debe aceptar mayúsculas y minúsculas, vocales con o sin tilde, apóstrofo y diéresis. "Pueden ser varios nombres, al menos uno es obligatorio."
- **Fuente:** SRCeI; Circular 21 de la Superintendencia de Salud.

#### 1.2 Número de identificación (p. 51)
Se usa el RUN en todas las personas que lo tienen. Solo si no lo tienen se usa "otro número de identificación". La lista `TipoIdentificador` se basa en la tabla HL7 0203 (v2.5.1), adaptada por el DEIS en 2020.

##### 1.2.1 Rol Único Nacional (RUN) (p. 51-53)
- **Definición:** número único, irrepetible e intransferible de todo chileno, resida o no en Chile. También lo reciben los extranjeros con visa distinta a la de turismo.
- **Recién nacidos sin inscripción:** se identifican con el RUN del progenitor o con el Folio del Comprobante de Parto, **solo en forma transitoria**. El dato se actualiza al inscribir.
- **Alcance:** cada vez que se usan datos nominales es obligatorio registrar algún identificador. En casos excepcionales (p. ej. partos múltiples) se sugiere registrar más de uno: RUN de la madre o progenitor más el folio.
- El RUN se puede validar contra la cédula de identidad, el pasaporte chileno, la licencia de conducir u otro documento que lo consigne.

| TipoIdentificadorCodigo | TipoIdentificadorGlosa |
|---|---|
| 1 | RUN |
| 2 | RUN Materno/progenitor(a) |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| RUN | `Run` | Numérico | 9 | Obligatorio cuando se usa este documento. Entero, incluido el cero, máximo 999999999 |
| Dígito Verificador | `Digito Verificador` | Alfanumérico | 1 | Número (incluido 0) o letra K, mayúscula o minúscula |

- **Fuente:** SRCeI. El RUN y el DV van en **dos campos** en la norma. En el RCE se usa el value object `Run`, que valida el DV y guarda el valor normalizado.

##### 1.2.2 Otro número de documento de identificación (p. 54-56)
Solo cuando la persona **no tiene RUN**. Se elige **una sola** categoría, siguiendo el orden de la tabla.

| TipoIdentificadorCodigo | TipoIdentificadorGlosa |
|---|---|
| 3 | Número de Folio del Comprobante de Parto |
| 4 | Número de Pasaporte |
| 5 | Número de Documento identificador país de origen |
| 6 | Número de Identificador FONASA |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Número de otro documento de identificación | `OtraIdentificacion` | Alfanumérico | 100 | Obligatorio cuando se usa este documento |

Notas:
- El comprobante de parto lo extiende un médico o matrona.
- La vigencia del pasaporte no es barrera de acceso. Se registra el número del documento vigente, que puede cambiar al renovarlo.
- Documentos de país de origen: DNI (Argentina, España, Perú), cédula de identidad (Bolivia, Costa Rica, Ecuador, Nicaragua, Uruguay, Venezuela), carta de identidad o registro general (Brasil), cédula de ciudadanía (Colombia), tarjeta de identidad (Honduras), otros.
- El identificador FONASA es un número **provisorio con estructura de RUN**, para extranjeros cotizantes con visa en trámite o indocumentados. Se debe vincular después con el documento definitivo para mantener la trazabilidad.
- **Fuentes:** registros nacionales de personas de cada país; HL7 0203; UNAIDS 2014.

### 2. Datos demográficos

#### 2.1 Fecha de nacimiento (p. 57)
- **Definición:** el momento del nacimiento, expresado en día, mes y año (consta en el comprobante de parto).

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Fecha de Nacimiento | `FechaNacimiento` | Numérico | 10 | Obligatorio. Formato DD-MM-AAAA |

- **Fuente:** SRCeI.

#### 2.2 Edad (p. 58)
- **Definición:** el tiempo entre el nacimiento y el hecho que se registra. **En sistemas, se calcula automáticamente** a partir de la fecha de nacimiento y la fecha del evento. En menores de un año se usan horas, días y meses según corresponda.

| Variable | Campo | Rango | Exigencia |
|---|---|---|---|
| Años | `EdadAño` | 0 en adelante | Obligatorio (resto de grupos etarios; en el resumen, "mayor a 1 año") |
| Meses | `EdadMes` | 0-11 meses | Lactantes |
| Días | `EdadDia` | 0-29 días | Recién nacido |
| Horas | `EdadHora` | 0-23 horas | Recién nacido |
| Minutos | `EdadMinuto` | 1-59 min | Recién nacido |

#### 2.3 Sexo biológico (p. 59-61)
- **Definición:** el sexo asignado al nacer según criterios biológicos. Lo registra quien atiende el parto, por observación y no por autodeclaración. "Intersexual" **no** es un tercer sexo. "Desconocido" se usa cuando un trauma, lesión o quemadura impide determinarlo.

| SexobiologicoCodigo | SexobiologicoGlosa | Concepto |
|---|---|---|
| 1 | Hombre | Al nacer se observa pene, escroto y/o testículos. |
| 2 | Mujer | Al nacer se observa vulva u orificio vaginal. |
| 3 | Intersexual | Variaciones anatómicas genitales que no permiten clasificar en forma dicotómica. No es un tercer sexo. |
| 93 | No informado | Casos en que el Registro Civil cambió el sexo registral. **En estos casos es obligatorio capturar la identidad de género.** |
| 99 | Desconocido | No es posible determinarlo al momento de la atención. |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Sexo Biológico | `SexobiologicoGlosa` | Texto | 15 | Obligatorio en el registro de la identificación de las personas |

- **Fuentes:** SRCeI, INE, Unidad de Género Minsal 2021, ONU.

#### 2.4 Identidad de género (p. 62-64)
- **Definición:** la vivencia interna e individual del género (Principios de Yogyakarta). La Ley 21.120 la define como la convicción personal e interna de ser hombre o mujer. Es independiente del sexo biológico.
- **Alcance:** es un dato de **autoidentificación**. El modelo de atención debe asegurar privacidad y confidencialidad mediante un protocolo local.

| GeneroCodigo | GeneroGlosa | Concepto |
|---|---|---|
| 1 | Masculino | Sexo "Hombre" al nacer, se identifica como masculino ("cisgénero masculino"). |
| 2 | Femenina | Sexo "Mujer" al nacer, se identifica como femenina ("cisgénero femenina"). |
| 4 | Transgénero Masculino | Sexo "Mujer" al nacer, se identifica como masculino. |
| 5 | Transgénero Femenina | Sexo "Hombre" al nacer, se identifica como femenina. |
| 6 | No binarie | No se identifica con los géneros convencionales. |
| 7 | Otra | Otra identidad no descrita. |
| 8 | No Revelado | No desea revelar su identidad de género. |

(El código 3 no existe en la tabla; verificado en p. 63.)

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Identidad de Género | `GeneroGlosa` | Texto | 25 | Obligatorio en el registro de la identificación de las personas |

- **Fuentes:** CEPAL 1994, OMS, Unidad de Género Minsal 2020, Ley 21.120, HL7 FHIR ValueSet `gender-identity`, CIDH.

#### 2.5 Nacionalidad (p. 65-66)
- **Definición:** la condición de quien nació en una nación o fue naturalizado.
- **Alcance:** puede ser originaria o adquirida, y una persona puede tener varias. Se consigna la que la persona señale, pero **si una es la chilena, se consigna la chilena** mientras la persona esté en territorio nacional.
- **Valores:** códigos numéricos de países **ISO 3166-1** (p. ej. 004 Afganistán, 008 Albania, 012 Argelia, 016 Samoa Americana, 020 Andorra…). Lista completa en iso.org (no se transcribe aquí).

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Nacionalidad | `NacionalidadCodigo` | Numérico | — | Obligatorio en el registro de la identificación de las personas |

#### 2.6 País de origen (p. 67-68)
- **Definición:** el país de nacimiento (tierra natal). Es una variable constante y puede ser distinta de la nacionalidad.
- **Valores:** la misma lista ISO 3166-1 numérica.

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| País de Origen | `PaisOrigenCodigo` | Numérico | — | Obligatorio en el registro de la identificación de las personas |

### 3. Datos de situación de la persona

#### 3.1 Estado civil (p. 69-70)
- **Definición:** artículo 304 del Código Civil. Se incluye "Conviviente Civil" por la Ley 20.830 (Acuerdo de Unión Civil).

| EstadoCivilCodigo | EstadoCivilGlosa |
|---|---|
| 1 | Soltero(a) |
| 2 | Casado(a) |
| 3 | Viudo(a) |
| 4 | Divorciado(a) |
| 5 | Separado(a) Judicialmente |
| 6 | Conviviente Civil |
| 99 | Desconocido |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Estado Civil | `EstadoCivilGlosa` | Numérico | — | Obligatorio en el registro de la identificación de las personas |

#### 3.2 Pueblos indígenas u originarios (p. 71-73)
- **Reconocidos (Ley 19.253):** mapuche, aymara, rapanui, lickanantay, quechua, colla, diaguita, kawésqar, yagán y chango.
- **Captura:** pregunta abierta estandarizada. Primero "¿Se considera perteneciente a algún pueblo indígena u originario?" (Sí/No). Si responde Sí: "¿A cuál pertenece?". En "Otro" **no** se ingresan pueblos de otros países. Si la respuesta es "Otro", se deja un campo de texto libre.

| PuebloIndigenaPertenencia |
|---|
| Si |
| No |

| PuebloIndigenaCodigo | PuebloIndigenaGlosa |
|---|---|
| 1 | Mapuche |
| 2 | Aymara |
| 3 | Rapa Nui O Pascuense |
| 4 | Lickanantay |
| 5 | Quechua |
| 6 | Colla |
| 7 | Diaguita |
| 8 | Kawésqar |
| 9 | Yagán |
| 11 | Chango |
| 10 | Otro (Especificar) |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Pertenencia | `PuebloIndigenaPertenencia` | Texto | 2 | Obligatorio (SI/NO) — tabla resumen p. 46 |
| Pueblo indígena u originario | `PuebloIndigenaPertenenciaGlosa` (detalle) / `PuebloIndigenaCodigoGlosa` (resumen) | Texto | 25 | Obligatorio cuando la pertenencia es SI |

- **Fuentes:** Ley 19.253; Convenio 169 OIT; consulta indígena Minsal 2016.

#### 3.3 Pueblos tribales: afrochileno o afrodescendiente (p. 74-75)
- **Definición:** Ley 21.151 (2019) y Convenio 169 OIT. Es un dato de autoidentificación. Pregunta: "¿Se considera perteneciente al pueblo Afrochileno o Afrodescendiente?". Valores: **Si / No**.

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Afrochileno o Afrodescendiente | `PuebloAfroPertenenciaGlosa` | Texto | 2 | Obligatorio. Valores Si o No |

#### 3.4 Religión o culto declarado (p. 76-77)
- **Captura:** pregunta abierta ("¿Qué religión profesa?").

| ReligionCultosCodigo | ReligionCultosGlosa |
|---|---|
| 1 | Católica |
| 2 | Evangélica |
| 3 | Testigo de Jehová |
| 4 | Judaica |
| 5 | Mormón |
| … | lista completa en VocabularioNorma820.xlsx |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Religión | `ReligionCultosGlosa` | Texto | 30 | Opcional |

- **Fuente:** Censo 2002 (INE).

#### 3.5 Situación de discapacidad (p. 78-79)
- **Definición:** personas que, por sus condiciones de salud y al interactuar con barreras, ven restringida su participación. **No se debe exigir la acreditación de SENADIS.**
- Valores: **Si / No**.

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Situación de Discapacidad | `DiscapacidadSituacion` | Texto | 2 | Obligatorio. Valores SI o NO |

- Si la respuesta es "SI", en el contexto clínico que corresponda se aplica la **Valoración de Desempeño en Comunidad (IVADEC-CIF)**, que identifica el origen y el nivel:

| IvadecCodigo | IvadecGlosa (origen) |
|---|---|
| 1 | Origen Físico |
| 2 | Origen sensorial visual |
| 3 | Origen sensorial auditivo |
| 4 | Origen mental síquico |
| 5 | Origen mental intelectual |
| 6 | Origen múltiple |

| IvadecSubCodigo | Glosa (nivel) |
|---|---|
| 1 | Sin discapacidad |
| 2 | Discapacidad leve |
| 3 | Discapacidad moderada |
| 4 | Discapacidad severa |
| 5 | Discapacidad profunda |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Evaluación Valoración de Desempeño en Comunidad | `IvadecSubCodigo` | Texto | 50 | Obligatorio en la identificación de la persona |

- **Fuentes:** recomendaciones de lenguaje inclusivo; Manual REM 2021.

### 4. Datos de nivel de instrucción y trabajo

#### 4.1 Nivel de instrucción (p. 80-82)
- **Definición:** el último año aprobado en el ciclo de educación más avanzado que la persona declara. La norma recomienda adaptarse a la CINE 2011 (UNESCO). Para calcularlo se necesita el nivel más alto **y** el último curso aprobado.
- **Último curso aprobado** (`UltimoCursoAprobado`): valores 0, 1, 2, 3, 4, 5, 6, 7, 8.

| NivelInstruccionCodigo | NivelInstruccionGlosa |
|---|---|
| 1 | Preescolar |
| 2 | Especial o Diferencial |
| 3 | Básica o Primaria |
| 4 | Media o Secundaria |
| 5 | Educación Superior |
| 6 | Sin Instrucción |
| 97 | No Recuerda |
| 98 | No Responde |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Nivel de Instrucción | `NivelInstruccionGlosa` | Texto | 50 | Obligatorio en la identificación de la persona |

- **Fuente:** INE 2017. La norma anuncia "dos conceptos" que no desarrolla (p. 81).

#### 4.2 Condición de la actividad (p. 83)

| CondActividadCodigo | CondActividadGlosa | Descripción |
|---|---|---|
| 1 | Inactivos | Dueña de casa, estudiante, pensionado, rentista, persona privada de libertad |
| 2 | Activos | Trabaja (obrero, operario, vendedor, arquitecto, médico, auxiliar…) |
| 3 | Cesante o Temporalmente sin Trabajo | Cesante, temporalmente sin trabajo |
| 99 | Ignorado | No se sabe |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Condición de Actividad | `CondActividadGlosa` | Texto | 150 | Opcional |

- **Fuente:** INE 2019.

#### 4.3 Ocupaciones (p. 84-85)
- **Definición:** un conjunto de empleos con tareas muy similares (CIUO-08, OIT). Se registra **sin importar** la condición de actividad. Ver el anexo "Clasificador Chileno de Ocupaciones CIUO 08.CL" (p. 188-196).

| OcupacionCodigo | OcupacionGlosa (grandes grupos) |
|---|---|
| 1 | Miembro del Poder Ejecutivo de los Cuerpos Legislativos, Personal Directivo de la Administración Pública y de Empresa. |
| 2 | Profesionales Científicos o Intelectuales. |
| 3 | Técnicos y Profesionales de Nivel Medio |
| 4 | Empleados de Oficina |
| 5 | Trabajadores de los Servicios y Vendedores de Comercio y Mercado |
| 6 | Agricultores y Trabajadores Calificados Agropecuarios y Pesqueros |
| 7 | Oficiales, Operarios y Artesanos de Artes Mecánicas y de Otros Oficios |
| 8 | Operadores de Instalaciones y Máquinas y Montadoras |
| 9 | Trabajadores No Calificados |
| 10 | Fuerzas Armadas |
| 99 | Desconocido |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Ocupaciones | `OcupacionGlosa` | Texto | 300 | Opcional |

#### 4.4 Ocupaciones detalladas (p. 86)
- **Valores:** códigos CIUO-08 de 4 dígitos (p. ej. 0110 Oficiales de las Fuerzas Armadas, 0210 Suboficiales, 0310 Otros miembros de las FF.AA., 1111 Miembros del poder ejecutivo y legislativo, 1112 Personal directivo de la administración pública, 1113 Jefes de comunidades indígenas…). Lista completa en VocabularioNorma820.xlsx; no se transcribe aquí.

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Ocupaciones Detalladas | `OcupacionDetalladaGlosa` | Texto | 300 | Opcional |

### 5. Previsión (p. 87-89)
- **Definición:** el sistema de aseguramiento de salud de la persona: FONASA (público), ISAPRE (privado), y los regímenes de FF.AA. (Ley 19.465), CAPREDENA y DIPRECA (Carabineros, PDI y Gendarmería). Los accidentes laborales y las enfermedades profesionales quedan fuera (Ley 16.744, mutualidades e ISL).

| PrevisionCodigo | PrevisionGlosa | Concepto |
|---|---|---|
| 1 | FONASA | Fondo Nacional de Salud |
| 2 | ISAPRE | Instituciones de Salud Previsional |
| 3 | CAPREDENA | Caja de Previsión de la Defensa Nacional |
| 4 | DIPRECA | Dirección de Previsión de Carabineros, PDI y Gendarmería |
| 5 | SISA | Sistema de Salud de las Fuerzas Armadas (Ejército, Armada y Fuerza Aérea) |
| 96 | NINGUNA* | *Solo para personas sin ningún tipo de previsión de salud en el país, que pagan por la atención |
| 99 | DESCONOCIDO | |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Previsión | `PrevisionGlosa` | Texto | 150 | La captura es obligatoria en la identificación de la persona |

### 6. Datos de dirección (p. 90)
Siguen el estándar del Capítulo IV. Exigencia respecto a la identificación de la persona:

| Variable | Exigencia |
|---|---|
| Región | Opcional |
| Provincia | Opcional |
| Comuna | Obligatorio |
| Área Urbano-Rural | Opcional |
| Tipo de vía | Opcional |
| Nombre de la vía | Obligatorio |
| Número | Obligatorio |
| Complemento Dirección | Condicional |
| Resto dirección | Condicional |

### 7. Datos de contacto (p. 91-93)

#### 7.1 Teléfonos de contacto

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Teléfono Móvil | `TelefonoMovil` | Numérico | 9 | Obligatorio |
| Teléfono fijo | `TelefonoFijo` | Numérico | 9 | Opcional |
| Otro Teléfono de contacto | `TelefonoOtro` | Numérico | 9 | Opcional |
| Tipo Otro Teléfono de contacto | `TipoOtroTelefonoCodigo` | Numérico | — | Opcional |

En la tabla resumen (p. 47) la exigencia es "Obligatorio al menos 1 teléfono" para los tres números.

| TipoOtroTelefonoCodigo | TipoOtroTelefonoGlosa |
|---|---|
| 1 | Del Trabajo de la persona |
| 2 | Pareja o cónyuge |
| 3 | Familiar |
| 4 | Tutor(a) o Cuidador(a) |
| 5 | Vecino(a) |
| 98 | No responde |

- **Fuente:** numeración LDN 2014 (MTT). Largo 9, sin código de país.

#### 7.2 Correo electrónico

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Correo electrónico | `CorreoElectronico` | Alfanumérico | 50 | Opcional. Formato texto@dominio |

- **Fuentes:** IETF; RAE.

## 5. Capítulo III — Estándares de datos del sistema de salud (p. 94-156)

### Tabla resumen del capítulo (p. 94-97)

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| **Sistema de Salud** | | | | |
| Tipo Sistema de Salud | `TipoSistemaSaludGlosa` | Texto | 50 | Opcional |
| Modalidad de Atención FONASA | `ModalidadAtencionFonasaGlosa` | Texto | 50 | Condicional (solo previsión FONASA) |
| Tramo FONASA | `TramoFonasaGlosa` | Texto | 20 | Condicional (solo FONASA) |
| Arancel FONASA | `ArancelFonasaGlosa` | Texto | 100 | Condicional (solo FONASA) |
| Otras Leyes de salud | `LeyPrevisionalGlosa` | Texto | 300 | Opcional |
| SEREMIs | `SeremiSaludGlosa` | Texto | 100 | Obligatorio para establecimientos NO pertenecientes al SNSS |
| Servicios de Salud | `ServicioDeSaludGlosa` | Texto | 100 | Obligatorio para establecimientos pertenecientes al SNSS |
| Tipo de Prestadores de Salud | `TipoPrestadorGlosa` | Texto | 200 | Opcional |
| **Prestadores Individuales (PI)** | | | | |
| Fecha de Nacimiento (PI) | `FechaNacimiento` | Numérico | 10 | Obligatorio, DD-MM-AAAA |
| RUN | `Run` | Numérico | 9 | Obligatorio; máx. 999999999 |
| Dígito Verificador | `Digito Verificador` | Alfanumérico | 1 | 0-9, K |
| Nacionalidad (PI) | `NacionalidadCodigo` | Numérico | — | Obligatorio (códigos países) |
| Región (PI) | `RegionCodigo` | Numérico | 2 | Lista |
| Provincia (PI) | `ProvinciaCodigo` | Numérico | 2 | Lista |
| Comuna (PI) | `ComunaCodigo` | Texto | 5 | Lista |
| Vía de la Dirección | `ViadeDireccionCodigo` | Texto | 2 | Lista |
| Nombre de la Vía | `NombredeVia` | Texto | 100 | — |
| Teléfono Fijo / Móvil | `TelefonoFijo` / `TelefonoMovil` | Numérico | 9 | — |
| Título profesional | `TituloProfesionalPrestadorGlosa` | Texto | 100 | Obligatorio en la SIS |
| Institución que emite el Título | `InstitucionEmisoraTituloGlosa` | Texto | 150 | Obligatorio |
| Fecha emisión del Título | `FechaEmisionTitulo` | Numérico | 10 | Condicional (con título profesional) |
| Mención | `MenciónGlosa` | Texto | 150 | Condicional (profesiones con mención) |
| Nombre de Institución extranjera | `InstitucionExtranjeraGlosa` | Texto | 100 | Condicional (título obtenido fuera de Chile) |
| N° Registro Nacional de Prestadores Individuales | `NumeroRegistroGlosa` | Numérico | (Opcional) | Opcional |
| Fecha de registro prestador | `FechaRegistroPrestador` | Fecha | 10 | DD-MM-AAAA |
| Especialidad y subespecialidad médica | `EspecialidadMedicaCódigo` | Texto | 2 | Lista |
| Especialidad y subespecialidad odontológica | `EspecialidadOdontologicCódigo` | Texto | 2 | Lista |
| Especialidades bioquímicas | `EspecialidadBioquimicaGlosa` | Texto | 100 | Obligatorio |
| Especialidades farmacéuticas | `EspecialidadFarmaGlosa` | Texto | 100 | Obligatorio |
| Institución que emite la Especialidad | `InstitucionEmisoraEspecialidadGlosa` | Texto | 150 | Obligatorio |
| Fecha de Emisión Especialidad | `FechaEmisionEspecialidadr` (sic) | Fecha | 10 | DD-MM-AAAA |
| **Prestador Institucional** | | | | |
| Establecimientos según tipo de atención | `TipoAtencionEstabGlosa` | Texto | 100 | Obligatorio |
| Nivel de Atención | `NivelAtencionEstabglosa` | Texto | 100 | Obligatorio |
| Nivel de Complejidad | `NivelComplejidadEstabGlosa` | Texto | 50 | Obligatorio |
| Tipo Estab. de Atención Primaria | `TipoEstabPubAtenPrimGlosa` | Texto | 100 | Obligatorio |
| Tipo Estab. de Atención de Especialidades | `TipoEstabPubEspecGlosa` | Texto | 100 | Obligatorio |
| Tipo Estab. de Atención de Urgencia | `TipoEstabPubUrgenciaGlosa` | Texto | 50 | Obligatorio (sin sección de detalle; ver preguntas abiertas) |
| Tipo Estab. Hospitalarios | `TipoEstabHospitalarioGlosa` | Texto | 50 | Opcional |
| Código de Establecimientos | `EstablecimientoCodigo` | Numérico | — | Obligatorio |
| Pertenencia | `PertenenciaGlosa` | Texto | 30 | Obligatorio para establecimientos asistenciales |
| Región (PI) / Provincia (PI) | `RegionCodigo` / `ProvinciaCodigo` | Numérico | 2 | Opcional |
| Comuna (PI) | `ComunaCodigo` | Texto | 5 | Obligatorio |
| Vía de la Dirección | `ViadeDireccionCodigo` | Texto | 2 | Obligatorio |
| Nombre de la Vía | `NombredeVia` | Texto | 100 | — |
| Teléfono Fijo | `TelefonoFijo` | Numérico | 9 | — |
| Fecha Inicio de funcionamiento | `FechaCeseFuncionamientoEstab` (sic) | Texto | 10 | Obligatoria |
| Fecha Cese de funcionamiento | `FechaCeseFuncionamientoEstab` | Texto | 10 | Obligatoria |
| Longitud / Latitud | `Longitud` / `Latitud` | Numérico | — | Obligatoria |
| Código Postal | `CodigoPostal` | Numérico | — | Opcional |

En el bloque Prestador Institucional, "(PI)" aparece así en el original aunque se refiera al establecimiento.

### 1. Sistema de salud (p. 98-99)
- **Definición:** el Estado protege el acceso libre e igualitario a las acciones de salud y garantiza el derecho a elegir el sistema, estatal o privado (Constitución, art. 19 N° 9; DFL 1/2005; DS 136/2004).

| TipoSistemaSaludCodigo | TipoSistemaSaludGlosa |
|---|---|
| 1 | Público |
| 2 | Privado |
| 3 | Fuerzas Armadas y de Orden y Seguridad Pública |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Tipo Sistema de Salud | `TipoSistemaSaludGlosa` | Texto | 50 | Opcional |

### 2. Sistema público

#### 2.1 Beneficiario FONASA (p. 100)
La afiliación a FONASA permite atenderse gratis en la red pública (MAI) o en la red privada en convenio con copago (MLE). Es solo una definición, sin tabla.

#### 2.2 Modalidad de atención (p. 100, 102)
- MAI: hospitales y centros APS de la red pública. MLE: prestadores privados adscritos, ambulatorios u hospitalarios. Los beneficiarios indigentes y los no cotizantes **no** pueden usar MLE.

| ModalidadAtencionFonasaCodigo | ModalidadAtencionFonasaGlosa |
|---|---|
| 1 | MAI Modalidad de Atención Institucional |
| 2 | MLE Modalidad de Atención Libre Elección |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Modalidad de Atención FONASA | `ModalidadAtencionFonasaGlosa` | Texto | 50 | Condicional (solo previsión FONASA) |

#### 2.3 Tramos FONASA (p. 101)
- **Definición:** clasificación según el ingreso mensual. Desde septiembre de 2022 el copago es cero en la red pública. El tramo A queda excluido de MLE.

| TramoFonasaCodigo | TramoFonasaGlosa |
|---|---|
| A | TRAMO A |
| B | TRAMO B |
| C | TRAMO C |
| D | TRAMO D |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Tramo FONASA | `TramoFonasaGlosa` | Texto | 20 | Condicional (solo previsión FONASA) |

#### 2.4 Arancel FONASA (p. 103-104)
- **Definición:** la valorización de las prestaciones, cada una con un código único. Hay dos aranceles: MAI y MLE. Los establecimientos del SNSS usan obligatoriamente los valores MAI. Los **privados usan los mismos códigos**, pero fijan precios de mercado (MLE).
- **Valores:** códigos de 7 dígitos (p. ej. `0101101` Consulta o control médico integral en atención primaria; `0101102` Consulta o control médico integral en especialidades (Hosp. Mediana complejidad)). Lista completa en www.fonasa.cl; no se transcribe aquí.

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Arancel FONASA | `ArancelFonasaGlosa` | Texto | 100 | Condicional (solo previsión FONASA) |

Nota: el código tiene cero a la izquierda. Se guarda como texto, no como número.

#### 2.5 Otras leyes previsionales de salud (p. 105-106)
Leyes que modifican o eximen copagos por causas específicas. Se suman al seguro general (FONASA o ISAPRE).

| LeyPrevisionCodigo | LeyPrevisionalGlosa |
|---|---|
| 1 | Ley N°18.490: Accidentes de Transporte |
| 2 | Ley N° 16.744: Accidentes del Trabajo y Enfermedades Profesionales |
| 3 | Ley N° 16.744: Accidente Escolar |
| 4 | Ley N° 19.650/99 de Urgencia |
| 5 | Ley 19.992 PRAIS |
| 6 | Ley N° 19.966 Régimen General de Garantías en Salud GES |
| 7 | Ley N°20.850 Ricarte Soto |
| 8 | Ley N° 21.030 de Despenalización de la Interrupción Voluntaria del Embarazo en Tres Causales. |
| 96 | Ninguna |
| 97 | No Recuerda |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Leyes Previsionales | `LeyPrevisionalGlosa` | Texto | 300 | Opcional cuando corresponde, según la lista de valores |

### 3. Estructura organizacional del sistema de salud

#### 3.1 Secretaría Regional Ministerial de Salud (SEREMI) (p. 107-108)
- **Alcance:** el código coincide con el de la región, que prevalece.

| SeremiSaludCodigo | SeremiSaludGlosa |
|---|---|
| 15 | SEREMI De Arica y Parinacota |
| 1 | SEREMI De Tarapacá |
| 2 | SEREMI De Antofagasta |
| 3 | SEREMI De Atacama |
| 4 | SEREMI De Coquimbo |
| 5 | SEREMI De Valparaíso |
| 13 | SEREMI Metropolitana de Santiago |
| 6 | SEREMI Del Libertador Gral. B. O'Higgins |
| 7 | SEREMI Del Maule |
| 16 | SEREMI Del Ñuble |
| 8 | SEREMI Del Biobío |
| 9 | SEREMI De La Araucanía |
| 14 | SEREMI De Los Ríos |
| 10 | SEREMI De Los Lagos |
| 11 | SEREMI De Aisén del General Carlos Ibáñez del Campo |
| 12 | SEREMI De Magallanes y la Antártica Chilena |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| SEREMIs | `SeremiSaludGlosa` | Texto | 100 | Obligatorio para todos los establecimientos NO pertenecientes al SNSS |

#### 3.2 Servicios de Salud (p. 109-111)

| ServicioDeSaludCodigo | ServicioDeSaludGlosa |
|---|---|
| 1 | Servicio de Salud Arica |
| 2 | Servicio de Salud Iquique |
| 3 | Servicio de Salud Antofagasta |
| 4 | Servicio de Salud Atacama |
| 5 | Servicio de Salud Coquimbo |
| 6 | Servicio de Salud Valparaíso San Antonio |
| 7 | Servicio de Salud Viña del Mar Quillota |
| 8 | Servicio de Salud Aconcagua |
| 9 | Servicio de Salud Metropolitano Norte |
| 10 | Servicio de Salud Metropolitano Occidente |
| 11 | Servicio de Salud Metropolitano Central |
| 12 | Servicio de Salud Metropolitano Oriente |
| 13 | Servicio de Salud Metropolitano Sur |
| 14 | Servicio de Salud Metropolitano Sur Oriente |
| 15 | Servicio de Salud Del Libertador B. O'Higgins |
| 16 | Servicio de Salud Del Maule |
| 17 | Servicio de Salud Ñuble |
| 18 | Servicio de Salud Concepción |
| 19 | Servicio de Salud Talcahuano |
| 20 | Servicio de Salud Biobío |
| 21 | Servicio de Salud Araucanía Sur |
| 22 | Servicio de Salud Valdivia |
| 23 | Servicio de Salud Osorno |
| 24 | Servicio de Salud Del Reloncaví |
| 25 | Servicio de Salud Aisén |
| 26 | Servicio de Salud Magallanes |
| 28 | Servicio de Salud Arauco |
| 29 | Servicio de Salud Araucanía Norte |
| 33 | Servicio de Salud Chiloé |
| 95 | No Aplica (se usa para Hospital Digital) |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Servicios de Salud | `ServicioDeSaludGlosa` | Texto | 100 | Obligatorio (resumen: para establecimientos pertenecientes al SNSS) |

### 4. Prestadores de salud (p. 112-113)
- **Definición:** toda persona natural o jurídica, establecimiento o institución autorizada para otorgar prestaciones de salud. La norma advierte que jurídicamente se confunde "prestador institucional" con "establecimiento".

| TipoPrestadorCodigo | TipoPrestadorGlosa |
|---|---|
| 1 | Individuales |
| 2 | Institucionales |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Tipo de Prestadores de Salud | `TipoPrestadorGlosa` | Texto | 200 | Opcional en la identificación de prestadores |

#### 4.1 Prestadores individuales (p. 114-116)
- **Definición:** personas naturales con una profesión legalmente habilitada (art. 8 del DS N° [ilegible en el original, p. 114] de 19 de enero de 2007 del Minsal). Incluye a los socios de personas jurídicas que prestan servicios personales de salud. Su identidad y habilitación se verifican en el **Registro Nacional de Prestadores Individuales (RNPI)** de la Superintendencia de Salud, que es público.

##### 4.1.1 Identificación del prestador individual (p. 115-116)
Usa los mismos datos de la "Persona". CMBD:

| Categoría | Dato | Exigencia |
|---|---|---|
| Identificación | Nombre, Primer Apellido, Segundo Apellido, RUN | Obligatorio |
| Demográficos | Fecha de Nacimiento, Sexo, País de Origen, Nacionalidad | Obligatorio |
| Ubicación | Región | Obligatorio |
| | Provincia | Opcional |
| | Comuna, Nombre de la Vía, Número de Dirección | Obligatorio |
| | Complemento Dirección | Condicional |
| Contacto | Teléfono Fijo | Opcional |
| | Teléfono Móvil | Obligatorio |
| | Correo Electrónico | Opcional |

##### 4.1.2 Título profesional (p. 117-118)

| TituloProfesionalPrestadorCodigo | TituloProfesionalPrestadorGlosa |
|---|---|
| 1 | Médico(a) Cirujano(a) |
| 2 | Cirujano(a) Dentista |
| 3 | Enfermeras (os) |
| 4 | Enfermeras (os) Matronas (es) |
| 5 | Matronas (es) |
| 6 | Tecnólogos(as) Médicos(as) |
| 7 | Psicólogas (os) |
| … | lista completa en VocabularioNorma820.xlsx |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Título profesional | `TituloProfesionalPrestadorGlosa` | Texto | 100 | "Obligatorio en la SIS" (sigla no definida en la norma) |

- **Fuente:** RNPI (rnpi.superdesalud.gob.cl).

##### 4.1.3 Mención (p. 119)

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Mención | `MenciónGlosa` | Texto | 150 | Condicional (profesiones que pueden optar a menciones) |

No trae lista de valores.

##### 4.1.4 Institución que emite el título (p. 120)
Si el título es extranjero, se registra la institución chilena que lo **validó**.

| InstitucionEmisoraTituloCodigo | InstitucionEmisoraTituloGlosa |
|---|---|
| 1 | Pontificia Universidad Católica De Valparaíso |
| 2 | Universidad Academia De Humanismo Cristiano |
| 3 | Universidad Adventista De Chile |
| 4 | Universidad Adolfo Ibáñez |
| … | lista completa en VocabularioNorma820.xlsx |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Institución que emite el Título | `InstitucionEmisoraTituloGlosa` | Texto | 150 | Obligatorio |

##### 4.1.5 Número de Registro Nacional (p. 121)
El número que asigna la Superintendencia al inscribir al prestador en el RNPI. Es correlativo y su largo puede variar.

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| N° de Registro Nacional de Prestadores Individuales | `NumeroRegistroGlosa` | Numérico | — | Opcional |

##### 4.1.6 Fecha de emisión del título (p. 122)

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Fecha emisión del Título | `FechaEmisionTitulo` | Numérico | 10 | Condicional (personas con título profesional). DD-MM-AAAA |

##### 4.1.7 Institución extranjera donde estudió (p. 123)

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Nombre de Institución extranjera | `InstitucionExtranjeraGlosa` | Texto | 100 | Condicional (título obtenido fuera de Chile) |

#### 4.2 Especialidades y subespecialidades (p. 124-131)
La certificación de especialidad reconoce que un prestador domina un ámbito específico (Reglamento de Certificación de las Especialidades de los Prestadores Individuales, Decreto Supremo Minsal-Mineduc [número ilegible en el original, p. 125]; y RNPI).

##### 4.2.1 Médicas (p. 124-125)

| EspecialidadMedicaCodigo | EspecialidadMedicaGlosa |
|---|---|
| 1 | Anatomía Patológica |
| 2 | Anestesiología |
| 3 | Cardiología |
| 4 | Cirugía General |
| 5 | Cirugía de Cabeza, Cuello y Maxilofacial |
| 6 | Cirugía Cardiovascular |
| 7 | Cirugía de Tórax |
| … | lista completa en VocabularioNorma820.xlsx |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Especialidades y Subespecialidades Médicas | `EspecialidadMedicaGlosa` | Texto | 100 | Obligatorio en el registro de identificación del prestador individual |

##### 4.2.2 Odontológicas (p. 126-127)

| EspecialidadOdontologicasCodigo | EspecialidadOdontologicasGlosa |
|---|---|
| 1 | Cirugía y Traumatología Buco Maxilofacial |
| 2 | Cirugía Bucal |
| 3 | Endodoncia |
| 4 | Imagenología Oral y Maxilofacial |
| 5 | Implantología Buco Maxilofacial |
| 6 | Odontología legal |
| 7 | Odontopediatría |
| 8 | Ortodoncia y Ortopedia Dento Máxilo Facial |
| 9 | Patología Oral |
| 10 | Periodoncia |
| 11 | Rehabilitación Oral |
| 12 | Salud Pública |
| 13 | Somato-Prótesis |
| 14 | Trastornos Temporomandibulares Y Dolor Orofacial |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Especialidades Odontológicas | `EspecialidadOdontologicaGlosa` | Texto | 100 | Obligatorio en el registro de identificación del prestador individual |

##### 4.2.3 Farmacéuticas o químico-farmacéuticas (p. 128)

| EspecialidadFarmaCodigo | EspecialidadFarmaGlosa |
|---|---|
| 1 | Farmacia Clínica |
| 2 | Farmacia Hospitalaria |
| 3 | Laboratorio Clínico |
| 4 | Salud Pública |
| 5 | Laboratorio Forense |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Especialidades Farmacéuticas o Químicas Farmacéuticas | `EspecialidadFarmaGlosa` | Texto | 100 | Obligatorio en el registro de identificación del prestador individual |

##### 4.2.4 Bioquímicas (p. 129)

| EspecialidadBioquimicaCodigo | EspecialidadBioquimicaGlosa |
|---|---|
| 1 | Laboratorio Clínico |
| 2 | Laboratorio Forense |
| 3 | Salud Pública |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Especialidades y Subespecialidades Bioquímicas | `EspecialidadBioquimicaGlosa` | Texto | 100 | Obligatorio en el registro de identificación del prestador individual |

##### 4.2.5 Institución que emite la especialidad (p. 130)
Si el profesional no tiene especialidad, se registra "No aplica". La lista es la misma de instituciones (1 Pontificia Universidad Católica De Valparaíso, 2 Universidad Academia De Humanismo Cristiano, 3 Universidad Adventista De Chile, 4 Universidad Adolfo Ibáñez, …; completa en VocabularioNorma820.xlsx).

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Institución que emite la especialidad | `InstituciónEmiteTítuloGlosa` (detalle) / `InstitucionEmisoraEspecialidadGlosa` (resumen) | Texto | 150 | Obligatorio en el registro del prestador individual |

##### 4.2.6 Fecha de emisión de la especialidad (p. 131)
La fecha en que la institución formadora emite el título de especialidad. La definición incluye además una frase sobrante sobre la fecha en que la Superintendencia "elimina del Registro" (probable error de edición).

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Fecha de Emisión de la Especialidad | `FechaEmisionTituloProfesional` (detalle) / `FechaEmisionEspecialidadr` (resumen) | Numérico | — | Obligatoria. DD-MM-AAAA |

#### 4.3 Prestadores institucionales (p. 132-151)
- **Definición:** entidades públicas o privadas que realizan acciones de promoción, protección, recuperación y rehabilitación. Necesitan la autorización sanitaria de la SEREMI. Los públicos pueden depender o no de un Servicio de Salud.

##### 4.3.1 Establecimiento según tipo de atención (p. 133)
Atención cerrada: internación con ocupación de cama. Atención abierta: sin pernoctación.

| TipoAtencionEstabCodigo | TipoAtencionEstabGlosa |
|---|---|
| 1 | Atención Abierta (Ambulatoria) |
| 2 | Atención Cerrada (Hospitalaria) |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Establecimientos según tipo de atención | `TipoAtencionEstabGlosa` | Texto | 100 | Obligatorio en la identificación del Prestador Institucional |

##### 4.3.2 Según nivel de atención (p. 134-135)
- **Primario:** CESFAM, consultorios, CECOSF, postas rurales, SAPU.
- **Secundario:** CRS, CDT y otros centros de especialidades.
- **Terciario:** hospitales de alta complejidad y urgencias.

| NivelAtencionEstabCodigo | NivelAtencionEstabglosa |
|---|---|
| 1 | Nivel Primario |
| 2 | Nivel Secundario |
| 3 | Nivel Terciario |
| 95 | No aplica |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Nivel de Atención | `NivelAtencionEstabglosa` | Texto | 100 | Obligatorio en la identificación del Prestador Institucional |

##### 4.3.3 Según nivel de complejidad (p. 136-138)
- Hospitales del SNSS: según el Reglamento Orgánico de los Servicios de Salud (DS 140).
- Atención cerrada (Reglamento de acreditación, DS 15/2007, art. 32-33):
  - alta complejidad: tiene unidad de pacientes críticos;
  - mediana: tiene pabellón para cirugía general;
  - baja: el resto.
- Privados y no SNSS (p. ej. municipales):
  - alta complejidad: cirugías ambulatorias como colecistectomía, ginecológica laparoscópica, artroscopia, mastectomía parcial o acceso vascular complejo;
  - mediana: procedimientos invasivos con sedación moderada a profunda;
  - baja: el resto.
- La complejidad de los hospitales la define DIGERA y la de APS la define DIVAP.

| NivelComplejidadEstabCodigo | NivelComplejidadEstabGlosa |
|---|---|
| 1 | Baja Complejidad |
| 2 | Mediana Complejidad |
| 3 | Alta Complejidad |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Nivel de Complejidad | `NivelComplejidadEstabGlosa` | Texto | 50 | Obligatorio en la identificación del Prestador Institucional |

##### 4.3.4 Establecimientos públicos (p. 139)
Son autorizados por la SEREMI y acreditados por la Superintendencia. La tipología va de lo general a lo particular.

| TipoEstablecimientosPúblicosCodigo | TipoEstablecimientosPúblicosGlosa |
|---|---|
| 1 | Establecimientos Públicos de Atención Primaria de Salud (APS) |
| 2 | Establecimientos Públicos de Atención de Especialidades |
| 3 | Establecimientos Hospitalarios |

Sin tabla de estructura.

##### 4.3.5 Públicos de atención primaria (p. 140-142)

| TipoEstabPubAtenPrimCodigo | TipoEstabPubAtenPrimGlosa |
|---|---|
| CGU | Consultorio General Urbano |
| CGR | Consultorio General Rural |
| PSR | Posta de Salud Rural |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Tipo de Establecimientos de Atención Primaria | `TipoEstabPubAtenPrimGlosa` | Texto | 100 | Obligatorio en la identificación del Prestador Institucional |

Conceptos:
- PSR: población rural, con un TENS residente y comunicación radial.
- CGR: localidades de 2.000 a 5.000 habitantes, área de hasta 20.000.
- CGU: localidades de más de 20.000 habitantes, área idealmente de hasta 50.000.

El CESFAM, el CECOSF y el SAPU se mencionan en 4.3.2 pero **no** tienen código en esta lista.

##### 4.3.6 Públicos de especialidades (p. 143-146)
La especialidad no forma parte del tipo de establecimiento: corresponde a las "Unidades de Atención".

| TipoEstabPubEspecCodigo | TipoEstabPubEspecGlosa |
|---|---|
| CRS | Centros de Referencia de Salud (CRS) |
| CDT | Centros de Diagnóstico Terapéutico (CDT) |
| CAE | Consultorios Adosados de Especialidad (CAE) |
| COSAM | Consultorio de Atención Mental (COSAM) |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Tipo Establecimientos de Atención de Especialidades | `TipoEstabPubEspecGlosa` | Texto | 100 (resumen p. 96) | Obligatorio en la identificación del Prestador Institucional |

##### 4.3.8 Públicos hospitalarios (p. 146-147)
La numeración salta de 4.3.6 a 4.3.8, y no hay 4.3.7 ni 4.3.9. Hogares de ancianos, asilos, casas de reposo y hospicios **no** son establecimientos hospitalarios.

| TipoEstabHospitalarioCodigo | TipoEstabHospitalarioGlosa |
|---|---|
| 1 | Hospitales |
| 2 | Institutos |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Tipo de Establecimientos Hospitalarios | `TipoEstabHospitalarioGlosa` | Texto | 50 | Opcional ("en la identificación de la persona", sic) |

##### 4.3.10 Establecimientos de salud privados (p. 148-151)

| OtroTipoEstabAsistencialesCodigo | OtroTipoEstabAsistencialesGlosa |
|---|---|
| 1 | Hospitales |
| 2 | Clínicas |
| 3 | Centros de Salud Privados |
| 4 | Laboratorios Clínicos |
| 5 | Centros de Diálisis |
| 6 | Clínicas Dentales |
| 7 | Vacunatorios |
| 8 | Centro CONIN |
| 9 | Centros de Diagnóstico y Tratamiento Privados |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Establecimientos de Salud Privada | `OtroTipoEstabAsistencialesGlosa` | Texto | 100 | Obligatorio |

Conceptos clave para el MVP privado ambulatorio:
- **Hospital privado:** tiene servicios clínicos y unidades de apoyo diferenciados.
- **Clínica:** atención médica y de enfermería continua, sin servicios ni unidades diferenciados.
- **Centro de Salud Privado:** ambulatorio, con prestaciones médicas o de otros profesionales de nivel primario y secundario, con o sin apoyo. Excluye farmacias y clínicas dentales.
- **Clínica dental:** ambulatoria, exclusivamente odontológica.
- **CDT privado:** centros de especialidades específicas (oftalmológicos, quimioterapia, radioterapia, dermatológicos, oncológicos, etc.).

#### 4.4 Identificación del establecimiento (p. 152-156)

##### 4.4.1 Código del establecimiento (p. 152)
- **Definición:** la denominación única de cada establecimiento asistencial. La asigna el **DEIS** después de presentar la resolución de autorización sanitaria. Las modificaciones se rigen por la Circular N° 2 de 2008.
- **Valores:** p. ej. 126303 Centro de Salud Familiar Dr. Thomas Fenton; 126219 Centro de Rehabilitación Club de Leones Cruz del Sur; 126300 Centro de Salud Familiar Dr. Mateo Bencur; 126800 SAPU Dr. Mateo Bencur; 200589 Clínica Manantiales.
- **Lista completa:** `https://deis.minsal.cl/#datosabiertos`, "Listado de Establecimientos", **actualizada semanalmente**. No se transcribe aquí.

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Código de Establecimientos | `EstablecimientoCodigo` | Numérico | — | Obligatorio |

##### 4.4.2 Pertenencia al SNSS (p. 153)
Los públicos pertenecen al SNSS. Los privados, los de FF.AA. y los de Orden y Seguridad no pertenecen.

| TipoPertinenciaEstabCodigo | TipoPertinenciaEstabGlosa |
|---|---|
| 1 | Perteneciente al Sistema Nacional de Servicios de Salud |
| 2 | No Perteneciente al Sistema Nacional de Servicios de Salud |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Pertenencia | `PertenenciaGlosa` | Texto | 30 | Obligatorio para establecimientos asistenciales |

##### 4.4.3 Dirección (p. 154)
Mismas exigencias que la dirección de la persona:
- Región, Provincia, Área urbano-rural y Tipo de vía: opcionales.
- Comuna, Nombre de vía y Número: obligatorios.
- Complemento y Resto: condicionales.

Incluye coordenadas geográficas (Capítulo IV).

##### 4.4.4 Fecha de inicio de funcionamiento (p. 155)
Es la fecha de inicio de la atención, según la ficha de autorización sanitaria (o la de demostración sanitaria si el establecimiento es anterior a 2006).

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Fecha Inicio de funcionamiento | `FechaCeseFuncionamientoEstab` (sic; probable error por `FechaInicioFuncionamientoEstab`) | Texto | 10 | Obligatoria. DD-MM-AAAA |

##### 4.4.5 Fecha de cese de funcionamiento (p. 156)
La informa el prestador a la SEREMI.

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Fecha Cese de funcionamiento | `FechaCeseFuncionamientoEstab` | Texto | 10 | Obligatoria. DD-MM-AAAA |

## 6. Capítulo IV — Estándares de información geoespacial en salud (p. 157-173)

### 1. Datos de ubicación (p. 157)
- **Domicilio:** el lugar donde la persona vive o se hospeda habitualmente y donde se la puede ubicar.
- En pueblos indígenas rurales, el domicilio suele asociarse a una "comunidad indígena" (Ley 19.253).
- Región, provincia y comuna siguen la **División Político Administrativa (DPA)** y los Códigos Únicos Territoriales del Decreto Exento N° 1.115 del Ministerio del Interior (DO 21-09-2018).

#### 1.1 Región (p. 158-159)

| RegionCodigo | RegionGlosa |
|---|---|
| 1 | Tarapacá |
| 2 | Antofagasta |
| 3 | Atacama |
| 4 | Coquimbo |
| 5 | Valparaíso |
| 6 | Libertador General Bernardo O'Higgins |
| 7 | Maule |
| 8 | Biobío |
| 9 | La Araucanía |
| 10 | Los Lagos |
| 11 | Aysén del General Carlos Ibáñez del Campo |
| 12 | Magallanes y de la Antártica Chilena |
| 13 | Metropolitana de Santiago |
| 14 | Los Ríos |
| 15 | Arica y Parinacota |
| 16 | Ñuble |
| 99 | Desconocido |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Región | `RegionGlosa` | Texto | 40 | Obligatorio (en la persona y el establecimiento: Opcional; ver nota) |

- **Fuentes:** Ley 21.033 (creación de la Región de Ñuble); Decreto Exento N° 204/2018 del Ministerio del Interior.

#### 1.2 Provincia (p. 160)
- **Valores:** códigos DPA de 3 dígitos con cero a la izquierda (011 Iquique, 014 Tamarugal, 021 Antofagasta, 022 El Loa, 023 Tocopilla, …). Lista completa en VocabularioNorma820.xlsx.

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Provincia | `ProvinciaGlosa` | Texto | 50 | Obligatorio |

#### 1.3 Comuna (p. 161)
- **Valores:** códigos DPA de 4-5 dígitos (1101 Iquique, 1102 Camiña, 1103 Colchane, 1104 Huara, …). Lista completa en VocabularioNorma820.xlsx (la norma no indica el número de entradas).

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Comuna | `ComunaGlosa` | Texto | 100 | Obligatorio |

#### 1.4 Límite urbano censal (p. 162-163)
- **Definición:** la línea imaginaria que separa el área urbana de la rural, con fines censales (criterio del INE). Las comunas netamente urbanas o rurales se codifican automáticamente. Las mixtas requieren analizar la dirección.

| AreaUrbanoCensalCodigo | AreaUrbanoCensalGlosa |
|---|---|
| 1 | Urbano |
| 2 | Rural |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Límite Urbano Censal | `LimiteUrbanoCensalGlosa` | Texto | 10 | Opcional |

#### 1.5 Dirección (p. 164)
- **Definición:** información estructurada que determina inequívocamente un objeto. "Alias de dirección" es otra dirección que identifica el mismo objeto.

| Variable | Exigencia |
|---|---|
| Tipo de vía | Opcional |
| Nombre de vía | Obligatorio |
| Número | Obligatorio |
| Anexo | Opcional |
| Comuna | Obligatorio |

##### 1.5.1 Tipo de vía (p. 165)

| TipoViaCodigo | TipoViaGlosa |
|---|---|
| 1 | Calle |
| 2 | Avenida |
| 3 | Pasaje |
| 4 | Camino |
| 5 | Carretera |
| 6 | Callejón |
| 7 | Paseo |
| 8 | Escalera |
| 9 | Otro |
| 10 | Rotonda |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Tipo de vía | `TipoViaGlosa` | Texto | 10 | Opcional |

- **Fuente:** Recomendación de Direcciones, Grupo de Trabajo Ejes Viales SNIT-IDE Chile.

##### 1.5.2 Nombre de la vía (p. 166)
El nombre asignado por el municipio.

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Nombre de la vía | `NombreViaDireccion` | Texto | 100 | Obligatoria |

##### 1.5.3 Número (p. 167)

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Número | `NumeroDireccion` | Numérico | 10 | Obligatoria. **Cuando no existe número se registra S/N** |

##### 1.5.4 Anexo de dirección (p. 168)
Detalle adicional: block, departamento, villa, población, etc.

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Anexo de dirección | `AnexoDireccion` | Texto | 200 | Opcional |

#### 1.6 Sistema de referencia (p. 169-170)
El marco de referencia oficial es **SIRGAS-CHILE** (densificación del ITRF; adoptado por el IGM en 2002). Todo dato geoespacial se registra con su sistema de referencia, como texto. En salud el valor debe ser **"SIRGASCHILE"**.

#### 1.7 Sistema de coordenadas (p. 171-172)
Se sugieren coordenadas **geográficas en formato decimal**. Como Chile está en el hemisferio sur y al oeste de Greenwich, **la latitud y la longitud llevan siempre signo negativo**.

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Latitud | `LatitudGlosa` (p. 171) | Numérico | — | Obligatorio |
| Longitud | `LongitudGlosa` (p. 171) | Numérico | 20 (p. 172) | Obligatorio |

La tabla de p. 172 pone "Numérico" en la columna Campo, y el largo de la latitud no está indicado.

#### 1.8 Código postal (p. 173)
Código de 7 dígitos de Correos de Chile. Se asigna por dirección, igual para todo un lado de cuadra.

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Código Postal | `CodigoPostal` | Numérico | (7 según la definición) | Opcional |

## 7. Capítulo V — Estándares de proceso asistencial (p. 174-187)

Los datos clínicos básicos son síntomas y signos, diagnóstico, exámenes complementarios e indicaciones de tratamiento. Registrarlos sin estándar es una barrera para la calidad y la continuidad. Los registros, en papel o electrónicos, deben cumplir criterios de calidad: oportunidad y completitud (p. 174).

### 2. Anamnesis (p. 175-176)
- **Definición:** el motivo de consulta y la historia clínica, con fines diagnósticos.

| AnamnesisCodigo | AnamnesisGlosa |
|---|---|
| 1 | Próxima |
| 2 | Remota |

- **2.1 Anamnesis próxima:** relato cronológico y ordenado de lo que le ocurre al paciente, incluidos los síntomas que ya no están presentes. Si hay varios problemas, va un párrafo por problema. Debe ser objetiva.
- **2.2 Anamnesis remota:** antecedentes ordenados así:
  - mórbidos (médicos, quirúrgicos, traumatismos);
  - gineco-obstétricos;
  - hábitos (tabaco, alcohol, drogas);
  - uso de medicamentos;
  - **alergias**;
  - sociales y personales;
  - familiares;
  - inmunizaciones;
  - transfusiones.
- Sin tabla de estructura (campo, tipo, largo). **Fuente:** manuales de semiología de la PUC; Goic, Chamorro y Reyes.

### 3. Diagnóstico clínico (p. 177)
- **Definición:** el diagnóstico presuntivo inicial o confirmado que hace el profesional según el relato, el examen físico y los exámenes complementarios.

| Variable | Campo | Largo | Exigencia |
|---|---|---|---|
| Término Clínico ingresado por el médico | `DiagnosticoTextoLibre` | 200 | Obligatorio |
| Tipo codificación diagnóstica | `TipoCodDiagnosticaCodigo` | Lista desplegable | 1.- CIE10; 2.- CIE11 |
| Código | `Codigo` | 200 | En CIE10 debe tener 4 caracteres; en CIE11, mínimo 4 caracteres |
| URL Entidad | `URLEntidad` | 300 | Obligatorio solo para CIE11 |

| DiagnosticoCodigo | DiagnosticoGlosa |
|---|---|
| 1 | Presuntivo (Hipótesis diagnóstica) |
| 2 | Confirmado |

Reglas para el RCE:
- Se guarda siempre el **texto libre del médico** junto al código.
- El sistema de codificación se indica explícitamente: CIE-10 o CIE-11.
- En CIE-11 también se guarda la URI de la entidad (`URLEntidad`).
- Cada diagnóstico se marca como presuntivo o confirmado.
- "4 caracteres" en CIE-10 equivale a la subcategoría (p. ej. `J45.9` sin punto = `J459`). La norma no dice si se guarda con punto o sin él.

### 4. Datos de consultas médicas

#### 4.1 Consulta médica general (p. 178-179)
- **Definición:** atención del médico en un lugar destinado a ello, con anamnesis, examen físico, hipótesis diagnóstica y, si corresponde, prescripción de exámenes o terapia. Incluye procedimientos mínimos: presión arterial, otoscopia, peso y talla.

| ConsultaMedGeneralCodigo | ConsultaMedGeneralGLosa |
|---|---|
| 1 | Primera consulta médica general o consulta nueva |
| 2 | Consulta médica de control o seguimiento |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Consulta Médica General | `ConsultaMedGeneralGLosa` | Texto | 50 | Obligatoria en APS |

- **Fuentes:** Manual Series REM (DEIS); Orientaciones técnicas COMGES.

#### 4.2 Consulta médica de especialidad (p. 180-181)
- **Consulta nueva:** la primera atención generada por un episodio. Cada primera atención originada por una interconsulta es nueva para esa especialidad, aunque el paciente ya haya sido atendido antes en ella.
- **Control o seguimiento:** las atenciones siguientes por el mismo problema.

| ConsultaMedEspecialidadCodigo | ConsultaMedEspecialidadGlosa |
|---|---|
| 1 | Primera consulta médica de especialidad o consulta nueva de especialidad |
| 2 | Consulta médica de control o seguimiento de especialidad |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Tipo de Consulta Médica de Especialidad | `ConsultaMedEspecialidadGlosa` | Texto | 100 | Obligatorio para consultas médicas |

#### 4.3 Consulta odontológica de especialidad (p. 182)

| ConsultaOdontoEspecialidadCodigo | ConsultaOdontoEspecialidadGlosa |
|---|---|
| 1 | Primera consulta odontológica de especialidad o consulta nueva. |
| 2 | Consulta odontológica de especialidad de control o seguimiento |

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Consulta Odontológica de Especialidad | `ConsultaOdontoloEspecialidadGlosa` | Texto | 100 | Obligatorio en establecimientos que otorguen atención odontológica |

#### 4.4 Consulta de urgencia (p. 183)
- **Emergencia:** atención no postergable según criterio clínico del equipo médico. **Urgencia:** la que la población percibe como impostergable.

| Variable | Campo | Tipo | Largo | Exigencia |
|---|---|---|---|---|
| Consulta de Urgencia | `ConsultaUrgenciaGlosa` | Texto | 300 | Obligatorio en establecimientos de urgencia |

No trae lista de valores.

### 5. Definiciones de otros tipos de consulta (p. 184-187)
Son solo definiciones, sin código ni estructura:
- **5.1 Consulta médica abreviada:** repetir receta, leer exámenes o revisar informes. No se considera consulta porque en general no hay interacción directa.
- **5.2 Consultoría en salud:** actividad conjunta entre el equipo de especialidad y el de APS, con o sin el usuario presente.
- **5.3 Consulta enfermera(o)** (nivel secundario: valoración, diagnóstico y plan de cuidados).
- **5.4 Consulta matrón(a)** (incluye ginecología, ITS, salud sexual y reproductiva, VIH, infertilidad).
- **5.5 Consulta nueva o ingreso nutricional** y **5.6 Consulta control nutricional**.
- **5.7** Psicólogo(a), **5.8** Fonoaudiólogo(a), **5.9** Terapeuta ocupacional.
- **5.10** Tecnólogo médico de oftalmología y otorrinolaringología.
- **5.11** Asistente social.
- **5.12 Teleconsulta de especialidad:** consulta a distancia mediante TIC, homologable a la presencial.

Fuente: Orientaciones para la Planificación y Programación en Red 2022.

### Anexo — Clasificador Chileno de Ocupaciones CIUO 08.CL (p. 188-196)
- Marco conceptual de la CIUO-08 (OIT): empleo, ocupación y competencias.
- Cuatro niveles de competencias, con su relación con la CINE 97 y el sistema educativo chileno.
- No trae la lista de códigos: remite a "Códigos de Ocupación CIUO-08".

## 8. Implicancias para el RCE

### Mapeo variable → módulo

| Variable de la norma | Dónde vive en el RCE | Exigencia NT820 | Nota |
|---|---|---|---|
| Nombres, apellidos (1.1) | `Clinical/Patient` | Obligatorio (2° apellido condicional/opcional) | Unicode completo (tildes, ñ, apóstrofo, diéresis). Largos 150/120/120. |
| Nombre social (1.1) | `Clinical/Patient` | Opcional | Se muestra en el trato, la ficha, el brazalete, las recetas y las interconsultas. El nombre legal se mantiene para documentos legales. |
| RUN + DV (1.2.1) | `Clinical/Patient` (VO `Run` compartido) | Obligatorio si lo tiene | Usar el value object `Run`: valida el DV y lo guarda normalizado. Tipo 1 = propio, 2 = RUN del progenitor (RN transitorio). |
| Otro identificador (1.2.2) | `Clinical/Patient` | Obligatorio si no tiene RUN | Tipo 3-6 + número alfanumérico (100). Permitir varios identificadores y vincular el provisorio (folio parto, FONASA) con el definitivo sin borrar. |
| Fecha de nacimiento (2.1) | `Clinical/Patient` | Obligatorio | Tipo fecha. DD-MM-AAAA solo al mostrar o exportar. |
| Edad (2.2) | `Clinical/Encounter` (calculada) | Obligatorio (calculada) | No se guarda como dato de paciente. Se calcula a la fecha del evento (años, o meses/días/horas/minutos en menores de 1 año). |
| Sexo biológico (2.3) | `Clinical/Patient` | Obligatorio | Catálogo 1/2/3/93/99. Si es 93, la identidad de género es obligatoria. |
| Identidad de género (2.4) | `Clinical/Patient` | Obligatorio | Catálogo 1,2,4-8. Autodeclarado, dato sensible: controlar acceso y auditar. |
| Nacionalidad, país de origen (2.5-2.6) | `Clinical/Patient` + `Platform/Catalog` | Obligatorio | Catálogo ISO 3166-1 numérico, versionado. Regla: si una nacionalidad es la chilena, se consigna la chilena. |
| Estado civil (3.1) | `Clinical/Patient` | Obligatorio | Catálogo 1-6, 99. |
| Pueblo indígena (3.2) | `Clinical/Patient` | Obligatorio (pertenencia); condicional (pueblo) | Dos preguntas + texto libre si "Otro" (10). Dato sensible. |
| Afrodescendiente (3.3) | `Clinical/Patient` | Obligatorio | Sí/No. |
| Religión (3.4) | `Clinical/Patient` | Opcional | Catálogo del xlsx DEIS. Dato sensible. |
| Discapacidad + IVADEC (3.5) | `Clinical/Patient` (sí/no); IVADEC en `Clinical/Encounter` o `PublicSector/PrimaryCare` | Obligatorio | IVADEC se aplica "en el contexto clínico que corresponda", así que puede no ir en el MVP privado. |
| Nivel de instrucción (4.1) | `Clinical/Patient` | Obligatorio | Nivel + último curso aprobado (0-8). |
| Condición de actividad, ocupación (4.2-4.4) | `Clinical/Patient` + `Platform/Catalog` (CIUO-08) | Opcional | Catálogo CIUO-08 del xlsx DEIS. |
| Previsión (5) | `Clinical/Patient` (cobertura vigente) | Obligatorio | Catálogo 1-5, 96, 99. Guardar con fecha de vigencia: cambia en el tiempo. La caja (`Ambulatory/Billing`) lo consulta vía `Application`. |
| Tramo FONASA, modalidad MAI/MLE (III 2.2-2.3) | `Clinical/Patient` (cobertura) / `Ambulatory/Billing` | Condicional (FONASA) | En el privado aplica MLE. El tramo A no tiene MLE. |
| Arancel FONASA (III 2.4) | `Platform/Catalog` + `Ambulatory/Billing` | Condicional | Catálogo de prestaciones con código de 7 dígitos como **texto**. Precios privados en arancel propio del tenant. Dinero en enteros. |
| Leyes previsionales (III 2.5) | `Clinical/Encounter` (por atención) | Opcional | Ley de urgencia, GES, Ricarte Soto, accidente laboral/escolar/tránsito. Afecta la caja y GES. |
| Dirección persona (II 6, IV) | `Clinical/Patient` + `Platform/Catalog` (DPA) | Comuna, vía, número obligatorios | Catálogos región/provincia/comuna (DPA, Decreto 1.115/2018) versionados. Número admite "S/N". |
| Teléfonos, correo (II 7) | `Clinical/Patient` | Móvil obligatorio; correo opcional | 9 dígitos sin prefijo +56. Tipo de "otro teléfono" (1-5, 98). |
| Coordenadas, SIRGAS, código postal (IV 1.6-1.8) | `Platform/Tenancy` (sedes) / opcional en paciente | Obligatorio para el establecimiento | Lat/long decimales negativas, sistema "SIRGASCHILE". |
| SEREMI, Servicio de Salud (III 3) | `Platform/Catalog` + configuración de sede en `Platform/Tenancy` | Obligatorio según pertenencia al SNSS | Un privado no SNSS informa SEREMI. Servicio 95 = Hospital Digital. |
| Prestador institucional: código DEIS, tipo de atención, nivel, complejidad, tipo privado, pertenencia, fechas (III 4.3-4.4) | `Platform/Tenancy` (o un área Sites/Establecimientos dentro de Platform) | Obligatorio (la mayoría) | Un tenant puede tener varias sedes, cada una con su código DEIS. Catálogo de establecimientos DEIS (semanal) en `Platform/Catalog`. |
| Prestador individual: identificación, título, institución, RNPI, especialidades, mención (III 4.1-4.2) | `Platform/Identity` (registro de profesionales) | Mixto (ver tablas) | Validar contra el RNPI de la Superintendencia. Catálogos de títulos, especialidades e instituciones desde el xlsx DEIS. La especialidad se exige para emitir recetas e interconsultas. |
| Tipo de prestador (III 4) | `Platform/Identity` / `Platform/Tenancy` | Opcional | 1 individual, 2 institucional. |
| Anamnesis próxima y remota (V 2) | `Clinical/Encounter` (próxima) y `Clinical/Patient` o antecedentes (remota) | Sin exigencia definida | La remota incluye **alergias**, que el RCE debe mostrar como alerta visible y que alimentan `DecisionSupport`. |
| Diagnóstico clínico (V 3) | `Clinical/Diagnosis` + `Platform/Catalog` (CIE-10, CIE-11) | Texto libre y código obligatorios | Guardar texto libre del médico, sistema (CIE10/CIE11), código, URI CIE-11 y estado presuntivo/confirmado. Corrección = enmienda. |
| Tipo de consulta (V 4.1-4.3) | `Clinical/Encounter` (y `Ambulatory/Scheduling` para agendar) | Obligatorio en APS / consultas médicas / odontología | Nueva vs. control. Una interconsulta crea una consulta nueva para esa especialidad. |
| Consulta de urgencia (V 4.4) | `Inpatient/Emergency` | Obligatorio en urgencia | Fuera del MVP. |
| Otros tipos de consulta, teleconsulta (V 5) | `Clinical/Encounter` (tipo de atención por profesión) / `Ambulatory/Telemedicine` | — | Útil como catálogo de tipos de atención no médicas. La "consulta abreviada" no cuenta como consulta. |
| Variables REM (series, tipo consulta APS, IVADEC, COMGES) | `PublicSector/Statistics` | — | Se derivan de los datos de `Clinical` vía eventos o `Application`. No se duplican. |

### Recomendaciones transversales
- **Todos los catálogos** de la norma van en `Platform/Catalog` con versión y fecha de vigencia (la norma exige poder indicar con qué versión del estándar se trabajó, p. 22). Se cargan desde `VocabularioNorma820.xlsx` y desde las fuentes oficiales (ISO 3166, DPA, CIE-10/11, establecimientos DEIS, arancel FONASA).
- Guardar **código + versión de catálogo**, no la glosa. Los nombres de campo PascalCase de la norma sirven para exportar e interoperar (REM, FHIR).
- Los datos de pueblo indígena, identidad de género, religión, discapacidad y salud son **sensibles** (Ley 19.628, p. 38): van cifrados o con acceso restringido y se auditan.
- Las correcciones de identidad (cambio de nombre o sexo registral, paso de identificador provisorio a RUN) son enmiendas con historial, nunca sobrescritura.

### Preguntas abiertas y ambigüedades de la norma
1. **Estado de la norma:** la tabla de versiones dice que la 3.0 está "En tramitación" y que "sustituirá" a la Res. Exenta 643 (p. 16). Hay que confirmar si existe una resolución que la aprueba y su fecha de vigencia.
2. **Pasaporte:** en la tabla resumen aparece como `TipoIdentificadorCodigo` = 3 (p. 44). En el detalle, el pasaporte es 4 y el folio es 3 (p. 54). Usamos el detalle.
3. **Máximo del RUN:** el resumen dice 99999999 y el detalle 999999999 con largo 9 (p. 44 vs. p. 53). Definir si el RUN se valida por largo o por rango.
4. **Segundo apellido:** el resumen dice "Obligatorio en quienes poseen dos apellidos" y el detalle "Opcional" (p. 43 vs. p. 50).
5. **Campos que apuntan a la glosa:** muchos campos son `...Glosa` aunque la lista es codificada. `EstadoCivilGlosa` incluso es "Numérico". No está claro si se intercambia el código o el texto.
6. **Identidad de género:** no hay código 3. ¿Es una eliminación intencional? No reutilizarlo.
7. **Pueblos indígenas:** Chango tiene código 11 y "Otro" código 10 (p. 72). El campo tiene distinto nombre en el resumen y en el detalle.
8. **Religión, títulos, especialidades médicas, instituciones, provincias, comunas y ocupaciones detalladas** solo traen un extracto. La lista completa está en el xlsx del DEIS, que **no se revisó** para este documento.
9. **Región:** "Obligatorio" en el capítulo IV (p. 159), pero "Opcional" en la dirección de la persona y del establecimiento (p. 90, 154), y "Obligatorio" para el prestador individual (p. 116).
10. **Teléfonos:** el resumen exige "al menos 1 teléfono" (p. 47) y el detalle exige el móvil (p. 91).
11. **Número de dirección:** es Numérico 10 pero debe admitir "S/N" (p. 167).
12. **Coordenadas:** la tabla de p. 172 está mal construida: "Numérico" aparece en la columna Campo y el largo de la latitud no se indica.
13. **Fecha de inicio de funcionamiento:** usa el campo `FechaCeseFuncionamientoEstab` (p. 97, 155), probable error de copia. Además la fecha de cese figura como "obligatoria" aunque solo existe si el establecimiento cerró.
14. **Tipo de establecimiento de urgencia:** `TipoEstabPubUrgenciaGlosa` está en el resumen (p. 96) sin sección ni lista. Faltan 4.3.7 y 4.3.9. CESFAM, CECOSF y SAPU no tienen código en la lista de APS.
15. **Tipo de establecimiento hospitalario:** su exigencia dice "en la identificación de la persona" (p. 147), probable error.
16. **Fecha de emisión de la especialidad:** la definición mezcla una frase sobre la "eliminación del Registro" (p. 131), y el nombre del campo difiere entre el resumen y el detalle.
17. **"Obligatorio en la SIS"** (título profesional, p. 95, 118): la sigla no está definida en la norma.
18. **Diagnóstico CIE-10:** la norma pide "4 caracteres", pero no aclara si van con punto, ni qué hacer con códigos de 3 caracteres sin subcategoría (p. ej. categorías sin cuarto dígito). CIE-11 se admite como alternativa, mientras el CLAUDE.md del proyecto exige CIE-10. Decidir si se admite CIE-11 en el futuro.
19. **Nivel de instrucción:** la norma anuncia "dos conceptos" que no desarrolla (p. 81).
20. **Leyes previsionales:** la exigencia es "opcional cuando corresponde" (p. 106), pero GES y la Ley de Urgencia son operativamente críticas. Definir si el RCE las hace obligatorias por contexto.

## Fuente

Ministerio de Salud de Chile, Departamento de Estadísticas e Información de Salud (DEIS). *Norma Técnica 820: Estándares de Información de Salud*, versión 3.0, 12-01-2023, 198 páginas (PDF). El texto se obtuvo por OCR. Las tablas de códigos, los largos, los tipos y los marcadores de exigencia se verificaron contra las imágenes de las páginas citadas. La tabla de sistemas de codificación del Capítulo I (p. 35-37) no se verificó por su mala calidad. No se consultó el archivo `VocabularioNorma820.xlsx`.
