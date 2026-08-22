<?php
/**
 * PrestamoRepositorio — Acceso a datos de préstamos de equipos.
 */

class PrestamoRepositorio extends RepositorioBase
{
    private const SELECT_BASE =
        'SELECT p.*,
                e.nombre AS equipo_nombre, e.codigo AS equipo_codigo,
                CONCAT(s.nombre," ",s.apellido) AS solicitante_nombre,
                CONCAT(r.nombre," ",r.apellido) AS registra_nombre,
                ep.nombre AS estado_nombre
         FROM prestamos p
         JOIN equipos e          ON e.id  = p.equipo_id
         JOIN usuarios s         ON s.id  = p.usuario_solicitante_id
         JOIN usuarios r         ON r.id  = p.usuario_registra_id
         JOIN estados_prestamo ep ON ep.id = p.estado_id';

    public function buscarPorId(int $id): ?Prestamo
    {
        $fila = $this->fila(self::SELECT_BASE . ' WHERE p.id = ?', 'i', [$id]);
        return $fila ? $this->mapear($fila) : null;
    }

    public function listar(array $filtros = [], int $soloUsuarioId = 0): array
    {
        $sql = self::SELECT_BASE . ' WHERE 1=1';
        $tipos = '';
        $params = [];

        if ($soloUsuarioId > 0) {
            $sql .= ' AND p.usuario_solicitante_id = ?';
            $tipos .= 'i';
            $params[] = $soloUsuarioId;
        }
        if (!empty($filtros['estado'])) {
            $sql .= ' AND p.estado_id = ?';
            $tipos .= 'i';
            $params[] = (int)$filtros['estado'];
        }
        if (!empty($filtros['q'])) {
            $sql .= ' AND (e.codigo LIKE ? OR e.nombre LIKE ? OR s.nombre LIKE ? OR s.apellido LIKE ?)';
            $tipos .= 'ssss';
            $like = '%' . $filtros['q'] . '%';
            foreach ([$like, $like, $like, $like] as $l) {
                $params[] = $l;
            }
        }
        // Activos primero, vencidos arriba; luego por fecha descendente
        $sql .= " ORDER BY FIELD(ep.nombre,'activo','devuelto'), p.fecha_devolucion_esperada ASC";

        return array_map([$this, 'mapear'], $this->filas($sql, $tipos, $params));
    }

    public function insertar(Prestamo $p): int
    {
        $id = 0;
        $this->modificar(
            'INSERT INTO prestamos (equipo_id, usuario_solicitante_id, usuario_registra_id,
                                    fecha_prestamo, fecha_devolucion_esperada, estado_id, observaciones)
             VALUES (?, ?, ?, NOW(), ?, ?, ?)',
            'iiisis',
            [$p->equipoId(), $p->solicitanteId(), $p->registraId(),
             $p->fechaDevolucionEsperada(), $p->estadoId(), $p->observaciones() ?: null],
            $id
        );
        return $id;
    }

    /** Registra la devolución real y marca el préstamo como devuelto. */
    public function registrarDevolucion(int $prestamoId, string $obs = ''): void
    {
        $sql = 'UPDATE prestamos
                SET fecha_devolucion_real = NOW(),
                    estado_id = (SELECT id FROM estados_prestamo WHERE nombre = \'devuelto\'),
                    observaciones = NULLIF(CONCAT_WS(" | ", observaciones, ?), "")
                WHERE id = ? AND fecha_devolucion_real IS NULL';
        $this->modificar($sql, 'si', [$obs ?: null, $prestamoId]);
    }

    /** Préstamos activos de un equipo (para impedir doble préstamo). */
    public function tieneActivo(int $equipoId): bool
    {
        return (bool)$this->escalar(
            "SELECT COUNT(*) FROM prestamos p
             JOIN estados_prestamo ep ON ep.id = p.estado_id
             WHERE p.equipo_id = ? AND ep.nombre = 'activo'",
            'i', [$equipoId]
        );
    }

    public function contarActivos(): int
    {
        return (int)$this->escalar(
            "SELECT COUNT(*) FROM prestamos p
             JOIN estados_prestamo ep ON ep.id = p.estado_id
             WHERE ep.nombre = 'activo'"
        );
    }

    private function mapear(array $f): Prestamo
    {
        $p = new Prestamo();
        $p->setId((int)$f['id']);
        $p->setEquipoId((int)$f['equipo_id']);
        $p->setEquipoNombre($f['equipo_nombre']);
        $p->setEquipoCodigo($f['equipo_codigo']);
        $p->setSolicitanteId((int)$f['usuario_solicitante_id']);
        $p->setSolicitanteNombre($f['solicitante_nombre']);
        $p->setRegistraId((int)$f['usuario_registra_id']);
        $p->setFechaPrestamo($f['fecha_prestamo']);
        $p->setFechaDevolucionEsperada((string)$f['fecha_devolucion_esperada']);
        $p->setFechaDevolucionReal($f['fecha_devolucion_real']);
        $p->setEstadoId((int)$f['estado_id']);
        $p->setEstadoNombre($f['estado_nombre']);
        $p->setObservaciones($f['observaciones'] ?? '');
        return $p;
    }
}
