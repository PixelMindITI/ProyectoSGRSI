<?php
/**
 * solicitudes/eliminar.php — Borrado lógico de una solicitud de servicio.
 * Administrador: cualquier solicitud. Solicitante: solo las propias.
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirLogin();
exigirMetodo('POST');
Csrf::verificar();

$id = (int)post('id');
if ($id <= 0) {
    abortar(400, 'Identificador inválido.');
}

try {
    (new SolicitudesServicio())->eliminar($id);
} catch (AccesoDenegadoException) {
    abortar(403, 'No tiene permisos para eliminar esta solicitud.');
} catch (NoEncontradoException) {
    abortar(404, 'La solicitud solicitada no existe.');
}

Sesion::flash('success', 'Solicitud eliminada.');
redirigir('listar.php');