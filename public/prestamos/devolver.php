<?php
/**
 * prestamos/devolver.php — Confirmación y registro de devolución.
 * GET muestra el formulario; POST ejecuta la devolución (PRG).
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirRol(['administrador', 'tecnico']);

$base = '../';
$servicio = new PrestamoServicio();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    abortar(404);
}

try {
    $prestamo = $servicio->obtenerPorId($id);
} catch (NoEncontradoException) {
    abortar(404, 'El préstamo solicitado no existe.');
}

if ($prestamo->estadoNombre() !== 'activo') {
    Sesion::flash('warning', 'Ese préstamo ya fue devuelto.');
    redirigir('listar.php');
}

if (esPost()) {
    Csrf::verificar();
    try {
        $servicio->devolver($id, $_POST['observaciones'] ?? '');
        Sesion::flash('success', "Devolución del préstamo #$id registrada.");
        redirigir('listar.php');
    } catch (AplicacionException $ex) {
        Sesion::flash('danger', $ex->getMessage());
        redirigir('listar.php');
    }
}

require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 fw-bold mb-1"><?= e(t('devolucion')) ?> — Préstamo #<?= $prestamo->id() ?></h1>
<p class="text-secondary mb-4">
    <?= e($prestamo->equipoCodigo()) ?> · <?= e($prestamo->equipoNombre()) ?><br>
    <?= e(t('solicitante')) ?>: <strong><?= e($prestamo->solicitanteNombre()) ?></strong> ·
    <?= e(t('devolucion_esperada')) ?>: <?= formatearFecha($prestamo->fechaDevolucionEsperada()) ?>
    <?php if ($prestamo->estaVencido()): ?> <span class="badge text-bg-danger"><?= e(t('vencido')) ?></span><?php endif; ?>
</p>

<div class="card shadow-sm border-0" style="max-width: 720px;">
    <div class="card-body">
        <form method="post" action="devolver.php?id=<?= $id ?>" data-confirmar="¿Confirma que el equipo fue devuelto?">
            <?= Csrf::campo() ?>
            <div class="mb-3">
                <label for="observaciones" class="form-label"><?= e(t('observaciones')) ?></label>
                <textarea name="observaciones" id="observaciones" class="form-control" rows="3"
                          maxlength="255" placeholder="Estado del equipo al recibir, accesorios, etc."></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><?= e(t('marcar_devuelto')) ?></button>
                <a href="listar.php" class="btn btn-outline-secondary"><?= e(t('cancelar')) ?></a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
