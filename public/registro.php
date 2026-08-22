<?php
/**
 * registro.php — Registro público de usuarios (rol solicitante).
 * La validación se repite en el servidor (Validador) y en el cliente (JS).
 */

require_once __DIR__ . '/_init.php';

if (Sesion::estaLogueado()) {
    redirigir('dashboard.php');
}

$errores = [];
$valores = ['nombre' => '', 'apellido' => '', 'email' => ''];

if (esPost()) {
    Csrf::verificar();

    $datos = [
        'nombre'           => post('nombre'),
        'apellido'         => post('apellido'),
        'email'            => post('email'),
        'password'         => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
    ];
    $valores = array_intersect_key($datos, $valores);

    try {
        (new UsuarioServicio())->registrarSolicitante($datos);
        Sesion::flash('success', 'Cuenta creada. Ya puede iniciar sesión.');
        redirigir('index.php');
    } catch (ValidacionException $ex) {
        $errores = $ex->errores();
        http_response_code(400);
    }
}
$e = fn(string $c) => $errores[$c] ?? '';
?>
<!DOCTYPE html>
<html lang="<?= Idioma::actual() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('registrarse')) ?> | <?= e(t('app_nombre')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="bg-body-tertiary">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-6 col-xl-5">
            <h1 class="h4 fw-bold mb-1">PixelMind</h1>
            <p class="text-secondary"><?= e(t('registrarse')) ?></p>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form id="formRegistro" method="post" action="registro.php" novalidate>
                        <?= Csrf::campo() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label"><?= e(t('nombre')) ?> *</label>
                                <input type="text" id="nombre" name="nombre" class="form-control"
                                       value="<?= e($valores['nombre']) ?>" required maxlength="80" data-minlength="2">
                                <div class="invalid-feedback"><?= e($e('nombre') ?: 'Campo obligatorio.') ?></div>
                            </div>
                            <div class="col-md-6">
                                <label for="apellido" class="form-label"><?= e(t('apellido')) ?> *</label>
                                <input type="text" id="apellido" name="apellido" class="form-control"
                                       value="<?= e($valores['apellido']) ?>" required maxlength="80" data-minlength="2">
                                <div class="invalid-feedback"><?= e($e('apellido') ?: 'Campo obligatorio.') ?></div>
                            </div>
                            <div class="col-12">
                                <label for="email" class="form-label"><?= e(t('email')) ?> *</label>
                                <input type="email" id="email" name="email" class="form-control"
                                       value="<?= e($valores['email']) ?>" required maxlength="120">
                                <div class="invalid-feedback"><?= e($e('email') ?: 'Ingrese un email válido.') ?></div>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label"><?= e(t('password')) ?> *</label>
                                <input type="password" id="password" name="password" class="form-control"
                                       required minlength="8" data-strength>
                                <div class="progress d-none mt-1" style="height:4px"><div class="progress-bar"></div></div>
                                <div class="invalid-feedback"><?= e($e('password') ?: 'Mínimo 8 caracteres.') ?></div>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirm" class="form-label"><?= e(t('confirmar_password')) ?> *</label>
                                <input type="password" id="password_confirm" name="password_confirm"
                                       class="form-control" required data-match="#password">
                                <div class="invalid-feedback"><?= e($e('password_confirm') ?: 'Las contraseñas no coinciden.') ?></div>
                            </div>
                            <div class="col-12 d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-primary flex-grow-1"><?= e(t('registrarse')) ?></button>
                                <a href="index.php" class="btn btn-outline-secondary"><?= e(t('entrar')) ?></a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
