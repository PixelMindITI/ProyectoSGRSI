#!/usr/bin/env bash
# =====================================================================
# sgrsi-admin.sh — MÓDULO PRINCIPAL del kit de administración PixelMind
# Menú que conecta todos los módulos (formato modular, 2ª entrega SO).
# Instalación sugerida: /opt/sgrsi-admin/
# Ejecución: sudo ./sgrsi-admin.sh
# =====================================================================
set -euo pipefail
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$DIR/modulos/comunes.sh"     # librería común compartida

requiere_root

while true; do
    echo ""
    echo -e "${TITULO}=================================================${NC}"
    echo -e "${TITULO}  SGRSI :: Administración del servidor — PixelMind${NC}"
    echo -e "${TITULO}=================================================${NC}"
    echo "  1) Respaldo completo (BD + aplicación + config)"
    echo "  2) Respaldo mensual a largo plazo"
    echo "  3) Restaurar base de datos"
    echo "  4) Verificar estado del servidor"
    echo "  5) Actualizar sistema (APT)"
    echo "  6) Gestión SSH"
    echo "  0) Salir"
    echo ""
    read -rp "Seleccione una opción: " OP

    case "$OP" in
        1) bash "$DIR/modulos/backup_completo.sh" ;;
        2) bash "$DIR/modulos/respaldo_mensual.sh" ;;
        3) bash "$DIR/modulos/restaurar_bd.sh" ;;
        4) bash "$DIR/modulos/verificar_estado.sh" ;;
        5) bash "$DIR/modulos/actualizar_sistema.sh" ;;
        6) bash "$DIR/modulos/gestion_ssh.sh" ;;
        0) salir ;;
        *) aviso "Opción inválida." ;;
    esac
done
