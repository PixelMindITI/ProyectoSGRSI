<?php
/**
 * index.php — Login (página pública).
 * Patrón PRG: el POST se procesa, se redirige y nunca se reenvía.
 */

require_once __DIR__ . '/_init.php';

// Si ya está autenticado, directo al panel
if (Sesion::estaLogueado()) {
    redirigir('dashboard.php');
}

$error = '';

if (esPost()) {
    Csrf::verificar();

    // Validación obligatoria del lado del servidor
    $email    = post('email');
    $password = $_POST['password'] ?? ''; // la contraseña no se sanitiza: solo se verifica

    if ($email === '' || $password === '') {
        $error = t('recordar_campo');
    } else {
        try {
            $usuario = (new UsuarioServicio())->login($email, $password);
            if ($usuario) {
                Sesion::flash('success', 'Sesión iniciada correctamente.');
                redirigir('dashboard.php');
            }
            $error = t('credenciales_invalidas');
        } catch (AplicacionException $ex) {
            error_log('Error en login: ' . $ex->getMessage());
            $error = 'No se pudo iniciar sesión. Intente nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= Idioma::actual() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('entrar')) ?> | <?= e(t('app_nombre')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="bg-body-tertiary">
<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100 py-5">
        <div class="col-12 col-md-8 col-lg-5 col-xl-4">

            <div class="text-center mb-4">
                <span class="brand-mark brand-mark-lg">PM</span>
                <h1 class="h4 fw-bold mt-3 mb-1">PixelMind</h1>
                <p class="text-secondary mb-0"><?= e(t('acceso_sistema')) ?></p>
            </div>

            <?php foreach (Sesion::tomarFlashes() as $f): ?>
                <div class="alert alert-<?= e($f['tipo']) ?>"><?= e($f['mensaje']) ?></div>
            <?php endforeach; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger" role="alert" data-testid="login-error"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form id="formLogin" method="post" action="index.php" novalidate>
                        <?= Csrf::campo() ?>
                        <div class="mb-3">
                            <label for="email" class="form-label"><?= e(t('email')) ?></label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email"
                                   value="<?= e($_POST['email'] ?? '') ?>" required maxlength="120"
                                   autocomplete="username" autofocus>
                            <div class="invalid-feedback">Ingrese un email válido.</div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label"><?= e(t('password')) ?></label>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-lg" id="password"
                                       name="password" required minlength="8" autocomplete="current-password">
                                <button class="btn btn-outline-secondary" type="button"
                                        data-toggle-password="#password" aria-label="Mostrar contraseña">&#128065;</button>
                            </div>
                            <div class="invalid-feedback">La contraseña es obligatoria.</div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100"><?= e(t('entrar')) ?></button>
                    </form>
                </div>
            </div>

            <p class="text-center mt-3 small">
                <?= e(t('no_tiene_cuenta')) ?><br>
                <a href="registro.php"><?= e(t('registrarse')) ?></a>
            </p>

            <details class="mt-3 text-center small text-secondary">
                <summary class="user-select-none">Usuarios de prueba</summary>
                admin@pixelmind.uy / tecnico1@pixelmind.uy / docente1@iti.edu.uy<br>Contraseñas: Pixel2026! o Docente2026!
            </details>

            <div class="text-center mt-4">
                <a class="small text-secondary" href="?idioma=en">English</a> ·
                <a class="small text-secondary" href="?idioma=es">Español</a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
