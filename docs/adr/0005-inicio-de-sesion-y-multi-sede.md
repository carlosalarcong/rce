# 0005 — Identidad central (rce-central), un usuario en varias clínicas y multi-sede

- **Estado:** aceptada (2026-11-16, Carlos). Cada paso de implementación se planifica y confirma aparte, porque toca `Platform/Security`, `Platform/Identity`, la configuración de Hakam, migraciones y el `CLAUDE.md` raíz.
- **Fecha:** 2026-11-16
- **Referencia:** el modelo de Nexia Core (`~/Developer/nexia/docs`, contrato `contratos/CON-01-integracion-de-productos.md`) y cómo lo consume Acredita360 (sus D-20 y D-21). Se copia el diseño, no la instancia: **Nexia no tiene relación con el RCE**.

## Contexto
- Un profesional puede trabajar en varias clínicas. Debe entrar **una vez** y cambiar de clínica sin volver a iniciar sesión (`docs/specs/navegacion-por-areas.md`, idea B).
- Dentro de una clínica puede haber **varias sedes**.
- Las cuentas se crean en un proyecto central propio, **rce-central**, al estilo de Nexia Core (decisión de Carlos).
- El segundo factor debe ser **configurable** desde un módulo de configuración (decisión de Carlos).
- Dominio provisorio: **`app.rce.cl`** (todavía no hay dominio comprado).
- Hoy el `CLAUDE.md` dice que el tenant se resuelve por subdominio y que los usuarios viven en la base de cada clínica. Eso cambia.

## Decisión propuesta

### 1. Dos proyectos, dos responsabilidades
| | **rce-central** (proyecto aparte) | **RCE** (este proyecto) |
|---|---|---|
| Responde | **Quién es** la persona y **a qué clínicas puede entrar** | **Qué puede hacer** adentro |
| Guarda | Personas, cuentas (contraseña y segundo factor), clínicas, membresías, administradores de clínica | Usuario funcional, perfiles, roles, permisos, sedes, todo lo clínico |
| Base de datos | Su propio PostgreSQL, separado de las clínicas, sin Hakam | Main (registro técnico de clínicas) + una base por clínica |

- **rce-central** es una aplicación Symfony aparte, con su propio repositorio y despliegue (Cloud Run + Cloud SQL + Cloud Tasks).
- El RCE **no guarda contraseñas ni segundos factores**.
- Identificadores entre los dos: UUID externos (`central_person_id`, `central_clinic_id`), sin llaves foráneas entre bases. **El correo nunca identifica.**

### 2. Hosts
| Host | Qué es |
|---|---|
| `id.rce.cl` | rce-central: inicio de sesión (emisor OIDC) |
| `central.rce.cl` | rce-central: administración (soporte) y API para el RCE |
| `app.rce.cl` | El RCE. **Una sola dirección para todas las clínicas** |

En desarrollo: `id.rce.localhost`, `central.rce.localhost` y `app.rce.localhost`.

### 3. Inicio de sesión (OIDC)
- **Flujo:** OIDC Authorization Code + PKCE (S256), con `state` y `nonce`. El RCE se autentica ante rce-central con `private_key_jwt`: firma con su llave y publica su JWKS. No hay secretos compartidos.
- **`id_token`:** `sub` = la persona en rce-central, `sid`, `email` (solo informativo). Si la persona usó segundo factor, también `acr`/`amr`.
- **Al entrar:**
  1. El RCE pregunta a rce-central en qué clínicas puede entrar la persona (`/contexts`).
  2. Con una sola clínica, entra directo. Con varias, la persona elige; nunca se elige la primera en silencio.
  3. Antes de fijar la clínica, el RCE la **valida** (`/contexts/validate`). La respuesta trae `valid_until`, y el RCE revalida cada vez que ese plazo vence (15 min, plazo que fija rce-central).
  4. Si rce-central no responde, el RCE bloquea con un aviso, sin desloguear.
- **Clínica activa (TenantContext):**
  - Va en la sesión, solo después de validarla. Ningún parámetro de la petición la cambia.
  - Un listener antes del firewall despacha `SwitchDbEvent`. El resolver por subdominio de Hakam se apaga.
  - El usuario de seguridad va atado a la clínica: `refreshUser` lo compara con la sesión y, si no coinciden, cierra la sesión.
- **Cambio de clínica:** desde el menú de arriba a la derecha. Valida en rce-central, regenera la sesión, recarga roles y sedes, guarda borradores y abre la última área usada.
- **Salir:**
  - "Salir" cierra el RCE, con un POST protegido con CSRF.
  - "Cerrar sesión en todas partes" cierra también rce-central.
  - Cuando termina la sesión central, rce-central avisa al RCE (`session_ended`) y el RCE cierra las sesiones con ese `sid`.
- **Inactividad:** cierre configurable, pensado para computadores compartidos.

### 4. Segundo factor configurable
- **Vive en rce-central.** El RCE no implementa un segundo factor propio.
- **Mecanismo:** app autenticadora (TOTP), con códigos de respaldo.
- **Obligatorio siempre** para soporte y administradores de rce-central.
- **Para el resto lo decide cada clínica** en el **módulo de configuración** del RCE (`Configuración → Seguridad`): a qué perfiles se les exige (por ejemplo, médico y enfermera sí; recepción no). La política solo puede endurecer lo que rce-central exige como mínimo.
- **Cómo se exige (step-up):**
  1. Al validar la clínica, el RCE revisa si el perfil de la persona lo requiere y si el `id_token` trae segundo factor.
  2. Si falta, la manda a rce-central con `acr_values=…mfa`.
  3. La persona ingresa el código y vuelve.

  Un solo intento, sin bucles. Cada cambio de esta política queda en la auditoría.

### 5. Quién crea las cuentas
- **Soporte, en `central.rce.cl`:** crea las clínicas y elige su **primer administrador**.
- **El administrador de cada clínica, desde el RCE** (`Configuración → Usuarios`):
  1. Invita a una persona por correo. El RCE se lo pide a rce-central por API, y rce-central crea o reutiliza la persona y la cuenta, crea la membresía pendiente y envía el correo de activación. La respuesta no revela si la persona ya existía.
  2. El RCE crea el usuario funcional con su perfil y sus sedes.
  3. El RCE confirma la membresía. Así, una persona nunca tiene acceso sin perfil.
- **Desactivar** a alguien en la clínica desactiva la membresía en rce-central. Sus registros en la ficha **no se borran**: el autor queda identificado.

### 6. Nueva clínica (aprovisionamiento)
- **rce-central orquesta y el RCE ejecuta:**
  1. Soporte crea la clínica en rce-central.
  2. rce-central pide al RCE crearla (`POST /central/v1/provisioning`, con llave de idempotencia).
  3. El RCE crea la base de la clínica con Hakam, corre las migraciones de clínica y crea al primer administrador con rol de administrador. Lo hace en la cola de encargos (ADR 0006).
  4. El RCE reporta el resultado y rce-central activa la clínica.
- **Nombre de la base:** `rce_<código>`. El código es inmutable.
- **Credenciales de la base:** las guarda solo el RCE (Secret Manager, ADR 0003). rce-central no tiene ningún secreto de las clínicas.

### 7. Sedes (multi-sede dentro de una clínica)
- Las sedes son datos de la base de la clínica (`site`: nombre, dirección, códigos externos como el lugar de atención de IMED).
- Cada persona tiene una o más sedes asignadas en el RCE. La sede activa va en la sesión y se elige desde el mismo menú.
- Agenda, caja, camas y reportes se filtran por sede. La ficha clínica es **una sola por clínica**.

### 8. Avisos de rce-central al RCE
- Llegan a `POST https://app.rce.cl/central/v1/events`, firmados por rce-central.
- **Eventos:** persona suspendida, clínica desactivada, membresía desactivada y sesión terminada.
- Significan "vuelve a preguntar" (el RCE vence el contexto y revalida), salvo "sesión terminada", que cierra la sesión.
- Idempotentes por `event_id`.
- **Riesgo aceptado:** si un aviso se pierde, una persona suspendida puede seguir operando hasta 15 min, hasta la siguiente revalidación.

### 9. Cómo nace rce-central: copia de Nexia Core
**Decisión de Carlos (2026-11-16):** rce-central parte de una **copia del código de Nexia Core**, en un repositorio nuevo y privado, con base, dominio y llaves propios, y **sin ninguna conexión con Nexia**.

Al copiarlo:
- se renombra todo lo que diga Nexia (hosts, rutas `/nexia/v1/...` → `/central/v1/...`, prefijos de claims y `acr`);
- se simplifica el concepto de "varios productos": el RCE es el único;
- se programa el segundo factor (en Nexia está diseñado, no implementado);
- a partir de ahí, cada proyecto sigue su propio camino.

## Consecuencias
- Cambian:
  - el `CLAUDE.md` raíz ("el tenant se resuelve por subdominio" y "usuarios del centro en la base del tenant");
  - `config/packages/hakam_multi_tenancy.yaml` (resolver apagado);
  - nginx (host único, sin comodín);
  - `.env` (`TENANT_BASE_DOMAIN` desaparece y aparece el host del RCE y de rce-central).
- **Migraciones:**
  - en Main: el `central_clinic_id` en `tenant_db_config`;
  - en cada clínica: usuario funcional, perfiles, roles, permisos, sedes, asignaciones y política de segundo factor.
- **En el RCE se construye** (en `Platform/Identity` y `Platform/Security`):
  - el cliente OIDC;
  - el TenantContext;
  - la validación y revalidación de contextos;
  - la recepción de avisos y aprovisionamiento;
  - la invitación de personas;
  - el módulo de configuración (seguridad y usuarios).
- **Tests de aislamiento obligatorios:**
  - la clínica no cambia con ningún parámetro de la petición;
  - un usuario de la clínica A con la sesión cambiada a B cierra la sesión;
  - sin TenantContext no se llega a ninguna ruta de clínica;
  - el cambio de clínica sin membresía se rechaza;
  - una persona sin el segundo factor exigido no entra.
- **En local**, rce-central corre como otro proyecto en Docker con su propio `compose.yaml`, unido al RCE por una red compartida.

## Orden de implementación (cada paso con su plan y confirmación)
1. Crear el repositorio de rce-central a partir de Nexia Core (sección 9).
2. rce-central mínimo: personas, cuentas, clínicas, membresías, OIDC y contextos.
3. RCE: inicio de sesión OIDC, TenantContext, resolver por subdominio apagado y tests de aislamiento.
4. RCE: usuario funcional, perfiles, roles, permisos y sedes, con el menú de cambio de clínica y sede.
5. Invitación de personas y aprovisionamiento de clínicas.
6. Segundo factor en rce-central y su política en el módulo de configuración del RCE.
7. Avisos firmados de rce-central y página de retorno de proveedores (ADR 0004).
