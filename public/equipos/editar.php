<?php
/**
 * equipos/editar.php — Edición de equipo (solo personal de soporte).
 * 404 si el equipo no existe (manejo de estados HTTP).
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirRol(['administrador', 'tecnico']);

$base = '../';
$servicio = new EquipoServicio();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    abortar(404);
}

try {
    $equipo = $servicio->obtenerPorId($id);
} catch (NoEncontradoException) {
    abortar(404, 'El equipo solicitado no existe.');
}

$errores = [];
$valores = [
    'codigo' => $equipo->codigo(), 'nombre' => $equipo->nombre(), 'tipo' => $equipo->tipoId(),
    'marca' => $equipo->marca(), 'modelo' => $equipo->modelo(), 'numero_serie' => $equipo->numeroSerie(),
    'ubicacion' => $equipo->ubicacion(), 'fecha_adquisicion' => $equipo->fechaAdquisicion() ?? '',
    'observaciones' => $equipo->observaciones() ?? '', 'estado' => $equipo->estadoId(),
];

if (esPost()) {
    Csrf::verificar();
    try {
        $servicio->editar($id, $_POST);
        Sesion::flash('success', 'Equipo actualizado correctamente.');
        redirigir("detalle.php?id=$id");
    } catch (ValidacionException $ex) {
        $errores = $ex->errores();
        http_response_code(400);
        foreach ($valores as $campo => $_) {
            if (isset($_POST[$campo])) {
                $valores[$campo] = is_array($_POST[$campo]) ? '' : Validador::texto($_POST[$campo]);
            }
        }
    }
}

$catalogos = new CatalogoRepositorio();
$e = fn(string $c) => $errores[$c] ?? '';
$valor = fn(string $c) => e((string)$valores[$c]);

require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 fw-bold mb-1"><?= e(t('editar_equipo')) ?></h1>
<p class="text-secondary text-monospace small"><?= e($equipo->codigo()) ?></p>

<?php if ($errores): ?>
    <div class="alert alert-danger">Revise los campos marcados: hay errores de validación.</div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="post" action="editar.php?id=<?= $id ?>" id="formEquipo" class="row g-3" novalidate>
            <?= Csrf::campo() ?>

            <div class="col-12 col-md-6 col-xl-4">
                <label for="codigo" class="form-label"><?= e(t('codigo_inv')) ?> *</label>
                <input type="text" id="codigo" name="codigo" class="form-control <?= $e('codigo') ? 'is-invalid' : '' ?>"
                       value="<?= $valor('codigo') ?>" required maxlength="30" pattern="[A-Za-z0-9\-]+">
                <div class="invalid-feedback"><?= e($e('codigo') ?: 'Formato alfanumérico.') ?></div>
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
                    <?php foreach ($catalogos->tiposEquipo() as $tid => $nombre): ?>
                        <option value="<?= $tid ?>" <?= (int)$valores['tipo'] === $tid ? 'selected' : '' ?>><?= e($nombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label for="estado" class="form-label"><?= e(t('estado')) ?> *</label>
                <select id="estado" name="estado" class="form-select <?= $e('estado') ? 'is-invalid' : '' ?>" required
                        data-advertencia-baja>
                    <?php foreach ($catalogos->estadosEquipo() as $eid => $enombre): ?>
                        <option value="<?= $eid ?>" <?= (int)$valores['estado'] === $eid ? 'selected' : '' ?>>
                            <?= e(str_replace('_', ' ', ucfirst($enombre))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"><?= e($e('estado') ?: '') ?></div>
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
                       value="<?= $valor('ubicacion') ?>" required maxlength="100">
                <div class="invalid-feedback"><?= e($e('ubicacion') ?: 'Campo obligatorio.') ?></div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label for="fecha_adquisicion" class="form-label"><?= e(t('fecha_adquisicion')) ?></label>
                <input type="date" id="fecha_adquisicion" name="fecha_adquisicion"
                       class="form-control <?= $e('fecha_adquisicion') ? 'is-invalid' : '' ?>"
                       value="<?= $valor('fecha_adquisicion') ?>" max="<?= date('Y-m-d') ?>">
            </div>

            <div class="col-12">
                <label for="observaciones" class="form-label"><?= e(t('observaciones')) ?></label>
                <textarea id="observaciones" name="observaciones" class="form-control" rows="3"
                          maxlength="500" data-contador-caracteres><?= $valor('observaciones') ?></textarea>
                <small class="text-secondary"><span data-salida-contador>0</span>/500</small>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= e(t('guardar')) ?></button>
                <a href="detalle.php?id=<?= $id ?>" class="btn btn-outline-secondary"><?= e(t('cancelar')) ?></a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
