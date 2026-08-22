<?php
/**
 * prestamos/listar.php — Listado de préstamos con estado y vencimientos.
 * Solicitantes solo ven los propios; soporte ve todos.
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirLogin();

$base = '../';
$servicio = new PrestamoServicio();

$filtros = ['estado' => $_GET['estado'] ?? '', 'q' => $_GET['q'] ?? ''];
$prestamos = $servicio->listarParaUsuarioActual(array_filter($filtros));
$catalogos = new CatalogoRepositorio();

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h1 class="h3 fw-bold mb-0"><?= e(t('prestamos')) ?></h1>
    <?php if (Auth::esSoporte()): ?>
        <a href="nuevo.php" class="btn btn-primary">+ <?= e(t('nuevo_prestamo')) ?></a>
    <?php endif; ?>
</div>

<form method="get" class="row g-2 mb-3" data-filtro-auto>
    <div class="col-12 col-md">
        <input type="search" name="q" class="form-control" placeholder="<?= e(t('buscar')) ?>" value="<?= e($filtros['q']) ?>">
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <select name="estado" class="form-select">
            <option value=""><?= e(t('estado')) ?>: <?= e(t('todos')) ?></option>
            <?php foreach ($catalogos->estadosPrestamo() as $id => $nombre): ?>
                <option value="<?= $id ?>" <?= ($filtros['estado'] ?? '') == $id ? 'selected' : '' ?>><?= e(ucfirst($nombre)) ?></option>
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
                <th><?= e(t('equipo')) ?></th>
                <th><?= e(t('solicitante')) ?></th>
                <th class="d-none d-lg-table-cell"><?= e(t('fecha_prestamo')) ?></th>
                <th><?= e(t('devolucion_esperada')) ?></th>
                <th class="d-none d-xl-table-cell"><?= e(t('devolucion_real')) ?></th>
                <th><?= e(t('estado')) ?></th>
                <?php if (Auth::esSoporte()): ?><th class="text-end"><?= e(t('acciones')) ?></th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php if (!$prestamos): ?>
                <tr><td colspan="<?= Auth::esSoporte() ? 8 : 7 ?>" class="text-center text-secondary py-4"><?= e(t('sin_datos')) ?></td></tr>
            <?php endif; ?>
            <?php foreach ($prestamos as $p): ?>
                <tr class="<?= $p->estaVencido() ? 'table-danger' : '' ?>">
                    <td><?= $p->id() ?></td>
                    <td><span class="text-monospace small"><?= e($p->equipoCodigo()) ?></span><br><?= e($p->equipoNombre()) ?></td>
                    <td><?= e($p->solicitanteNombre()) ?></td>
                    <td class="d-none d-lg-table-cell"><?= formatearFecha($p->fechaPrestamo(), true) ?></td>
                    <td><?= formatearFecha($p->fechaDevolucionEsperada()) ?>
                        <?php if ($p->estaVencido()): ?><br><span class="badge text-bg-danger"><?= e(t('vencido')) ?></span><?php endif; ?>
                    </td>
                    <td class="d-none d-xl-table-cell"><?= $p->fechaDevolucionReal() ? formatearFecha($p->fechaDevolucionReal(), true) : '—' ?></td>
                    <td><?= badge($p->estadoNombre()) ?></td>
                    <?php if (Auth::esSoporte()): ?>
                        <td class="text-end">
                            <?php if ($p->estadoNombre() === 'activo'): ?>
                                <a class="btn btn-sm btn-success" href="devolver.php?id=<?= $p->id() ?>"><?= e(t('devolucion')) ?></a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
