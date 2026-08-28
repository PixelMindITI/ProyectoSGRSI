<?php
/**
 * incidencias/detalle.php — Ficha del ticket con ciclo de vida completo.
 * Soporte: asigna técnico, cambia estado, agrega diagnósticos (base de
 * conocimiento) y resuelve. Solicitante: solo lectura del propio ticket.
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirLogin();

$base = '../';
$servicio = new TicketServicio();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    abortar(404);
}

try {
    $ticket = $servicio->obtenerPorId($id);
} catch (NoEncontradoException) {
    abortar(404, 'El ticket solicitado no existe.');
}

// Control de acceso por propiedad/rol
if (!$servicio->puedeVer($ticket)) {
    abortar(403, 'No puede ver un ticket que no registró.');
}

$intervenciones = $servicio->intervenciones($id);
$errores = [];

if (esPost() && Auth::esSoporte()) {
    Csrf::verificar();
    try {
        $servicio->gestionar($id, $_POST);
        Sesion::flash('success', 'Ticket actualizado.');
        redirigir("detalle.php?id=$id");
    } catch (ValidacionException $ex) {
        $errores = $ex->errores();
        http_response_code(400);
    }
}

$catalogos = new CatalogoRepositorio();
require __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= $base ?>dashboard.php"><?= e(t('inicio')) ?></a></li>
        <li class="breadcrumb-item"><a href="listar.php"><?= e(t('incidencias')) ?></a></li>
        <li class="breadcrumb-item active" aria-current="page">#<?= $ticket->id() ?></li>
    </ol>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Ticket #<?= $ticket->id() ?> — <?= e($ticket->titulo()) ?></h1>
        <small class="text-secondary">
            <?= e(t('creado_por')) ?>: <?= e($ticket->solicitanteNombre()) ?> ·
            <?= formatearFecha($ticket->fechaCreacion(), true) ?>
            <?php if ($ticket->fechaResolucion()): ?> · <?= e(t('resuelto_el')) ?> <?= formatearFecha($ticket->fechaResolucion(), true) ?><?php endif; ?>
        </small>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <?= badge($ticket->prioridadNombre()) ?> <?= badge($ticket->estadoNombre()) ?>
        <?php if (Auth::esAdmin() || $ticket->solicitanteId() === (int)Sesion::id()): ?>
            <form method="post" action="eliminar.php" class="d-inline"
                  data-confirmar="¿Eliminar el ticket #<?= $ticket->id() ?>? El historial se conservará para auditoría.">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= $ticket->id() ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"><?= e('Eliminar') ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><?= e(t('descripcion')) ?></div>
            <div class="card-body">
                <p class="mb-2"><?= nl2br(e($ticket->descripcion())) ?></p>
                <?php if ($ticket->equipoCodigo()): ?>
                    <hr>
                    <span class="text-secondary small"><?= e(t('equipo')) ?>:</span>
                    <a class="small" href="../equipos/detalle.php?id=<?= $ticket->equipoId() ?>">
                        <?= e($ticket->equipoCodigo()) ?>
                    </a>
                <?php endif; ?>
                <dl class="row small text-secondary mt-3 mb-0">
                    <dt class="col-sm-5"><?= e(t('tecnico_asignado')) ?>:</dt>
                    <dd class="col-sm-7"><?= e($ticket->tecnicoNombre() ?: '—') ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <?php if (Auth::esSoporte() && $ticket->estaAbierto()): ?>
    <div class="col-xl-5">
        <div class="card shadow-sm border-info h-100">
            <div class="card-header bg-white fw-semibold">Gestión</div>
            <div class="card-body">
                <?php if ($errores): ?><div class="alert alert-danger py-2 small"><?= e(reset($errores)) ?></div><?php endif; ?>
                <form method="post" action="detalle.php?id=<?= $id ?>" id="formGestionTicket" novalidate>
                    <?= Csrf::campo() ?>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label for="tecnico" class="form-label small"><?= e(t('tecnico_asignado')) ?></label>
                            <select name="tecnico" id="tecnico" class="form-select form-select-sm">
                                <option value="">—</option>
                                <?php foreach ($catalogos->usuariosPorRol(2) as $tid => $tnombre): ?>
                                    <option value="<?= $tid ?>" <?= $ticket->tecnicoId() === $tid ? 'selected' : '' ?>><?= e($tnombre) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="estado" class="form-label small"><?= e(t('estado')) ?></label>
                            <?php foreach ($catalogos->estadosTicket() as $eid => $enombre): ?>
                                <input type="hidden" data-estado-nombre="<?= $eid ?>" value="<?= e($enombre) ?>">
                            <?php endforeach; ?>
                            <select name="estado" id="estado" class="form-select form-select-sm"
                                    data-estado-actual="<?= $ticket->estadoNombre() ?>" required>
                                <?php foreach ($catalogos->estadosTicket() as $eid => $enombre): ?>
                                    <option value="<?= $eid ?>" <?= $ticket->estadoId() === $eid ? 'selected' : '' ?>>
                                        <?= e(str_replace('_', ' ', ucfirst($enombre))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="nota" class="form-label small"><?= e(t('diagnostico_nota')) ?></label>
                        <textarea name="nota" id="nota" rows="3" class="form-control form-control-sm" maxlength="2000"></textarea>
                        <small class="text-secondary d-none" id="avisoResolucion">
                            Obligatorio al resolver el ticket.
                        </small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100"><?= e(t('guardar')) ?></button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold"><?= e(t('intervenciones')) ?></div>
    <ul class="list-group list-group-flush">
        <?php if (!$intervenciones): ?>
            <li class="list-group-item text-secondary"><?= e(t('sin_datos')) ?></li>
        <?php endif; ?>
        <?php foreach ($intervenciones as $i): ?>
            <li class="list-group-item">
                <div class="d-flex justify-content-between flex-wrap gap-1">
                    <strong><?= e($i['tecnico_nombre']) ?></strong>
                    <small class="text-secondary"><?= formatearFecha($i['fecha'], true) ?></small>
                </div>
                <p class="mb-1 mt-1"><?= nl2br(e($i['diagnostico'])) ?></p>
                <?php if ((int)$i['es_resolucion'] === 1): ?><span class="badge text-bg-success">RESOLUCIÓN</span><?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
