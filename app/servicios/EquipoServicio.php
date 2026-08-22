<?php
/**
 * EquipoServicio — Lógica de negocio del inventario tecnológico.
 *
 * Reglas implementadas:
 *  - Código de inventario y número de serie únicos.
 *  - Al ASIGNAR un equipo se registra el movimiento en el historial
 *    (trazabilidad, requerimiento B) y cambia su estado.
 *  - No se puede asignar un equipo que no esté disponible.
 *  - La baja física no existe: los equipos pasan a estado "baja"
 *    conservando todo su historial (auditoría).
 */

class EquipoServicio
{
    public function __construct(
        private EquipoRepositorio $repositorio = new EquipoRepositorio(),
        private CatalogoRepositorio $catalogos = new CatalogoRepositorio(),
    ) {}

    public function alta(array $datos): int
    {
        $errores = $this->validar($datos);
        if ($errores) {
            throw new ValidacionException($errores);
        }

        $e = $this->hidratar(new Equipo(), $datos);
        if ($this->repositorio->codigoExiste($e->codigo())) {
            throw new ValidacionException(['codigo' => 'Ese código de inventario ya existe.']);
        }
        if ($this->repositorio->serieExiste($e->numeroSerie())) {
            throw new ValidacionException(['numero_serie' => 'Ese número de serie ya fue registrado.']);
        }

        // Estado inicial: disponible (primer id del catálogo)
        $estados = $this->catalogos->estadosEquipo();
        $disponibleId = array_search('disponible', $estados, true) ?: array_key_first($estados);
        $e->setEstadoId((int)$disponibleId);

        return $this->repositorio->insertar($e);
    }

    public function editar(int $id, array $datos): void
    {
        $errores = $this->validar($datos);
        if ($errores) {
            throw new ValidacionException($errores);
        }

        $e = $this->obtenerPorId($id);
        if ($this->repositorio->codigoExiste(Validador::texto($datos['codigo']), $id)) {
            throw new ValidacionException(['codigo' => 'Ese código de inventario ya existe en otro equipo.']);
        }
        if ($this->repositorio->serieExiste(Validador::texto($datos['numero_serie']), $id)) {
            throw new ValidacionException(['numero_serie' => 'Ese número de serie pertenece a otro equipo.']);
        }

        $estadoAnterior = $e->estadoNombre();
        $this->hidratar($e, $datos);
        $e->setEstadoId((int)$datos['estado']);

        $this->repositorio->actualizar($e);

        // Si pasó a mantenimiento/baja se cierra cualquier asignación abierta
        if (in_array($e->estadoNombre(), ['en_mantenimiento', 'baja'], true)
            && in_array($estadoAnterior, ['disponible', 'prestado'], true)) {
            $this->repositorio->cerrarAsignacionActiva($id);
        }
    }

    /** Asignación con trazabilidad (requerimiento B). */
    public function asignar(int $equipoId, int $usuarioDestino, string $fecha, string $observaciones): void
    {
        if (!Auth::esSoporte()) {
            throw new AccesoDenegadoException('Solo el personal de soporte puede asignar equipos.');
        }

        $v = new Validador();
        $v->enteroPositivo('usuario', $usuarioDestino, 'Debe seleccionar el usuario responsable.');
        $v->requerido('fecha', $fecha, 'La fecha es obligatoria.');
        $v->fecha('fecha', $fecha, 'Fecha inválida.');
        if ($v->hayErrores()) {
            throw new ValidacionException($v->errores());
        }

        $e = $this->obtenerPorId($equipoId);
        if (!$e->estaDisponible()) {
            throw new ValidacionException(['usuario' => 'El equipo no está disponible (estado actual: ' . $e->estadoNombre() . ').']);
        }

        $estadoAsignado = $this->idEstadoEquipo('prestado');
        $e->setEstadoId($estadoAsignado);
        $this->repositorio->actualizar($e);
        $this->repositorio->registrarAsignacion(
            $equipoId,
            $usuarioDestino,
            (int)Sesion::id(),
            $fecha,
            Validador::texto($observaciones)
        );
    }

    /** Devolución de una asignación: vuelve a "disponible". */
    public function devolver(int $equipoId): void
    {
        if (!Auth::esSoporte()) {
            throw new AccesoDenegadoException('Solo el personal de soporte puede registrar devoluciones.');
        }
        $e = $this->obtenerPorId($equipoId);
        $this->repositorio->cerrarAsignacionActiva($equipoId);
        $e->setEstadoId($this->idEstadoEquipo('disponible'));
        $this->repositorio->actualizar($e);
    }

    public function obtenerPorId(int $id): Equipo
    {
        $e = $this->repositorio->buscarPorId($id);
        if (!$e) {
            http_response_code(404);
            throw new NoEncontradoException('El equipo solicitado no existe.');
        }
        return $e;
    }

    /** @return array<int,Equipo> */
    public function listar(array $filtros = []): array
    {
        return $this->repositorio->listar($filtros);
    }

    public function historial(int $equipoId): array
    {
        return $this->repositorio->historial($equipoId);
    }

    public function disponibles(): array
    {
        return $this->repositorio->porEstado('disponible');
    }

    /* ---------------- helpers ---------------- */

    private function idEstadoEquipo(string $nombre): int
    {
        $id = array_search($nombre, $this->catalogos->estadosEquipo(), true);
        return $id === false ? 1 : (int)$id;
    }

    private function hidratar(Equipo $e, array $datos): Equipo
    {
        $e->setCodigo(strtoupper(Validador::texto($datos['codigo'])));
        $e->setNombre(Validador::texto($datos['nombre']));
        $e->setTipoId((int)$datos['tipo']);
        $e->setMarca(Validador::texto($datos['marca']));
        $e->setModelo(Validador::texto($datos['modelo']));
        $e->setNumeroSerie(Validador::texto($datos['numero_serie']));
        $e->setUbicacion(Validador::texto($datos['ubicacion']));
        $e->setFechaAdquisicion(($datos['fecha_adquisicion'] ?? '') ?: null);
        $e->setObservaciones(Validador::texto($datos['observaciones'] ?? '') ?: null);
        return $e;
    }

    private function validar(array $datos): array
    {
        $v = new Validador();
        $tipos   = array_keys($this->catalogos->tiposEquipo());
        $estados = array_keys($this->catalogos->estadosEquipo());

        $v->requerido('codigo', $datos['codigo'] ?? '', 'El código de inventario es obligatorio.');
        $v->largoMax('codigo', $datos['codigo'] ?? '', 30, 'Máximo 30 caracteres.');
        $v->requerido('nombre', $datos['nombre'] ?? '', 'El nombre/descripción es obligatorio.');
        $v->largoMax('nombre', $datos['nombre'] ?? '', 100, 'Máximo 100 caracteres.');
        $v->requerido('marca', $datos['marca'] ?? '', 'La marca es obligatoria.');
        $v->requerido('modelo', $datos['modelo'] ?? '', 'El modelo es obligatorio.');
        $v->requerido('numero_serie', $datos['numero_serie'] ?? '', 'El número de serie es obligatorio.');
        $v->requerido('ubicacion', $datos['ubicacion'] ?? '', 'La ubicación es obligatoria.');

        $v->enteroPositivo('tipo', $datos['tipo'] ?? 0, 'Debe seleccionar el tipo de equipo.');
        if (isset($datos['tipo']) && !in_array((int)$datos['tipo'], $tipos, true)) {
            $v->agregarError('tipo', 'Tipo de equipo inválido.');
        }
        if (isset($datos['estado']) && $datos['estado'] !== ''
            && !in_array((int)$datos['estado'], $estados, true)) {
            $v->agregarError('estado', 'Estado inválido.');
        }
        if (!empty($datos['fecha_adquisicion'])) {
            $v->fecha('fecha_adquisicion', $datos['fecha_adquisicion'], 'Fecha de adquisición inválida.');
        }
        $v->largoMax('observaciones', $datos['observaciones'] ?? '', 65535, 'Observaciones demasiado largas.');

        return $v->errores();
    }
}
