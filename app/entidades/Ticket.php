<?php
/**
 * Ticket — Entidad del dominio: incidencia técnica de la mesa de ayuda.
 * Ciclo de vida: pendiente → en_proceso → resuelto.
 */

class Ticket implements JsonSerializable
{
    public function __construct(
        private int $id = 0,
        private string $titulo = '',
        private string $descripcion = '',
        private int $solicitanteId = 0,
        private string $solicitanteNombre = '',
        private ?int $tecnicoId = null,
        private string $tecnicoNombre = '',
        private ?int $equipoId = null,
        private string $equipoCodigo = '',
        private int $prioridadId = 2,
        private string $prioridadNombre = 'media',
        private int $estadoId = 1,
        private string $estadoNombre = 'pendiente',
        private ?string $fechaCreacion = null,
        private ?string $fechaResolucion = null,
    ) {}

    public function id(): int { return $this->id; }
    public function setId(int $i): void { $this->id = $i; }

    public function titulo(): string { return $this->titulo; }
    public function setTitulo(string $t): void { $this->titulo = $t; }

    public function descripcion(): string { return $this->descripcion; }
    public function setDescripcion(string $d): void { $this->descripcion = $d; }

    public function solicitanteId(): int { return $this->solicitanteId; }
    public function setSolicitanteId(int $s): void { $this->solicitanteId = $s; }
    public function solicitanteNombre(): string { return $this->solicitanteNombre; }
    public function setSolicitanteNombre(string $s): void { $this->solicitanteNombre = $s; }

    public function tecnicoId(): ?int { return $this->tecnicoId; }
    public function setTecnicoId(?int $t): void { $this->tecnicoId = $t; }
    public function tecnicoNombre(): string { return $this->tecnicoNombre; }
    public function setTecnicoNombre(string $t): void { $this->tecnicoNombre = $t; }

    public function equipoId(): ?int { return $this->equipoId; }
    public function setEquipoId(?int $e): void { $this->equipoId = $e; }
    public function equipoCodigo(): string { return $this->equipoCodigo; }
    public function setEquipoCodigo(string $e): void { $this->equipoCodigo = $e; }

    public function prioridadId(): int { return $this->prioridadId; }
    public function setPrioridadId(int $p): void { $this->prioridadId = $p; }
    public function prioridadNombre(): string { return $this->prioridadNombre; }
    public function setPrioridadNombre(string $p): void { $this->prioridadNombre = $p; }

    public function estadoId(): int { return $this->estadoId; }
    public function setEstadoId(int $e): void { $this->estadoId = $e; }
    public function estadoNombre(): string { return $this->estadoNombre; }
    public function setEstadoNombre(string $e): void { $this->estadoNombre = $e; }

    public function fechaCreacion(): ?string { return $this->fechaCreacion; }
    public function setFechaCreacion(?string $f): void { $this->fechaCreacion = $f; }

    public function fechaResolucion(): ?string { return $this->fechaResolucion; }
    public function setFechaResolucion(?string $f): void { $this->fechaResolucion = $f; }

    public function estaAbierto(): bool
    {
        return $this->estadoNombre !== 'resuelto';
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id, 'titulo' => $this->titulo, 'estado' => $this->estadoNombre,
            'prioridad' => $this->prioridadNombre, 'tecnico' => $this->tecnicoNombre,
        ];
    }
}
