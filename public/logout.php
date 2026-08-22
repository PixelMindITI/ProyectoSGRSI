<?php
/**
 * logout.php — Cierre de sesión.
 */

require_once __DIR__ . '/_init.php';

Sesion::cerrar();
session_start();
Sesion::flash('success', 'Sesión cerrada correctamente.');
redirigir('index.php');
