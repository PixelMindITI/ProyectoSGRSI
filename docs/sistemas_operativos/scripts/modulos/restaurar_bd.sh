#!/usr/bin/env bash
# =====================================================================
# restaurar_bd.sh — Restauración SEGURA de la base de datos SGRSI
# Uso: sudo ./restaurar_bd.sh [archivo.sql.gz]
# Pide confirmación explícita porque sobrescribe datos de producción.
# =====================================================================
set -euo pipefail
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$DIR/comunes.sh"

requiere_root

ARCHIVO="${1:-}"
if [ -z "$ARCHIVO" ]; then
    echo "Respaldos disponibles en $BACKUP_DB:"
    ls -1t "$BACKUP_DB"/*.sql.gz 2>/dev/null || error "No hay respaldos."
    read -rp "Ruta del dump a restaurar: " ARCHIVO
fi
[ -f "$ARCHIVO" ] || error "No existe el archivo: $ARCHIVO"

echo -e "${AVISO}Se SOBREESCRIBIRA la base de datos '$BD_NOMBRE'.${NC}"
read -rp "Escriba SI para confirmar: " CONFIRMA
[ "$CONFIRMA" = "SI" ] || salir

info "Creando copia previa de seguridad (por las dudas)..."
mysqldump --defaults-extra-file="$BD_CNF" --single-transaction \
          "$BD_NOMBRE" | gzip > "$BACKUP_DB/pre_restauracion_$(date +%Y%m%d_%H%M).sql.gz"

info "Restaurando $ARCHIVO en $BD_NOMBRE..."
gunzip < "$ARCHIVO" | mysql --defaults-extra-file="$BD_CNF" "$BD_NOMBRE"

FILAS=$(mysql --defaults-extra-file="$BD_CNF" -N \
        -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$BD_NOMBRE';")
info "Restauración completada. Tablas presentes: $FILAS"
