<?php
/**
 * equipos/detalle.php — Ficha del equipo + trazabilidad.
 * El personal de soporte puede asignar un responsable o registrar
 * la devolución de una asignación activa (requerimiento B).
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirLogin();

$base = '../';
$servicio = new EquipoServicio();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    abortar(404);
}

try {
    $equipo = $servicio->obtenerPorId($id);
    $historial = $servicio->historial($id);
} catch (NoEncontradoException) {
    abortar(404, 'El equipo solicitado no existe.');
}

/* --- Asignación de responsable (PRG) --- */
$erroresAsignacion = [];
if (esPost() && ($_POST['accion'] ?? '') === 'asignar') {
    Csrf::verificar();
    try {
        $servicio->asignar($id, (int)($_POST['usuario'] ?? 0), $_POST['fecha'] ?? '', $_POST['observaciones'] ?? '');
        Sesion::flash('success', 'Asignación registrada.');
        redirigir("detalle.php?id=$id");
    } catch (ValidacionException $ex) {
        $erroresAsignacion = $ex->errores();
        http_response_code(400);
    }
}

/* --- Devolución de asignación activa --- */
if (esPost() && ($_POST['accion'] ?? '') === 'devolver') {
    Csrf::verificar();
    try {
        $servicio->devolver($id);
        Sesion::flash('success', 'Devolución registrada: el equipo vuelve a estar disponible.');
        redirigir("detalle.php?id=$id");
    } catch (AplicacionException $ex) {
        Sesion::flash('danger', $ex->getMessage());
        redirigir("detalle.php?id=$id");
    }
}

$catalogos = new CatalogoRepositorio();
require __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= $base ?>dashboard.php"><?= e(t('inicio')) ?></a></li>
        <li class="breadcrumb-item"><a href="listar.php"><?= e(t('inventario')) ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($equipo->codigo()) ?></li>
    </ol>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-start mb-4 gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1"><?= e($equipo->nombre()) ?></h1>
        <span class="text-monospace text-secondary"><?= e($equipo->codigo()) ?></span> <?= badge($equipo->estadoNombre()) ?>
    </div>
    <div class="d-flex gap-2">
        <?php if (Auth::esSoporte()): ?>
            <a href="editar.php?id=<?= $equipo->id() ?>" class="btn btn-outline-primary"><?= e(t('editar')) ?></a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><?= e(t('detalle_equipo')) ?></div>
            <div class="card-body">
                <dl class="row mb-0 detalle-lista">
                    <dt class="col-sm-4"><?= e(t('tipo')) ?></dt><dd class="col-sm-8"><?= e($equipo->tipoNombre()) ?></dd>
                    <dt class="col-sm-4"><?= e(t('marca')) ?> / <?= e(t('modelo')) ?></dt><dd class="col-sm-8"><?= e($equipo->marca()) ?> — <?= e($equipo->modelo()) ?></dd>
                    <dt class="col-sm-4"><?= e(t('numero_serie')) ?></dt><dd class="col-sm-8 text-monospace"><?= e($equipo->numeroSerie()) ?></dd>
                    <dt class="col-sm-4"><?= e(t('ubicacion')) ?></dt><dd class="col-sm-8"><?= e($equipo->ubicacion()) ?></dd>
                    <dt class="col-sm-4"><?= e(t('fecha_adquisicion')) ?></dt><dd class="col-sm-8"><?= formatearFecha($equipo->fechaAdquisicion()) ?></dd>
                    <dt class="col-sm-4"><?= e(t('estado')) ?></dt><dd class="col-sm-8"><?= badge($equipo->estadoNombre()) ?></dd>
                    <?php if ($equipo->observaciones()): ?>
                        <dt class="col-sm-4"><?= e(t('observaciones')) ?></dt>
                        <dd class="col-sm-8"><?= nl2br(e($equipo->observaciones())) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

    <?php if (Auth::esSoporte()): ?>
    <div class="col-xl-4">
        <div class="card shadow-sm h-100 border-info">
            <div class="card-header bg-white fw-semibold"><?= e(t('asignar')) ?></div>
            <div class="card-body">
                <?php if (!$equipo->estaDisponible()): ?>
                    <p class="text-secondary small"><?= e(t('equipo_no_disponible')) ?></p>
                    <form method="post" action="detalle.php?id=<?= $id ?>" data-confirmar="¿Confirma registrar la devolución?">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="accion" value="devolver">
                        <button type="submit" class="btn btn-success w-100"><?= e(t('devolver_equipo')) ?></button>
                    </form>
                <?php else: ?>
                    <form method="post" action="detalle.php?id=<?= $id ?>" id="formAsignar" novalidate>
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="accion" value="asignar">
                        <div class="mb-2">
                            <label for="usuario" class="form-label small"><?= e(t('responsable')) ?> *</label>
                            <select name="usuario" id="usuario" class="form-select <?= isset($erroresAsignacion['usuario']) ? 'is-invalid' : '' ?>" required>
                                <option value="">—</option>
                                <?php foreach ($catalogos->usuariosSolicitantes() as $uid => $unombre): ?>
                                    <option value="<?= $uid ?>"><?= e($unombre) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?= e($erroresAsignacion['usuario'] ?? '') ?></div>
                        </div>
                        <div class="mb-2">
                            <label for="fecha" class="form-label small"><?= e(t('fecha')) ?> *</label>
                            <input type="date" name="fecha" id="fecha" class="form-control"
                                   value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="obs_asig" class="form-label small"><?= e(t('observaciones')) ?></label>
                            <input type="text" name="observaciones" id="obs_asig" class="form-control" maxlength="255">
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><?= e(t('asignar')) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold"><?= e(t('historial')) ?></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th><?= e(t('fecha')) ?></th>
                <th><?= e(t('asignado_a')) ?></th>
                <th><?= e(t('registrado_por')) ?></th>
                <th><?= e(t('devolucion_real')) ?></th>
                <th><?= e(t('observaciones')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$historial): ?>
                <tr><td colspan="5" class="text-center text-secondary py-4"><?= e(t('sin_datos')) ?></td></tr>
            <?php endif; ?>
            <?php foreach ($historial as $mov): ?>
                <tr class="<?= $mov['fecha_devolucion'] ? '' : 'table-warning fw-semibold' ?>">
                    <td><?= formatearFecha($mov['fecha_asignacion']) ?></td>
                    <td><?= e($mov['usuario_nombre']) ?></td>
                    <td><?= e($mov['registrado_por_nombre']) ?></td>
                    <td><?= $mov['fecha_devolucion'] ? formatearFecha($mov['fecha_devolucion']) : '<span class="badge text-bg-warning">ACTIVO</span>' ?></td>
                    <td class="small"><?= e($mov['observaciones'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
