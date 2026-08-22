<?php
/**
 * usuarios/listar.php — Administración de usuarios (solo administrador).
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirRol(['administrador']);

$base = '../';
$servicio = new UsuarioServicio();
$catalogos = new CatalogoRepositorio();

$filtros = ['q' => $_GET['q'] ?? '', 'rol' => $_GET['rol'] ?? ''];
$usuarios = $servicio->listar(array_filter($filtros));
$roles = $catalogos->roles();

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h1 class="h3 fw-bold mb-0"><?= e(t('usuarios')) ?></h1>
    <a href="alta.php" class="btn btn-primary">+ <?= e(t('registrarse')) ?></a>
</div>

<form method="get" class="row g-2 mb-3" data-filtro-auto>
    <div class="col-12 col-md">
        <input type="search" name="q" class="form-control" placeholder="<?= e(t('buscar')) ?>" value="<?= e($filtros['q']) ?>">
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <select name="rol" class="form-select">
            <option value=""><?= e(t('rol')) ?>: <?= e(t('todos')) ?></option>
            <?php foreach ($roles as $rid => $rnombre): ?>
                <option value="<?= $rid ?>" <?= ($filtros['rol'] ?? '') == $rid ? 'selected' : '' ?>><?= e(ucfirst($rnombre)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-outline-secondary"><?= e(t('filtrar')) ?></button></div>
</form>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tablaUsuarios">
            <thead class="table-light">
            <tr>
                <th><?= e(t('nombre')) ?></th>
                <th><?= e(t('email')) ?></th>
                <th><?= e(t('rol')) ?></th>
                <th><?= e(t('activo')) ?></th>
                <th class="text-end"><?= e(t('acciones')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$usuarios): ?>
                <tr><td colspan="5" class="text-center text-secondary py-4"><?= e(t('sin_datos')) ?></td></tr>
            <?php endif; ?>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= e($u->nombreCompleto()) ?></td>
                    <td class="small"><?= e($u->email()) ?></td>
                    <td><?= badge($u->rolNombre()) ?></td>
                    <td><?= $u->activo() ? '<span class="badge text-bg-success">SI</span>' : '<span class="badge text-bg-secondary">NO</span>' ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary"
                           href="editar.php?id=<?= $u->id() ?>"><?= e(t('editar')) ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white text-secondary small">Total: <?= count($usuarios) ?></div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
