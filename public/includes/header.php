<?php
/**
 * header.php — Cabecera común: navbar Bootstrap 5, selector de idioma
 * y menú filtrado por rol (control de acceso visible en la interfaz).
 *
 * Cada página define $base ('' o '../') antes de incluir este archivo.
 */
if (!isset($base)) { $base = ''; }
$flashes = Sesion::tomarFlashes();
?>
<!DOCTYPE html>
<html lang="<?= Idioma::actual() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('app_nombre')) ?></title>
    <!-- Framework CSS: Bootstrap 5 (justificación en README.md) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>assets/css/estilos.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand fw-bold" href="<?= $base ?>dashboard.php">
            <span class="brand-mark">PM</span> PixelMind <span class="d-none d-md-inline text-white-50">| SGRSI</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal"
                aria-controls="menuPrincipal" aria-expanded="false" aria-label="Menú">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuPrincipal">
            <?php if (Sesion::estaLogueado()): ?>
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= $base ?>dashboard.php"><?= e(t('inicio')) ?></a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base ?>equipos/listar.php"><?= e(t('inventario')) ?></a></li>
                <?php if (Auth::esSoporte()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $base ?>prestamos/listar.php"><?= e(t('prestamos')) ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base ?>usuarios/listar.php"><?= e(t('usuarios')) ?></a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="<?= $base ?>incidencias/listar.php"><?= e(t('incidencias')) ?></a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base ?>solicitudes/listar.php"><?= e(t('solicitudes')) ?></a></li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false"><?= e(t('idioma')) ?></a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item <?= Idioma::actual()==='es'?'active':'' ?>"
                               href="?<?= http_build_query(array_merge($_GET, ['idioma'=>'es'])) ?>">Español</a></li>
                        <li><a class="dropdown-item <?= Idioma::actual()==='en'?'active':'' ?>"
                               href="?<?= http_build_query(array_merge($_GET, ['idioma'=>'en'])) ?>">English</a></li>
                    </ul>
                </li>
                <li class="nav-item text-white-50 small d-none d-xl-block">
                    <?= e(t('bienvenida')) ?>, <strong class="text-white"><?= e(Sesion::nombre()) ?></strong>
                    (<?= e(badge(Sesion::rol())) ?>)
                </li>
                <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="<?= $base ?>logout.php"><?= e(t('salir')) ?></a></li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container-fluid px-3 px-lg-4 py-4">
<?php foreach ($flashes as $f): ?>
    <div class="alert alert-<?= e($f['tipo']) ?> alert-dismissible fade show" role="alert">
        <?= e($f['mensaje']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endforeach; ?>
