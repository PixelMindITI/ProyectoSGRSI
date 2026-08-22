<?php
/**
 * TicketRepositorio — Acceso a datos de tickets e intervenciones.
 */

class TicketRepositorio extends RepositorioBase
{
    private const SELECT_BASE =
        'SELECT t.*,
                CONCAT(s.nombre," ",s.apellido) AS solicitante_nombre,
                CONCAT(COALESCE(te.nombre,"")," ",COALESCE(te.apellido,"")) AS tecnico_nombre,
                e.codigo AS equipo_codigo,
                p.nombre AS prioridad_nombre,
                et.nombre AS estado_nombre
         FROM tickets t
         JOIN usuarios s        ON s.id  = t.usuario_solicitante_id
         LEFT JOIN usuarios te  ON te.id = t.tecnico_asignado_id
         LEFT JOIN equipos e    ON e.id  = t.equipo_id
         JOIN prioridades p     ON p.id  = t.prioridad_id
         JOIN estados_ticket et ON et.id = t.estado_id';

    public function buscarPorId(int $id): ?Ticket
    {
        $fila = $this->fila(self::SELECT_BASE . ' WHERE t.id = ?', 'i', [$id]);
        return $fila ? $this->mapear($fila) : null;
    }

    /**
     * Lista tickets. Si $soloUsuarioId > 0 filtra por creador
     * (un solicitante solo ve sus propios tickets).
     */
    public function listar(array $filtros = [], int $soloUsuarioId = 0): array
    {
        $sql = self::SELECT_BASE . ' WHERE 1=1';
        $tipos = '';
        $params = [];

        if ($soloUsuarioId > 0) {
            $sql .= ' AND t.usuario_solicitante_id = ?';
            $tipos .= 'i';
            $params[] = $soloUsuarioId;
        }
        if (!empty($filtros['estado'])) {
            $sql .= ' AND t.estado_id = ?';
            $tipos .= 'i';
            $params[] = (int)$filtros['estado'];
        }
        if (!empty($filtros['tecnico'])) {
            $sql .= ' AND t.tecnico_asignado_id = ?';
            $tipos .= 'i';
            $params[] = (int)$filtros['tecnico'];
        }
        if (!empty($filtros['q'])) {
            $sql .= ' AND (t.titulo LIKE ? OR t.descripcion LIKE ?)';
            $tipos .= 'ss';
            $like = '%' . $filtros['q'] . '%';
            array_push($params, $like, $like);
        }
        $sql .= ' ORDER BY FIELD(et.nombre, "pendiente", "en_proceso", "resuelto"), t.fecha_creacion DESC';

        return array_map([$this, 'mapear'], $this->filas($sql, $tipos, $params));
    }

    public function insertar(Ticket $t): int
    {
        $id = 0;
        $this->modificar(
            'INSERT INTO tickets (titulo, descripcion, usuario_solicitante_id, tecnico_asignado_id,
                                  equipo_id, prioridad_id, estado_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            'ssiiiii',
            [$t->titulo(), $t->descripcion(), $t->solicitanteId(),
             $t->tecnicoId(), $t->equipoId(), $t->prioridadId(), $t->estadoId()],
            $id
        );
        return $id;
    }

    /** Asignación de técnico y cambio de estado. */
    public function asignar(int $ticketId, ?int $tecnicoId, int $estadoId): void
    {
        $this->modificar(
            'UPDATE tickets SET tecnico_asignado_id = ?, estado_id = ? WHERE id = ?',
            'iii', [$tecnicoId, $estadoId, $ticketId]
        );
    }

    /** Cierra el ticket marcando fecha de resolución. */
    public function resolver(int $ticketId): void
    {
        $this->modificar(
            "UPDATE tickets SET estado_id = (SELECT id FROM estados_ticket WHERE nombre='resuelto'),
                    fecha_resolucion = NOW()
             WHERE id = ?",
            'i', [$ticketId]
        );
    }

    /* ---------------- Intervenciones ---------------- */

    public function agregarIntervencion(int $ticketId, int $tecnicoId, string $diagnostico, bool $esResolucion = false): void
    {
        $this->modificar(
            'INSERT INTO intervenciones_ticket (ticket_id, tecnico_id, diagnostico, es_resolucion)
             VALUES (?, ?, ?, ?)',
            'iisi', [$ticketId, $tecnicoId, $diagnostico, (int)$esResolucion]
        );
    }

    public function intervenciones(int $ticketId): array
    {
        return $this->filas(
            'SELECT i.*, CONCAT(u.nombre," ",u.apellido) AS tecnico_nombre
             FROM intervenciones_ticket i
             JOIN usuarios u ON u.id = i.tecnico_id
             WHERE i.ticket_id = ?
             ORDER BY i.fecha ASC',
            'i', [$ticketId]
        );
    }

    /** Métricas para dashboard. */
    public function contarPorEstado(): array
    {
        return $this->filas(
            'SELECT et.nombre AS estado, COUNT(t.id) AS total
             FROM estados_ticket et
             LEFT JOIN tickets t ON t.estado_id = et.id
             GROUP BY et.id, et.nombre'
        );
    }

    public function promedioResolucionDias(): ?float
    {
        $v = $this->escalar('SELECT AVG(TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_resolucion)) FROM tickets WHERE fecha_resolucion IS NOT NULL');
        return $v === null ? null : round((float)$v / 24, 1);
    }

    private function mapear(array $f): Ticket
    {
        return new Ticket(
            (int)$f['id'], $f['titulo'], $f['descripcion'],
            (int)$f['usuario_solicitante_id'], $f['solicitante_nombre'],
            isset($f['tecnico_asignado_id']) && $f['tecnico_asignado_id'] !== null ? (int)$f['tecnico_asignado_id'] : null,
            trim($f['tecnico_nombre']),
            isset($f['equipo_id']) && $f['equipo_id'] !== null ? (int)$f['equipo_id'] : null,
            $f['equipo_codigo'] ?? '',
            (int)$f['prioridad_id'], $f['prioridad_nombre'],
            (int)$f['estado_id'], $f['estado_nombre'],
            $f['fecha_creacion'], $f['fecha_resolucion']
        );
    }
}
