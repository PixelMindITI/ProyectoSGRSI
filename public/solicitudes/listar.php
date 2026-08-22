<?php
/**
 * solicitudes/listar.php — Listado de solicitudes de servicio.
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirLogin();

$base = '../';
$servicio = new SolicitudesServicio();
$catalogos = new CatalogoRepositorio();

$filtros = ['estado' => $_GET['estado'] ?? '', 'tipo' => $_GET['tipo'] ?? ''];
$solicitudes = $servicio->listarParaUsuarioActual(array_filter($filtros));

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h1 class="h3 fw-bold mb-0"><?= e(t('solicitudes')) ?></h1>
    <a href="nueva.php" class="btn btn-primary">+ <?= e(t('nueva_solicitud')) ?></a>
</div>

<form method="get" class="row g-2 mb-3" data-filtro-auto>
    <div class="col-6 col-md-3">
        <select name="estado" class="form-select">
            <option value=""><?= e(t('estado')) ?>: <?= e(t('todos')) ?></option>
            <?php foreach ($catalogos->estadosSolicitud() as $eid => $enombre): ?>
                <option value="<?= $eid ?>" <?= ($filtros['estado'] ?? '') == $eid ? 'selected' : '' ?>><?= e(ucfirst($enombre)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-3">
        <select name="tipo" class="form-select">
            <option value=""><?= e(t('tipo')) ?>: <?= e(t('todos')) ?></option>
            <?php foreach ($catalogos->tiposSolicitud() as $tid => $tnombre): ?>
                <option value="<?= $tid ?>" <?= ($filtros['tipo'] ?? '') == $tid ? 'selected' : '' ?>><?= e($tnombre) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-outline-secondary"><?= e(t('filtrar')) ?></button></div>
</form>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>#</th>
                <th><?= e(t('titulo')) ?></th>
                <?php if (Auth::esSoporte()): ?><th class="d-none d-lg-table-cell"><?= e(t('solicitante')) ?></th><?php endif; ?>
                <th class="d-none d-xl-table-cell"><?= e(t('laboratorio')) ?></th>
                <th><?= e(t('fecha_necesidad')) ?></th>
                <th><?= e(t('estado')) ?></th>
                <th class="text-end"></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$solicitudes): ?>
                <tr><td colspan="<?= Auth::esSoporte() ? 7 : 6 ?>" class="text-center text-secondary py-4"><?= e(t('sin_datos')) ?></td></tr>
            <?php endif; ?>
            <?php foreach ($solicitudes as $so): ?>
                <tr>
                    <td><?= $so->id() ?></td>
                    <td><a href="detalle.php?id=<?= $so->id() ?>"><?= e($so->titulo()) ?></a><br><small class="text-secondary"><?= e($so->tipoNombre()) ?></small></td>
                    <?php if (Auth::esSoporte()): ?><td class="d-none d-lg-table-cell"><?= e($so->solicitanteNombre()) ?></td><?php endif; ?>
                    <td class="d-none d-xl-table-cell"><?= e($so->laboratorio() ?: '—') ?></td>
                    <td><?= formatearFecha($so->fechaNecesidad()) ?></td>
                    <td><?= badge($so->estadoNombre()) ?></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="detalle.php?id=<?= $so->id() ?>"><?= e(t('ver')) ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
