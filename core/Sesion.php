<?php
/**
 * Sesion — Manejo centralizado de la sesión del usuario.
 *
 * Medidas de seguridad aplicadas:
 *  - Cookie httponly: impide robar la cookie desde JavaScript (XSS).
 *  - session_regenerate_id(true) al iniciar sesión: evita fijación de sesión.
 *  - Expiración por inactividad configurable en config.php.
 */

class Sesion
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        // Expiración por inactividad
        $duracion = $GLOBALS['config_app']['sesion_duracion_min'] ?? 120;
        if (isset($_SESSION['ultima_actividad'])) {
            if (time() - $_SESSION['ultima_actividad'] > $duracion * 60) {
                self::cerrar();
                redirigir('index.php');
            }
        }
        $_SESSION['ultima_actividad'] = time();
    }

    public static function estaLogueado(): bool
    {
        return isset($_SESSION['usuario_id']);
    }

    public static function rol(): ?string
    {
        return $_SESSION['usuario_rol'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
    }

    public static function nombre(): string
    {
        return $_SESSION['usuario_nombre'] ?? '';
    }

    /** Registra los datos mínimos del usuario autenticado. */
    public static function autenticar(int $id, string $nombreCompleto, string $rol): void
    {
        session_regenerate_id(true); // anti fijación de sesión
        $_SESSION['usuario_id']      = $id;
        $_SESSION['usuario_nombre']  = $nombreCompleto;
        $_SESSION['usuario_rol']     = $rol;
    }

    public static function cerrar(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /* ---- Mensajes flash (sobreviven a una redirección PRG) ---- */
    public static function flash(string $tipo, string $mensaje): void
    {
        $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    public static function tomarFlashes(): array
    {
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flashes;
    }
}
