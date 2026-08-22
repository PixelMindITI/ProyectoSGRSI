<?php
/**
 * Solicitud — Entidad del dominio: pedido de preparación de
 * laboratorio, instalación de software o configuración de equipos.
 */

class Solicitud implements JsonSerializable
{
    public function __construct(
        private int $id = 0,
        private int $solicitanteId = 0,
        private string $solicitanteNombre = '',
        private int $tipoId = 0,
        private string $tipoNombre = '',
        private string $titulo = '',
        private string $descripcion = '',
        private string $laboratorio = '',
        private ?string $fechaNecesidad = null,
        private int $estadoId = 1,
        private string $estadoNombre = 'pendiente',
        private string $respuesta = '',
        private ?int $atendidaPor = null,
        private string $atendidaPorNombre = '',
        private ?string $fechaCreacion = null,
        private ?string $fechaCierre = null,
    ) {}

    public function id(): int { return $this->id; }
    public function setId(int $i): void { $this->id = $i; }

    public function solicitanteId(): int { return $this->solicitanteId; }
    public function setSolicitanteId(int $s): void { $this->solicitanteId = $s; }
    public function solicitanteNombre(): string { return $this->solicitanteNombre; }
    public function setSolicitanteNombre(string $s): void { $this->solicitanteNombre = $s; }

    public function tipoId(): int { return $this->tipoId; }
    public function setTipoId(int $t): void { $this->tipoId = $t; }
    public function tipoNombre(): string { return $this->tipoNombre; }
    public function setTipoNombre(string $t): void { $this->tipoNombre = $t; }

    public function titulo(): string { return $this->titulo; }
    public function setTitulo(string $t): void { $this->titulo = $t; }

    public function descripcion(): string { return $this->descripcion; }
    public function setDescripcion(string $d): void { $this->descripcion = $d; }

    public function laboratorio(): string { return $this->laboratorio; }
    public function setLaboratorio(string $l): void { $this->laboratorio = $l; }

    public function fechaNecesidad(): ?string { return $this->fechaNecesidad; }
    public function setFechaNecesidad(?string $f): void { $this->fechaNecesidad = $f; }

    public function estadoId(): int { return $this->estadoId; }
    public function setEstadoId(int $e): void { $this->estadoId = $e; }
    public function estadoNombre(): string { return $this->estadoNombre; }
    public function setEstadoNombre(string $e): void { $this->estadoNombre = $e; }

    public function respuesta(): string { return $this->respuesta; }
    public function setRespuesta(string $r): void { $this->respuesta = $r; }

    public function atendidaPor(): ?int { return $this->atendidaPor; }
    public function setAtendidaPor(?int $a): void { $this->atendidaPor = $a; }
    public function atendidaPorNombre(): string { return $this->atendidaPorNombre; }
    public function setAtendidaPorNombre(string $a): void { $this->atendidaPorNombre = $a; }

    public function fechaCreacion(): ?string { return $this->fechaCreacion; }
    public function setFechaCreacion(?string $f): void { $this->fechaCreacion = $f; }

    public function fechaCierre(): ?string { return $this->fechaCierre; }
    public function setFechaCierre(?string $f): void { $this->fechaCierre = $f; }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id, 'titulo' => $this->titulo, 'tipo' => $this->tipoNombre,
            'estado' => $this->estadoNombre, 'necesidad' => $this->fechaNecesidad,
        ];
    }
}
