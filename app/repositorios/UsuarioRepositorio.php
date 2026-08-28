<?php
/**
 * UsuarioRepositorio — Acceso a datos de usuarios.
 */

class UsuarioRepositorio extends RepositorioBase
{
    private const SELECT_BASE =
        'SELECT u.*, r.nombre AS rol_nombre
         FROM usuarios u JOIN roles r ON r.id = u.rol_id';

    public function buscarPorEmail(string $email): ?Usuario
    {
        $fila = $this->fila(self::SELECT_BASE . ' WHERE u.email = ?', 's', [$email]);
        return $fila ? $this->mapear($fila) : null;
    }

    public function buscarPorId(int $id): ?Usuario
    {
        $fila = $this->fila(self::SELECT_BASE . ' WHERE u.id = ?', 'i', [$id]);
        return $fila ? $this->mapear($fila) : null;
    }

    /** @return array<int,Usuario> */
    public function listar(array $filtros = []): array
    {
        $sql = self::SELECT_BASE . ' WHERE 1=1';
        $tipos = '';
        $params = [];

        if (!empty($filtros['rol'])) {
            $sql .= ' AND u.rol_id = ?';
            $tipos .= 'i';
            $params[] = (int)$filtros['rol'];
        }
        if (!empty($filtros['q'])) {
            $sql .= ' AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ?)';
            $tipos .= 'sss';
            $like = '%' . $filtros['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        $sql .= ' ORDER BY u.nombre, u.apellido';

        return array_map([$this, 'mapear'], $this->filas($sql, $tipos, $params));
    }

    public function emailEnUso(string $email, int $exceptoId = 0): bool
    {
        return (bool)$this->escalar('SELECT COUNT(*) FROM usuarios WHERE email = ? AND id <> ?', 'si', [$email, $exceptoId]);
    }

    public function insertar(Usuario $u): int
    {
        $id = 0;
        $this->modificar(
            'INSERT INTO usuarios (nombre, apellido, email, password_hash, rol_id, activo)
             VALUES (?, ?, ?, ?, ?, ?)',
            'sssssi',
            [$u->nombre(), $u->apellido(), $u->email(), $u->passwordHash(), $u->rolId(), (int)$u->activo()],
            $id
        );
        return $id;
    }

    public function actualizar(Usuario $u): void
    {
        $this->modificar(
            'UPDATE usuarios SET nombre=?, apellido=?, email=?, rol_id=?, activo=? WHERE id=?',
            'sssiii',
            [$u->nombre(), $u->apellido(), $u->email(), $u->rolId(), (int)$u->activo(), $u->id()]
        );
    }

    /** Cambia la contraseña conservando el hash bcrypt. */
    public function actualizarPassword(int $id, string $hash): void
    {
        $this->modificar('UPDATE usuarios SET password_hash=? WHERE id=?', 'si', [$hash, $id]);
    }

    public function registrarAcceso(int $id): void
    {
        $this->modificar('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id=?', 'i', [$id]);
    }

    /** Borrado lógico: desactiva la cuenta sin eliminar su historial (trazabilidad). */
    public function desactivar(int $id): void
    {
        $this->modificar('UPDATE usuarios SET activo = 0 WHERE id = ?', 'i', [$id]);
    }

    /** Cuántos administradores activos hay (protección: nunca dejar el sistema sin admin). */
    public function contarAdministradoresActivos(): int
    {
        return (int)$this->escalar(
            "SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id = u.rol_id
             WHERE r.nombre = 'administrador' AND u.activo = 1"
        );
    }

    public function contarPorRol(): array
    {
        return $this->filas(
            'SELECT r.nombre AS rol, COUNT(u.id) AS total
             FROM roles r LEFT JOIN usuarios u ON u.rol_id = r.id AND u.activo = 1
             GROUP BY r.id, r.nombre'
        );
    }

    private function mapear(array $f): Usuario
    {
        $u = new Usuario(
            (int)$f['id'], $f['nombre'], $f['apellido'], $f['email'],
            $f['password_hash'], (int)$f['rol_id'], $f['rol_nombre'],
            (bool)$f['activo']
        );
        return $u;
    }
}
