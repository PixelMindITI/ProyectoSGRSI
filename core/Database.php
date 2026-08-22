<?php
/**
 * Database — Patrón SINGLETON
 * ---------------------------------------------------------------
 * ¿Por qué Singleton? Garantiza que durante cada petición exista
 * UNA sola conexión mysqli compartida por todos los repositorios.
 * Evita el costo de abrir múltiples conexiones y centraliza la
 * configuración (que se lee del archivo config/config.php, fuera
 * del repositorio Git).
 *
 * Se usa mysqli porque es la extensión nativa de PHP para MySQL,
 * está incluida en XAMPP, soporta sentencias preparadas (protección
 * contra inyección SQL) y ofrece una API orientada a objetos.
 */

class Database
{
    /** @var mysqli|null Instancia única de la conexión */
    private static ?mysqli $conexion = null;

    private function __construct() {}
    private function __clone() {}

    public static function obtener(): mysqli
    {
        if (self::$conexion === null) {
            $cfg = self::cargarConfiguracion();

            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            try {
                self::$conexion = new mysqli(
                    $cfg['host'],
                    $cfg['usuario'],
                    $cfg['password'],
                    $cfg['nombre']
                );
                self::$conexion->set_charset($cfg['charset']);
            } catch (mysqli_sql_exception $e) {
                error_log('Error de conexión a la BD: ' . $e->getMessage());
                http_response_code(500);
                die('No se pudo conectar con la base de datos. Verifique que MySQL esté activo en XAMPP.');
            }
        }
        return self::$conexion;
    }

    /**
     * Lee config/config.php. Ese archivo no viaja en Git: las credenciales
     * reales nunca quedan versionadas (requisito de seguridad).
     */
    private static function cargarConfiguracion(): array
    {
        $ruta = dirname(__DIR__) . '/config/config.php';
        if (!is_file($ruta)) {
            http_response_code(500);
            die('Falta config/config.php. Copie config/config.example.php como config.php.');
        }
        $config = require $ruta;
        // El archivo devuelve un arreglo con secciones; esta capa solo
        // necesita los parámetros de conexión.
        return $config['base_datos'];
    }

    public static function cerrar(): void
    {
        if (self::$conexion !== null) {
            self::$conexion->close();
            self::$conexion = null;
        }
    }
}
