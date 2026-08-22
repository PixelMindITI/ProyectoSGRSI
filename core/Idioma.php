<?php
/**
 * Idioma — Interfaz bilingüe (español / inglés).
 *
 * La letra del proyecto exige la interfaz disponible en dos idiomas.
 * El mecanismo: archivos de traducción app/lang/{es,en}.php que devuelven
 * un arreglo clave => texto. El idioma activo se guarda en la sesión y se
 * cambia con ?idioma=en|es (el switcher del navbar). En las vistas se usa:
 *
 *     <?= t('inicio') ?>
 *
 * Si una clave no existe, devuelve la clave misma (fácil de detectar).
 */

class Idioma
{
    private static array $lineas = [];
    private static string $actual = 'es';

    public static function iniciar(string $solicitado = null): void
    {
        // 1) petición explícita, 2) sesión, 3) configuración por defecto
        if ($solicitado && in_array($solicitado, ['es', 'en'], true)) {
            self::$actual = $solicitado;
            $_SESSION['idioma'] = $solicitado;
        } else {
            self::$actual = $_SESSION['idioma']
                ?? ($GLOBALS['config_app']['idioma_defecto'] ?? 'es');
        }

        $ruta = dirname(__DIR__) . '/app/lang/' . self::$actual . '.php';
        self::$lineas = is_file($ruta) ? require $ruta : [];
    }

    public static function actual(): string { return self::$actual; }

    public static function traducir(string $clave): string
    {
        return self::$lineas[$clave] ?? $clave;
    }
}

/** Helper global de traducción para las vistas. */
function t(string $clave): string
{
    return Idioma::traducir($clave);
}
