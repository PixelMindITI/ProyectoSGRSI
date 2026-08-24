#!/usr/bin/env bash
# =====================================================================
# verificar_estado.sh — Salud del servidor SGRSI (alerta temprana)
# Cron: 0 5 * * * /opt/sgrsi-admin/modulos/verificar_estado.sh
# =====================================================================
set -euo pipefail
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$DIR/comunes.sh"

requiere_root
echo -e "${TITULO}=== ESTADO DEL SERVIDOR $(hostname) ===${NC}"

# 1) Servicios críticos del SGRSI
for SVC in apache2 mariadb ssh; do
    if systemctl is-active --quiet "$SVC"; then info "Servicio $SVC: ACTIVO"
    else aviso "Servicio $SVC: INACTIVO — revisar con systemctl status $SVC"; fi
done

# 2) Uso del disco raíz
USO=$(df / | awk 'NR==2 {gsub("%",""); print $5}')
if [ "$USO" -ge "$USO_MAX_DISCO" ]; then
    aviso "Disco raíz al ${USO}% (umbral $USO_MAX_DISCO%). Liberar espacio."
else
    info "Disco raíz al ${USO}%."
fi

# 3) Memoria
free -h | awk 'NR<=2'

# 4) Vigencia del último respaldo (>24h es alerta)
ULTIMO=$(ls -1t "$BACKUP_DB"/*.sql.gz 2>/dev/null | head -1 || true)
if [ -z "$ULTIMO" ]; then
    aviso "NO EXISTE ningún respaldo de BD."
elif [ $(( $(date +%s) - $(stat -c %Y "$ULTIMO") )) -gt 86400 ]; then
    aviso "El último respaldo tiene más de 24 h: $ULTIMO"
else
    info "Último respaldo vigente: $ULTIMO"
fi

echo "$(date '+%F %T') verificación completada" >> "$LOG_ESTADO"
