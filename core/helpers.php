<?php
/**
 * helpers.php — Funciones globales de apoyo usadas por toda la aplicación.
 */

/** Redirige (patrón PRG: Post-Redirect-Get evita reenvíos duplicados). */
function redirigir(string $destino, int $codigo = 303): never
{
    header('Location: ' . $destino, true, $codigo);
    exit;
}

/** Corta la ejecución con un código HTTP y una página amigable. */
function abortar(int $codigo, string $mensaje = ''): never
{
    http_response_code($codigo);
    $titulo = match ($codigo) {
        403 => '403 - Acceso denegado',
        404 => '404 - Página no encontrada',
        405 => '405 - Método no permitido',
        default => 'Error ' . $codigo,
    };
    $detalle = $mensaje !== '' ? e($mensaje)
        : ($codigo === 404 ? 'El recurso solicitado no existe.' : 'Ocurrió un error inesperado.');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>' . e($titulo) . '</title>'
       . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
       . '<body class="bg-light d-flex align-items-center" style="min-height:100vh"><div class="container text-center py-5">'
       . '<h1 class="display-6 fw-bold">' . e($titulo) . '</h1>'
       . '<p class="lead text-secondary">' . $detalle . '</p>'
       . '<a href="dashboard.php" class="btn btn-primary">Volver al inicio</a>'
       . '</div></body></html>';
    exit;
}

/** ¿La petición actual es POST? */
function esPost(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Exigir método HTTP; si no coincide responde 405. */
function exigirMetodo(string $metodo): void
{
    if (!esPost() && strtoupper($metodo) === 'POST') {
        http_response_code(405);
        abortar(405, 'Método no permitido para esta acción.');
    }
}

/** Recupera un valor POST sanitizado. */
function post(string $campo): string
{
    return Validador::texto($_POST[$campo] ?? '');
}

/** Fecha/hora legible en español o inglés. */
function formatearFecha(?string $fechaHora, bool $conHora = false): string
{
    if (!$fechaHora) return '—';
    $ts  = strtotime($fechaHora);
    $fmt = $conHora ? 'd/m/Y H:i' : 'd/m/Y';
    return date($fmt, $ts);
}

/** Badge de Bootstrap según el nombre de estado/prioridad/rol. */
function badge(string $nombre): string
{
    $mapa = [
        'disponible' => 'success',  'prestado' => 'warning',   'en_mantenimiento' => 'info',
        'baja' => 'secondary',      'pendiente' => 'warning',  'en_proceso' => 'info',
        'resuelto' => 'success',    'completada' => 'success', 'rechazada' => 'danger',
        'devuelto' => 'secondary',  'activo' => 'primary',     'alta' => 'danger',
        'media' => 'warning',       'baja_prio' => 'secondary','administrador' => 'dark',
        'tecnico' => 'primary',     'solicitante' => 'secondary',
    ];
    $clase = $mapa[$nombre] ?? 'light';
    return '<span class="badge text-bg-' . $clase . '">' . e(str_replace('_', ' ', ucfirst($nombre))) . '</span>';
}

/**
 * Alias corto para escapar datos al imprimir en HTML (anti XSS).
 * Definida aquí (y no en Validador) porque helpers.php se carga SIEMPRE
 * desde _init.php, mientras que Validador solo se autocarga al usarse.
 */
function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}
