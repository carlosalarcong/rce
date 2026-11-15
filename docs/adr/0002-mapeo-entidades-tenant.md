# 0002 — Mapeo de entidades de tenant: un mapping XML por submódulo

- **Estado:** aceptada (validada con un spike)
- **Fecha:** 2026-11-15

## Contexto
El `CLAUDE.md` dejaba pendiente validar si Hakam permite que el EM de tenant apunte a varios directorios (uno por módulo). Si no, el respaldo era `src/Entity/Tenant/{Modulo}`.

Además, `Domain/` no puede tener atributos de Doctrine: el mapeo va en `Infrastructure/`.

## Lo que encontramos
- La configuración de Hakam (`tenant_entity_manager.mapping`) acepta **un solo** `dir`/`prefix`.
- Pero Hakam crea el EM `tenant` con `prependExtensionConfig('doctrine', ...)`. Por eso los mappings que declaramos en `config/packages/doctrine.yaml` bajo `orm.entity_managers.tenant.mappings` **se suman** al suyo.

## Spike (2026-11-15)
- Hicimos dos entidades desechables en módulos distintos (`Clinical/SpikeA`, `Ambulatory/SpikeB`), sin atributos y con mapeo XML en `Infrastructure/Doctrine/Mapping/`, y las registramos en `doctrine.yaml`.
- Lo que comprobamos:
  - `doctrine:mapping:info --em=tenant` lista las dos y `--em=default` solo `TenantDbConfig`;
  - `tenant:migrations:diff` genera una migración de tenant con las tablas de ambos módulos;
  - después de `SwitchDbEvent('spike')`, el EM de tenant escribe y lee las dos entidades en `rce_tenant_spike`, y Main no tiene esas tablas;
  - sin tenant resuelto, el EM de tenant falla con `ConnectionException`, porque la base de arranque `rce_no_tenant` no existe.
- El código del spike se borró.

## Decisión
- Las entidades de tenant viven en `src/{Modulo}/{Submodulo}/Domain/`, **sin atributos**.
- El mapeo es XML, en `src/{Modulo}/{Submodulo}/Infrastructure/Doctrine/Mapping/{Entidad}.orm.xml`.
- Cada submódulo agrega su mapping en `doctrine.yaml`:
  ```yaml
  ClinicalPatient:
      type: xml
      is_bundle: false
      dir: '%kernel.project_dir%/src/Clinical/Patient/Infrastructure/Doctrine/Mapping'
      prefix: 'App\Clinical\Patient\Domain'
  ```
- El mapping propio de Hakam apunta a `src/Platform/Tenancy/Infrastructure/Doctrine/TenantMapping` (vacío). Hakam exige uno.
- No usamos el respaldo `src/Entity/Tenant/{Modulo}`.

## Consecuencias
- **Las entidades no pueden ser `final`.** Doctrine ORM 3 genera "lazy ghosts" y falla con `Cannot generate lazy ghost: class ... is final`.
- Agregar un submódulo con entidades implica tocar `doctrine.yaml`. Ese archivo requiere confirmación, según `src/Platform/Tenancy/CLAUDE.md`.
- Los directorios de mapeo de Main y de tenant no se pueden anidar entre sí: el driver escanea recursivamente y mapearía dos veces (la misma lección está en acredita360).
