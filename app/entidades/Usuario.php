<?php
/**
 * Usuario — Entidad del dominio: persona registrada en el sistema.
 * La contraseña nunca se guarda como atributo plano: solo su hash bcrypt.
 */

class Usuario implements JsonSerializable
{
    public function __construct(
        private int $id = 0,
        private string $nombre = '',
        private string $apellido = '',
        private string $email = '',
        private string $passwordHash = '',
        private int $rolId = 3,
        private string $rolNombre = 'solicitante',
        private bool $activo = true,
    ) {}

    public function id(): int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function nombre(): string { return $this->nombre; }
    public function setNombre(string $n): void { $this->nombre = $n; }

    public function apellido(): string { return $this->apellido; }
    public function setApellido(string $a): void { $this->apellido = $a; }

    public function nombreCompleto(): string
    {
        return trim($this->nombre . ' ' . $this->apellido);
    }

    public function email(): string { return $this->email; }
    public function setEmail(string $e): void { $this->email = strtolower($e); }

    public function passwordHash(): string { return $this->passwordHash; }
    public function setPassword(string $plano): void
    {
        // Encriptación de credenciales: bcrypt con salt automático.
        $this->passwordHash = password_hash($plano, PASSWORD_BCRYPT);
    }

    public function rolId(): int { return $this->rolId; }
    public function setRolId(int $r): void { $this->rolId = $r; }
    public function rolNombre(): string { return $this->rolNombre; }
    public function setRolNombre(string $r): void { $this->rolNombre = $r; }

    public function activo(): bool { return $this->activo; }
    public function setActivo(bool $a): void { $this->activo = $a; }

    /** Verifica una contraseña plana contra el hash almacenado. */
    public function verificarPassword(string $plana): bool
    {
        return password_verify($plana, $this->passwordHash);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id, 'nombre' => $this->nombreCompleto(),
            'email' => $this->email, 'rol' => $this->rolNombre, 'activo' => $this->activo,
        ];
    }
}
