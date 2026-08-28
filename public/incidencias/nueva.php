<?php
/**
 * incidencias/nueva.php — Creación de ticket de soporte.
 * Cualquier usuario autenticado puede reportar una incidencia.
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirLogin();

$base = '../';
$servicio = new TicketServicio();
$catalogos = new CatalogoRepositorio();

$valores = ['titulo' => '', 'descripcion' => '', 'prioridad' => '2', 'equipo_id' => ''];
$errores = [];

if (esPost()) {
    Csrf::verificar();
    $valores = ['titulo' => post('titulo'), 'descripcion' => post('descripcion'),
                'prioridad' => post('prioridad'), 'equipo_id' => post('equipo_id')];
    try {
        $id = $servicio->crear($_POST);
        Sesion::flash('success', "Ticket #$id creado. El equipo técnico lo atenderá a la brevedad.");
        redirigir("detalle.php?id=$id");
    } catch (ValidacionException $ex) {
        $errores = $ex->errores();
        http_response_code(400);
    }
}
$e = fn(string $c) => $errores[$c] ?? '';

require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 fw-bold mb-4"><?= e(t('nuevo_ticket')) ?></h1>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="post" action="nueva.php" id="formTicket" class="row g-3" novalidate>
            <?= Csrf::campo() ?>

            <div class="col-12 col-xl-8">
                <label for="titulo" class="form-label"><?= e(t('titulo')) ?> *</label>
                <input type="text" id="titulo" name="titulo"
                       class="form-control <?= $e('titulo') ? 'is-invalid' : '' ?>"
                       value="<?= e($valores['titulo']) ?>" required maxlength="150"
                       data-texto-sensible
                       placeholder="Resumen breve del problema">
                <div class="invalid-feedback"><?= e($e('titulo') ?: 'Campo obligatorio.') ?></div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label for="prioridad" class="form-label"><?= e(t('prioridad')) ?> *</label>
                <select id="prioridad" name="prioridad" class="form-select <?= $e('prioridad') ? 'is-invalid' : '' ?>" required>
                    <?php foreach ($catalogos->prioridades() as $pid => $pnombre): ?>
                        <option value="<?= $pid ?>" <?= (int)$valores['prioridad'] === $pid ? 'selected' : '' ?>><?= e(ucfirst($pnombre)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-xl-8">
                <label for="equipo_id" class="form-label"><?= e(t('equipo')) ?></label>
                <select id="equipo_id" name="equipo_id" class="form-select">
                    <option value="">—</option>
                    <?php foreach ((new EquipoServicio())->listar() as $eq): ?>
                        <option value="<?= $eq->id() ?>" <?= (int)$valores['equipo_id'] === $eq->id() ? 'selected' : '' ?>>
                            <?= e($eq->codigo()) ?> — <?= e($eq->nombre()) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-secondary">Opcional: si el problema está asociado a un equipo.</small>
            </div>

            <div class="col-12">
                <label for="descripcion" class="form-label"><?= e(t('descripcion')) ?> *</label>
                <textarea id="descripcion" name="descripcion" rows="5" required maxlength="2000"
                          class="form-control <?= $e('descripcion') ? 'is-invalid' : '' ?>"
                          data-contador-caracteres data-texto-sensible
                          placeholder="Detalle qué ocurre, desde cuándo y pasos para reproducirlo"><?= e($valores['descripcion']) ?></textarea>
                <small class="text-secondary"><span data-salida-contador>0</span>/2000</small>
                <div class="invalid-feedback"><?= e($e('descripcion') ?: 'Campo obligatorio.') ?></div>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= e(t('guardar')) ?></button>
                <a href="listar.php" class="btn btn-outline-secondary"><?= e(t('cancelar')) ?></a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
