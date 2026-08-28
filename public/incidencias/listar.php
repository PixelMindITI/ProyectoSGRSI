<?php
/**
 * incidencias/listar.php — Mesa de ayuda: listado de tickets.
 * Solicitantes solo ven los propios; soporte ve todos con filtros.
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirLogin();

$base = '../';
$servicio = new TicketServicio();
$catalogos = new CatalogoRepositorio();

$filtros = [
    'q'       => $_GET['q'] ?? '',
    'estado'  => $_GET['estado'] ?? '',
    'tecnico' => $_GET['tecnico'] ?? '',
];
$tickets = $servicio->listarParaUsuarioActual(array_filter($filtros));

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h1 class="h3 fw-bold mb-0"><?= e(t('incidencias')) ?></h1>
    <a href="nueva.php" class="btn btn-primary">+ <?= e(t('nuevo_ticket')) ?></a>
</div>

<form method="get" class="row g-2 mb-3" data-filtro-auto>
    <div class="col-12 col-md">
        <input type="search" name="q" class="form-control" placeholder="<?= e(t('buscar')) ?>" value="<?= e($filtros['q']) ?>">
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <select name="estado" class="form-select">
            <option value=""><?= e(t('estado')) ?>: <?= e(t('todos')) ?></option>
            <?php foreach ($catalogos->estadosTicket() as $eid => $enombre): ?>
                <option value="<?= $eid ?>" <?= ($filtros['estado'] ?? '') == $eid ? 'selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst($enombre))) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if (Auth::esSoporte()): ?>
    <div class="col-6 col-md-3 col-xl-2">
        <select name="tecnico" class="form-select">
            <option value=""><?= e(t('tecnico_asignado')) ?>: <?= e(t('todos')) ?></option>
            <?php foreach ($catalogos->usuariosPorRol(2) as $tid => $tnombre): ?>
                <option value="<?= $tid ?>" <?= ($filtros['tecnico'] ?? '') == $tid ? 'selected' : '' ?>><?= e($tnombre) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="col-auto"><button type="submit" class="btn btn-outline-secondary"><?= e(t('filtrar')) ?></button></div>
</form>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tablaTickets">
            <thead class="table-light">
            <tr>
                <th>#</th>
                <th><?= e(t('titulo')) ?></th>
                <th class="d-none d-lg-table-cell"><?= e(t('creado_por')) ?></th>
                <th class="d-none d-xl-table-cell"><?= e(t('tecnico_asignado')) ?></th>
                <th><?= e(t('prioridad')) ?></th>
                <th><?= e(t('estado')) ?></th>
                <th class="d-none d-lg-table-cell"><?= e(t('fecha')) ?></th>
                <th class="text-end"></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$tickets): ?>
                <tr><td colspan="8" class="text-center text-secondary py-4"><?= e(t('sin_datos')) ?></td></tr>
            <?php endif; ?>
            <?php foreach ($tickets as $tk): ?>
                <tr>
                    <td><?= $tk->id() ?></td>
                    <td class="text-truncate" style="max-width:280px; min-width:180px;">
                        <a href="detalle.php?id=<?= $tk->id() ?>"><?= e($tk->titulo()) ?></a>
                        <?php if ($tk->equipoCodigo()): ?><br><small class="text-monospace text-secondary"><?= e($tk->equipoCodigo()) ?></small><?php endif; ?>
                    </td>
                    <td class="d-none d-lg-table-cell"><?= e($tk->solicitanteNombre()) ?></td>
                    <td class="d-none d-xl-table-cell"><?= e($tk->tecnicoNombre() ?: '—') ?></td>
                    <td><?= badge($tk->prioridadNombre()) ?></td>
                    <td><?= badge($tk->estadoNombre()) ?></td>
                    <td class="d-none d-lg-table-cell small"><?= formatearFecha($tk->fechaCreacion(), true) ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="detalle.php?id=<?= $tk->id() ?>"><?= e(t('ver')) ?></a>
                        <?php if (Auth::esAdmin() || $tk->solicitanteId() === (int)Sesion::id()): ?>
                            <form method="post" action="eliminar.php" class="d-inline"
                                  data-confirmar="¿Eliminar el ticket #<?= $tk->id() ?>?">
                                <?= Csrf::campo() ?>
                                <input type="hidden" name="id" value="<?= $tk->id() ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><?= e('Eliminar') ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
