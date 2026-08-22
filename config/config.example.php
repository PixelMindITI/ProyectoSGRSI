<?php
/**
 * Plantilla de configuración — PixelMind SGRSI
 *
 * SEGURIDAD: el archivo real "config.php" NO se sube al repositorio
 * (está excluido en .gitignore). Solo esta plantilla viaja en Git.
 * Para instalar: copiar este archivo como "config.php" y completar valores.
 */

return [
    'base_datos' => [
        'host'     => 'localhost',
        'usuario'  => 'root',
        'password' => '',            // XAMPP por defecto no tiene contraseña
        'nombre'   => 'sgrsi',
        'charset'  => 'utf8mb4',
    ],
    'aplicacion' => [
        'nombre'        => 'SGRSI - PixelMind',
        'idioma_defecto'=> 'es',     // es | en
        'zona_horaria'  => 'America/Montevideo',
        'sesion_duracion_min' => 120, // expiración por inactividad
        'mostrar_errores' => false,   // true SOLO en desarrollo
    ],
];
