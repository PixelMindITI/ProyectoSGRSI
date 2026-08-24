#!/usr/bin/env bash
# =====================================================================
# comunes.sh — Librería compartida por todos los módulos del kit
# Define variables globales, colores y funciones de utilidad.
# Es "sourced" por sgrsi-admin.sh y por cada módulo.
# =====================================================================

# --- Variables globales del entorno SGRSI ---------------------------
BD_NOMBRE="sgrsi_db"                      # base de datos del proyecto
BD_USUARIO="sgrsi_user"                   # usuario exclusivo de la app
BD_CNF="/root/.sgrsi_backup.cnf"          # credenciales fuera del script
BACKUP_DIR="/backup/sgrsi"                # raíz de respaldos locales
BACKUP_DB="$BACKUP_DIR/db"
BACKUP_APP="$BACKUP_DIR/app"
BACKUP_CFG="$BACKUP_DIR/config"
APP_DIR="/var/www/html/sgrsi"             # instalación de la aplicación
CFG_DIR="/etc"                            # configuraciones a resguardar
RETENCION_DIAS=7                          # rotación local (regla GFS: hijo)
USO_MAX_DISCO=85                          # % que dispara alerta en estado
LOG_BACKUP="/var/log/sgrsi-backup.log"
LOG_ESTADO="/var/log/sgrsi-estado.log"

# --- Colores ---------------------------------------------------------
NC='\033[0m'; TITULO='\033[1;34m'; OK='\033[1;32m'; AVISO='\033[1;33m'; ERROR='\033[1;31m'

# --- Funciones de registro y salida ----------------------------------
info()  { echo -e "${OK}[INFO ] $(date '+%F %T') $*${NC}"; }
aviso() { echo -e "${AVISO}[AVISO] $(date '+%F %T') $*${NC}"; }
error() { echo -e "${ERROR}[ERROR] $(date '+%F %T') $*${NC}" >&2; exit 1; }
salir() { echo -e "${TITULO}Hasta luego.${NC}"; exit 0; }

requiere_root() { [ "$(id -u)" -eq 0 ] || error "Ejecute como root (sudo)."; }

preparar_carpetas() {
    mkdir -p "$BACKUP_DB" "$BACKUP_APP" "$BACKUP_CFG"
}

# Verifica dependencias antes de operar (usada por los módulos)
verificar_dependencias() {
    command -v mysqldump >/dev/null || error "mysqldump no disponible"
    [ -r "$BD_CNF" ] || error "Falta $BD_CNF (credenciales MariaDB)"
}
