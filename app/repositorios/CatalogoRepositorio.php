<?php
/**
 * CatalogoRepositorio — Acceso a las tablas catálogo (roles, tipos,
 * estados). Devuelven id => nombre para poblar selects de formularios.
 */

class CatalogoRepositorio extends RepositorioBase
{
    public function roles(): array
    {
        return $this->pares('roles');
    }

    public function tiposEquipo(): array
    {
        return $this->pares('tipos_equipo');
    }

    public function estadosEquipo(): array
    {
        return $this->pares('estados_equipo');
    }

    public function prioridades(): array
    {
        return $this->pares('prioridades');
    }

    public function estadosTicket(): array
    {
        return $this->pares('estados_ticket');
    }

    public function estadosPrestamo(): array
    {
        return $this->pares('estados_prestamo');
    }

    public function tiposSolicitud(): array
    {
        return $this->pares('tipos_solicitud');
    }

    public function estadosSolicitud(): array
    {
        return $this->pares('estados_solicitud');
    }

    public function usuariosPorRol(int $rolId): array
    {
        $filas = $this->filas(
            'SELECT id, CONCAT(nombre, " ", apellido) AS nombre FROM usuarios WHERE rol_id = ? AND activo = 1 ORDER BY nombre',
            'i', [$rolId]
        );
        return array_column($filas, 'nombre', 'id');
    }

    public function usuariosSolicitantes(): array
    {
        $filas = $this->filas(
            'SELECT id, CONCAT(nombre, " ", apellido) AS nombre FROM usuarios WHERE activo = 1 ORDER BY nombre'
        );
        return array_column($filas, 'nombre', 'id');
    }

    private function pares(string $tabla): array
    {
        // $tabla proviene solo de llamadas internas (no del usuario),
        // por eso puede interpolarse sin riesgo.
        $filas = $this->filas("SELECT id, nombre FROM {$tabla} ORDER BY id");
        return array_column($filas, 'nombre', 'id');
    }
}
