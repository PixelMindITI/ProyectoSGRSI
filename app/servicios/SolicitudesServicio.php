<?php
/**
 * SolicitudesServicio — Lógica de negocio de solicitudes de servicio
 * (preparación de laboratorios, instalación de software, configuraciones).
 *
 * Reglas implementadas:
 *  - Cualquier usuario autenticado crea solicitudes (requerimiento F).
 *  - Un solicitante solo ve las suyas.
 *  - El personal de soporte atiende: en_proceso / completada / rechazada,
 *    siempre dejando una respuesta documentada.
 *  - La fecha de necesidad no puede ser anterior a hoy al crear.
 */

class SolicitudesServicio
{
    public function __construct(
        private SolicitudRepositorio $repositorio = new SolicitudRepositorio(),
        private CatalogoRepositorio $catalogos = new CatalogoRepositorio(),
    ) {}

    public function crear(array $datos): int
    {
        $tipos = array_keys($this->catalogos->tiposSolicitud());

        $v = new Validador();
        $v->requerido('titulo', $datos['titulo'] ?? '', 'El título es obligatorio.');
        $v->largoMax('titulo', $datos['titulo'] ?? '', 150, 'Máximo 150 caracteres.');
        $v->textoSensible('titulo', $datos['titulo'] ?? '', 'El título parece texto sin sentido; escriba un resumen con palabras reales.');
        $v->requerido('descripcion', $datos['descripcion'] ?? '', 'La descripción es obligatoria.');
        $v->textoSensible('descripcion', $datos['descripcion'] ?? '', 'La descripción parece texto sin sentido; describa lo que necesita con claridad.');
        $v->enteroPositivo('tipo', $datos['tipo'] ?? 0, 'Debe seleccionar el tipo de solicitud.');
        if (!empty($datos['tipo']) && !in_array((int)$datos['tipo'], $tipos, true)) {
            $v->agregarError('tipo', 'Tipo de solicitud inválido.');
        }
        $v->requerido('fecha_necesidad', $datos['fecha_necesidad'] ?? '', 'Indique para qué fecha la necesita.');
        $v->fecha('fecha_necesidad', $datos['fecha_necesidad'] ?? '', 'Fecha inválida.');

        $fechaNec = $datos['fecha_necesidad'] ?? '';
        if ($fechaNec !== '' && $fechaNec < date('Y-m-d')) {
            $v->agregarError('fecha_necesidad', 'La fecha de necesidad no puede ser anterior a hoy.');
        }
        if ($v->hayErrores()) {
            throw new ValidacionException($v->errores());
        }

        $s = new Solicitud();
        $s->setSolicitanteId((int)Sesion::id());
        $s->setTipoId((int)$datos['tipo']);
        $s->setTitulo(Validador::texto($datos['titulo']));
        $s->setDescripcion(Validador::texto($datos['descripcion']));
        $s->setLaboratorio(Validador::texto($datos['laboratorio'] ?? ''));
        $s->setFechaNecesidad($fechaNec);
        $s->setEstadoId(1); // pendiente

        return $this->repositorio->insertar($s);
    }

    /** Atención por parte del personal de soporte. */
    public function atender(int $solicitudId, array $datos): void
    {
        if (!Auth::esSoporte()) {
            throw new AccesoDenegadoException('Solo el personal de soporte puede atender solicitudes.');
        }

        $estados = $this->catalogos->estadosSolicitud();
        $estadoId = (int)($datos['estado'] ?? 0);
        $respuesta = Validador::texto($datos['respuesta'] ?? '');

        $v = new Validador();
        $v->textoSensible('respuesta', $respuesta, 'La respuesta parece texto sin sentido; escriba la respuesta con claridad.');
        if (!isset($estados[$estadoId]) || $estadoId === 1) {
            $v->agregarError('estado', 'Seleccione un estado válido de atención.');
        }
        $v->requerido('respuesta', $respuesta, 'Debe dejar una respuesta/nota para el solicitante.');
        if ($v->hayErrores()) {
            throw new ValidacionException($v->errores());
        }

        $s = $this->obtenerPorId($solicitudId);
        if (in_array($s->estadoNombre(), ['completada', 'rechazada'], true)) {
            throw new ValidacionException(['general' => 'Esa solicitud ya fue cerrada.']);
        }

        $this->repositorio->atender($solicitudId, $estadoId, $respuesta, (int)Sesion::id());
    }

    public function obtenerPorId(int $id): Solicitud
    {
        $s = $this->repositorio->buscarPorId($id);
        if (!$s) {
            http_response_code(404);
            throw new NoEncontradoException('La solicitud solicitada no existe.');
        }
        return $s;
    }

    public function listarParaUsuarioActual(array $filtros = []): array
    {
        $soloPropios = Auth::esSolicitante() ? (int)Sesion::id() : 0;
        return $this->repositorio->listar($filtros, $soloPropios);
    }

    public function puedeVer(Solicitud $s): bool
    {
        return Auth::esSoporte() || $s->solicitanteId() === Sesion::id();
    }

    /**
     * Borrado lógico de una solicitud: el administrador puede eliminar cualquier
     * solicitud y el solicitante únicamente las propias. El historial se conserva.
     */
    public function eliminar(int $id): void
    {
        $solicitud = $this->obtenerPorId($id);
        if (!Auth::esAdmin() && $solicitud->solicitanteId() !== (int)Sesion::id()) {
            throw new AccesoDenegadoException('No tiene permisos para eliminar esta solicitud.');
        }
        $this->repositorio->eliminar($id);
    }
}
