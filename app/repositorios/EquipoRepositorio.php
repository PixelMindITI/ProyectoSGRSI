<?php
/**
 * EquipoRepositorio — Acceso a datos del inventario y su trazabilidad.
 */

class EquipoRepositorio extends RepositorioBase
{
    private const SELECT_BASE =
        'SELECT e.*, te.nombre AS tipo_nombre, ee.nombre AS estado_nombre
         FROM equipos e
         JOIN tipos_equipo te   ON te.id = e.tipo_id
         JOIN estados_equipo ee ON ee.id = e.estado_id';

    public function buscarPorId(int $id): ?Equipo
    {
        $fila = $this->fila(self::SELECT_BASE . ' WHERE e.id = ?', 'i', [$id]);
        return $fila ? $this->mapear($fila) : null;
    }

    /** @return array<int,Equipo> */
    public function listar(array $filtros = []): array
    {
        $sql  = self::SELECT_BASE . ' WHERE 1=1';
        $tipos = '';
        $params = [];

        if (!empty($filtros['estado'])) {
            $sql .= ' AND e.estado_id = ?';
            $tipos .= 'i';
            $params[] = (int)$filtros['estado'];
        }
        if (!empty($filtros['tipo'])) {
            $sql .= ' AND e.tipo_id = ?';
            $tipos .= 'i';
            $params[] = (int)$filtros['tipo'];
        }
        if (!empty($filtros['q'])) {
            $sql .= ' AND (e.codigo LIKE ? OR e.nombre LIKE ? OR e.numero_serie LIKE ?)';
            $tipos .= 'sss';
            $like = '%' . $filtros['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        $sql .= ' ORDER BY e.codigo';

        return array_map([$this, 'mapear'], $this->filas($sql, $tipos, $params));
    }

    /** Equipos con un estado dado (ej.: disponibles para prestar). */
    public function porEstado(string $nombreEstado): array
    {
        $filas = $this->filas(
            self::SELECT_BASE . ' WHERE ee.nombre = ? ORDER BY e.codigo',
            's', [$nombreEstado]
        );
        return array_map([$this, 'mapear'], $filas);
    }

    public function codigoExiste(string $codigo, int $exceptoId = 0): bool
    {
        return (bool)$this->escalar('SELECT COUNT(*) FROM equipos WHERE codigo = ? AND id <> ?', 'si', [$codigo, $exceptoId]);
    }

    public function serieExiste(string $serie, int $exceptoId = 0): bool
    {
        return (bool)$this->escalar('SELECT COUNT(*) FROM equipos WHERE numero_serie = ? AND id <> ?', 'si', [$serie, $exceptoId]);
    }

    public function insertar(Equipo $e): int
    {
        $id = 0;
        $this->modificar(
            'INSERT INTO equipos (codigo, nombre, tipo_id, marca, modelo, numero_serie, estado_id, ubicacion, fecha_adquisicion, observaciones)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'ssisssisss',
            [$e->codigo(), $e->nombre(), $e->tipoId(), $e->marca(), $e->modelo(),
             $e->numeroSerie(), $e->estadoId(), $e->ubicacion(),
             $e->fechaAdquisicion() ?: null, $e->observaciones() ?: null],
            $id
        );
        return $id;
    }

    public function actualizar(Equipo $e): void
    {
        $this->modificar(
            'UPDATE equipos SET codigo=?, nombre=?, tipo_id=?, marca=?, modelo=?, numero_serie=?,
                    estado_id=?, ubicacion=?, fecha_adquisicion=?, observaciones=?
             WHERE id=?',
            'ssisssisssi',
            [$e->codigo(), $e->nombre(), $e->tipoId(), $e->marca(), $e->modelo(),
             $e->numeroSerie(), $e->estadoId(), $e->ubicacion(),
             $e->fechaAdquisicion() ?: null, $e->observaciones() ?: null, $e->id()]
        );
    }

    /* ---------------- Trazabilidad ---------------- */

    public function registrarAsignacion(int $equipoId, int $usuarioId, int $registradoPor, string $fecha, string $obs = ''): int
    {
        $id = 0;
        $this->modificar(
            'INSERT INTO asignaciones (equipo_id, usuario_id, registrado_por, fecha_asignacion, fecha_devolucion, observaciones)
             VALUES (?, ?, ?, ?, NULL, ?)',
            'iiiss', [$equipoId, $usuarioId, $registradoPor, $fecha, $obs],
            $id
        );
        return $id;
    }

    public function cerrarAsignacionActiva(int $equipoId): void
    {
        $this->modificar(
            'UPDATE asignaciones SET fecha_devolucion = CURDATE()
             WHERE equipo_id = ? AND fecha_devolucion IS NULL',
            'i', [$equipoId]
        );
    }

    /** Historial completo de movimientos de un equipo. */
    public function historial(int $equipoId): array
    {
        return $this->filas(
            'SELECT a.*, CONCAT(u.nombre," ",u.apellido) AS usuario_nombre,
                    CONCAT(r.nombre," ",r.apellido) AS registrado_por_nombre
             FROM asignaciones a
             JOIN usuarios u ON u.id = a.usuario_id
             JOIN usuarios r ON r.id = a.registrado_por
             WHERE a.equipo_id = ?
             ORDER BY a.fecha_asignacion DESC',
            'i', [$equipoId]
        );
    }

    /** Equipos con más tickets: métrica para el dashboard. */
    public function rankingFallas(int $limite = 5): array
    {
        return $this->filas(
            'SELECT e.codigo, e.nombre, COUNT(t.id) AS total_tickets
             FROM equipos e JOIN tickets t ON t.equipo_id = e.id
             GROUP BY e.id, e.codigo, e.nombre
             ORDER BY total_tickets DESC LIMIT ?',
            'i', [$limite]
        );
    }

    private function mapear(array $f): Equipo
    {
        return new Equipo(
            (int)$f['id'], $f['codigo'], $f['nombre'], (int)$f['tipo_id'], $f['tipo_nombre'],
            $f['marca'], $f['modelo'], $f['numero_serie'], (int)$f['estado_id'], $f['estado_nombre'],
            $f['ubicacion'], $f['fecha_adquisicion'], $f['observaciones']
        );
    }
}
