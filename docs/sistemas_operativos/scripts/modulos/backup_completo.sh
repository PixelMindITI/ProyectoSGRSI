#!/usr/bin/env bash
# =====================================================================
# backup_completo.sh — Respaldo DIARIO del SGRSI (BD + app + config)
# Programado por cron: 0 2 * * * /opt/sgrsi-admin/modulos/backup_completo.sh
# =====================================================================
set -euo pipefail
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$DIR/comunes.sh"

requiere_root
preparar_carpetas
verificar_dependencias

FECHA="$(date +%Y%m%d_%H%M)"

# 1) Base de datos (dump lógico comprimido)
info "Respaldando base de datos $BD_NOMBRE..."
mysqldump --defaults-extra-file="$BD_CNF" \
          --single-transaction --routines --triggers \
          "$BD_NOMBRE" | gzip > "$BACKUP_DB/${BD_NOMBRE}_${FECHA}.sql.gz"

# 2) Aplicación (código desplegado)
info "Respaldando aplicación en $APP_DIR..."
tar -czf "$BACKUP_APP/app_${FECHA}.tar.gz" -C "$(dirname "$APP_DIR")" "$(basename "$APP_DIR")"

# 3) Configuraciones clave del servidor
info "Respaldando configuraciones (/etc)..."
tar -czf "$BACKUP_CFG/etc_${FECHA}.tar.gz" \
    /etc/ssh/sshd_config /etc/netplan /etc/apache2 /etc/php /etc/fail2ban 2>/dev/null || true

# 4) Rotación local: elimina respaldos con más de RETENCION_DIAS días
find "$BACKUP_DIR" -type f \( -name '*.sql.gz' -o -name '*.tar.gz' \) \
     -mtime "+$RETENCION_DIAS" -print -delete | sed 's/^/[ROTADO] /'

# 5) Resumen de tamaños generados
du -h "$BACKUP_DB/${BD_NOMBRE}_${FECHA}.sql.gz" \
      "$BACKUP_APP/app_${FECHA}.tar.gz" \
      "$BACKUP_CFG/etc_${FECHA}.tar.gz" | tee -a "$LOG_BACKUP"
info "Respaldo diario completado correctamente."
