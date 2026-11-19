# Prompt: decisión 1, identificadores y número de ficha

Antes de pegarlo, crea una carpeta aparte para la rama `arquitecto`, así tu trabajo en `clinical` no se toca. Desde la carpeta del repo `rce`:

```bash
git worktree add ../rce-arquitecto arquitecto
cd ../rce-arquitecto
claude
```

Copia todo lo que está debajo de la línea y pégalo en esa sesión nueva de Claude Code (la de `rce-arquitecto`).

---

Trabajamos en el RCE (registro clínico electrónico SaaS para Chile; Symfony 7.4, PHP 8.5, PostgreSQL 18, Hakam con una base de datos por clínica). Estamos en **fase de diseño**: no escribas código. Solo propones, discutimos y, cuando yo lo apruebe, se escribe una ADR.

## Antes de empezar

1. Esta sesión corre en un worktree (`rce-arquitecto`) con la rama `arquitecto`, donde viven el modo arquitecto, el agente `arquitecto-revisor` y las revisiones. Confirma con `git branch --show-current` que estás en `arquitecto`; si no, detente y avísame. **No cambies de rama y no modifiques nada en la carpeta `../rce`**: ahí estoy trabajando en la rama `clinical`, con cambios que pueden estar sin commit. Solo puedes leer de ahí.
2. Lee, en este orden:
   - `CLAUDE.md` (sección "Modo arquitecto").
   - `docs/revisiones/2026-11-17-auditoria-inicial.md`: hallazgo 3 y la sección "Decisiones que faltan". Esta decisión es la **número 1** del orden.
   - `docs/revisiones/2026-11-17-identidad-paciente.md`: lo que dijo el revisor sobre la identidad del paciente y el orden corregido.
   - `docs/adr/0005-inicio-de-sesion-y-multi-sede.md` (clínica activa en la sesión, un solo host `app.rce.cl`) y `docs/adr/0000-plantilla.md`.
   - De la rama `clinical`, la spec del paciente (datos del paciente y casos de identificación). Lee la versión de trabajo en `../rce/docs/specs/nucleo-clinico/paciente.md`, que incluye mis cambios sin commit. Si esa ruta no existe, usa `git show clinical:docs/specs/nucleo-clinico/paciente.md` y avísame que estás leyendo solo lo que tiene commit. **Ojo:** esa spec cita la NT820, que ya no está vigente (ver "Datos que ya están cerrados"). Si encuentras diferencias entre la spec y lo que dice este prompt, avísame; no corrijas la spec por tu cuenta.
   - `config/packages/doctrine.yaml` (hoy el EM `tenant` usa ids `identity`).

## Qué hay que decidir

La decisión 1 tiene tres partes. **No toca la identidad del paciente** (es la decisión 6 del orden, con su propia ADR), pero debe dejarle el camino abierto.

### A. Número técnico de cada registro de la base de la clínica
- Problema (hallazgo 3): con ids enteros y un solo host, una pestaña vieja puede guardar una nota en el paciente 42 de otra clínica, que es otra persona.
- Propuesta en discusión:
  - **UUID v7** para toda entidad de tenant, generado en la aplicación con `symfony/uid` (ya está en `composer.json`, versión 7.4.*).
  - Cambiar la preferencia `identity` del EM `tenant` antes de la primera migración clínica (hoy no hay ninguna).
  - PostgreSQL 18 también tiene `uuidv7()`. Explica por qué conviene generarlo en la app o en la base.
- Datos verificados que debes considerar:
  - La documentación de PostgreSQL 18 describe `uuidv7()` como "time-ordered" (milisegundos + fracción sub-milisegundo + aleatorio). **No promete orden estricto entre sesiones.** Fuente: https://www.postgresql.org/docs/18/functions-uuid.html
  - En Symfony 7.4 la versión por defecto de la fábrica de UUID es v7 y la precisión es de microsegundos. Fuente: https://symfony.com/doc/7.4/components/uid.html
  - Trampa 1: `#[ORM\CustomIdGenerator(class: UuidGenerator::class)]` crea un generador nuevo que ignora la versión configurada. Hay que usar el servicio: `#[ORM\CustomIdGenerator('doctrine.uuid_generator')]`. Misma fuente.
  - Trampa 2: en DQL o consultas armadas a mano, el parámetro UUID necesita tipo explícito (`UuidType::NAME`). Misma fuente.
  - Costo: v7 resuelve la fragmentación del índice, pero el índice sigue siendo más grande que con enteros. Misma fuente.

### B. Número de ficha legible
- El **Decreto 41 de 2012 (MINSAL), art. 6, letra b)** exige un "número identificador de la ficha", y cada documento que se agrega a la ficha debe llevarlo. El artículo no tiene modificaciones desde 2012. Fuentes: https://iura.cl/dto-41-minsal/6.html y https://superdesalud.gob.cl/normativa/571/articles-12707_recurso_1.pdf (la página de LeyChile no cargó al verificar: https://www.bcn.cl/leychile/navegar?i=1046753).
- Debe poder dictarse por teléfono y es distinto del UUID.
- Propuesta en discusión: correlativo **por clínica** con una secuencia de PostgreSQL. No se reutiliza nunca, ni siquiera si la ficha se fusiona o se anula.
- Respaldo normativo de la propuesta:
  - **Art. 4:** debe existir una ficha clínica de cada paciente que atiende el prestador respectivo. El decreto habla de prestador, no de sede. Es una lectura, no un pronunciamiento oficial. Fuente: https://iura.cl/dto-41-minsal/4
  - **Art. 12:** al eliminar fichas vencido el plazo de conservación, se debe registrar al menos el nombre del paciente y el número identificatorio de las fichas. Un número reutilizado haría ambiguo ese registro. Fuente: https://iura.cl/dto-41-minsal/12
- **Preguntas que te debo responder** (pregúntamelas si no están respondidas abajo):
  1. Cuando un centro se cambia al RCE, ¿mantiene los números de ficha que ya usaba? (No hay norma que lo regule; es decisión de producto.)
  2. Si un centro tiene varias sedes, ¿el número de ficha es uno para el centro o uno por sede?

  Mis respuestas: _(completar)_

### C. Contexto de clínica en cada petición
- Toda petición que cambia datos lleva la clave de la clínica en que se generó la pantalla (encabezado o campo oculto).
- Un listener global la compara con la clínica de la sesión. Si no coincide o falta, responde **409** con un aviso claro ("Cambiaste a Clínica B; recarga la página").
- El frontend es **solo Turbo + Stimulus** (sin Live Components). Explica cómo viaja la clave en formularios normales, Turbo Frames y peticiones de Stimulus.
- Datos verificados que debes considerar (fuente: https://turbo.hotwired.dev/reference/events):
  - `turbo:before-fetch-request` se dispara en visitas de página, Turbo Frames y envíos de formulario, y expone `event.detail.fetchOptions` para agregar encabezados. Un solo listener cubre esos tres casos.
  - Un `fetch()` propio desde un controlador Stimulus no pasa por Turbo; necesita su propio envoltorio. (Deducción: el evento solo cubre peticiones que emite Turbo.)
  - La documentación **no explica** cómo se muestra una respuesta 409 a un formulario; solo dice que `turbo:submit-end` llega con `success: false` y `fetchResponse`. Déjalo anotado como algo a probar antes de cerrarlo en la ADR.
- Código de clínica **inmutable** (no cambia aunque cambie el nombre comercial).
- Agregar el caso "pestaña vieja después de cambiar de clínica" a los tests de aislamiento de la ADR 0005.

## Datos que ya están cerrados y debes respetar

- **Norma vigente:** la NT820 ya no rige. El **Decreto Exento N° 9 del 24 de febrero de 2023** (Diario Oficial del 6 de marzo de 2023) aprobó la **Norma Técnica N° 231 "Estándares de Información en Salud"** y dejó sin efecto el Decreto Exento N° 643 de 2016, que había sustituido a la 820. En la ADR cita la NT 231. Fuente: https://www.diariooficial.interior.gob.cl/publicaciones/2023/03/06/43494/01/2280987.pdf
- **RUN:** valor numérico con máximo 999999999 (9 dígitos) más dígito verificador alfanumérico (0-9 o K). Fuente: https://deis.minsal.cl/norma-eis-estandares-de-datos-de-la-persona-identificacion-de-la-persona-responsivo/ . El número de página en el PDF de la NT 231 está **sin verificar**; no cites "p. 53". El value object `Run` unificará las tres reglas que hoy hay en el código (pagos acepta 8, IMED 10).
- **El paciente no siempre tiene RUN.** Según la misma página del DEIS, los identificadores son subclases separadas, en este orden de uso:
  1. RUN principal (siempre que esté disponible).
  2. RUN materno/progenitor(a) (cuando no hay RUN principal). En gemelos se repite; la página del DEIS no trata ese caso.
  3. Folio del comprobante de parto (excepcional: solo si no hay RUN principal **ni** materno). **Corrección:** no es "RUN de la madre + folio" como un solo identificador; son dos identificadores distintos. **Sin verificar contra el PDF completo de la NT 231**; si la spec de `clinical` dice otra cosa, avísame.
  4. Pasaporte, documento identificador del país de origen, identificador provisorio FONASA (NIP): excepcionales, solo si no hay RUN.
  5. Persona sin ningún documento: la página del DEIS no lo contempla; el sistema igual debe soportarlo.
- **País emisor del documento extranjero:** se guarda. La página del DEIS no define ese campo; lo exige el perfil FHIR de paciente del MINSAL (extensión `PaisEmisor`, 1..1). Fuente: https://build.fhir.org/ig/Minsal-CL/NID/StructureDefinition-MINSALPaciente.html
- El número técnico y el número de ficha **no pueden depender del RUN**.
- Nada se borra ni se sobrescribe. El número de ficha de un paciente no cambia nunca.

## Riesgos normativos a anotar en la ADR (no resolver aquí)

- **Reglamento de fichas en actualización:** la Ley 21.668 (28-05-2024) dio al MINSAL dieciocho meses para actualizar el reglamento de fichas clínicas; el plazo venció y sigue pendiente. El Decreto 41 puede cambiar. Fuentes: https://www.bcn.cl/leychile/navegar?idNorma=1203827 y https://estadodiario.com/columnas/la-ficha-clinica-ante-sus-nuevas-leyes/
- **Ley 21.719 de datos personales:** entra en vigencia el 1 de diciembre de 2026. Misma columna.
- **NT 245 "Identificación de paciente"** (Resolución Exenta 471, 4 de julio de 2025): afecta la decisión 6, no esta. Contenido sin revisar. Fuente: https://www.diariooficial.interior.gob.cl/publicaciones/2025/07/25/44207/01/2671588.pdf

## Lo que no hay que decidir ahora

Enmiendas, auditoría, cifrado, fusión de pacientes e historial reservado de la Ley 21.120: son decisiones posteriores. Si algo de esta decisión depende de ellas, dímelo y déjalo anotado como pendiente; no lo resuelvas aquí.

## Cómo quiero que trabajes

1. Explícame cada parte con palabras simples y un ejemplo cotidiano, en español de Chile. El código, en inglés.
2. Dame tu recomendación y las alternativas que descartas, con el motivo en una línea.
3. Cuando te responda las dos preguntas de la parte B, pasa la propuesta completa por el agente `arquitecto-revisor`.
4. Muéstrame sus hallazgos, verifica tú los bloqueantes y corrige.
5. Con mi aprobación, escribe la ADR con la plantilla, con el siguiente número libre en `docs/adr/` (ej. `0007-identificadores-y-contexto-de-clinica.md`) y guarda la revisión en `docs/revisiones/`. En la ADR, cada dato normativo o técnico lleva su fuente (las URLs de este prompt). Haz commit en `arquitecto` solo cuando te lo pida.

Reglas que no cambian: pregunta antes de tocar migraciones, la configuración de Hakam, `Platform/Security`, `Platform/Audit` o dependencias de Composer. Nunca uses datos reales de pacientes en ejemplos.
