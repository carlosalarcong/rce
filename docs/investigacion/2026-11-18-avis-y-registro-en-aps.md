# Investigación: AVIS como competidor y cómo se registra hoy en APS

- **Fecha:** 2026-11-18
- **Estado:** borrador. Mezcla hechos verificados en la web con una conversación informal con un usuario. Cada sección dice de dónde sale lo que afirma.
- **Para qué sirve:** entender a la competencia (AVIS y Rayen) y dejar anotado lo que le duele a un usuario real de APS, para usarlo cuando se diseñe la capa pública (Fase 4) y para afinar desde ya los principios de diseño del MVP.
- **Qué no es:** no es una spec ni una decisión de arquitectura. Lo que aquí aparece como "candidato" se decide en una ADR, no en este documento.

## 1. Resumen

1. **AVIS y Rayen son la misma empresa.** Rayen Salud absorbió a Avis SpA y a Tecmédica SpA en septiembre de 2024. No hay dos competidores: hay uno, con varios productos.
2. **Ese mismo grupo vende Melisa**, su producto para clínicas y centros de salud privados. Es el segmento de nuestro MVP, así que la competencia directa de la Fase 1 también es Rayen.
3. Un usuario de la red APS de Rancagua (donde todos los Cesfam usan AVIS) cuenta que el sistema "funciona", pero que **no saben bien qué casillas llenar**, que el cierre de la ficha tiene demasiadas pestañas y campos, y que nadie los capacitó bien.
4. La consecuencia: **el REM no les cuadra**, y para cuadrarlo los encargados de programa llevan **tarjeteros en Excel**, por fuera del sistema. Registran dos veces.
5. Además, **las reglas del REM van cambiando**, y eso les complica cada cierre.
6. La oportunidad para el RCE no es "tener más funciones que AVIS". Es que el registro se entienda solo, que el dato quede bien a la primera y que el REM salga del mismo registro, sin Excel.

## 2. Quién es AVIS (hechos verificados)

### 2.1 Origen y dueños

| Dato | Detalle | Fuente |
|---|---|---|
| Creador | TecMédica, después llamada AVIS LATAM. Fundada en 2001, sede en Santiago, entre 51 y 200 empleados. | LinkedIn de AVIS LATAM |
| Fusión de Avis SpA | Junta de accionistas de Rayen del 27-09-2024: Rayen absorbe a Avis SpA, que se disuelve. Rayen se queda con sus activos y pasivos. | Extracto legal en vLex |
| Fusión de Tecmédica SpA | Junta del 30-09-2024: Rayen absorbe a Tecmédica SpA, que también se disuelve. | Extracto legal en vLex |
| Empresa que queda | Sociedad de Ingeniería en Informática Rayen Salud SpA, RUT 77.917.240-6. | vLex, RedConecta |
| Grupo controlador | Vela Software Group muestra a Rayen Salud y a AVIS en su portafolio de salud. Vela es filial de Constellation Software (Canadá, bolsa de Toronto), un grupo que compra empresas de software y las mantiene a largo plazo. | Sitio de Vela, IMAP, Diario Financiero |

**No verificado:** en qué fecha y de qué forma Vela compró Rayen y AVIS. Solo consta que hoy ambas están en su portafolio.

### 2.2 Los productos del grupo Rayen

| Nivel de atención | Producto | Para quién |
|---|---|---|
| Atención primaria (APS) | **RAYEN** | Cesfam y red municipal |
| Atención primaria (APS) | **AVIS** | Cesfam y red municipal |
| Hospitales | RAYEN Eloísa | Baja y media complejidad |
| Hospitales | SINASUITE | Media y alta complejidad |
| Clínicas y centros de salud | **Melisa** | Recintos de media y alta complejidad (sector privado) |
| Software especializado | Avis.Care | Seguimiento de pacientes crónicos |

Fuente: sitio de Rayen Salud (rayensalud.com) y nota de TrendTIC de agosto de 2026.

Dos productos de APS que hacen lo mismo (RAYEN y AVIS) dentro de una misma empresa es algo a mirar. Ver hipótesis en la sección 6.1.

### 2.3 Tamaño y presencia

- El gerente de negocios de Rayen dijo en septiembre de 2026 que 4 de cada 5 recintos operan con su tecnología de fichas clínicas. **Es una afirmación de la propia empresa**, no un dato independiente. (TXS+)
- Rayen implementó la interoperabilidad de interconsultas en 20 Servicios de Salud donde está presente alguno de sus registros clínicos. (TrendTIC, agosto 2026)
- TecMédica decía que con AVIS se hacen más de 25 millones de transacciones diarias. Dato del proveedor, sin fecha. (tecmedica.net)
- **Quillota:** los centros de la red municipal empezaron a usar un RCE nuevo a mediados de noviembre de 2023. La plataforma es de AVIS y el proceso es parte de la estrategia SIDRA, a través del Servicio de Salud Viña del Mar-Quillota. (La Región Hoy)
- **Rancagua:** todos los Cesfam trabajan con AVIS. (Dicho por el usuario entrevistado; no verificado por otra vía.)

### 2.4 Qué ofrece AVIS (según su propio material)

- Registro clínico electrónico, administración de establecimientos, y gestión de farmacia y bodega. Plataforma web y aplicación móvil.
- Calculadora gestacional y de riesgo cardiovascular.
- Generación de certificados y licencias médicas.
- Genograma integrado al RCE y módulo de salud familiar.
- Vademécum en línea (versiones para Chile, México, España y una LATAM), con alertas de interacción de medicamentos.
- Registro de alergias.
- Integración con laboratorios.
- Biblioteca de imágenes.
- Contacto con el paciente: toma y confirmación de citas por teléfono (IVR), reportes de contactabilidad y mensajería entre paciente y equipo de salud.

Fuentes: avislatam.com (AVIS Enterprise) y tecmedica.net. Es material comercial: dice qué existe, no qué tan bien funciona.

**Lectura:** en el papel, a AVIS no le faltan funciones. Lo que sigue muestra que el problema está en otra parte.

## 3. Lo que contó un usuario

### 3.1 Contexto de la conversación

- **Quién:** Daniel, contacto personal de Carlos, trabaja en la red de salud pública de Rancagua. Su cargo exacto no se preguntó.
- **Cómo:** conversación informal por WhatsApp, el 18-11-2026.
- **Cuidado:** fue una conversación de confianza. No citarlo con nombre fuera del equipo ni en material comercial.
- **Límite que puso:** no puede compartir las planillas que usa fuera de los correos institucionales. Se respetó y no se insistió.

### 3.2 Qué dijo y cómo lo leemos

| Lo que dijo | Cómo lo leemos |
|---|---|
| "Tiene sus mañas", "aún no logramos sacarle full potencial", "pero funciona, que es lo bueno". | El sistema es estable para lo básico. La queja no es que se caiga, sino que no logran aprovecharlo. |
| A él en lo personal no le complica nada. A los **encargados de programas** les cuesta "el tema de los censos". | El dolor fuerte no está en quien atiende, sino en quien tiene que informar: los encargados de programa. |
| El cierre de la ficha clínica tiene "muchas pestañas, muchas cosas que rellenar". | Demasiados campos y pasos para cerrar una atención. |
| "A veces nadie nos ha capacitado bien para usarlo." | El sistema no se entiende sin que alguien lo enseñe, y esa enseñanza no llegó. |
| "No nos cuadra el REM." | Lo que el sistema calcula no coincide con lo que deben informar. |
| "Entonces hay que usar tarjeteros virtuales y es más relleno de cosas." | Para cuadrar, llevan un registro paralelo. Trabajo doble. |
| Los tarjeteros son "Don Excel", con "algunos datos". | El registro paralelo es una planilla Excel, por fuera de AVIS. |
| "Los pacientes en controles a veces no cuadran", y "algunos procedimientos". | Lo que descuadra es sobre todo el conteo de pacientes en control, y algunos procedimientos. |
| "Más que nada es porque no sabemos bien qué casillas llenar." | **La causa que él mismo identifica:** el dato se registra mal o incompleto en la atención. |
| "Para cerrar los REM van cambiando." | Las reglas o planillas del REM cambian y eso complica cada cierre. |

### 3.3 Qué tan sólido es esto

- Es **una sola persona de una sola comuna**. Sirve como pista, no como prueba.
- Sobre los censos habló **de terceros** (los encargados de programa), no de su propia experiencia.
- Según Carlos, Daniel lleva poco tiempo usando AVIS. Parte de la dificultad puede ser de la puesta en marcha y no del producto.
- No se preguntó, y no sabemos: si AVIS es lento o se cae, cómo es el soporte después de la fusión con Rayen, ni quién decide qué sistema se usa (la municipalidad o el Servicio de Salud).

## 4. Diagnóstico: por qué aparece el Excel

Uniendo lo que dijo, la cadena es esta:

1. La pantalla de atención tiene muchas pestañas y campos, y no guía.
2. Falta capacitación, así que cada persona llena lo que cree que corresponde.
3. El dato queda incompleto o en el lugar equivocado.
4. El REM se calcula sobre esos datos, y no cuadra.
5. El encargado de programa arma un tarjetero en Excel para tener un número confiable.
6. Desde ahí, todo se registra dos veces.
7. Cuando el REM cambia, hay que volver a aprender qué llenar, y el ciclo se repite.

**Lo importante:** el Excel no aparece porque al sistema le falte un módulo. Aparece porque el registro en la atención no es confiable. Un RCE que copie las funciones de AVIS, pero con la misma complejidad de pantalla, terminaría igual.

## 5. Contexto: qué son el REM, los censos y los tarjeteros

Esto es para leer la conversación con el vocabulario correcto. Lo verificado tiene fuente; lo demás está marcado.

- **REM (Resumen Estadístico Mensual):** las estadísticas que los establecimientos informan al Minsal. Las define el DEIS, el mismo departamento que emite la norma de estándares de información (ver `docs/normas/nt820-estandares-de-informacion.md`).
- **REM Serie P:** es el instrumento que recoge las estadísticas de la **población bajo control** de los distintos programas. Hay un manual vigente "REM 2025-2026, Serie P". (Manual del DEIS)
- **"Censo":** por el contexto, lo que Daniel llama censo es el conteo de población bajo control "al corte" que pide la Serie P. **Es nuestra interpretación; hay que confirmarla** con un encargado de programa. Los cortes serían semestrales (junio y diciembre): **por verificar en el manual**.
- **Tarjetero:** el registro que lleva cada programa con sus pacientes en control (quién está, cuándo fue su último control, cuándo le toca el próximo). Antes era de cartón; hoy en Rancagua es un Excel. **No hemos visto ninguno.**

### Planillas de la Serie P (según el índice del manual)

| Planilla | Programa |
|---|---|
| REM-P1 | Salud de la Mujer (regulación de fertilidad, gestantes, alto riesgo obstétrico) |
| REM-P2 | Salud de la Infancia (incluye estado nutricional) |
| REM-P3 | Otros programas (incluye respiratorios crónicos y salas IRA-ERA) |
| REM-P4 | Salud Cardiovascular (PSCV), con sus metas de compensación |
| REM-P5 | Salud Integral de Personas Mayores (funcionalidad, riesgo de caídas) |
| REM-P6 | Salud Mental en APS y especialidad |
| REM-P7 | Familias en control de salud familiar |
| REM-P9 | Salud integral de adolescentes (número de planilla por confirmar) |

Hay más planillas (por ejemplo, VIH/SIDA). La lista completa está en el índice del manual.

### El REM sí cambia, y seguido

Coincide con lo que dijo Daniel:
- El manual de la Serie P 2025 tiene al menos una versión 1.0 y una 1.1.
- En 2024 se publicó una versión 1.1 de la planilla de la Serie P que corrigió fórmulas de validación (por ejemplo, en el REM-P2) y tocó secciones del REM-P6.

Para el RCE, esto significa que las reglas del REM no pueden ir escritas en el código: van como datos, con versión y fecha de vigencia.

## 6. Qué significa para el RCE

### 6.1 Competencia y estrategia

- **Un competidor, no dos.** Rayen tiene productos en los tres niveles: APS (RAYEN y AVIS), hospitales (Eloísa y SINASUITE) y privados (Melisa).
- **El MVP compite con Melisa**, no con AVIS. AVIS y RAYEN son competencia de la Fase 4.
- **Hipótesis (sin evidencia todavía):** una empresa con dos productos de APS que se pisan puede terminar invirtiendo en uno solo. Si eso pasa, los clientes del producto que quede atrás serían una entrada. Para validarla habría que preguntar a usuarios si notaron cambios en soporte o actualizaciones desde 2024.
- **El diferenciador se amplía.** Hasta ahora era "más rápido y con mejor conectividad que Rayen". Esta conversación agrega dos, al menos para APS:
  - se aprende solo, sin depender de una capacitación;
  - el REM cuadra sin Excel.
- **Otro competidor en el segmento del MVP:** AgendaPro se promociona como software médico para centros privados y publicita certificación CENS. Conviene revisar qué exige esa certificación.

### 6.2 Principios de diseño que aplican desde el MVP

Aunque el REM es de la Fase 4, estos principios se construyen (o se pierden) en el núcleo clínico:

1. **Pocos campos, y solo los que corresponden.** Según el tipo de atención, la pantalla muestra lo que importa y esconde el resto. Coincide con el objetivo del proyecto: rápido, simple y pocos clics.
2. **El cierre de la atención avisa lo que falta**, en una sola lista, antes de cerrar. Nada de recorrer pestañas para descubrirlo.
3. **Dato estructurado y codificado**, no texto libre, en todo lo que después se cuenta (diagnósticos, controles, procedimientos, resultados que definen metas).
4. **Catálogos y reglas con versión y vigencia.** La norma NT 231 ya lo pide para los catálogos (`docs/specs/nucleo-clinico/README.md`, regla 2). Lo mismo vale para las reglas de cálculo de reportes.
5. **Medir la facilidad de uso:** una persona nueva debería poder registrar una atención completa sin capacitación. Es un criterio de aceptación concreto para las pantallas del MVP.

### 6.3 Ideas para la Fase 4 (`PublicSector/PrimaryCare` y `PublicSector/Statistics`)

No son decisiones. Son la traducción directa de lo aprendido:

- **Registrar una sola vez.** Lo que hoy va al Excel debe quedar en la atención misma.
- **Programa como concepto propio:** ingreso al programa, controles, egreso (alta, traslado, fallecimiento, abandono). De ahí sale la población bajo control.
- **Tarjetero como vista, no como registro.** Se arma solo desde las atenciones: quién está en control, su último control, su próximo control, quién está atrasado.
- **REM calculado con trazabilidad.** Cada número del REM debe poder abrirse y mostrar qué pacientes y qué atenciones lo componen.
- **Explicar el descuadre.** Si un paciente no entra en el conteo, el sistema dice por qué (por ejemplo: "sin control en el período" o "falta el dato X"). Esto ataca directo el "no nos cuadra".
- **Reglas del REM como datos versionados**, por año y versión del manual, para cambiar la regla sin cambiar el código.

### 6.4 Candidatos a revisar en arquitectura

Para discutir con el revisor de arquitectura; si alguno prospera, va a una ADR:

- **Dependencias:** `Clinical` no puede saber que existe el sector público (regla 2 del `CLAUDE.md`). Para que la Fase 4 pueda derivar población bajo control y REM, `Clinical` tiene que publicar eventos de dominio suficientes (atención cerrada, diagnóstico registrado, orden o procedimiento realizado), con los datos codificados que se van a contar. Conviene revisar ahora qué eventos publica el núcleo clínico, para no tener que reabrirlo después.
- **Versionado de reglas:** ver si el mecanismo de versión y vigencia de `Platform/Catalog` sirve también para reglas de cálculo, o si necesitan algo propio.
- **Formularios por tipo de atención:** el editor de plantillas clínicas debería poder marcar qué campos son obligatorios para cerrar y cuáles alimentan reportes. Así el "qué casillas llenar" lo resuelve la plantilla y no la capacitación.

## 7. Lo que falta saber

### Investigación de escritorio (pública, sin hablar con nadie)

| Qué | Para qué |
|---|---|
| Manual REM Serie P 2025-2026 completo | Saber qué se cuenta por programa, cada cuánto y con qué reglas. Confirmar las fechas de corte. |
| Manual REM Serie A | Es la serie de atenciones y actividades; ver cómo se cruza con la Serie P. |
| Orientaciones técnicas de los programas (partir por la del Programa de Salud Cardiovascular) | Reconstruir los campos de un tarjetero sin necesitar la planilla real. |
| Convenio marco SIDRA vigente | Requisitos para vender a Servicios de Salud. El material que encontramos es de 2016 (categorías "sistema para APS" y "sistema para atención especializada", en modalidad SaaS) y puede estar desactualizado. |
| Certificación CENS | Qué exige y cuánto cuesta. Los competidores del segmento privado la publicitan. |

### Con usuarios (cuando haya oportunidad, sin apurarlo)

- Ver un tarjetero vacío, pedido por una vía formal o a otro contacto. No volver a pedírselo a Daniel.
- Conversar con un encargado de programa: cómo cierra el mes y dónde se le descuadra.
- Preguntas que quedaron pendientes: ¿AVIS es lento o se cae?, ¿cambió el soporte desde 2024?, ¿quién decide la compra?

### Afirmaciones de este documento que no están verificadas

- Que "censo" sea el corte de población bajo control de la Serie P, y que los cortes sean en junio y diciembre.
- Que todos los Cesfam de Rancagua usen AVIS (lo dijo una persona).
- La fecha y la forma en que Vela compró Rayen y AVIS.
- Que Rayen vaya a dejar de invertir en alguno de sus productos (es una hipótesis nuestra).
- El número de planilla del REM de adolescentes (P9).

## 8. Fuentes

Consultadas el 2026-11-18.

**Empresa y propiedad**
- Fusión de Avis SpA en Rayen Salud SpA (extracto legal): https://vlex.cl/vid/sociedad-ingenieria-informatica-rayen-1053497586
- Fusión de Tecmédica SpA en Rayen Salud SpA (extracto legal): https://vlex.cl/vid/sociedad-ingenieria-informatica-rayen-1053771015
- Rayen Salud, productos por nivel de atención: https://www.rayensalud.com/
- Rayen Salud en RedConecta (razón social y RUT): https://red-conecta.com/empresa/12715/rayen-salud
- Vela Software Group, portafolio de salud: https://velasoftwaregroup.com/portfolio/healthcare/
- Vela como filial de Constellation Software: https://www.imap.com/de-de/transactions/imap-colombia-advises-healthcare-software-specialist-syac-on-its-sale-to-vela-software~t y https://www.df.cl/df-mas/coffee-break/no-paran-filial-de-gigante-constellation-software-concreta-su-tercera
- AVIS LATAM en LinkedIn: https://cl.linkedin.com/company/tecm%C3%A9dica

**Producto y presencia**
- AVIS Enterprise, módulos: https://www.avislatam.com/avisenterprise.html
- AVIS en TecMédica (solo se pudo leer el resumen del buscador; la página no cargó): https://www.tecmedica.net/avis
- TrendTIC, interoperabilidad de interconsultas (agosto 2026): https://www.trendtic.cl/2026/08/rayen-salud-impulsa-la-interoperabilidad-de-interconsultas-salud/
- TXS+, entrevista al gerente de negocios de Rayen (septiembre 2026): https://txsplus.com/2026/09/capacidades-tecnologicas-transacciones/
- Quillota, preparación del nuevo RCE (septiembre 2023): https://laregionhoy.cl/2023/09/29/red-municipal-de-centros-de-salud-de-quillota-se-prepara-para-la-implementacion-de-nuevo-sistema-de-registro-clinico-electronico
- Quillota, balance del primer mes con AVIS: https://laregionhoy.cl/?p=62227
- AgendaPro, software para centros médicos: https://agendapro.com/cl/centro-medico/software-para-centro-medico

**REM y programas**
- Manual REM Serie P 2025-2026, versión 1.1: https://saludtarapaca.gob.cl/wp-content/uploads/2025/03/MANUAL-REM-P-2025-Version-1.1.pdf
- Manual REM Serie P, versión 2026: https://www.saludtarapaca.gob.cl/wp-content/uploads/2026/01/MANUAL-REM-P-2026-Version-1.0.pdf
- Modificaciones a la planilla de la Serie P 2024 (versión 1.1): https://www.saludtarapaca.gob.cl/wp-content/uploads/2024/04/Modificaciones-planilla-serie-P-2024.docx
- Manual REM Serie A 2025-2026: https://saludtarapaca.gob.cl/wp-content/uploads/2025/03/Manual-Series-REM-2025-2026-SERIE-A-BS-BM-DV1.2.pdf
- Orientación técnica del Programa de Salud Cardiovascular (2016): https://www.saludtarapaca.gob.cl/wp-content/uploads/2023/10/Orientaciones-Tecnicas-Programa-Salud-Cardio-Vascular.pdf
- Difusión de la licitación del convenio marco SIDRA 2 (2016): https://www.alejandrobarros.com/wp-content/uploads/2016/12/DIfusion-Licitacion-SIDRA-2.pdf

**Fuente directa**
- Conversación por WhatsApp de Carlos con un usuario de la red APS de Rancagua, 2026-11-18.
