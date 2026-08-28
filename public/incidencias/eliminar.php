<?php
/**
 * incidencias/eliminar.php — Borrado lógico de un ticket.
 * Administrador: cualquier ticket. Solicitante: solo los propios.
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
    (new TicketServicio())->eliminar($id);
} catch (AccesoDenegadoException) {
    abortar(403, 'No tiene permisos para eliminar este ticket.');
} catch (NoEncontradoException) {
    abortar(404, 'El ticket solicitado no existe.');
}

Sesion::flash('success', 'Ticket eliminado.');
redirigir('listar.php');