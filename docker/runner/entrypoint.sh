#!/usr/bin/env bash
# Registra el runner la primera vez (con el token de registro, que vence en una hora y no se guarda) y
# después lo deja corriendo. Su registro queda en el volumen /estado: al reiniciar, no pide token.
set -euo pipefail
cd /actions-runner

archivos=(.runner .credentials .credentials_rsaparams)
if [ -f /estado/.runner ]; then
    for f in "${archivos[@]}"; do cp "/estado/$f" "./$f"; done
else
    : "${RUNNER_TOKEN:?Falta el token de registro: inicia el runner con make runner (lo pide a GitHub).}"
    ./config.sh --unattended --replace \
        --url "${RUNNER_URL}" --token "${RUNNER_TOKEN}" \
        --name "${RUNNER_NAME}" --labels "${RUNNER_LABELS}" --work "${RUNNER_WORKDIR}"
    for f in "${archivos[@]}"; do cp "./$f" "/estado/$f"; done
fi
unset RUNNER_TOKEN
exec ./run.sh
