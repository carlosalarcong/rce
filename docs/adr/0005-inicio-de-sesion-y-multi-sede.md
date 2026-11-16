# 0005 — Identidad dentro del RCE, un usuario en varias clínicas y multi-sede

- **Estado:** aceptada (2026-11-16, Carlos). Cada paso de implementación se planifica y confirma aparte, porque toca `Platform/Security`, `Platform/Identity`, la configuración de Hakam, migraciones y el `CLAUDE.md` raíz.
- **Fecha:** 2026-11-16
- **Reemplaza** la versión anterior de esta ADR, que ponía la identidad en un proyecto aparte (rce-central, copia de Nexia Core).

## Contexto
- Un profesional puede trabajar en varias clínicas. Debe entrar **una vez** y cambiar de clínica sin volver a iniciar sesión (`docs/specs/navegacion-por-areas.md`, idea B).
- Dentro de una clínica puede haber **varias sedes**.
- El segundo factor debe ser **configurable** desde un módulo de configuración.
- Dominio provisorio: **`app.rce.cl`** (todavía no hay dominio comprado).
- **El RCE será el único producto** (Carlos, 2026-11-16).

## Por qué la identidad no va en un sistema aparte
Se evaluó un sistema central separado (rce-central), al estilo de Nexia Core, y se descartó.
- **Nexia lo necesita porque es una familia de productos** que comparten personas y organizaciones. El RCE es uno solo.
- **Separar suma costos sin beneficio para un solo producto:**
  - dos despliegues, dos bases y dos CI;
  - inicio de sesión federado (OIDC) entre los dos, con llaves, revalidación y avisos firmados;
  - un punto de falla más: si el central se cae, nadie entra al RCE;
  - mantener todo el código heredado de Nexia (catálogo de productos, entitlements, estándares, Portal).
- **El MVP no tiene margen** (2,6 a 3 meses con 2 personas).
- **La ventaja de seguridad se conserva sin separar:** las cuentas viven en la base **Main**, que ya está separada de las bases clínicas de cada clínica.

El repositorio `carlosalarcong/rce-central` quedó **archivado** (privado, solo lectura) como referencia, con su último estado: PHP 8.5, entorno local propio y 744 tests en verde.

**Cuándo revisar esta decisión** (extraer `Platform/Identity` a un servicio):
- aparece un segundo producto que deba compartir las cuentas;
- clínicas que exijan entrar con su cuenta corporativa para varios sistemas (SSO con Microsoft o Google);
- equipos distintos para identidad y producto.

Para que extraerlo sea barato, `Platform/Identity` solo se usa por su capa `Application` (Deptrac lo vigila).

## Decisión

### 1. Quién es la persona y a qué clínicas entra: en Main
- **`Platform/Identity`, en la base Main:**
  - `user_account`: UUID, correo (único), hash de contraseña (argon2id), estado, datos del segundo factor;
  - `tenant_membership`: persona, clínica, estado (activa o suspendida) y si es administrador de la clínica;
  - usuarios de **soporte** (plataforma).
- **En la base de cada clínica:** qué puede hacer la persona ahí: usuario funcional, perfil (médico, enfermera, cajera, administrativo), roles, permisos, sedes asignadas y la política de segundo factor.
- Se enlazan por el **UUID** de `user_account`, nunca por id entero (se repiten entre bases). **El correo nunca identifica.**

### 2. Hosts
| Host | Qué es |
|---|---|
| `app.rce.cl` | El RCE, **una sola dirección para todas las clínicas** |
| `soporte.rce.cl` | Panel de soporte (plataforma). Host aparte para que su cookie de sesión no se comparta con la de las clínicas |

En desarrollo: `app.rce.localhost` y `soporte.rce.localhost`.

### 3. Inicio de sesión y clínica activa
- **Inicio de sesión propio** (Symfony Security):
  - correo y contraseña;
  - límite de intentos (`symfony/rate-limiter`);
  - recuperación por correo con token de un solo uso;
  - "Salir" por POST con CSRF;
  - cierre por inactividad configurable.
- **Al entrar:** con una sola clínica, entra directo. Con varias, la persona elige; nunca se elige la primera en silencio. Después se recuerda la última.
- **Clínica activa (TenantContext):**
  - Va en la sesión, solo después de verificar en Main que la membresía está activa. Ningún parámetro de la petición la cambia.
  - Un listener antes del firewall despacha `SwitchDbEvent`. El resolver por subdominio de Hakam se apaga.
  - El usuario de seguridad va atado a la clínica: `refreshUser` lo compara con la sesión y, si no coinciden, cierra la sesión.
  - La membresía se vuelve a verificar en cada petición. Es una consulta liviana a Main, así que una persona suspendida deja de entrar al instante.
- **Cambio de clínica:** desde el menú de arriba a la derecha. Verifica la membresía, regenera la sesión, recarga roles y sedes, guarda borradores y abre la última área usada.

### 4. Segundo factor configurable
- **Mecanismo:** app autenticadora (TOTP) con códigos de respaldo (`scheb/2fa`).
- **Obligatorio siempre** para soporte.
- **Para el resto lo decide cada clínica** en `Configuración → Seguridad`, por perfil (por ejemplo, médico y enfermera sí, recepción no).
- **Cómo se exige:**
  1. Al entrar a una clínica (o cambiar a ella), si el perfil de la persona lo exige y en esta sesión aún no lo usó, se le pide el código.
  2. La primera vez, lo configura.
- Cada cambio de esta política queda en la auditoría.

### 5. Quién crea las cuentas
- **Soporte** (`soporte.rce.cl`): crea las clínicas y su **primer administrador**.
- **El administrador de cada clínica** (`Configuración → Usuarios`): invita a una persona por correo.
  1. Si el correo ya tiene cuenta, se reutiliza. Si no, se crea y se envía un enlace de activación. La pantalla no revela si ya existía.
  2. Se crea la membresía y el usuario funcional con su perfil y sedes, en un mismo paso: nunca hay acceso sin perfil.
- **Desactivar** a alguien desactiva su membresía. Sus registros clínicos **no se borran**: el autor queda identificado.

### 6. Nueva clínica (aprovisionamiento)
- Soporte la crea. La creación de la base, sus migraciones y el primer administrador corren en la **cola de encargos** (ADR 0006), porque toman tiempo.
- **Nombre de la base:** `rce_<código>`. El código es inmutable.
- **Clave de la base:** Secret Manager (ADR 0003).

### 7. Sedes (multi-sede dentro de una clínica)
- Las sedes son datos de la base de la clínica (`site`: nombre, dirección, códigos externos como el lugar de atención de IMED).
- Cada persona tiene una o más sedes asignadas. La sede activa va en la sesión y se elige desde el mismo menú.
- Agenda, caja, camas y reportes se filtran por sede. La ficha clínica es **una sola por clínica**.

### 8. Página de retorno de proveedores (Webpay, IMED)
- Con un solo host, el navegador **no manda la cookie de sesión** cuando el proveedor devuelve al paciente con un POST desde otro sitio (`SameSite=Lax`).
- Por eso la URL de retorno lleva un **identificador público opaco** de la clínica (UUID aleatorio en `tenant_db_config`): `/retorno/{proveedor}/{tenantRef}`.
- El retorno solo registra que llegó. El estado real se confirma con el proveedor en la cola (ADR 0004 y 0006).

### 9. Operación por clínica: saber si una clínica cayó y levantarla
Con una base por clínica, una falla en la base de una clínica afecta **solo a esa clínica**. El panel de soporte debe permitir detectarlo y resolverlo sin improvisar:
- **Estado de las clínicas** (panel de soporte): una lista en verde o rojo. Por cada clínica muestra:
  - si su base responde;
  - si sus migraciones están al día;
  - cuántos encargos fallidos tiene en la cola;
  - cuándo fue su último respaldo.
- **Alertas automáticas** (Cloud Monitoring): si una clínica no responde por unos minutos, o si sube su tasa de errores, llega un aviso a soporte.
- **Registros con la clínica:** todo error lleva el identificador de la clínica, para saber si el problema es de una o de todas.
- **Respaldo por clínica:** además del respaldo de Cloud SQL, una copia diaria de cada base de clínica en Cloud Storage, para restaurar **solo esa clínica** sin tocar las demás.
- **Modo mantenimiento por clínica:** mientras se arregla, sus usuarios ven un aviso claro en vez de errores, y las demás clínicas siguen normal.
- **Manuales paso a paso** en `docs/manuales/`: base que no responde, migración fallida en una clínica, restaurar una clínica desde su respaldo, poner y quitar el modo mantenimiento.

## Consecuencias
- Cambian:
  - el `CLAUDE.md` raíz: "el tenant se resuelve por subdominio" pasa a ser "la clínica activa va en la sesión", y los usuarios del centro pasan a "cuenta en Main + usuario funcional en la clínica";
  - `config/packages/hakam_multi_tenancy.yaml` (resolver apagado);
  - nginx (host único más el de soporte);
  - `.env` (`TENANT_BASE_DOMAIN` desaparece).
- **Dependencias de Composer nuevas** (requieren aprobación): `symfony/security-bundle`, `symfony/rate-limiter`, `scheb/2fa-bundle` y `scheb/2fa-totp`.
- **Migraciones:**
  - en Main: `user_account`, `tenant_membership`, soporte y el `public_ref` de `tenant_db_config`;
  - en cada clínica: usuario funcional, perfiles, roles, permisos, sedes y política de segundo factor.
- **Tests de aislamiento obligatorios:**
  - la clínica no cambia con ningún parámetro de la petición;
  - un usuario cargado para la clínica A con la sesión cambiada a B cierra la sesión;
  - sin TenantContext no se llega a ninguna ruta de clínica;
  - el cambio de clínica sin membresía se rechaza;
  - la sesión de soporte no se mezcla con la de clínica;
  - una persona sin el segundo factor exigido no entra.
- **UI:** las pantallas pueden partir de `rce/ui` (copia de `nexia/ui`, repositorio `carlosalarcong/rce-ui`), con su receta en `carlosalarcong/rce-recetas-flex`.

## Orden de implementación (cada paso con su plan y confirmación)
1. `Platform/Identity`: cuentas en Main, inicio de sesión, recuperación de contraseña.
2. TenantContext, membresías, apagado del resolver por subdominio y tests de aislamiento.
3. Usuario funcional, perfiles, roles, permisos y sedes en la base de la clínica, más el menú de cambio de clínica y sede.
4. Panel de soporte: clínicas, primer administrador y aprovisionamiento por la cola.
5. Operación por clínica (sección 9): estado de las clínicas, alertas, respaldo por clínica, modo mantenimiento y manuales. **Antes de la primera clínica real.**
6. Invitación de personas.
7. Segundo factor y su política en `Configuración → Seguridad`.
8. `public_ref` y página de retorno de proveedores.
