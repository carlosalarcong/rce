#!/usr/bin/env bash
# Regla de src/Integration/CLAUDE.md: un adaptador no depende de otro adaptador.
# Revisa cada src/Integration/*/Adapter/<Proveedor>/ y falla si nombra a otro proveedor del mismo módulo.
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

fallas=0
for adapter_root in src/Integration/*/Adapter; do
    [ -d "$adapter_root" ] || continue
    module="$(basename "$(dirname "$adapter_root")")"
    for dir in "$adapter_root"/*/; do
        own="$(basename "$dir")"
        for other_dir in "$adapter_root"/*/; do
            other="$(basename "$other_dir")"
            [ "$other" = "$own" ] && continue
            if grep -rnE "App\\\\Integration\\\\${module}\\\\Adapter\\\\${other}\\\\" "$dir" --include='*.php' 2>/dev/null; then
                echo "El adaptador ${module}/${own} usa al adaptador ${module}/${other}: un adaptador no depende de otro." >&2
                fallas=1
            fi
        done
    done
done
exit "$fallas"
