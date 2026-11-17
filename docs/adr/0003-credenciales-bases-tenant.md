# 0003 — La clave de las bases de tenant vive solo en Secret Manager (GCP)

- **Estado:** aceptada
- **Fecha:** 2026-11-15

## Contexto
Desplegamos en Google Cloud Platform. Hakam lee la conexión de cada tenant desde `tenant_db_config` en Main, incluida la clave en texto plano, y su DTO exige `host`, `port` y `user` no nulos. Quien leyera esa tabla (un respaldo, un volcado o una consulta) obtendría la clave de **todas** las bases clínicas.

## Decisión
1. **La clave sale solo de `TENANT_DB_PASSWORD`** (`Platform/Tenancy/Infrastructure/Hakam/TenantDbPassword`). En GCP esa variable viene de Secret Manager; en desarrollo, de `.env`.
2. **Se decoran los dos puertos de Hakam** con `#[AsDecorator]`, sin tocar la librería:
   - `TenantConfigProviderWithSecretPassword` envuelve `TenantConfigProviderInterface`, que conecta la base en cada request;
   - `TenantDatabaseManagerWithSecretPassword` envuelve `TenantDatabaseManagerInterface`, que usan `tenant:database:create` y `tenant:migrations:*`. Al registrar un tenant lo guarda sin clave.

   Los dos ponen la clave del entorno e **ignoran** lo que diga la columna.
3. **`tenant_db_config` guarda host, puerto, usuario y nombre de la base** (no son secretos) y **nunca la clave**. La columna existe porque la exige el trait de Hakam.
4. **Regla en `make rules`** (parte de `make check`): nada en `src/` usa `setDbPassword(`, `getDbPassword(` ni la columna de la clave.

## Cambiar la clave
1. Crear una nueva versión del secreto en Secret Manager.
2. `ALTER ROLE <usuario_app> PASSWORD '…'`.
3. Redesplegar los servicios para que tomen la versión nueva. No se toca ninguna fila de Main.

## Consecuencias
- Hay un solo usuario y una sola clave para todas las bases de tenant. Si más adelante queremos un usuario por centro, `TenantDbPassword` pasaría a leer un secreto por tenant, sin cambiar los decoradores.
- Pendiente para el despliegue: Cloud Run llega a Cloud SQL por el socket `/cloudsql/<proyecto>:<región>:<instancia>` (`?host=` en la URL). Validar cómo queda `db_host` en ese caso.

## Otros detalles de Hakam encontrados
- `tenant:database:create --dbid=X` convierte X a entero y lo busca en la columna `subdomain`, así que no sirve con nuestro identificador. Usar `--all`.
- `addNewTenantDbConfig()` hace `new TenantDbConfig()` sin argumentos. Los tenants se crean con un caso de uso propio, no con ese método.
- Hakam arma DSN `pgsql://` para crear bases, y DBAL lo resuelve al driver nativo. Por eso la imagen PHP incluye la extensión `pgsql` además de `pdo_pgsql`.
