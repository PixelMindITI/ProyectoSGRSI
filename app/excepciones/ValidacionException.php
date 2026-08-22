<?php
/** ValidacionException — agrupa errores de validación del servidor. */

class ValidacionException extends AplicacionException
{
    /** @var array<string,string> campo => mensaje */
    private array $errores;

    public function __construct(array $errores)
    {
        parent::__construct('Errores de validación.');
        $this->errores = $errores;
    }

    public function errores(): array { return $this->errores; }
}
