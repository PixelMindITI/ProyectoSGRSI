<?php
/**
 * _init.php — Punto único de arranque de todas las páginas (Front Controller
 * simplificado). Carga configuración, autoload, sesión, idioma y el gestor
 * global de errores/excepciones.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

/* --- Configuración --- */
$configArchivo = BASE_PATH . '/config/config.php';
if (!is_file($configArchivo)) {
    http_response_code(500);
    die('Falta config/config.php: copie config/config.example.php como config/config.php.');
}
$config = require $configArchivo;
$GLOBALS['config_app'] = $config['aplicacion'];

date_default_timezone_set($config['aplicacion']['zona_horaria'] ?? 'America/Montevideo');

/* --- Errores: en producción no se muestran al usuario, solo se registran --- */
$mostrarErrores = (bool)($config['aplicacion']['mostrar_errores'] ?? false);
ini_set('display_errors', $mostrarErrores ? '1' : '0');
error_reporting(E_ALL);

require_once BASE_PATH . '/core/autoload.php';
require_once BASE_PATH . '/core/helpers.php';

/* --- Gestor global de excepciones no capturadas (manejo de errores servidor) --- */
set_exception_handler(function (Throwable $ex) use ($mostrarErrores): void {
    error_log('[SGRSI] ' . get_class($ex) . ': ' . $ex->getMessage()
        . ' en ' . $ex->getFile() . ':' . $ex->getLine());
    if ($mostrarErrores) {
        abortar(500, e(get_class($ex)) . ': ' . e($ex->getMessage()));
    }
    if (!headers_sent()) {
        abortar(500, 'Ocurrió un error interno. El detalle fue registrado para revisión técnica.');
    }
    exit;
});

/* --- Sesión segura --- */
Sesion::iniciar();

/* --- Idioma (es | en) --- */
Idioma::iniciar($_GET['idioma'] ?? null);
