<?php
/**
 * usuarios/alta.php — Creación de usuario por el administrador
 * (permite crear técnicos y otros administradores).
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirRol(['administrador']);

$base = '../';
$servicio = new UsuarioServicio();
$catalogos = new CatalogoRepositorio();

$valores = ['nombre' => '', 'apellido' => '', 'email' => '', 'rol' => '3'];
$errores = [];

if (esPost()) {
    Csrf::verificar();
    $valores = ['nombre' => post('nombre'), 'apellido' => post('apellido'),
                'email' => post('email'), 'rol' => post('rol')];
    try {
        $id = $servicio->crear($_POST);
        Sesion::flash('success', "Usuario #$id creado correctamente.");
        redirigir('listar.php');
    } catch (ValidacionException $ex) {
        $errores = $ex->errores();
        http_response_code(400);
    }
}
$e = fn(string $c) => $errores[$c] ?? '';

require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 fw-bold mb-4">Nuevo usuario</h1>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="post" action="alta.php" id="formRegistro" class="row g-3" novalidate>
            <?= Csrf::campo() ?>

            <div class="col-md-6">
                <label for="nombre" class="form-label"><?= e(t('nombre')) ?> *</label>
                <input type="text" id="nombre" name="nombre"
                       class="form-control <?= $e('nombre') ? 'is-invalid' : '' ?>"
                       value="<?= e($valores['nombre']) ?>" required maxlength="80" data-minlength="2">
                <div class="invalid-feedback"><?= e($e('nombre') ?: 'Campo obligatorio.') ?></div>
            </div>

            <div class="col-md-6">
                <label for="apellido" class="form-label"><?= e(t('apellido')) ?> *</label>
                <input type="text" id="apellido" name="apellido"
                       class="form-control <?= $e('apellido') ? 'is-invalid' : '' ?>"
                       value="<?= e($valores['apellido']) ?>" required maxlength="80" data-minlength="2">
                <div class="invalid-feedback"><?= e($e('apellido') ?: 'Campo obligatorio.') ?></div>
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label"><?= e(t('email')) ?> *</label>
                <input type="email" id="email" name="email"
                       class="form-control <?= $e('email') ? 'is-invalid' : '' ?>"
                       value="<?= e($valores['email']) ?>" required maxlength="120">
                <div class="invalid-feedback"><?= e($e('email') ?: 'Ingrese un email válido.') ?></div>
            </div>

            <div class="col-md-6">
                <label for="rol" class="form-label"><?= e(t('rol')) ?> *</label>
                <select id="rol" name="rol" class="form-select <?= $e('rol') ? 'is-invalid' : '' ?>" required>
                    <?php foreach ($catalogos->roles() as $rid => $rnombre): ?>
                        <option value="<?= $rid ?>" <?= (int)$valores['rol'] === $rid ? 'selected' : '' ?>><?= e(ucfirst($rnombre)) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"><?= e($e('rol') ?: '') ?></div>
            </div>

            <div class="col-md-6">
                <label for="password" class="form-label"><?= e(t('password')) ?> *</label>
                <input type="password" id="password" name="password"
                       class="form-control <?= $e('password') ? 'is-invalid' : '' ?>"
                       required minlength="8" data-strength data-password-segura>
                <div class="progress d-none mt-1" style="height:4px"><div class="progress-bar"></div></div>
                <div class="invalid-feedback"><?= e($e('password') ?: 'Mínimo 8 caracteres, una mayúscula y un carácter especial.') ?></div>
            </div>

            <div class="col-md-6">
                <label for="password_confirm" class="form-label"><?= e(t('confirmar_password')) ?> *</label>
                <input type="password" id="password_confirm" name="password_confirm"
                       class="form-control <?= $e('password_confirm') ? 'is-invalid' : '' ?>"
                       required data-match="#password">
                <div class="invalid-feedback"><?= e($e('password_confirm') ?: 'Las contraseñas no coinciden.') ?></div>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= e(t('guardar')) ?></button>
                <a href="listar.php" class="btn btn-outline-secondary"><?= e(t('cancelar')) ?></a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
