<?php
/**
 * SolicitudRepositorio — Acceso a datos de solicitudes de servicio.
 */

class SolicitudRepositorio extends RepositorioBase
{
    private const SELECT_BASE =
        'SELECT ss.*,
                CONCAT(s.nombre," ",s.apellido) AS solicitante_nombre,
                ts.nombre AS tipo_nombre,
                es.nombre AS estado_nombre,
                CONCAT(COALESCE(a.nombre,"")," ",COALESCE(a.apellido,"")) AS atendida_por_nombre
         FROM solicitudes_servicio ss
         JOIN usuarios s           ON s.id  = ss.usuario_solicitante_id
         JOIN tipos_solicitud ts   ON ts.id = ss.tipo_id
         JOIN estados_solicitud es ON es.id = ss.estado_id
         LEFT JOIN usuarios a      ON a.id  = ss.atendida_por';

    public function buscarPorId(int $id): ?Solicitud
    {
        $fila = $this->fila(self::SELECT_BASE . ' WHERE ss.id = ?', 'i', [$id]);
        return $fila ? $this->mapear($fila) : null;
    }

    public function listar(array $filtros = [], int $soloUsuarioId = 0): array
    {
        $sql = self::SELECT_BASE . ' WHERE 1=1';
        $tipos = '';
        $params = [];

        if ($soloUsuarioId > 0) {
            $sql .= ' AND ss.usuario_solicitante_id = ?';
            $tipos .= 'i';
            $params[] = $soloUsuarioId;
        }
        if (!empty($filtros['estado'])) {
            $sql .= ' AND ss.estado_id = ?';
            $tipos .= 'i';
            $params[] = (int)$filtros['estado'];
        }
        if (!empty($filtros['tipo'])) {
            $sql .= ' AND ss.tipo_id = ?';
            $tipos .= 'i';
            $params[] = (int)$filtros['tipo'];
        }
        $sql .= " ORDER BY FIELD(es.nombre,'pendiente','en_proceso','completada','rechazada'), ss.fecha_necesidad ASC";

        return array_map([$this, 'mapear'], $this->filas($sql, $tipos, $params));
    }

    public function insertar(Solicitud $s): int
    {
        $id = 0;
        $this->modificar(
            'INSERT INTO solicitudes_servicio
                (usuario_solicitante_id, tipo_id, titulo, descripcion, laboratorio, fecha_necesidad, estado_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            'iissssi',
            [$s->solicitanteId(), $s->tipoId(), $s->titulo(), $s->descripcion(),
             $s->laboratorio() ?: null, $s->fechaNecesidad(), $s->estadoId()],
            $id
        );
        return $id;
    }

    /** El personal de soporte atiende: cambia estado y guarda respuesta. */
    public function atender(int $id, int $estadoId, string $respuesta, int $atendidaPor): void
    {
        $cerrar = in_array($estadoId, [3, 4], true); // completada | rechazada
        $sql = 'UPDATE solicitudes_servicio
                SET estado_id = ?, respuesta = ?, atendida_por = ?,
                    fecha_cierre = ' . ($cerrar ? 'NOW()' : 'fecha_cierre') . '
                WHERE id = ?';
        $this->modificar($sql, 'isii', [$estadoId, $respuesta ?: null, $atendidaPor, $id]);
    }

    public function contarPendientes(): int
    {
        return (int)$this->escalar(
            "SELECT COUNT(*) FROM solicitudes_servicio ss
             JOIN estados_solicitud es ON es.id = ss.estado_id
             WHERE es.nombre IN ('pendiente','en_proceso')"
        );
    }

    private function mapear(array $f): Solicitud
    {
        return new Solicitud(
            (int)$f['id'],
            (int)$f['usuario_solicitante_id'], $f['solicitante_nombre'],
            (int)$f['tipo_id'], $f['tipo_nombre'],
            $f['titulo'], $f['descripcion'],
            $f['laboratorio'] ?? '', $f['fecha_necesidad'],
            (int)$f['estado_id'], $f['estado_nombre'],
            $f['respuesta'] ?? '',
            isset($f['atendida_por']) && $f['atendida_por'] !== null ? (int)$f['atendida_por'] : null,
            trim($f['atendida_por_nombre']),
            $f['fecha_creacion'], $f['fecha_cierre']
        );
    }
}
