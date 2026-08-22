<?php
/**
 * RepositorioBase — Patrón REPOSITORY / DAO.
 * ---------------------------------------------------------------
 * Clase abstracta padre de todos los repositorios. Centraliza el
 * acceso a mysqli y obliga al uso de SENTENCIAS PREPARADAS para
 * toda consulta: los valores viajan por parámetros vinculados y
 * NUNCA se concatenan en el SQL (prevención de inyección SQL).
 */

abstract class RepositorioBase
{
    protected mysqli $db;

    public function __construct()
    {
        $this->db = Database::obtener();
    }

    /**
     * Ejecuta una consulta preparada y devuelve el statement.
     * $tipos es la cadena 'is d...' de bind_param; $params los valores.
     */
    protected function ejecutar(string $sql, string $tipos = '', array $params = []): mysqli_stmt
    {
        $sentencia = $this->db->prepare($sql);
        if ($sentencia === false) {
            throw new RuntimeException('Error al preparar la consulta.');
        }
        if ($tipos !== '') {
            $sentencia->bind_param($tipos, ...$params);
        }
        $sentencia->execute();
        return $sentencia;
    }

    /** Devuelve todas las filas como arreglos asociativos. */
    protected function filas(string $sql, string $tipos = '', array $params = []): array
    {
        $st = $this->ejecutar($sql, $tipos, $params);
        $resultado = $st->get_result();
        $datos = $resultado->fetch_all(MYSQLI_ASSOC);
        $resultado->free();
        $st->close();
        return $datos;
    }

    /** Devuelve la primera fila o null. */
    protected function fila(string $sql, string $tipos = '', array $params = []): ?array
    {
        return $this->filas($sql, $tipos, $params)[0] ?? null;
    }

    /** Devuelve un único valor escalar (COUNT, MAX...). */
    protected function escalar(string $sql, string $tipos = '', array $params = []): mixed
    {
        $fila = $this->fila($sql, $tipos, $params);
        return $fila === null ? null : reset($fila);
    }

    /** INSERT/UPDATE/DELETE; devuelve affected_rows (y setea insert_id). */
    protected function modificar(string $sql, string $tipos = '', array $params = [], ?int &$insertId = null): int
    {
        $st = $this->ejecutar($sql, $tipos, $params);
        $afectadas = $st->affected_rows;
        if ($insertId !== null) {
            $insertId = $st->insert_id;
        }
        $st->close();
        return $afectadas;
    }
}
