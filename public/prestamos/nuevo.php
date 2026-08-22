<?php
/**
 * prestamos/nuevo.php — Registro de préstamo (solo soporte).
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirRol(['administrador', 'tecnico']);

$base = '../';
$servicio = new PrestamoServicio();
$catalogos = new CatalogoRepositorio();

$valores = ['equipo' => $_GET['equipo'] ?? '', 'solicitante' => '', 'fecha_devolucion' => date('Y-m-d', strtotime('+7 days')), 'observaciones' => ''];
$errores = [];

if (esPost()) {
    Csrf::verificar();
    $valores = ['equipo' => post('equipo'), 'solicitante' => post('solicitante'),
                'fecha_devolucion' => $_POST['fecha_devolucion'] ?? '', 'observaciones' => post('observaciones')];
    try {
        $id = $servicio->registrar($_POST);
        Sesion::flash('success', "Préstamo #$id registrado. El equipo quedó marcado como prestado.");
        redirigir('listar.php');
    } catch (ValidacionException $ex) {
        $errores = $ex->errores();
        http_response_code(400);
    }
}

require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 fw-bold mb-4"><?= e(t('nuevo_prestamo')) ?></h1>

<?php if ($errores): ?>
    <div class="alert alert-danger">Revise los datos: hay errores de validación.</div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="post" action="nuevo.php" id="formPrestamo" class="row g-3" novalidate>
            <?= Csrf::campo() ?>

            <div class="col-12 col-xl-6">
                <label for="equipo" class="form-label"><?= e(t('equipo')) ?> *</label>
                <select name="equipo" id="equipo" class="form-select <?= ($errores['equipo'] ?? '') ? 'is-invalid' : '' ?>" required>
                    <option value="">—</option>
                    <?php foreach ((new EquipoServicio())->disponibles() as $eq): ?>
                        <option value="<?= $eq->id() ?>" <?= ($valores['equipo'] ?? '') == $eq->id() ? 'selected' : '' ?>>
                            <?= e($eq->codigo()) ?> — <?= e($eq->nombre()) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-secondary">Solo se listan equipos con estado «disponible».</small>
                <div class="invalid-feedback"><?= e($errores['equipo'] ?? '') ?></div>
            </div>

            <div class="col-12 col-xl-6">
                <label for="solicitante" class="form-label"><?= e(t('solicitante')) ?> *</label>
                <select name="solicitante" id="solicitante" class="form-select <?= ($errores['solicitante'] ?? '') ? 'is-invalid' : '' ?>" required>
                    <option value="">—</option>
                    <?php foreach ($catalogos->usuariosSolicitantes() as $uid => $unombre): ?>
                        <option value="<?= $uid ?>" <?= ($valores['solicitante'] ?? '') == $uid ? 'selected' : '' ?>><?= e($unombre) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"><?= e($errores['solicitante'] ?? '') ?></div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label for="fecha_devolucion" class="form-label"><?= e(t('devolucion_esperada')) ?> *</label>
                <input type="date" name="fecha_devolucion" id="fecha_devolucion"
                       class="form-control <?= ($errores['fecha_devolucion'] ?? '') ? 'is-invalid' : '' ?>"
                       value="<?= e($valores['fecha_devolucion']) ?>" min="<?= date('Y-m-d') ?>" required>
                <div class="invalid-feedback"><?= e($errores['fecha_devolucion'] ?? 'Fecha inválida.') ?></div>
            </div>

            <div class="col-12 col-xl-8">
                <label for="observaciones" class="form-label"><?= e(t('observaciones')) ?></label>
                <input type="text" name="observaciones" id="observaciones" class="form-control"
                       maxlength="255" value="<?= e($valores['observaciones']) ?>">
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= e(t('registrar_prestamo')) ?></button>
                <a href="listar.php" class="btn btn-outline-secondary"><?= e(t('cancelar')) ?></a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
