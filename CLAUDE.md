# CLAUDE.md — RCE (Registro Clínico Electrónico)

## Qué es este proyecto
RCE SaaS multi-tenant para Chile. Parte por el sector **privado ambulatorio** (MVP) y crece por fases:
1. MVP privado ambulatorio
2. Lo que salió del MVP (pagos, telemedicina, etc.)
3. Hospitalario privado
4. Capa pública (APS, REM, GES)
5. Integraciones según demanda

Objetivo de producto: **rápido, simple y confiable**. Cada pantalla debe cargar rápido y requerir pocos clics.

## Stack
- PHP 8.5 · Symfony 7.4 LTS · PostgreSQL 18 · Docker (nginx + php-fpm) — ver `docs/adr/0001`
- Multi-tenant: **hakam/multi-tenancy-bundle** (una base de datos por tenant)
- Frontend: Twig + Symfony UX (Live Components, Turbo, Stimulus)
- Cola: Symfony Messenger con outbox en la base de cada tenant y Google Cloud Tasks (simulador local en el contenedor `tasks`) — ver `docs/adr/0006`
- FHIR: HAPI FHIR en contenedor aparte (no se programa en Java)
- HL7 v2: OIE (fork gratis de Mirth), solo en Fase 5
- Calidad: PHPUnit, PHPStan (nivel max), Deptrac (`deptrac.modules.yaml` y `deptrac.layers.yaml`), PHP CS Fixer. `make check` corre todo.

## Arquitectura: monolito modular
```
src/
├── Platform/        # base transversal: Tenancy, Identity, Audit, Security, Catalog, Files, Documents, Notification, Reporting
├── Integration/     # Messaging (outbox, cola, visor), Fhir, Hl7v2, Imed, Webpay, Sii, Lme, MinsalRecipe
├── Clinical/        # núcleo clínico compartido: Patient, Encounter, Diagnosis, Prescription, Order, ClinicalDocument
├── Ambulatory/      # Scheduling, Reception, Telemedicine, Billing (caja ambulatoria)
├── Inpatient/       # Admission, Billing (caja hospitalaria), Pharmacy, Emergency, Surgery, Ward, Nursing
└── PublicSector/    # PrimaryCare, Statistics, Ges, Network, Grd
```

Cada módulo tiene la misma estructura interna:
```
Modulo/
├── CLAUDE.md          # reglas propias del módulo (léelo antes de trabajar ahí)
├── Domain/            # entidades, value objects, eventos, interfaces de repositorio
├── Application/       # comandos, consultas y handlers (la API pública del módulo)
├── Infrastructure/    # repositorios Doctrine, mapeos, clientes externos
└── UI/                # controllers, Live Components, forms
```

## Reglas de dependencia (Deptrac las hace cumplir)
1. Las dependencias van **solo hacia abajo**: `Ambulatory | Inpatient | PublicSector` → `Clinical` → `Platform`. `Integration` puede usar `Clinical` y `Platform`. La caja (`Ambulatory`, `Inpatient`) puede usar de `Integration` **solo** su `Contract/` y su `Application/`, nunca un adaptador.
2. **Nunca al revés.** `Clinical` no sabe que existen la caja, el hospital ni el sector público.
3. Un módulo **no usa entidades ni repositorios de otro**. Se comunica solo por:
   - servicios de la capa `Application` del otro módulo, o
   - eventos de dominio por Symfony Messenger.
4. `Ambulatory/Billing` e `Inpatient/Billing` son cajas **separadas**. Lo compartido (medios de pago) vive en `Integration`.
5. `Domain/` no depende de Symfony ni de Doctrine (sin atributos de framework en el dominio salvo el mapeo, que va en `Infrastructure/`).

## Multi-tenant con Hakam
- **Una base de datos por tenant** (por centro de salud). Aísla los datos clínicos de cada cliente: es clave para la Ley 21.719 y permite respaldar o eliminar un cliente completo.
- **Base principal (Main):** solo lo global
  - registro de tenants y su configuración de conexión (`TenantDbConfigurationInterface`)
  - usuarios de soporte o superadmin
  - catálogos nacionales de solo lectura (CIE-10, comunas, previsiones)
- **Base del tenant:** todo lo demás: pacientes, atenciones, agenda, caja, auditoría, aranceles y usuarios del centro.
- El tenant se resuelve **antes** de cualquier lógica (por subdominio: `centro.dominio.cl`). Nunca confiar en un `tenant_id` que venga del cliente.
- Para cambiar de base usar `SwitchDbEvent`; en código de tenant usar el `TenantEntityManager`, nunca el entity manager por defecto.
- Migraciones separadas: `migrations/Main/` y `migrations/Tenant/`. Toda migración de tenant debe poder correr en **todas** las bases de tenant sin intervención manual.
- Mapeo de entidades: un mapping XML por submódulo, registrado en `doctrine.yaml` bajo `entity_managers.tenant.mappings` (validado, ver `docs/adr/0002`). Las entidades no pueden ser `final`.
- La clave de las bases de tenant vive solo en Secret Manager (GCP), nunca en `tenant_db_config` (ver `docs/adr/0003`).
- Fixtures por tenant con el atributo `#[TenantFixture]`.
- Comandos de consola de tenants: ver la documentación oficial del bundle (sección CLI commands) antes de usarlos; no inventar nombres de comandos.

## Reglas de datos clínicos (no negociables)
- **La ficha clínica no se borra ni se sobrescribe.** Toda corrección es una enmienda nueva que guarda quién, cuándo y por qué, y deja visible el valor anterior.
- **Todo acceso y cambio a datos clínicos se audita** (`Platform/Audit`): quién vio o cambió qué, cuándo y desde dónde.
- Los datos sensibles van **cifrados** (adjuntos y campos marcados como sensibles).
- Las **alergias** se muestran siempre como alerta visible en la ficha y en la receta.
- El RUN se valida con dígito verificador y se guarda normalizado (sin puntos, con guion). Usar el value object `Run`.
- Diagnósticos siempre codificados en CIE-10.
- Retención mínima: 15 años (Ley 20.584).
- Nunca poner datos de pacientes en logs, mensajes de error ni fixtures (usar datos ficticios).

## Convenciones de código
- Código, clases y variables en **inglés**. Los términos chilenos sin traducción quedan tal cual: `Run`, `Rem`, `Ges`, `Pam`, `Imed`, `Fonasa`, `Isapre`.
- Textos de la interfaz en **español de Chile**.
- `declare(strict_types=1);` en todos los archivos.
- Un caso de uso = un comando o consulta con su handler en `Application/`.
- Controllers delgados: solo reciben, llaman a `Application` y responden.
- Fechas en UTC en la base; se muestran en `America/Santiago`.
- Dinero en enteros (pesos, sin decimales), nunca en float.

## Tests
- Cada funcionalidad nueva llega **con sus tests en el mismo cambio**, no después.
- `tests/` replica la estructura de `src/`: `Unit/`, `Integration/`, `Functional/`.
- Lo clínico, la caja y los permisos necesitan tests de casos borde (no solo el camino feliz).
- Antes de terminar una tarea: `make check` debe pasar (tests, PHPStan, Deptrac, estilo, lint y reglas). El hook `pre-push` corre `make ci-rapida` y la CI corre `make check` en el runner propio (ver `docs/ci.md`).
- Ramas: `master` y `develop` son las principales. Se permiten ramas de trabajo que salen de `develop` y vuelven a `develop` (ej. `clinical`).

## Cómo trabajar en este repo (para Claude Code)
1. **Lee el `CLAUDE.md` del módulo** antes de tocarlo.
2. **Plan primero:** antes de escribir código, propone qué archivos vas a crear o cambiar y espera confirmación.
3. **No salgas del módulo** de la tarea. Si necesitas algo de otro módulo, usa su capa `Application` o pide que se cree.
4. **Pregunta antes de:** crear migraciones, cambiar la configuración de Hakam, tocar `Platform/Security` o `Platform/Audit`, o agregar dependencias de Composer.
5. Cambios chicos y enfocados: un caso de uso por vez.
6. Si una regla de este archivo choca con lo que se te pide, avisa antes de hacerlo.

## Documentación
- `docs/specs/`: una especificación por bloque del plan (pantallas, reglas, casos borde).
- `docs/adr/`: decisiones de arquitectura (una por archivo, numeradas).
- `docs/investigacion/`: investigación de competencia y de usuarios (un archivo por tema, con la fecha al inicio del nombre). Es contexto, no decide nada: lo que salga de ahí se decide en una ADR.
