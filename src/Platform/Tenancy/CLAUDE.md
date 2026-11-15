# Platform/Tenancy

Registro de tenants (centros de salud) y conexión a la base de cada uno, sobre hakam/multi-tenancy-bundle.

- `Infrastructure/Doctrine/Entity/TenantDbConfig`: el registro del tenant. Vive en la base **Main** y se mapea con atributos (usa el trait de Hakam). Es infraestructura, no dominio.
- El tenant se identifica por su **subdominio** (`tenant_database_identifier: subdomain`).
- El registro guarda host, puerto, usuario y nombre de la base. **Nunca la clave**: la pone `Infrastructure/Hakam/TenantDbPassword` desde `TENANT_DB_PASSWORD` (Secret Manager en GCP). Ver `docs/adr/0003`. `make rules` lo verifica.
- Los decoradores de `Infrastructure/Hakam/` envuelven los puertos de Hakam: no reemplazarlos ni saltárselos.
- La conexión de arranque del EM de tenant apunta a una base inexistente (`rce_no_tenant`) para que nada escriba en Main por accidente.
- Las entidades de tenant de cada módulo se registran en `doctrine.yaml` (`entity_managers.tenant.mappings`). Ver `docs/adr/0002`.
- **Pregunta antes de cambiar** `config/packages/hakam_multi_tenancy.yaml` o `doctrine.yaml`.
- Comandos de Hakam: `tenant:database:create --all` (no usar `--dbid`, ver ADR 0003), `tenant:migrations:diff <subdominio>`, `tenant:migrations:migrate init|migrate`, `tenant:fixtures:load`.
