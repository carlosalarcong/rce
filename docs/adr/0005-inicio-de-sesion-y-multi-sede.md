# 0005 — Inicio de sesión propio, un usuario en varias clínicas y multi-sede

- **Estado:** propuesta (requiere confirmación: toca `Platform/Security`, `Platform/Identity`, la configuración de Hakam, migraciones y el `CLAUDE.md` raíz)
- **Fecha:** 2026-11-16

## Contexto
- Un profesional puede trabajar en varias clínicas (tenants). Debe entrar **una vez** y cambiar de clínica sin volver a iniciar sesión (`docs/specs/navegacion-por-areas.md`, idea B).
- Dentro de una clínica puede haber **varias sedes** (sucursales) y cada persona trabaja en una o más.
- Hoy el `CLAUDE.md` dice que el tenant se resuelve **por subdominio** y que los usuarios del centro viven en la base del tenant. Con eso, cambiar de clínica obliga a volver a entrar: cada subdominio tiene su propia cookie y una cuenta distinta por base.
- El RCE tiene **su propio inicio de sesión**. No depende de ningún proveedor de identidad externo.
- Acredita360 resolvió el mismo problema (su D-21): un solo host, la clínica activa guardada en la sesión y el usuario atado a esa clínica. Se toma esa mecánica, no su proveedor de identidad.
- La página de retorno de Webpay e IMED necesita saber a qué clínica pertenece un retorno (ADR 0004, pendiente 7).

## Decisión propuesta

### 1. Identidad única en Main, permisos en cada clínica
- **Main** guarda a la persona y a qué clínicas puede entrar:
  - `user_account`: identificador UUID, correo (único), hash de contraseña (argon2id), estado y segundo factor;
  - `tenant_membership`: persona, clínica y estado (activa o suspendida).
- **La base de cada clínica** guarda qué puede hacer la persona ahí: su perfil (médico, enfermera, cajera, administrativo), sus roles y permisos, y las sedes donde trabaja. Se enlaza por el UUID de `user_account`, nunca por un id entero (los ids enteros se repiten entre bases).
- Separación: **quién es y a qué clínicas entra** se decide en Main; **qué puede hacer adentro** se decide en la base de la clínica.
- Usuarios de soporte y superadministración (plataforma): en Main, con firewall propio (`/plataforma`) y segundo factor obligatorio.

### 2. Un solo host; la clínica activa sale de la sesión
- Todas las clínicas entran por un mismo host (por ejemplo, `app.<dominio>.cl`). Ya no hay subdominio por clínica.
- **Clínica activa (TenantContext):**
  - Se guarda en la sesión **solo** después de verificar en Main que la persona tiene membresía activa en esa clínica.
  - Ningún parámetro de la petición (query, cuerpo, cabecera, host) la cambia.
  - Un listener de `kernel.request` que corre **antes del firewall** lee la clínica de la sesión y despacha `SwitchDbEvent`.
  - El resolver por subdominio de Hakam se apaga (`resolver.enabled: false`).
- **El usuario de seguridad va atado a la clínica.** Lleva serializada la clínica para la que se cargaron sus roles. Si no coincide con la de la sesión, `refreshUser` lo rechaza y la sesión se cierra, sin ventana en que otro código actúe con roles de otra clínica.
- **Cambio de clínica:** un menú en la cabecera (arriba a la derecha). El cambio:
  - verifica la membresía en Main;
  - regenera el id de sesión;
  - recarga roles y sedes desde la nueva base;
  - guarda borradores (spec, idea A);
  - abre la última área usada en esa clínica.
- **Al entrar:** con una sola clínica, se entra directo. Con varias, la persona elige (nunca se elige la primera en silencio). Después se recuerda la última.
- **Por qué un solo host:**
  - se cambia de clínica sin volver a entrar;
  - no se puede averiguar qué clínicas son clientes probando subdominios;
  - un solo certificado y una sola configuración de nginx.
- **Lo que se pierde, y cómo se compensa:** el navegador ya no separa las cookies por clínica. El aislamiento pasa a depender del código (TenantContext, usuario atado a la clínica, tests de aislamiento). Más adelante se puede sumar una segunda barrera en PostgreSQL (RLS).

### 3. Sedes (multi-sede dentro de una clínica)
- Las sedes son datos de la base de la clínica (`site`: nombre, dirección, códigos externos como el lugar de atención de IMED).
- Cada persona tiene asignadas una o más sedes. Si tiene varias, la sede activa también va en la sesión y se elige desde el mismo menú de la cabecera.
- Agenda, caja, camas y reportes se filtran por sede. La ficha clínica del paciente es **una sola por clínica**, no por sede.

### 4. Inicio de sesión propio
- Correo y contraseña (argon2id), con:
  - límite de intentos (`symfony/rate-limiter`, dependencia nueva);
  - recuperación de contraseña por correo con token de un solo uso;
  - id de sesión regenerado al entrar.
- **Segundo factor (TOTP):** obligatorio para plataforma; para perfiles clínicos se recomienda obligatorio por la Ley 21.719 (decidir en su propia tarea).
- **Cierre por inactividad:** configurable; pensado para computadores compartidos en box y recepción.
- "Salir" es un POST con CSRF.

### 5. Página de retorno de Webpay, IMED y otros
- Con un solo host, **el navegador no manda la cookie de sesión** cuando Webpay o IMED devuelven al paciente con un POST desde otro sitio (`SameSite=Lax`). La clínica no puede salir de la sesión en ese momento.
- La URL de retorno lleva un **identificador público opaco de la clínica** (UUID aleatorio en `tenant_db_config`, no el nombre ni un id correlativo), por ejemplo `/retorno/webpay/{tenantRef}`. Con eso se conecta la base correcta.
- El retorno **solo registra que llegó**. El estado real se confirma con el proveedor (`confirm()`) en el worker (`docs/adr/0004`, cola). Por eso un `tenantRef` adivinado o un retorno falso no producen un pago confirmado.
- Ruta pública, sin CSRF (la llama el proveedor), con límite de intentos y auditoría de cada llamada.

## Consecuencias
- Cambian el `CLAUDE.md` raíz ("el tenant se resuelve por subdominio" y "usuarios del centro en la base del tenant"), `config/packages/hakam_multi_tenancy.yaml` (resolver apagado), nginx (sin comodín de subdominio) y `.env` (`TENANT_BASE_DOMAIN` desaparece y aparece el host único).
- Migraciones:
  - en Main: `user_account`, `tenant_membership` y el `public_ref` en `tenant_db_config`;
  - en cada clínica: perfil, roles, permisos, sedes y asignaciones.
- **Tests de aislamiento obligatorios:**
  - la clínica no cambia con ningún parámetro de la petición;
  - un usuario cargado para la clínica A con la sesión cambiada a B cierra la sesión;
  - sin TenantContext no se llega a ninguna ruta de clínica;
  - el cambio de clínica sin membresía se rechaza;
  - la sesión de plataforma no se mezcla con la de clínica.

## Preguntas abiertas (para Carlos)
1. ¿Segundo factor obligatorio para perfiles clínicos desde el inicio, o solo para plataforma?
2. ¿Quién crea las cuentas: el administrador de cada clínica (invitación por correo) o soporte desde plataforma?
3. Nombre del host único (por ejemplo, `app.<dominio>.cl`).

## Orden de implementación (cada paso con su plan y confirmación)
1. Identidad en Main e inicio de sesión (sin clínica todavía).
2. Membresías, TenantContext, apagado del resolver por subdominio y tests de aislamiento.
3. Perfiles, roles y sedes en la base de la clínica, y el menú de cambio de clínica y sede.
4. `public_ref` y la página de retorno de proveedores (después de la cola).
