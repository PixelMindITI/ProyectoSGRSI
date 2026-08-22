<?php
/**
 * equipos/listar.php — Listado del inventario con filtros.
 * Acceso: todos los usuarios autenticados.
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirLogin();

$base = '../';
$servicio = new EquipoServicio();

$filtros = [
    'q'      => post('q') !== '' ? post('q') : ($_GET['q'] ?? ''),
    'estado' => $_GET['estado'] ?? '',
    'tipo'   => $_GET['tipo'] ?? '',
];
$equipos = $servicio->listar(array_filter($filtros));
$catalogos = new CatalogoRepositorio();

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h1 class="h3 fw-bold mb-0"><?= e(t('inventario')) ?></h1>
    <?php if (Auth::esSoporte()): ?>
        <a href="alta.php" class="btn btn-primary"><?= e(t('nuevo_equipo')) ?></a>
    <?php endif; ?>
</div>

<form method="get" class="row g-2 mb-3" data-filtro-auto>
    <div class="col-12 col-md">
        <input type="search" name="q" class="form-control" placeholder="<?= e(t('buscar')) ?>"
               value="<?= e($filtros['q']) ?>" aria-label="Buscar">
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <select name="estado" class="form-select" aria-label="Estado">
            <option value=""><?= e(t('estado')) ?>: <?= e(t('todos')) ?></option>
            <?php foreach ($catalogos->estadosEquipo() as $id => $nombre): ?>
                <option value="<?= $id ?>" <?= ($filtros['estado'] ?? '') == $id ? 'selected' : '' ?>><?= e(str_replace('_',' ',ucfirst($nombre))) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <select name="tipo" class="form-select" aria-label="Tipo">
            <option value=""><?= e(t('tipo')) ?>: <?= e(t('todos')) ?></option>
            <?php foreach ($catalogos->tiposEquipo() as $id => $nombre): ?>
                <option value="<?= $id ?>" <?= ($filtros['tipo'] ?? '') == $id ? 'selected' : '' ?>><?= e($nombre) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-secondary"><?= e(t('filtrar')) ?></button>
    </div>
</form>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tablaEquipos">
            <thead class="table-light">
            <tr>
                <th><?= e(t('codigo_inv')) ?></th>
                <th><?= e(t('equipo')) ?></th>
                <th class="d-none d-lg-table-cell"><?= e(t('marca')) ?> / <?= e(t('modelo')) ?></th>
                <th class="d-none d-xl-table-cell"><?= e(t('ubicacion')) ?></th>
                <th><?= e(t('estado')) ?></th>
                <th class="text-end"><?= e(t('acciones')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$equipos): ?>
                <tr><td colspan="6" class="text-center text-secondary py-4"><?= e(t('sin_datos')) ?></td></tr>
            <?php endif; ?>
            <?php foreach ($equipos as $eq): ?>
                <tr>
                    <td class="text-monospace small"><?= e($eq->codigo()) ?></td>
                    <td><?= e($eq->nombre()) ?><br><small class="text-secondary">S/N: <?= e($eq->numeroSerie()) ?></small></td>
                    <td class="d-none d-lg-table-cell"><?= e($eq->marca()) ?> — <?= e($eq->modelo()) ?></td>
                    <td class="d-none d-xl-table-cell"><?= e($eq->ubicacion()) ?></td>
                    <td><?= badge($eq->estadoNombre()) ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="detalle.php?id=<?= $eq->id() ?>"><?= e(t('ver')) ?></a>
                        <?php if (Auth::esSoporte()): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="editar.php?id=<?= $eq->id() ?>"><?= e(t('editar')) ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white text-secondary small">Total: <?= count($equipos) ?></div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
