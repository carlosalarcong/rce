#!/usr/bin/env bash
# El runner propio de GitHub Actions en este equipo (docs/ci.md). Basado en el de acredita360.
#
#   scripts/runner/runner.sh iniciar   construye la imagen, lo registra en GitHub (la primera vez) y lo deja corriendo
#   scripts/runner/runner.sh detener   lo detiene; queda registrado y vuelve con «iniciar»
#   scripts/runner/runner.sh logs      lo que está haciendo
#   scripts/runner/runner.sh quitar    lo da de baja en GitHub y borra su contenedor y su registro local
#
# Queda registrado solo en el repositorio carlosalarcong/rce (etiqueta rce-local). Usa la sesión de
# GitHub CLI de quien administra el repositorio para pedir los tokens de registro y de baja. Los tokens
# llegan al contenedor solo por el entorno; nunca se muestran ni se guardan.
set -euo pipefail

raiz="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$raiz"
repositorio="carlosalarcong/rce"
export RUNNER_WORKDIR="${RUNNER_WORKDIR:-$HOME/.rce-runner/trabajo}"
compose=(docker compose -f compose.runner.yaml)

exigir_gh() {
    if ! gh auth status >/dev/null 2>&1; then
        echo "Falta la sesión de GitHub CLI: corre «gh auth login» con una cuenta que administre $repositorio." >&2
        exit 1
    fi
}

iniciar() {
    exigir_gh
    mkdir -p "$RUNNER_WORKDIR"
    # La última versión oficial del runner y su SHA-256, del lanzamiento en GitHub.
    case "$(docker info --format '{{.Architecture}}')" in
        aarch64|arm64) export RUNNER_ARCH=linux-arm64 ;;
        x86_64|amd64) export RUNNER_ARCH=linux-x64 ;;
        *) echo "Arquitectura de Docker no soportada." >&2; exit 1 ;;
    esac
    lanzamiento="$(gh api repos/actions/runner/releases/latest)"
    RUNNER_VERSION="$(jq -r '.tag_name | ltrimstr("v")' <<<"$lanzamiento")"
    RUNNER_SHA256="$(jq -r '.body' <<<"$lanzamiento" | sed -n "s/.*<!-- BEGIN SHA ${RUNNER_ARCH} -->\([0-9a-f]\{64\}\)<!-- END SHA ${RUNNER_ARCH} -->.*/\1/p" | head -1)"
    if [ -z "$RUNNER_SHA256" ]; then
        echo "No se encontró el SHA-256 del runner ${RUNNER_VERSION} para ${RUNNER_ARCH}." >&2
        exit 1
    fi
    export RUNNER_VERSION RUNNER_SHA256
    echo "Runner oficial v${RUNNER_VERSION} (${RUNNER_ARCH})."
    "${compose[@]}" build
    # El token de registro solo hace falta la primera vez (el registro queda en el volumen).
    if ! { docker volume inspect rce-runner_estado >/dev/null 2>&1 \
        && docker run --rm -v rce-runner_estado:/estado alpine test -f /estado/.runner; }; then
        RUNNER_TOKEN="$(gh api -X POST "repos/$repositorio/actions/runners/registration-token" --jq .token)"
        export RUNNER_TOKEN
    fi
    "${compose[@]}" up -d
    unset RUNNER_TOKEN
    echo "Runner arriba. Míralo con «make runner-logs» o en GitHub: el repositorio, Settings → Actions → Runners."
}

quitar() {
    exigir_gh
    if docker ps --format '{{.Names}}' | grep -qx rce_runner; then
        token="$(gh api -X POST "repos/$repositorio/actions/runners/remove-token" --jq .token)"
        docker exec -e TOKEN="$token" rce_runner bash -c './config.sh remove --token "$TOKEN"' || true
    fi
    "${compose[@]}" down -v
    echo "Runner dado de baja y borrado de este equipo."
}

case "${1:-}" in
    iniciar) iniciar ;;
    detener) "${compose[@]}" stop ;;
    logs) "${compose[@]}" logs -f --tail 50 ;;
    quitar) quitar ;;
    *) echo "Uso: $0 iniciar|detener|logs|quitar" >&2; exit 2 ;;
esac
