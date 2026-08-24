#!/usr/bin/env bash
# =====================================================================
# respaldo_mensual.sh — Copia MENSUAL a largo plazo (regla GFS: abuelo)
# Crea un paquete único con hash SHA-256 y, si rclone está configurado,
# lo envía al almacenamiento en nube institucional.
# Cron: 0 4 1 * * /opt/sgrsi-admin/modulos/respaldo_mensual.sh
# =====================================================================
set -euo pipefail
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$DIR/comunes.sh"

requiere_root
preparar_carpetas

MES="$(date +%Y-%m)"
DESTINO="$BACKUP_DIR/mensual"
mkdir -p "$DESTINO"

PAQUETE="$DESTINO/sgrsi_full_${MES}.tar.gz"
info "Generando respaldo mensual consolidado..."
tar -czf "$PAQUETE" -C "$BACKUP_DIR" db app config

info "Calculando hash de integridad SHA-256..."
sha256sum "$PAQUETE" > "${PAQUETE}.sha256"

# Envío a la nube si existe el remoto 'institucional' configurado en rclone
if command -v rclone >/dev/null && rclone listremotes | grep -q '^institucional:'; then
    info "Enviando a almacenamiento en nube (rclone: institucional)..."
    rclone copy "$DESTINO" institucional:sgrsi-respaldos/mensual/ --transfers 2
else
    aviso "rclone sin remoto 'institucional': copie manualmente $PAQUETE a un medio externo."
fi

echo "$PAQUETE" >> "$LOG_BACKUP"
info "Respaldo mensual finalizado: $PAQUETE"
