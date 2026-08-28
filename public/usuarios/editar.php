<?php
/**
 * usuarios/editar.php — Edición de usuario (solo administrador).
 * La contraseña es opcional: si se completa se re-encripta con bcrypt.
 * Un administrador no puede desactivarse a sí mismo.
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirRol(['administrador']);

$base = '../';
$servicio = new UsuarioServicio();
$catalogos = new CatalogoRepositorio();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    abortar(404);
}

try {
    $usuario = $servicio->obtenerPorId($id);
} catch (NoEncontradoException) {
    abortar(404, 'El usuario solicitado no existe.');
}

$errores = [];
$valores = [
    'nombre' => $usuario->nombre(), 'apellido' => $usuario->apellido(),
    'email' => $usuario->email(), 'rol' => $usuario->rolId(), 'activo' => $usuario->activo(),
];

if (esPost()) {
    Csrf::verificar();
    if ($id === Sesion::id() && empty($_POST['activo'])) {
        $_POST['activo'] = '1'; // autoprotección: no auto-desactivarse
    }
    try {
        $servicio->editar($id, $_POST);
        Sesion::flash('success', 'Usuario actualizado.');
        redirigir('listar.php');
    } catch (ValidacionException $ex) {
        $errores = $ex->errores();
        http_response_code(400);
        $valores = [
            'nombre'  => Validador::texto($_POST['nombre'] ?? $valores['nombre']),
            'apellido'=> Validador::texto($_POST['apellido'] ?? $valores['apellido']),
            'email'   => Validador::texto($_POST['email'] ?? $valores['email']),
            'rol'     => (int)($_POST['rol'] ?? $valores['rol']),
            'activo'  => !empty($_POST['activo']),
        ];
    }
}
$e = fn(string $c) => $errores[$c] ?? '';

require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 fw-bold mb-1">Editar: <?= e($usuario->nombreCompleto()) ?></h1>
<p class="text-secondary small"><?= badge($usuario->rolNombre()) ?></p>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="post" action="editar.php?id=<?= $id ?>" id="formRegistro" class="row g-3" novalidate>
            <?= Csrf::campo() ?>

            <div class="col-md-6">
                <label for="nombre" class="form-label"><?= e(t('nombre')) ?> *</label>
                <input type="text" id="nombre" name="nombre"
                       class="form-control <?= $e('nombre') ? 'is-invalid' : '' ?>"
                       value="<?= e($valores['nombre']) ?>" required maxlength="80">
                <div class="invalid-feedback"><?= e($e('nombre') ?: '') ?></div>
            </div>

            <div class="col-md-6">
                <label for="apellido" class="form-label"><?= e(t('apellido')) ?> *</label>
                <input type="text" id="apellido" name="apellido"
                       class="form-control <?= $e('apellido') ? 'is-invalid' : '' ?>"
                       value="<?= e($valores['apellido']) ?>" required maxlength="80">
                <div class="invalid-feedback"><?= e($e('apellido') ?: '') ?></div>
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label"><?= e(t('email')) ?> *</label>
                <input type="email" id="email" name="email"
                       class="form-control <?= $e('email') ? 'is-invalid' : '' ?>"
                       value="<?= e($valores['email']) ?>" required maxlength="120">
                <div class="invalid-feedback"><?= e($e('email') ?: 'Email inválido o ya registrado.') ?></div>
            </div>

            <div class="col-md-6">
                <label for="rol" class="form-label"><?= e(t('rol')) ?> *</label>
                <select id="rol" name="rol" class="form-select <?= $e('rol') ? 'is-invalid' : '' ?>" required
                        <?= $id === Sesion::id() ? 'disabled' : '' ?>>
                    <?php foreach ($catalogos->roles() as $rid => $rnombre): ?>
                        <option value="<?= $rid ?>" <?= (int)$valores['rol'] === $rid ? 'selected' : '' ?>><?= e(ucfirst($rnombre)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($id === Sesion::id()): ?><input type="hidden" name="rol" value="<?= (int)$valores['rol'] ?>"><?php endif; ?>
                <?php if ($id === Sesion::id()): ?>
                    <small class="text-secondary">No puede cambiar su propio rol.</small>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label for="password" class="form-label"><?= e(t('password')) ?></label>
                <input type="password" id="password" name="password"
                       class="form-control <?= $e('password') ? 'is-invalid' : '' ?>"
                       minlength="8" data-strength data-password-segura
                       placeholder="(sin cambios)">
                <div class="progress d-none mt-1" style="height:4px"><div class="progress-bar"></div></div>
                <div class="invalid-feedback"><?= e($e('password') ?: 'Mínimo 8 caracteres, una mayúscula y un carácter especial si se modifica.') ?></div>
            </div>

            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="activo"
                           name="activo" value="1" <?= !empty($valores['activo']) ? 'checked' : ''
                           ?> <?= $id === Sesion::id() ? 'disabled' : '' ?>>
                    <label class="form-check-label" for="activo"><?= e(t('activo')) ?></label>
                    <?php if ($id === Sesion::id()): ?><input type="hidden" name="activo" value="1"><?php endif; ?>
                </div>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= e(t('guardar')) ?></button>
                <a href="listar.php" class="btn btn-outline-secondary"><?= e(t('cancelar')) ?></a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
