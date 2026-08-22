<?php
/**
 * dashboard.php — Inicio / Panel de métricas.
 * El administrador y el técnico ven métricas globales (requerimiento H);
 * el solicitante ve un resumen de sus propios tickets y solicitudes.
 */

require_once __DIR__ . '/_init.php';
Auth::requerirLogin();

$base = '';

$metricas = null;
$misTickets = [];
$misSolicitudes = [];

if (Auth::esSoporte()) {
    $metricas = (new MetricaServicio())->resumen();
} else {
    $misTickets     = array_slice((new TicketServicio())->listarParaUsuarioActual(), 0, 5);
    $misSolicitudes = array_slice((new SolicitudesServicio())->listarParaUsuarioActual(), 0, 5);
}

require __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h1 class="h3 fw-bold mb-0"><?= e(t('dashboard')) ?></h1>
    <span class="text-secondary">
        <?= e(t('bienvenida')) ?>, <strong><?= e(Sesion::nombre()) ?></strong> <?= badge(Sesion::rol()) ?>
    </span>
</div>

<?php if ($metricas): ?>
    <!-- Tarjetas de métricas: grid responsive (1 col móvil → 4 cols monitores) -->
    <div class="row g-3 mb-4">
        <?php
        $tarjetas = [
            ['valor' => $metricas['total_equipos'],  'texto' => t('equipos_totales'),   'icono' => '🖥️', 'color' => 'primary'],
            ['valor' => $metricas['prestamos_activos'], 'texto' => t('prestamos_activos'), 'icono' => '📦', 'color' => 'warning'],
            ['valor' => $metricas['tickets_por_estado']['pendiente'] ?? 0, 'texto' => t('tickets_pendientes'), 'icono' => '🎫', 'color' => 'danger'],
            ['valor' => $metricas['solicitudes_pendientes'], 'texto' => t('sol_pendientes'), 'icono' => '📝', 'color' => 'info'],
        ];
        foreach ($tarjetas as $tarjeta): ?>
            <div class="col-6 col-xl-3">
                <div class="card metric-card border-0 shadow-sm h-100 border-start border-4 border-<?= e($tarjeta['color']) ?>">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="fs-2" aria-hidden="true"><?= $tarjeta['icono'] ?></span>
                        <div>
                            <div class="h3 fw-bold mb-0" data-contador="<?= (int)$tarjeta['valor'] ?>"><?= (int)$tarjeta['valor'] ?></div>
                            <small class="text-secondary"><?= e($tarjeta['texto']) ?></small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white fw-semibold"><?= e(t('por_estado')) ?> — <?= e(t('equipos')) ?></div>
                <div class="card-body">
                    <?php if (!$metricas['equipos_por_estado']): ?>
                        <p class="text-secondary mb-0"><?= e(t('sin_datos')) ?></p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($metricas['equipos_por_estado'] as $estado => $cantidad): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <?= badge($estado) ?> <span class="fw-bold"><?= (int)$cantidad ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white fw-semibold"><?= e(t('mas_fallados')) ?></div>
                <div class="card-body p-0">
                    <?php if (!$metricas['equipos_mas_fallados']): ?>
                        <p class="text-secondary m-3"><?= e(t('sin_datos')) ?></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light"><tr><th><?= e(t('codigo_inv')) ?></th><th><?= e(t('equipo')) ?></th><th class="text-end">Tickets</th></tr></thead>
                                <tbody>
                                <?php foreach ($metricas['equipos_mas_fallados'] as $f): ?>
                                    <tr><td class="text-monospace small"><?= e($f['codigo']) ?></td><td><?= e($f['nombre']) ?></td><td class="text-end fw-bold"><?= (int)$f['total_tickets'] ?></td></tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white text-secondary small">
                    <?= e(t('prom_resolucion')) ?>:
                    <strong><?= $metricas['promedio_resolucion_dias'] !== null ? e((string)$metricas['promedio_resolucion_dias']) : '—' ?></strong>
                </div>
            </div>
        </div>
    </div>

<?php else: /* vista solicitante */ ?>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                    <?= e(t('mis_tickets')) ?>
                    <a href="incidencias/nueva.php" class="btn btn-sm btn-primary">+ <?= e(t('nuevo_ticket')) ?></a>
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (!$misTickets): ?><li class="list-group-item text-secondary"><?= e(t('sin_datos')) ?></li><?php endif; ?>
                    <?php foreach ($misTickets as $tk): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="incidencias/detalle.php?id=<?= $tk->id() ?>">#<?= $tk->id() ?> — <?= e($tk->titulo()) ?></a>
                            <?= badge($tk->estadoNombre()) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                    <?= e(t('mis_solicitudes')) ?>
                    <a href="solicitudes/nueva.php" class="btn btn-sm btn-primary">+ <?= e(t('nueva_solicitud')) ?></a>
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (!$misSolicitudes): ?><li class="list-group-item text-secondary"><?= e(t('sin_datos')) ?></li><?php endif; ?>
                    <?php foreach ($misSolicitudes as $so): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="solicitudes/detalle.php?id=<?= $so->id() ?>">#<?= $so->id() ?> — <?= e($so->titulo()) ?></a>
                            <?= badge($so->estadoNombre()) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
