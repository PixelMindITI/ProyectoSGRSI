<?php
/**
 * solicitudes/nueva.php — Creación de solicitud de servicio
 * (preparación de laboratorios, instalación de software, configuraciones).
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirLogin();

$base = '../';
$servicio = new SolicitudesServicio();
$catalogos = new CatalogoRepositorio();

$valores = ['titulo' => '', 'tipo' => '', 'descripcion' => '', 'laboratorio' => '',
            'fecha_necesidad' => date('Y-m-d', strtotime('+7 days'))];
$errores = [];

if (esPost()) {
    Csrf::verificar();
    $valores = ['titulo' => post('titulo'), 'tipo' => post('tipo'), 'descripcion' => post('descripcion'),
                'laboratorio' => post('laboratorio'), 'fecha_necesidad' => $_POST['fecha_necesidad'] ?? ''];
    try {
        $id = $servicio->crear($_POST);
        Sesion::flash('success', "Solicitud #$id enviada. Será evaluada por el equipo de soporte.");
        redirigir("detalle.php?id=$id");
    } catch (ValidacionException $ex) {
        $errores = $ex->errores();
        http_response_code(400);
    }
}
$e = fn(string $c) => $errores[$c] ?? '';

require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 fw-bold mb-4"><?= e(t('nueva_solicitud')) ?></h1>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="post" action="nueva.php" id="formSolicitud" class="row g-3" novalidate>
            <?= Csrf::campo() ?>

            <div class="col-12 col-xl-8">
                <label for="titulo" class="form-label"><?= e(t('titulo')) ?> *</label>
                <input type="text" id="titulo" name="titulo"
                       class="form-control <?= $e('titulo') ? 'is-invalid' : '' ?>"
                       value="<?= e($valores['titulo']) ?>" required maxlength="150">
                <div class="invalid-feedback"><?= e($e('titulo') ?: 'Campo obligatorio.') ?></div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label for="tipo" class="form-label"><?= e(t('tipo')) ?> *</label>
                <select id="tipo" name="tipo" class="form-select <?= $e('tipo') ? 'is-invalid' : '' ?>" required>
                    <option value="">—</option>
                    <?php foreach ($catalogos->tiposSolicitud() as $tid => $tnombre): ?>
                        <option value="<?= $tid ?>" <?= ($valores['tipo'] ?? '') == $tid ? 'selected' : '' ?>><?= e($tnombre) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"><?= e($e('tipo') ?: 'Seleccione una opción.') ?></div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label for="laboratorio" class="form-label"><?= e(t('laboratorio')) ?></label>
                <input type="text" id="laboratorio" name="laboratorio" class="form-control"
                       value="<?= e($valores['laboratorio']) ?>" maxlength="80" list="listaLabs">
                <datalist id="listaLabs">
                    <option value="Laboratorio A"><option value="Laboratorio B"><option value="Aula 8">
                    <option value="Aula 12"><option value="Salón principal">
                </datalist>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label for="fecha_necesidad" class="form-label"><?= e(t('fecha_necesidad')) ?> *</label>
                <input type="date" id="fecha_necesidad" name="fecha_necesidad"
                       class="form-control <?= $e('fecha_necesidad') ? 'is-invalid' : '' ?>"
                       value="<?= e($valores['fecha_necesidad']) ?>" min="<?= date('Y-m-d') ?>" required>
                <div class="invalid-feedback"><?= e($e('fecha_necesidad') ?: 'Fecha inválida.') ?></div>
            </div>

            <div class="col-12">
                <label for="descripcion" class="form-label"><?= e(t('descripcion')) ?> *</label>
                <textarea id="descripcion" name="descripcion" rows="5" required maxlength="2000"
                          class="form-control <?= $e('descripcion') ? 'is-invalid' : '' ?>"
                          data-contador-caracteres><?= e($valores['descripcion']) ?></textarea>
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
