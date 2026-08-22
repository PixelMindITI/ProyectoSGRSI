<?php
/**
 * MetricaServicio — Métricas para el dashboard de toma de decisiones
 * (requerimiento H de la letra): equipos por estado, tickets por estado,
 * préstamos activos, tiempos promedio de resolución, equipos más fallados.
 */

class MetricaServicio
{
    public function __construct(
        private TicketRepositorio $tickets = new TicketRepositorio(),
        private PrestamoRepositorio $prestamos = new PrestamoRepositorio(),
        private SolicitudRepositorio $solicitudes = new SolicitudRepositorio(),
        private EquipoRepositorio $equipos = new EquipoRepositorio(),
    ) {}

    /** Devuelve todas las métricas consolidadas en un arreglo. */
    public function resumen(): array
    {
        return [
            'equipos_por_estado'  => $this->contarEquiposPorEstado(),
            'tickets_por_estado'  => $this->indexar($this->tickets->contarPorEstado(), 'estado'),
            'prestamos_activos'   => $this->prestamos->contarActivos(),
            'solicitudes_pendientes' => $this->solicitudes->contarPendientes(),
            'promedio_resolucion_dias' => $this->tickets->promedioResolucionDias(),
            'equipos_mas_fallados' => $this->equipos->rankingFallas(5),
            'total_equipos'       => count($this->equipos->listar()),
        ];
    }

    private function contarEquiposPorEstado(): array
    {
        $conteo = [];
        foreach ($this->equipos->listar() as $equipo) {
            $estado = $equipo->estadoNombre();
            $conteo[$estado] = ($conteo[$estado] ?? 0) + 1;
        }
        return $conteo;
    }

    /** Convierte filas [estado=>x, total=>n] en mapa estado => total. */
    private function indexar(array $filas, string $clave): array
    {
        $mapa = [];
        foreach ($filas as $f) {
            $mapa[$f[$clave]] = (int)$f['total'];
        }
        return $mapa;
    }
}
