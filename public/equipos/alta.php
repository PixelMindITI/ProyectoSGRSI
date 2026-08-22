<?php
/**
 * equipos/alta.php — Alta de equipo (solo personal de soporte).
 * Validación doble: cliente (JS) + servidor (Validador/Servicio).
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirRol(['administrador', 'tecnico']);

$base = '../';
$errores = [];
$valores = [
    'codigo' => '', 'nombre' => '', 'tipo' => '', 'marca' => '', 'modelo' => '',
    'numero_serie' => '', 'ubicacion' => '', 'fecha_adquisicion' => '', 'observaciones' => '',
];

if (esPost()) {
    Csrf::verificar();
    $valores = array_map('post', array_keys($valores));

    try {
        $id = (new EquipoServicio())->alta($_POST);
        Sesion::flash('success', "Equipo registrado correctamente (#$id).");
        redirigir("detalle.php?id=$id");
    } catch (ValidacionException $ex) {
        $errores = $ex->errores();
        http_response_code(400);
    }
}

$catalogos = new CatalogoRepositorio();
$e = fn(string $c) => $errores[$c] ?? '';
$valor = fn(string $c) => e($valores[$c] ?? '');

require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 fw-bold mb-4"><?= e(t('alta_equipo')) ?></h1>

<?php if ($errores): ?>
    <div class="alert alert-danger">Revise los campos marcados: hay errores de validación.</div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="post" action="alta.php" id="formEquipo" class="row g-3" novalidate>
            <?= Csrf::campo() ?>

            <div class="col-12 col-md-6 col-xl-4">
                <label for="codigo" class="form-label"><?= e(t('codigo_inv')) ?> *</label>
                <input type="text" id="codigo" name="codigo" class="form-control <?= $e('codigo') ? 'is-invalid' : '' ?>"
                       value="<?= $valor('codigo') ?>" required maxlength="30" placeholder="INV-0001"
                       pattern="[A-Za-z0-9\-]+">
                <div class="invalid-feedback"><?= e($e('codigo') ?: 'Formato alfanumérico, ej. INV-0010.') ?></div>
            </div>

            <div class="col-12 col-md-6 col-xl-8">
                <label for="nombre" class="form-label"><?= e(t('equipo')) ?> *</label>
                <input type="text" id="nombre" name="nombre" class="form-control <?= $e('nombre') ? 'is-invalid' : '' ?>"
                       value="<?= $valor('nombre') ?>" required maxlength="100">
                <div class="invalid-feedback"><?= e($e('nombre') ?: 'Campo obligatorio.') ?></div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label for="tipo" class="form-label"><?= e(t('tipo')) ?> *</label>
                <select id="tipo" name="tipo" class="form-select <?= $e('tipo') ? 'is-invalid' : '' ?>" required>
                    <option value="">—</option>
                    <?php foreach ($catalogos->tiposEquipo() as $id => $nombre): ?>
                        <option value="<?= $id ?>" <?= ($valores['tipo'] ?? '') == $id ? 'selected' : '' ?>><?= e($nombre) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"><?= e($e('tipo') ?: 'Seleccione una opción.') ?></div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label for="marca" class="form-label"><?= e(t('marca')) ?> *</label>
                <input type="text" id="marca" name="marca" class="form-control <?= $e('marca') ? 'is-invalid' : '' ?>"
                       value="<?= $valor('marca') ?>" required maxlength="60">
                <div class="invalid-feedback"><?= e($e('marca') ?: 'Campo obligatorio.') ?></div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label for="modelo" class="form-label"><?= e(t('modelo')) ?> *</label>
                <input type="text" id="modelo" name="modelo" class="form-control <?= $e('modelo') ? 'is-invalid' : '' ?>"
                       value="<?= $valor('modelo') ?>" required maxlength="80">
                <div class="invalid-feedback"><?= e($e('modelo') ?: 'Campo obligatorio.') ?></div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label for="numero_serie" class="form-label"><?= e(t('numero_serie')) ?> *</label>
                <input type="text" id="numero_serie" name="numero_serie"
                       class="form-control <?= $e('numero_serie') ? 'is-invalid' : '' ?>"
                       value="<?= $valor('numero_serie') ?>" required maxlength="100">
                <div class="invalid-feedback"><?= e($e('numero_serie') ?: 'Campo obligatorio.') ?></div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label for="ubicacion" class="form-label"><?= e(t('ubicacion')) ?> *</label>
                <input type="text" id="ubicacion" name="ubicacion" class="form-control <?= $e('ubicacion') ? 'is-invalid' : '' ?>"
                       value="<?= $valor('ubicacion') ?>" required maxlength="100" list="listaUbicaciones">
                <datalist id="listaUbicaciones">
                    <option value="Laboratorio A"><option value="Laboratorio B"><option value="Aula 12">
                    <option value="Sala de profesores"><option value="Depósito informática"><option value="Rack servidor">
                </datalist>
                <div class="invalid-feedback"><?= e($e('ubicacion') ?: 'Campo obligatorio.') ?></div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label for="fecha_adquisicion" class="form-label"><?= e(t('fecha_adquisicion')) ?></label>
                <input type="date" id="fecha_adquisicion" name="fecha_adquisicion"
                       class="form-control <?= $e('fecha_adquisicion') ? 'is-invalid' : '' ?>"
                       value="<?= $valor('fecha_adquisicion') ?>" max="<?= date('Y-m-d') ?>">
                <div class="invalid-feedback"><?= e($e('fecha_adquisicion') ?: 'Fecha inválida.') ?></div>
            </div>

            <div class="col-12">
                <label for="observaciones" class="form-label"><?= e(t('observaciones')) ?></label>
                <textarea id="observaciones" name="observaciones" class="form-control" rows="3"
                          maxlength="500" data-contador-caracteres><?= e($valores['observaciones'] ?? '') ?></textarea>
                <small class="text-secondary"><span data-salida-contador>0</span>/500</small>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= e(t('guardar')) ?></button>
                <a href="listar.php" class="btn btn-outline-secondary"><?= e(t('cancelar')) ?></a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
