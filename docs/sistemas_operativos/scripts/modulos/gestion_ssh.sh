#!/usr/bin/env bash
# =====================================================================
# gestion_ssh.sh — Diagnóstico del servicio SSH del servidor SGRSI
# Muestra estado del demonio y las llaves autorizadas instaladas.
# =====================================================================
set -euo pipefail
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$DIR/comunes.sh"

requiere_root

echo -e "${TITULO}=== GESTIÓN SSH ===${NC}"
systemctl status ssh --no-pager -l | head -5

echo -e "${TITULO}\nConfiguración efectiva relevante:${NC}"
sshd -T | grep -Ei 'permitrootlogin|passwordauthentication|pubkeyauthentication|allowusers|maxauthtries' | sort

echo -e "${TITULO}\nLlaves autorizadas (sgrsi-admin):${NC}"
AK="/home/sgrsi-admin/.ssh/authorized_keys"
if [ -f "$AK" ]; then
    awk '{print NR": tipo=" $1 " comentario=" $NF}' "$AK"
else
    aviso "Sin llaves instaladas en $AK"
fi

echo -e "${TITULO}\nÚltimas conexiones:${NC}"
last -n 5 sgrsi-admin || true
