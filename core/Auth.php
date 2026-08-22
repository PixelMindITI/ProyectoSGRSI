<?php
/**
 * Auth — Autenticación y control de acceso por roles (RBAC).
 *
 * Roles del sistema (definidos en la letra del proyecto):
 *   - administrador : acceso completo, administra usuarios e inventario.
 *   - tecnico       : atiende tickets, gestiona préstamos e inventario.
 *   - solicitante   : crea tickets/solicitudes y consulta sus registros.
 *
 * Uso en cada página protegida:
 *   Auth::requerirLogin();
 *   Auth::requerirRol(['administrador']);
 */

class Auth
{
    /** Corta la ejecución con 403 si el rol no está permitido. */
    public static function requerirRol(array $rolesPermitidos): void
    {
        self::requerirLogin();
        $rol = Sesion::rol();
        if (!in_array($rol, $rolesPermitidos, true)) {
            error_log(sprintf('Acceso denegado: usuario %d (%s) intentó %s',
                Sesion::id(), $rol, $_SERVER['SCRIPT_NAME'] ?? ''));
            abortar(403, 'No tiene permisos para acceder a esta página.');
        }
    }

    public static function requerirLogin(): void
    {
        if (!Sesion::estaLogueado()) {
            redirigir('index.php');
        }
    }

    public static function esRol(...$roles): bool
    {
        return in_array(Sesion::rol(), $roles, true);
    }

    /* Atajos legibles */
    public static function esAdmin(): bool       { return self::esRol('administrador'); }
    public static function esTecnico(): bool     { return self::esRol('tecnico'); }
    public static function esSolicitante(): bool { return self::esRol('solicitante'); }

    /** Admin o técnico: personal de soporte. */
    public static function esSoporte(): bool
    {
        return self::esAdmin() || self::esTecnico();
    }
}
