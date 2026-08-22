<?php
/**
 * PrestamoServicio — Lógica de negocio de préstamos y devoluciones.
 *
 * Reglas implementadas:
 *  - Solo el personal de soporte registra préstamos/devoluciones.
 *  - El equipo debe estar "disponible" y sin préstamo activo
 *    (disponibilidad en tiempo real, requerimiento C).
 *  - La fecha de devolución esperada no puede ser anterior a hoy.
 *  - Al registrar el préstamo el equipo pasa a estado "prestado";
 *    al devolverse vuelve a "disponible".
 */

class PrestamoServicio
{
    public function __construct(
        private PrestamoRepositorio $repositorio = new PrestamoRepositorio(),
        private EquipoServicio $equipos = new EquipoServicio(),
        private CatalogoRepositorio $catalogos = new CatalogoRepositorio(),
    ) {}

    public function registrar(array $datos): int
    {
        if (!Auth::esSoporte()) {
            throw new AccesoDenegadoException('Solo el personal de soporte puede registrar préstamos.');
        }

        $solicitantes = $this->catalogos->usuariosSolicitantes();
        $v = new Validador();
        $v->enteroPositivo('equipo', $datos['equipo'] ?? 0, 'Debe seleccionar un equipo.');
        $v->enteroPositivo('solicitante', $datos['solicitante'] ?? 0, 'Debe seleccionar el usuario que retira.');
        if (isset($datos['solicitante']) && !isset($solicitantes[(int)$datos['solicitante']])) {
            $v->agregarError('solicitante', 'Usuario inválido.');
        }
        $v->requerido('fecha_devolucion', $datos['fecha_devolucion'] ?? '', 'La fecha de devolución esperada es obligatoria.');
        $v->fecha('fecha_devolucion', $datos['fecha_devolucion'] ?? '', 'Fecha inválida.');

        $fechaDev = $datos['fecha_devolucion'] ?? '';
        if ($fechaDev !== '' && $fechaDev < date('Y-m-d')) {
            $v->agregarError('fecha_devolucion', 'La fecha de devolución no puede ser anterior a hoy.');
        }
        if ($v->hayErrores()) {
            throw new ValidacionException($v->errores());
        }

        $equipoId = (int)$datos['equipo'];
        $equipo   = $this->equipos->obtenerPorId($equipoId);

        // Regla de negocio: disponibilidad en tiempo real
        if (!$equipo->estaDisponible()) {
            throw new ValidacionException(['equipo' => 'El equipo no está disponible para préstamo.']);
        }
        if ($this->repositorio->tieneActivo($equipoId)) {
            throw new ValidacionException(['equipo' => 'Ese equipo ya tiene un préstamo activo.']);
        }

        $p = new Prestamo();
        $p->setEquipoId($equipoId);
        $p->setSolicitanteId((int)$datos['solicitante']);
        $p->setRegistraId((int)Sesion::id());
        $p->setFechaDevolucionEsperada($fechaDev);
        $p->setObservaciones(Validador::texto($datos['observaciones'] ?? ''));

        $estadoActivo = array_search('activo', $this->catalogos->estadosPrestamo(), true);
        $p->setEstadoId($estadoActivo === false ? 1 : (int)$estadoActivo);

        $id = $this->repositorio->insertar($p);

        // Actualiza disponibilidad del equipo + trazabilidad
        $this->equipos->asignar(
            $equipoId,
            (int)$datos['solicitante'],
            date('Y-m-d'),
            'Préstamo #' . $id . ' — devolver: ' . $fechaDev
        );

        return $id;
    }

    /** Devolución: cierra el préstamo y libera el equipo. */
    public function devolver(int $prestamoId, string $observaciones = ''): void
    {
        if (!Auth::esSoporte()) {
            throw new AccesoDenegadoException('Solo el personal de soporte puede registrar devoluciones.');
        }

        $p = $this->obtenerPorId($prestamoId);
        if ($p->estadoNombre() !== 'activo') {
            throw new ValidacionException(['general' => 'Ese préstamo ya fue devuelto.']);
        }

        $this->repositorio->registrarDevolucion($prestamoId, Validador::texto($observaciones));
        $this->equipos->devolver($p->equipoId());
    }

    public function obtenerPorId(int $id): Prestamo
    {
        $p = $this->repositorio->buscarPorId($id);
        if (!$p) {
            http_response_code(404);
            throw new NoEncontradoException('El préstamo solicitado no existe.');
        }
        return $p;
    }

    /** Lista aplicando visibilidad según rol. */
    public function listarParaUsuarioActual(array $filtros = []): array
    {
        $soloPropios = Auth::esSolicitante() ? (int)Sesion::id() : 0;
        return $this->repositorio->listar($filtros, $soloPropios);
    }

    public function activos(): int
    {
        return $this->repositorio->contarActivos();
    }
}
