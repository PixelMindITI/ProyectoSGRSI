#!/usr/bin/env bash
# =====================================================================
# actualizar_sistema.sh — Actualizaciones APT con registro en log
# Uso: sudo ./actualizar_sistema.sh   (o desde el menú principal)
# =====================================================================
set -euo pipefail
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$DIR/comunes.sh"

requiere_root

info "Actualizando índice de paquetes..."
apt-get update -qq

info "Aplicando actualizaciones disponibles..."
DEBIAN_FRONTEND=noninteractive apt-get upgrade -y

info "Limpiando paquetes huérfanos y caché..."
apt-get autoremove -y >/dev/null
apt-get autoclean >/dev/null

PENDIENTES=$(apt list --upgradable 2>/dev/null | grep -c upgradable || true)
info "Sistema actualizado. Paquetes pendientes: $PENDIENTES"
echo "$(date '+%F %T') actualización ejecutada" >> "$LOG_ESTADO"
