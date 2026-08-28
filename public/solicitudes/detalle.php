<?php
/**
 * solicitudes/detalle.php — Detalle y atención de una solicitud.
 * Soporte: cambia estado (en proceso / completada / rechazada) dejando
 * siempre respuesta documentada. Solicitante: solo lectura.
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirLogin();

$base = '../';
$servicio = new SolicitudesServicio();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    abortar(404);
}

try {
    $solicitud = $servicio->obtenerPorId($id);
} catch (NoEncontradoException) {
    abortar(404, 'La solicitud solicitada no existe.');
}

if (!$servicio->puedeVer($solicitud)) {
    abortar(403, 'No puede ver una solicitud que no registró.');
}

$errores = [];
if (esPost() && Auth::esSoporte()) {
    Csrf::verificar();
    try {
        $servicio->atender($id, $_POST);
        Sesion::flash('success', 'Solicitud actualizada.');
        redirigir("detalle.php?id=$id");
    } catch (ValidacionException $ex) {
        $errores = $ex->errores();
        http_response_code(400);
    }
}

require __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= $base ?>dashboard.php"><?= e(t('inicio')) ?></a></li>
        <li class="breadcrumb-item"><a href="listar.php"><?= e(t('solicitudes')) ?></a></li>
        <li class="breadcrumb-item active" aria-current="page">#<?= $solicitud->id() ?></li>
    </ol>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Solicitud #<?= $solicitud->id() ?> — <?= e($solicitud->titulo()) ?></h1>
        <small class="text-secondary">
            <?= e($solicitud->tipoNombre()) ?> · <?= e(t('creado_por')) ?>: <?= e($solicitud->solicitanteNombre()) ?>
            · <?= formatearFecha($solicitud->fechaCreacion(), true) ?>
            <?php if ($solicitud->atendidaPorNombre()): ?> · <?= e(t('atendida_por')) ?>: <?= e($solicitud->atendidaPorNombre()) ?><?php endif; ?>
        </small>
    </div>
    <?= badge($solicitud->estadoNombre()) ?>
    <?php if (Auth::esAdmin() || $solicitud->solicitanteId() === (int)Sesion::id()): ?>
        <form method="post" action="eliminar.php" class="d-inline"
              data-confirmar="¿Eliminar la solicitud #<?= $solicitud->id() ?>? El historial se conservará para auditoría.">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= $solicitud->id() ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger"><?= e('Eliminar') ?></button>
        </form>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><?= e(t('descripcion')) ?></div>
            <div class="card-body">
                <p><?= nl2br(e($solicitud->descripcion())) ?></p>
                <dl class="row small mb-0">
                    <dt class="col-sm-5 text-secondary"><?= e(t('laboratorio')) ?>:</dt>
                    <dd class="col-sm-7"><?= e($solicitud->laboratorio() ?: '—') ?></dd>
                    <dt class="col-sm-5 text-secondary"><?= e(t('fecha_necesidad')) ?>:</dt>
                    <dd class="col-sm-7"><strong><?= formatearFecha($solicitud->fechaNecesidad()) ?></strong></dd>
                    <?php if ($solicitud->fechaCierre()): ?>
                        <dt class="col-sm-5 text-secondary">Cierre:</dt>
                        <dd class="col-sm-7"><?= formatearFecha($solicitud->fechaCierre(), true) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
            <?php if ($solicitud->respuesta()): ?>
                <div class="card-footer bg-light">
                    <span class="fw-semibold small"><?= e(t('respuesta')) ?>:</span>
                    <p class="mb-0 small"><?= nl2br(e($solicitud->respuesta())) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (Auth::esSoporte() && !in_array($solicitud->estadoNombre(), ['completada', 'rechazada'], true)): ?>
    <div class="col-xl-5">
        <div class="card shadow-sm border-info h-100">
            <div class="card-header bg-white fw-semibold"><?= e(t('atender')) ?></div>
            <div class="card-body">
                <?php if ($errores): ?><div class="alert alert-danger py-2 small"><?= e(reset($errores)) ?></div><?php endif; ?>
                <form method="post" action="detalle.php?id=<?= $id ?>" id="formAtender" novalidate>
                    <?= Csrf::campo() ?>
                    <div class="mb-2">
                        <label for="estado" class="form-label small"><?= e(t('estado')) ?></label>
                        <?php foreach ([2 => 'en_proceso', 3 => 'completada', 4 => 'rechazada'] as $eid => $enombre): ?>
                            <input type="hidden" data-estado-nombre="<?= $eid ?>" value="<?= e($enombre) ?>">
                        <?php endforeach; ?>
                        <select name="estado" id="estado" class="form-select form-select-sm"
                                data-estado-actual="<?= $solicitud->estadoNombre() ?>" required>
                            <option value="2" <?= (int)$solicitud->estadoId() === 2 ? 'selected' : '' ?>>En proceso</option>
                            <option value="3">Completada</option>
                            <option value="4">Rechazada</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="respuesta" class="form-label small"><?= e(t('respuesta')) ?> *</label>
                        <textarea name="respuesta" id="respuesta" rows="3" class="form-control form-control-sm"
                                  maxlength="2000" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100"><?= e(t('guardar')) ?></button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
