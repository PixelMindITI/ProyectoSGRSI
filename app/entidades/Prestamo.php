<?php
/**
 * Prestamo — Entidad del dominio: préstamo de un equipo a un usuario.
 * El alta del préstamo cambia el estado del equipo (disponibilidad en tiempo real).
 */

class Prestamo implements JsonSerializable
{
    public function __construct(
        private int $id = 0,
        private int $equipoId = 0,
        private string $equipoNombre = '',
        private string $equipoCodigo = '',
        private int $solicitanteId = 0,
        private string $solicitanteNombre = '',
        private int $registraId = 0,
        private string $registraNombre = '',
        private ?string $fechaPrestamo = null,
        private string $fechaDevolucionEsperada = '',
        private ?string $fechaDevolucionReal = null,
        private int $estadoId = 1,
        private string $estadoNombre = 'activo',
        private string $observaciones = '',
    ) {}

    public function id(): int { return $this->id; }
    public function setId(int $i): void { $this->id = $i; }

    public function equipoId(): int { return $this->equipoId; }
    public function setEquipoId(int $e): void { $this->equipoId = $e; }
    public function equipoNombre(): string { return $this->equipoNombre; }
    public function setEquipoNombre(string $e): void { $this->equipoNombre = $e; }
    public function equipoCodigo(): string { return $this->equipoCodigo; }
    public function setEquipoCodigo(string $c): void { $this->equipoCodigo = $c; }

    public function solicitanteId(): int { return $this->solicitanteId; }
    public function setSolicitanteId(int $s): void { $this->solicitanteId = $s; }
    public function solicitanteNombre(): string { return $this->solicitanteNombre; }
    public function setSolicitanteNombre(string $s): void { $this->solicitanteNombre = $s; }

    public function registraId(): int { return $this->registraId; }
    public function setRegistraId(int $r): void { $this->registraId = $r; }

    public function fechaPrestamo(): ?string { return $this->fechaPrestamo; }
    public function setFechaPrestamo(?string $f): void { $this->fechaPrestamo = $f; }

    public function fechaDevolucionEsperada(): string { return $this->fechaDevolucionEsperada; }
    public function setFechaDevolucionEsperada(string $f): void { $this->fechaDevolucionEsperada = $f; }

    public function fechaDevolucionReal(): ?string { return $this->fechaDevolucionReal; }
    public function setFechaDevolucionReal(?string $f): void { $this->fechaDevolucionReal = $f; }

    public function estadoId(): int { return $this->estadoId; }
    public function setEstadoId(int $e): void { $this->estadoId = $e; }
    public function estadoNombre(): string { return $this->estadoNombre; }
    public function setEstadoNombre(string $e): void { $this->estadoNombre = $e; }

    public function observaciones(): string { return $this->observaciones; }
    public function setObservaciones(string $o): void { $this->observaciones = $o; }

    /** ¿El préstamo venció y sigue activo? */
    public function estaVencido(): bool
    {
        return $this->estadoNombre === 'activo'
            && $this->fechaDevolucionEsperada < date('Y-m-d');
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id, 'equipo' => $this->equipoNombre, 'codigo' => $this->equipoCodigo,
            'solicitante' => $this->solicitanteNombre, 'estado' => $this->estadoNombre,
            'vence' => $this->fechaDevolucionEsperada,
        ];
    }
}
