<?php
/**
 * Validador — Validación OBLIGATORIA del lado del servidor.
 * ---------------------------------------------------------------
 * Las validaciones JavaScript mejoran la experiencia, pero son
 * evadibles (el cliente puede desactivarlas). Por eso TODA regla se
 * repite aquí, en el servidor, antes de tocar la base de datos.
 *
 * También centraliza la sanitización:
 *  - Entrada: texto() recorta y elimina etiquetas.
 *  - Salida : e() escapa caracteres especiales HTML (previene XSS).
 */

class Validador
{
    /** Contraseñas seguras: mínimo 8 caracteres, al menos una mayúscula y un carácter especial (no espacio). */
    public const REGEX_CONTRASENA = '/^(?=.*[A-Z])(?=.*[^\sA-Za-z0-9])\S{8,}$/D';

    private array $errores = [];

    public function errores(): array { return $this->errores; }

    public function hayErrores(): bool { return !empty($this->errores); }

    public function agregarError(string $campo, string $mensaje): void
    {
        $this->errores[$campo] = $mensaje;
    }

    public function requerido(string $campo, $valor, string $mensaje): void
    {
        if ($valor === null || trim((string)$valor) === '') {
            $this->agregarError($campo, $mensaje);
        }
    }

    public function largoMax(string $campo, $valor, int $max, string $mensaje): void
    {
        if ($valor !== null && mb_strlen((string)$valor) > $max) {
            $this->agregarError($campo, $mensaje);
        }
    }

    public function largoMin(string $campo, $valor, int $min, string $mensaje): void
    {
        if ($valor !== null && $valor !== '' && mb_strlen((string)$valor) < $min) {
            $this->agregarError($campo, $mensaje);
        }
    }

    /** Contraseña segura: mínimo 8 caracteres, mayúscula y carácter especial. */
    public function contrasenaSegura(string $campo, $valor, string $mensaje): void
    {
        if ($valor === null || preg_match(self::REGEX_CONTRASENA, (string)$valor) !== 1) {
            $this->agregarError($campo, $mensaje);
        }
    }

    public function email(string $campo, $valor, string $mensaje): void
    {
        if ($valor !== null && $valor !== '' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            $this->agregarError($campo, $mensaje);
        }
    }

    /** El valor debe estar dentro de una lista blanca (ids de catálogo). */
    public function entre(string $campo, $valor, array $permitidos, string $mensaje): void
    {
        if (!in_array((int)$valor, array_map('intval', $permitidos), true)) {
            $this->agregarError($campo, $mensaje);
        }
    }

    public function fecha(string $campo, $valor, string $mensaje): void
    {
        if ($valor === null || $valor === '') return;
        $d = DateTime::createFromFormat('Y-m-d', (string)$valor);
        if (!$d || $d->format('Y-m-d') !== $valor) {
            $this->agregarError($campo, $mensaje);
        }
    }

    public function enteroPositivo(string $campo, $valor, string $mensaje): void
    {
        if (filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $this->agregarError($campo, $mensaje);
        }
    }

    /* ---------------- Sanitización ---------------- */

    /** Sanitiza entrada de texto: recorta espacios y elimina etiquetas. */
    public static function texto(?string $valor): string
    {
        return htmlspecialchars(trim(strip_tags($valor ?? '')), ENT_QUOTES, 'UTF-8');
    }

    /** Sanitiza salida: SIEMPRE que se imprime un dato usar e(). */
    public static function salida($valor): string
    {
        return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
    }
}
