# Despliegue de SGRSI en Ubuntu Server 24.04 (máquina virtual)

Guía de instalación y puesta en marcha del Sistema de Gestión de Recursos y
Servicios de Informática (SGRSI) en una VM con **Ubuntu Server 24.04** usando
**LAMP** (Apache 2.4 + PHP 8.3 + MariaDB).

---

## 1. Arquitectura objetivo

```
Celular / PC (misma red Wi-Fi)
        │  http://<IP_DEL_VM>/
        ▼
┌───────────────────────────────────────┐
│ Ubuntu Server 24.04 (VM - adaptador   │
│ PUENTE /bridged => IP propia en LAN)  │
│  Apache → DocumentRoot /var/www/      │
│          SGRSI/public                 │
│  PHP 8.3 (mysqli)                     │
│  MariaDB → base "sgrsi"               │
│  ufw: solo 22/tcp y 80/tcp            │
└───────────────────────────────────────┘
```

Razones del diseño:

- `public/` como DocumentRoot: el código (`app/`, `core/`, `config/`) queda
  **fuera del árbol web** y no puede descargarse desde el navegador.
- MariaDB con usuario dedicado (`sgrsi`), nunca `root`.
- `config/config.php` no viaja en Git (está en `.gitignore`); se genera en el VM.

---

## 2. Requisitos de la VM

- **Hipervisor**: VirtualBox o VMware.
- **Sistema**: Ubuntu Server 24.04 LTS, carga base (sin escritorio).
- **Recursos sugeridos**: 2 GB RAM, 2 vCPU, 20 GB disco.
- **Red**: adaptador en modo **Puente (bridged)** para obtener IP de la LAN y
  poder acceder desde el celular (con NAT no sería accesible desde afuera).

---

## 3. Instalación de LAMP

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y apache2 mariadb-server php php-mysql libapache2-mod-php git
sudo systemctl enable --now apache2 mariadb
php -m | grep mysqli        # debe listar mysqli
php -v                      # Ubuntu 24.04 trae PHP 8.3 (el app exige PHP >= 8.1)
```

---

## 4. Base de datos

En Ubuntu/MariaDB, `root` autentica por socket; se entra con `sudo mysql`.

```bash
sudo mysql
CREATE DATABASE sgrsi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sgrsi'@'localhost' IDENTIFIED BY 'ELIGE_UNA_PASSWORD_FUERTE';
GRANT ALL PRIVILEGES ON sgrsi.* TO 'sgrsi'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Cargar el esquema del proyecto (tablas, catálogos y datos iniciales):

```bash
mysql -u sgrsi -p sgrsi < /var/www/SGRSI/database/ddl.sql
mysql -u sgrsi -p sgrsi < /var/www/SGRSI/database/dml.sql
```

---

## 5. Código y configuración

El código se obtiene del repositorio del grupo (no se copia la carpeta local):

```bash
sudo git clone https://github.com/PixelMindITI/ProyectoSGRSI.git /var/www/SGRSI
cd /var/www/SGRSI
sudo cp config/config.example.php config/config.php
sudo nano config/config.php
```

Ajustar el bloque `base_datos`:

```php
'base_datos' => [
    'host'     => '127.0.0.1',
    'usuario'  => 'sgrsi',
    'password' => 'ELIGE_UNA_PASSWORD_FUERTE',
    'nombre'   => 'sgrsi',
    'charset'  => 'utf8mb4',
],
```

Permisos del árbol:

```bash
sudo chown -R www-data:www-data /var/www/SGRSI
```

> `config/config.example.php` forma parte del repositorio; **nunca** se sube el
> `config.php` real (con credenciales) por estar ignorado en `.gitignore`.

---

## 6. Apache (VirtualHost hacia `public/`)

```bash
sudo tee /etc/apache2/sites-available/sgrsi.conf > /dev/null <<'EOF'
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

sudo a2dissite 000-default
sudo a2enmod rewrite
sudo a2ensite sgrsi
sudo systemctl reload apache2
```

---

## 7. Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw enable
sudo ufw status verbose
```

Conocer la IP del VM:

```bash
hostname -I
```

---

## 8. Acceso e inicio del servidor

```bash
sudo systemctl restart apache2 mariadb     # iniciar/recargar servicios
curl -I http://localhost/                  # comprobación local
tail -f /var/log/apache2/sgrsi-error.log   # ver errores de la app
```

Desde **cualquier dispositivo de la LAN** (incluido el celular):

```
http://<IP_DEL_VM>/
```

El sitio usa enlaces relativos, por lo que no requiere tocar `base_url`.

---

## 9. Operación y mantenimiento

| Tarea                    | Comando                                              |
|--------------------------|------------------------------------------------------|
| Iniciar Apache           | `sudo systemctl start apache2`                       |
| Ver estado               | `sudo systemctl status apache2 mariadb`              |
| Respaldos (kit del grupo)| `sudo bash /var/www/SGRSI/docs/sistemas_operativos/scripts/sgrsi-admin.sh` |
| Logs de Apache           | `journalctl -u apache2 -f`                           |
| Zona horaria             | `timedatectl set-timezone America/Montevideo`        |

---

## 10. Endurecimiento recomendado

- **SSH con llaves** (deshabilitar password y login de root): ver estrategia de
  la 2.ª entrega de Sistemas Operativos (`docs/sistemas_operativos/`).
- **HTTPS**: si la VM sale a internet, instalar `certbot` (Let's Encrypt); en
  LAN académica el HTTP alcanza.
- `config.php` mantiene `mostrar_errores => false` en producción (valor por
  defecto) y `sesion_duracion_min => 120`.
- Proteger los respaldos de la base con la estrategia 3-2-1 del grupo.

---

## 11. Troubleshooting rápido

| Síntoma                                | Causa probable                     | Solución                                   |
|----------------------------------------|------------------------------------|-------------------------------------------|
| 403 Forbidden                          | permisos de `/var/www`             | `chown -R www-data:www-data /var/www/SGRSI` |
| Página blanca / 500                    | `config/config.php` mal            | revisar credenciales y `mostrar_errores`  |
| "No such file or directory" en mysqli  | host `localhost` con socket        | usar `127.0.0.1` en `host`                |
| No accede desde el celular             | red NAT o firewall                 | adaptador puente + `ufw allow 80/tcp`     |