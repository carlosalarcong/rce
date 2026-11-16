# Cómo funciona el RCE, en palabras simples

Guía para entender la arquitectura sin entrar en el código. Las decisiones técnicas completas están en `docs/adr/`.

---

## 1. La arquitectura: un monolito modular con DDD

El RCE es **una sola aplicación** (monolito) dividida en **módulos** que no se mezclan. Cada módulo es como un departamento de la clínica: tiene su propio trabajo y solo se comunica con los demás por "ventanillas" oficiales.

### Los módulos (carpetas de `src/`)

| Carpeta | Qué es | Ejemplo |
|---|---|---|
| `Platform/` | La base que usan todos | clínicas (tenants), usuarios, auditoría, seguridad |
| `Clinical/` | El núcleo clínico compartido | paciente, atención, diagnóstico, receta |
| `Ambulatory/` | Atención ambulatoria | agenda, recepción, caja ambulatoria |
| `Inpatient/` | Atención hospitalaria | ingresos, camas, urgencia, pabellón, enfermería |
| `PublicSector/` | Capa pública | APS, REM, GES |
| `Integration/` | Conexión con el mundo exterior | pagos (Webpay, POS, IMED), cola de encargos, FHIR, HL7 |

**Qué hay hoy dentro de los módulos del negocio:** solo su ficha (`CLAUDE.md`) con las reglas del módulo. Los submódulos están planificados y se crean cuando toque:

| Módulo | Fase | Submódulos planificados |
|---|---|---|
| `Clinical/` | MVP | `Patient` (paciente, RUN, previsión, alergias), `Encounter` (atención), `Diagnosis` (CIE-10), `Prescription` (recetas), `Order` (exámenes y procedimientos), `ClinicalDocument` (documentos firmados) |
| `Ambulatory/` | MVP | `Scheduling` (agenda), `Reception` (recepción y sala de espera), `Telemedicine` (Fase 2), `Billing` (caja ambulatoria) |
| `Inpatient/` | Fase 3 | `Admission` (ingresos y altas), `Billing` (caja hospitalaria), `Pharmacy`, `Emergency` (urgencia y triage), `Surgery` (pabellón), `Ward` (servicios y camas), `Nursing` (enfermería) |
| `PublicSector/` | Fase 4 | `PrimaryCare` (APS), `Statistics` (REM), `Ges`, `Network` (red y derivaciones), `Grd` |

**Regla de oro: las dependencias van solo hacia abajo.** Ambulatorio y Hospitalario usan Clínico; Clínico usa Plataforma. Nunca al revés: Clínico no sabe que existe la caja. Una herramienta (Deptrac) revisa esta regla en cada cambio y no deja subir código que la rompa.

### Dentro de cada módulo: las capas de DDD

DDD (*Domain-Driven Design*, "diseño guiado por el negocio") ordena el código según el negocio y no según la tecnología. Cada módulo tiene las mismas capas, de adentro hacia afuera:

| Capa | Qué contiene | Puede usar | Ejemplo real |
|---|---|---|---|
| **Domain** (dominio) | Las reglas del negocio puras: qué es un intento de pago y en qué estados puede estar | nada (ni Symfony ni la base de datos) | `Integration/Payments/Domain/PaymentAttempt.php` |
| **Application** (aplicación) | Los casos de uso: "procesar un encargo", "entregar el adaptador de un proveedor". Es la **ventanilla** del módulo | Domain y Contract | `Integration/Messaging/Application/ProcessOutboxMessage.php` |
| **Infrastructure** (infraestructura) | Cómo se guarda y cómo se habla con lo técnico: base de datos, Valkey, Google | Application y Domain | `Integration/Messaging/Infrastructure/Outbox/DbalOutboxStore.php` |
| **UI** (interfaz) | Por dónde se entra: pantallas, rutas, comandos | solo Application | `Integration/Messaging/UI/TaskController.php` |

En `Integration/Payments` hay dos capas más:

| Capa | Qué es |
|---|---|
| **Contract** (enchufes) | Las interfaces que cumple cada proveedor: cobrar, emitir bonos, emitir boletas. No dependen de nada |
| **Adapter** (adaptadores) | Un proveedor concreto (Webpay, POS de Transbank, IMED) que cumple un enchufe. Solo conoce los enchufes; un adaptador no usa a otro |

**Por qué así:**
- Cambiar de proveedor o de tecnología no toca las reglas del negocio.
- Cada pieza se prueba por separado.
- Nadie se salta una ventanilla.

### Deptrac, el guardia de la arquitectura

Deptrac es un **revisor automático de las reglas de arquitectura**. Lee todo el código y verifica que cada parte use solo lo que tiene permitido. No mira si el código funciona (eso lo hacen las pruebas): mira **quién usa a quién**. Es como el guardia que revisa las puertas de un hospital: recepción no entra a pabellón y farmacia no modifica fichas.

Revisa dos tipos de reglas:
- **Entre módulos** (`deptrac.modules.yaml`): qué departamento puede pedirle a cuál. Como los pisos de un hospital: la urgencia le pide exámenes al laboratorio, pero el laboratorio no le da órdenes a la urgencia.
- **Entre capas** (`deptrac.layers.yaml`): quién hace qué dentro de un departamento. El paciente habla con recepción, recepción con el médico, y nadie entra directo a la bodega.

| # | Regla | Permitido | Prohibido | Por qué |
|---|---|---|---|---|
| 1 | Las dependencias van solo hacia abajo (Ambulatory, Inpatient y PublicSector → Clinical → Platform) | La caja pide los datos del paciente a Clinical | La ficha clínica llama al código de la caja | La ficha es la misma para ambulatorio, hospital y sector público: un cambio en una caja no puede romperla |
| 2 | La caja solo usa el "menú" de pagos (Contract y Application de Integration) | La caja pide "dame el proveedor `webpay`" al registro | La caja usa directamente el adaptador de Webpay | El registro revisa que el proveedor esté activo en la clínica, y cambiar de proveedor no toca la caja |
| 3 | Integration no conoce la caja (solo usa Clinical y Platform) | IMED recibe el RUN y los códigos ya traducidos | IMED busca datos en la caja ambulatoria | El mismo IMED lo usan la caja ambulatoria y la hospitalaria |
| 4 | Domain no usa Symfony ni la base de datos | "Un pago confirmado no vuelve a pendiente", dentro de `PaymentAttempt` | Esa regla consultando la base o usando Symfony | Las reglas del negocio duran más que la tecnología y se prueban en milésimas de segundo |
| 5 | UI no se salta la ventanilla (usa Application, nunca Infrastructure) | `TaskController` llama a `ProcessOutboxMessage` | `TaskController` escribe directo en la tabla de la bandeja | Las reglas importantes (verificar clínica, no repetir, reintentar) viven en el caso de uso |
| 6 | Los adaptadores solo conocen los enchufes (Contract) | `WebpayGateway` recibe "cobra $15.000" y devuelve "redirige aquí" | `WebpayGateway` cambia el estado del intento de pago | El adaptador solo traduce; las decisiones las toma el RCE, igual para todos los proveedores |
| 7 | Un adaptador no usa a otro (`scripts/check-adapters.sh`) | IMED y Webpay, cada uno por su lado | IMED reutiliza código de Webpay | Si cambia Webpay, IMED no debe romperse |

**Cuándo se ejecuta:**
- `make check` y `make deptrac`: falla y muestra qué clase usa a cuál sin permiso;
- el hook antes de cada `git push`: no deja subir el cambio;
- la CI en el runner propio, en cada cambio a `develop` o `master`.

**Ya sirvió:** al armar la cola, el comando del simulador quedó en UI usando una pieza de Infrastructure. Deptrac lo detectó y se movió a su lugar. Sin un guardia así, con el tiempo alguien toma un atajo "solo esta vez" y el código termina enredado: cambiar una cosa rompe otra.

### Una base de datos por clínica (multi-tenant)

- **Base principal (Main):** solo lo global, como el registro de clínicas.
- **Base de cada clínica:** todo lo demás (pacientes, atenciones, caja, bandeja de encargos). Así los datos de una clínica nunca se mezclan con los de otra.

---

## 2. Los enchufes de pagos

Un enchufe es "lo que hay que saber hacer para ser un proveedor de cobros". La caja solo conoce el enchufe; nunca sabe si detrás está Webpay o el POS.

| Enchufe | Proveedores | Estado |
|---|---|---|
| Cobros | Webpay, POS de Transbank (Klap después) | Webpay y POS (lado servidor) programados y apagados |
| Bonos | IMED (Snabb después) | IMED programado según melisa_prod y apagado: falta validarlo con el kit oficial |
| Boletas | El emisor que se elija | solo el enchufe |

Están **apagados a propósito**: se encienden cuando exista la configuración por clínica (qué proveedores tiene cada una y sus credenciales cifradas).

### ¿Cómo sabe el sistema si es Webpay, IMED o el POS?

- Cada adaptador tiene un **nombre**: `webpay`, `transbank_pos`, `imed`.
- El cobro guarda qué proveedor usa (en la tabla de intentos de pago, que falta armar).
- Cuando hay que hacer algo, el **`GatewayRegistry`** entrega el adaptador por su nombre, y solo si ese proveedor está activo en esa clínica.

---

## 3. La cola de encargos

### La idea

Cuando la cajera aprieta "Cobrar", el sistema no la deja esperando mientras habla con Transbank:

1. **Anota el encargo** en la bandeja de la clínica, junto con el cobro. Si el cobro se deshace, el encargo también.
2. Le responde al tiro a la cajera: "en proceso".
3. Un **timbre** avisa que hay un encargo nuevo.
4. El RCE atiende el timbre, se conecta a la clínica correcta y hace el encargo **una sola vez**.
5. Si falla, reintenta **al tiro, al minuto y a los 5 minutos**. Si sigue fallando, queda como "fallido" con una alerta.

### La bandeja (`outbox_message`, una por clínica)

| Columna | Qué guarda |
|---|---|
| `message_class` | Qué tipo de encargo es (por ejemplo, "iniciar un pago") |
| `body` | Los datos del encargo (por ejemplo, el id del intento de pago) |
| `tenant` | De qué clínica es |
| `status` | pendiente, en proceso, hecho o fallido |
| `attempts` | Cuántas veces se intentó |
| `available_at` | Desde cuándo se puede hacer (los reintentos esperan) |
| `last_error` | Solo el tipo de error, nunca su mensaje (podría traer datos del paciente) |

No hay columna "proveedor": el encargo lleva el id del intento de pago, y el intento sabe su proveedor.

### Google Cloud Tasks y Google Cloud Scheduler

Son dos servicios de Google que **llaman directo al RCE**. No se llaman entre ellos y ninguno mira la bandeja.

| | Cloud Tasks | Cloud Scheduler |
|---|---|---|
| Qué es | El **timbre** de cada encargo | Un **despertador** |
| Cuándo llama | Cuando el RCE le pidió avisar un encargo, a la hora indicada | Cada minuto, haya o no encargos |
| A dónde llama | `/_tasks/outbox/{clínica}/{encargo}` | `/_tasks/sweep` (la barrida) |
| Quién lo programa | El RCE, encargo por encargo | Se configura una vez al desplegar |

**Cómo le avisa el RCE a Cloud Tasks en producción:** con la librería oficial de Google (`google/cloud-tasks`). Le pide: "a esta hora, haz un POST a esta dirección del RCE y fírmalo como esta cuenta de servicio".
- El RCE se identifica ante Google con la cuenta de servicio de Cloud Run, sin contraseñas ni archivos de llaves.
- Cuando Cloud Tasks llama, el RCE verifica que la firma sea de Google y de esa cuenta. Sin firma válida responde 401.

**Si el aviso a Cloud Tasks falla:** el encargo igual quedó en la bandeja. Al minuto siguiente, Cloud Scheduler despierta la **barrida**: el RCE revisa las bandejas de todas las clínicas, encuentra los encargos atrasados y le pide **de nuevo** a Cloud Tasks que los avise. Por eso nada se pierde.

**En el computador de desarrollo:** el contenedor `tasks` hace los dos papeles (timbre y despertador). Guarda los avisos en Valkey y llama al RCE por HTTP igual que Google. El código del RCE es el mismo en los dos lados.

**¿Por qué no un worker siempre encendido?** En GCP no hace falta: Cloud Run atiende las llamadas de Cloud Tasks y crece solo si hay mucho trabajo.

### Ejemplo: un pago con Webpay (cuando esté armado)

1. La cajera aprieta "Pagar con Webpay". Se crea el intento de pago ("iniciado") y el encargo "iniciar en Webpay". La pantalla muestra "Conectando con Webpay…".
2. Cloud Tasks toca el timbre (menos de 1 segundo). El RCE crea la transacción en Transbank.
3. La pantalla lleva al paciente a Webpay.
4. El paciente paga y vuelve. El retorno solo anota que volvió y deja el encargo "confirmar".
5. Cloud Tasks toca el timbre. El RCE confirma con Transbank el estado real y la pantalla muestra "Confirmado" o "Rechazado".

**Nunca se llama a Transbank directo desde la pantalla:** siempre pasa por la bandeja. Así un Transbank lento no congela la caja, y si falla, se reintenta solo.

---

## 4. Valkey

**Valkey es la memoria rápida del sistema:** una base de datos muy liviana para cosas chicas y de corta vida, que comparten todos los procesos del RCE al mismo tiempo.

| Uso | Qué hace |
|---|---|
| **Candado contra cobros dobles** | Si la cajera aprieta "Cobrar" dos veces, el primero toma el candado y el segundo espera o se descarta |
| **Fusible por proveedor** | Cuenta las fallas recientes de Webpay o IMED. Si son demasiadas, se deja de llamar a ese proveedor por un rato |
| **Simulador de Cloud Tasks** (solo local) | Guarda los avisos de la cola con su hora hasta que el contenedor `tasks` los entrega |

**Por qué no PostgreSQL para esto:** PostgreSQL guarda lo que debe durar años (pacientes, cobros, la bandeja). Valkey guarda lo que dura segundos o minutos y se consulta miles de veces, como candados y contadores, y ahí es mucho más rápido.

**En producción** es **Memorystore for Valkey** de Google, con la misma versión (9.1) que corre en Docker.

---

## 5. Qué corre dónde

| Pieza | En tu computador (Docker) | En producción (GCP) |
|---|---|---|
| La aplicación RCE | contenedores `php` + `nginx` | Cloud Run |
| Bases de datos | contenedor `database` (PostgreSQL 18) | Cloud SQL (PostgreSQL) |
| Clave de las bases de clínica | `.env` (solo desarrollo) | Secret Manager |
| Memoria rápida | contenedor `redis` (Valkey 9.1) | Memorystore for Valkey 9.1 |
| Timbre de la cola | contenedor `tasks` | Cloud Tasks |
| Despertador de la barrida | contenedor `tasks` | Cloud Scheduler |
| Pruebas antes de subir | `make check` y el hook `pre-push` | la CI en el runner propio |
