#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# deploy_ubuntu24.sh — Instala LAMP en Ubuntu Server 24.04 y deja SGRSI listo.
# Uso:  sudo ./deploy_ubuntu24.sh
#
# Detecta/instala paquetes, crea la base y el usuario "sgrsi", importa el
# esquema del proyecto, configura el VirtualHost hacia public/ y abre ufw.
# Luego debe completarse a mano config/config.php con la clave elegida.
# ---------------------------------------------------------------------------
set -euo pipefail

if [[ "$(id -u)" -ne 0 ]]; then
    echo "Ejecutar como root: sudo $0" >&2
    exit 1
fi

INSTALAR_DIR=/var/www/SGRSI
REPO_URL=https://github.com/PixelMindITI/ProyectoSGRSI.git
DB_NOMBRE=sgrsi
DB_USUARIO=sgrsi

echo "==> 1/6 Instalando paquetes (apache2, php, mariadb, git)..."
apt-get update -y
apt-get install -y apache2 mariadb-server php php-mysql libapache2-mod-php git
systemctl enable --now apache2 mariadb

echo "==> 2/6 Verificando extensión mysqli..."
if ! php -m | grep -q mysqli; then
    echo "ERROR: mysqli no está disponible. Revisar el paquete php-mysql." >&2
    exit 1
fi

echo "==> 3/6 Clonando el proyecto..."
if [[ -d "$INSTALAR_DIR/.git" ]]; then
    echo "El directorio ya existe; se actualiza el código."
    git -C "$INSTALAR_DIR" pull --ff-only origin main
else
    git clone "$REPO_URL" "$INSTALAR_DIR"
fi
chown -R www-data:www-data "$INSTALAR_DIR"

echo "==> 4/6 Creando base y usuario '${DB_USUARIO}'..."
read -r -s -p "    Clave para el usuario '${DB_USUARIO}': " DB_CLAVE; echo
read -r -s -p "    Repita la clave: " DB_CLAVE2; echo
if [[ "$DB_CLAVE" != "$DB_CLAVE2" ]]; then
    echo "ERROR: las claves no coinciden." >&2
    exit 1
fi

mysql <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NOMBRE}
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USUARIO}'@'localhost'
    IDENTIFIED BY '${DB_CLAVE}';
GRANT ALL PRIVILEGES ON ${DB_NOMBRE}.* TO '${DB_USUARIO}'@'localhost';
FLUSH PRIVILEGES;
SQL

mysql -u "${DB_USUARIO}" -p"${DB_CLAVE}" "${DB_NOMBRE}" < "$INSTALAR_DIR/database/ddl.sql"
mysql -u "${DB_USUARIO}" -p"${DB_CLAVE}" "${DB_NOMBRE}" < "$INSTALAR_DIR/database/dml.sql"

echo "==> 5/6 Configurando VirtualHost (DocumentRoot → public/)..."
cat > /etc/apache2/sites-available/sgrsi.conf <<'EOF'
<VirtualHost *:80>
    ServerName sgrsi.local
    DocumentRoot /var/www/SGRSI/public
    <Directory /var/www/SGRSI/public>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/sgrsi-error.log
    CustomLog ${APACHE_LOG_DIR}/sgrsi-access.log combined
</VirtualHost>
EOF

a2dissite 000-default || true
a2enmod rewrite || true
a2ensite sgrsi
systemctl reload apache2

echo "==> 6/6 Firewall (SSH + HTTP)..."
ufw allow OpenSSH || true
ufw allow 80/tcp
ufw --force enable

echo
echo "Instalación lista. FALTA un paso manual (no se tokeniza la clave):"
echo
echo "  sudo nano $INSTALAR_DIR/config/config.php"
echo
echo "y completar el bloque 'base_datos': host=127.0.0.1, usuario=${DB_USUARIO},"
echo "password=<la clave ingresada>, nombre=${DB_NOMBRE}."
echo
echo "IP del VM: $(hostname -I)"
echo "Acceso web: http://$(hostname -I | awk '{print $1}')/"