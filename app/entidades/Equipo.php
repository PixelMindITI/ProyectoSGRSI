<?php
/**
 * Equipo — Entidad del dominio: un activo tecnológico del inventario.
 */

class Equipo implements JsonSerializable
{
    public function __construct(
        private int $id = 0,
        private string $codigo = '',
        private string $nombre = '',
        private int $tipoId = 0,
        private string $tipoNombre = '',
        private string $marca = '',
        private string $modelo = '',
        private string $numeroSerie = '',
        private int $estadoId = 0,
        private string $estadoNombre = 'disponible',
        private string $ubicacion = '',
        private ?string $fechaAdquisicion = null,
        private ?string $observaciones = null,
    ) {}

    public function id(): int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function codigo(): string { return $this->codigo; }
    public function setCodigo(string $c): void { $this->codigo = $c; }

    public function nombre(): string { return $this->nombre; }
    public function setNombre(string $n): void { $this->nombre = $n; }

    public function tipoId(): int { return $this->tipoId; }
    public function setTipoId(int $t): void { $this->tipoId = $t; }
    public function tipoNombre(): string { return $this->tipoNombre; }
    public function setTipoNombre(string $t): void { $this->tipoNombre = $t; }

    public function marca(): string { return $this->marca; }
    public function setMarca(string $m): void { $this->marca = $m; }

    public function modelo(): string { return $this->modelo; }
    public function setModelo(string $m): void { $this->modelo = $m; }

    public function numeroSerie(): string { return $this->numeroSerie; }
    public function setNumeroSerie(string $s): void { $this->numeroSerie = $s; }

    public function estadoId(): int { return $this->estadoId; }
    public function setEstadoId(int $e): void { $this->estadoId = $e; }
    public function estadoNombre(): string { return $this->estadoNombre; }
    public function setEstadoNombre(string $e): void { $this->estadoNombre = $e; }

    public function ubicacion(): string { return $this->ubicacion; }
    public function setUbicacion(string $u): void { $this->ubicacion = $u; }

    public function fechaAdquisicion(): ?string { return $this->fechaAdquisicion; }
    public function setFechaAdquisicion(?string $f): void { $this->fechaAdquisicion = $f; }

    public function observaciones(): ?string { return $this->observaciones; }
    public function setObservaciones(?string $o): void { $this->observaciones = $o; }

    public function estaDisponible(): bool
    {
        return $this->estadoNombre === 'disponible';
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id, 'codigo' => $this->codigo, 'nombre' => $this->nombre,
            'tipo' => $this->tipoNombre, 'marca' => $this->marca, 'modelo' => $this->modelo,
            'estado' => $this->estadoNombre, 'ubicacion' => $this->ubicacion,
        ];
    }
}
