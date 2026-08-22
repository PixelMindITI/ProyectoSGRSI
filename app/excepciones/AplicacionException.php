<?php
/**
 * AplicacionException — Excepción base del dominio.
 * Permite distinguir errores esperados de la aplicación
 * (validaciones, registros inexistentes) de errores imprevistos.
 */

class AplicacionException extends RuntimeException {}
