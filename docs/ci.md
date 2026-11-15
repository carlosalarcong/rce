# CI del RCE: hook y runner propio

Basado en el de acredita360. Dos niveles de revisión:

| Qué | Dónde corre | Cuándo | Comando |
|---|---|---|---|
| Hook antes del push | En tu computador | Solo, en cada `git push` | `make hooks` (o `make dev`) lo activa |
| CI (`.github/workflows/ci.yml`) | Runner propio (o GitHub) | Cada push y PR a `develop`/`master` | `make runner` (una vez) |

## Ramas
Solo `master` (producción, rama por defecto) y `develop` (integración). El trabajo entra a `develop` y de ahí a `master`.

## Hook antes del push
`scripts/git-hooks/pre-push`, que se activa con `git config core.hooksPath scripts/git-hooks`, corre `make ci-rapida`: reglas, estilo, lint, PHPStan, Deptrac y tests unitarios. Tarda menos de un minuto y, si falla, no se sube nada.

Necesita el stack de desarrollo arriba (`make up`). En un apuro: `git push --no-verify`.

## CI
Levanta el mismo stack de `compose.yaml` (la misma imagen PHP 8.5 que desarrollo) y corre `make check`: reglas, estilo, lint, PHPStan, Deptrac y **todos** los tests.

Cada corrida usa su propio proyecto de Compose (`rce-ci-<run_id>`) y puertos aleatorios, así no choca con el stack de desarrollo del mismo equipo. Al terminar baja todo, volúmenes incluidos.

## Runner propio
Es un contenedor (`compose.runner.yaml`, proyecto `rce-runner`) con:
- Ubuntu 26.04 LTS, en versión fija;
- el runner oficial de GitHub (última versión, verificada con su SHA-256);
- el cliente de Docker.

GitHub le manda la CI y muestra el resultado, sin gastar minutos de GitHub.

- `make runner`: construye la imagen, lo registra **en el repositorio `carlosalarcong/rce`** la primera vez (con tu sesión de `gh`) y lo deja corriendo. Se reinicia solo con Docker.
- `make runner-logs`, `make runner-detener` y `make runner-quitar` (este último lo da de baja en GitHub).
- **Activarlo:** la variable del repositorio `CI_RUNNER=rce-local` (`gh variable set CI_RUNNER --body rce-local`). Sin la variable, la CI corre en `ubuntu-26.04` de GitHub.
- **Es aparte del runner de acredita360.** Ese está registrado en la organización `nexia-health` y no puede servir a un repo personal. Los dos corren en paralelo, un trabajo cada uno.
- **Cómo funciona por dentro:** maneja el Docker del equipo por su socket. Su carpeta de trabajo (`~/.rce-runner/trabajo`) se monta en la misma ruta dentro y fuera, porque `compose.yaml` monta `./` y esa ruta la resuelve el Docker del equipo.
- **Seguridad:** quien controla el runner controla el Docker del equipo. El repositorio es privado y el runner sirve solo a ese repo. No hacerlo público mientras use este runner.
- **Requisitos:** el computador encendido (si no, la CI espera) y Docker con memoria suficiente.
- **Versión de Ubuntu:** fija en `docker/runner/Dockerfile` y en `runs-on` de `ci.yml`. Al subir de LTS, cambiar ambos.
