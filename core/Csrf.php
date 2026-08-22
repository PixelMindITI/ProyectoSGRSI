<?php
/**
 * Csrf — Protección contra Cross-Site Request Forgery.
 * Cada formulario incluye un token oculto que se valida en el servidor.
 */

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function campo(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::token() . '">';
    }

    public static function verificar(): void
    {
        $enviado = $_POST['csrf_token'] ?? '';
        if (!is_string($enviado) || !hash_equals($_SESSION['csrf_token'] ?? '', $enviado)) {
            error_log('CSRF inválido desde ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
            abortar(403, 'Token de seguridad inválido. Vuelva a cargar el formulario.');
        }
    }
}
