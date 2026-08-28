<?php
/**
 * usuarios/eliminar.php — Borrado lógico de un usuario (desactivación).
 * Exclusivo del administrador. No permite eliminar la propia cuenta ni
 * al último administrador activo del sistema.
 */

require_once __DIR__ . '/../_init.php';
Auth::requerirRol(['administrador']);
exigirMetodo('POST');
Csrf::verificar();

$id = (int)post('id');
if ($id <= 0) {
    abortar(400, 'Identificador inválido.');
}

try {
    (new UsuarioServicio())->eliminar($id);
} catch (AccesoDenegadoException) {
    abortar(403, 'No puede eliminar esa cuenta: o es la suya o es el último administrador activo.');
} catch (NoEncontradoException) {
    abortar(404, 'El usuario no existe.');
}

Sesion::flash('success', 'Usuario desactivado. Podrá reactivarlo desde su edición.');
redirigir('listar.php');