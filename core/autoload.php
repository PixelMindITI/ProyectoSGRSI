<?php
/**
 * autoload.php — Cargador automático de clases (PSR-4 simplificado).
 * Mapea el nombre de la clase a su archivo según la capa a la que pertenece:
 *   core/            → infraestructura transversal
 *   app/excepciones  → excepciones de la aplicación
 *   app/entidades    → entidades del dominio (POO)
 *   app/repositorios → capa de acceso a datos
 *   app/servicios    → capa de lógica de negocio
 */

spl_autoload_register(function (string $clase): void {
    $base = dirname(__DIR__);
    $candidatos = [
        "$base/core/$clase.php",
        "$base/app/excepciones/$clase.php",
        "$base/app/entidades/$clase.php",
        "$base/app/repositorios/$clase.php",
        "$base/app/servicios/$clase.php",
    ];
    foreach ($candidatos as $archivo) {
        if (is_file($archivo)) {
            require_once $archivo;
            return;
        }
    }
});
