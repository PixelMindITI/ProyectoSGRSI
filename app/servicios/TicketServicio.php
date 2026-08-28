<?php
/**
 * TicketServicio — Lógica de negocio de la mesa de ayuda.
 *
 * Reglas implementadas:
 *  - Cualquier usuario autenticado crea tickets.
 *  - Un solicitante SOLO ve y consulta sus propios tickets.
 *  - La asignación/cambio de estado/diagnóstico es tarea del personal
 *    de soporte (técnico o administrador).
 *  - Cada diagnóstico queda registrado como intervención: base de
 *    conocimiento para futuras fallas (requerimiento E).
 *  - Al resolver se cierra con fecha_resolucion (métricas).
 */

class TicketServicio
{
    public function __construct(
        private TicketRepositorio $repositorio = new TicketRepositorio(),
        private CatalogoRepositorio $catalogos = new CatalogoRepositorio(),
    ) {}

    public function crear(array $datos): int
    {
        $v = new Validador();
        $prioridades = array_keys($this->catalogos->prioridades());

        $v->requerido('titulo', $datos['titulo'] ?? '', 'El título es obligatorio.');
        $v->largoMax('titulo', $datos['titulo'] ?? '', 150, 'Máximo 150 caracteres.');
        $v->textoSensible('titulo', $datos['titulo'] ?? '', 'El título parece texto sin sentido; escriba un resumen con palabras reales.');
        $v->requerido('descripcion', $datos['descripcion'] ?? '', 'La descripción es obligatoria.');
        $v->textoSensible('descripcion', $datos['descripcion'] ?? '', 'La descripción parece texto sin sentido; describa el problema con claridad.');
        $v->enteroPositivo('prioridad', $datos['prioridad'] ?? 0, 'Debe seleccionar la prioridad.');
        if (!empty($datos['prioridad']) && !in_array((int)$datos['prioridad'], $prioridades, true)) {
            $v->agregarError('prioridad', 'Prioridad inválida.');
        }
        if ($v->hayErrores()) {
            throw new ValidacionException($v->errores());
        }

        $t = new Ticket();
        $t->setTitulo(Validador::texto($datos['titulo']));
        $t->setDescripcion(Validador::texto($datos['descripcion']));
        $t->setSolicitanteId((int)Sesion::id());
        $t->setPrioridadId((int)$datos['prioridad']);
        // El equipo asociado es opcional; si viene, debe existir.
        if (!empty($datos['equipo_id'])) {
            $equipos = new EquipoServicio();
            $t->setEquipoId($equipos->obtenerPorId((int)$datos['equipo_id'])->id());
        }
        return $this->repositorio->insertar($t);
    }

    /**
     * Asigna técnico y/o cambia estado. Si el nuevo estado es "resuelto"
     * exige nota de resolución y cierra el ticket.
     */
    public function gestionar(int $ticketId, array $datos): void
    {
        if (!Auth::esSoporte()) {
            throw new AccesoDenegadoException('Solo el personal de soporte puede gestionar tickets.');
        }

        $estados = $this->catalogos->estadosTicket();
        $tecnicos = $this->catalogos->usuariosPorRol($this->idRol('tecnico'));

        $estadoId = (int)($datos['estado'] ?? 0);
        $tecnicoId = !empty($datos['tecnico']) ? (int)$datos['tecnico'] : null;
        $nota = Validador::texto($datos['nota'] ?? '');

        $v = new Validador();
        $v->textoSensible('nota', $nota, 'La nota parece texto sin sentido; escriba la intervención con claridad.');
        if (!isset($estados[$estadoId])) {
            $v->agregarError('estado', 'Estado inválido.');
        }
        if ($tecnicoId !== null && !isset($tecnicos[$tecnicoId])) {
            $v->agregarError('tecnico', 'Técnico inválido.');
        }
        if ($v->hayErrores()) {
            throw new ValidacionException($v->errores());
        }

        $ticket = $this->obtenerPorId($ticketId);
        $esResolucion = ($estados[$estadoId] === 'resuelto');

        // Al resolver se exige una nota de resolución
        if ($esResolucion && $nota === '') {
            throw new ValidacionException(['nota' => 'Para resolver el ticket debe registrar la resolución.']);
        }
        // Si pasa a en_proceso sin técnico asignado, se asigna quien gestiona
        if ($estados[$estadoId] === 'en_proceso' && $tecnicoId === null && !$ticket->tecnicoId()) {
            $tecnicoId = (int)Sesion::id();
        }

        $this->repositorio->asignar($ticketId, $tecnicoId, $estadoId);

        if ($nota !== '') {
            $this->repositorio->agregarIntervencion(
                $ticketId,
                (int)(Sesion::id() ?? 0),
                $nota,
                $esResolucion
            );
        }
        if ($esResolucion) {
            $this->repositorio->resolver($ticketId);
        }
    }

    public function obtenerPorId(int $id): Ticket
    {
        $t = $this->repositorio->buscarPorId($id);
        if (!$t) {
            http_response_code(404);
            throw new NoEncontradoException('El ticket solicitado no existe.');
        }
        return $t;
    }

    /** Lista aplicando visibilidad según rol. */
    public function listarParaUsuarioActual(array $filtros = []): array
    {
        $soloPropios = Auth::esSolicitante() ? (int)Sesion::id() : 0;
        return $this->repositorio->listar($filtros, $soloPropios);
    }

    /** ¿El usuario actual puede ver este ticket? */
    public function puedeVer(Ticket $t): bool
    {
        return Auth::esSoporte() || $t->solicitanteId() === Sesion::id();
    }

    public function intervenciones(int $ticketId): array
    {
        return $this->repositorio->intervenciones($ticketId);
    }

    /**
     * Borrado lógico de un ticket: el administrador puede eliminar cualquier
     * ticket y el solicitante únicamente los propios. El historial se conserva.
     */
    public function eliminar(int $ticketId): void
    {
        $ticket = $this->obtenerPorId($ticketId);
        if (!Auth::esAdmin() && $ticket->solicitanteId() !== (int)Sesion::id()) {
            throw new AccesoDenegadoException('No tiene permisos para eliminar este ticket.');
        }
        $this->repositorio->eliminar($ticketId);
    }

    private function idRol(string $nombre): int
    {
        $id = array_search($nombre, $this->catalogos->roles(), true);
        return $id === false ? 0 : (int)$id;
    }
}
