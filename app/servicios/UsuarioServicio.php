<?php
/**
 * UsuarioServicio — Lógica de negocio de usuarios y autenticación.
 *
 * Reglas implementadas:
 *  - El registro público crea SIEMPRE rol "solicitante" (docentes,
 *    administrativos, estudiantes). Técnicos y admins se crean desde
 *    el módulo Usuarios, exclusivo del administrador.
 *  - Las credenciales se encriptan con bcrypt (PASSWORD_BCRYPT):
 *    hash unidireccional con salt automático, resistente a fuerza
 *    bruta y a tablas rainbow. NUNCA se guarda la contraseña plana.
 *  - El login verifica con password_verify() y registra el acceso.
 */

class UsuarioServicio
{
    public function __construct(
        private UsuarioRepositorio $repositorio = new UsuarioRepositorio(),
        private CatalogoRepositorio $catalogos = new CatalogoRepositorio(),
    ) {}

    /** Autentica por email+contraseña; devuelve el Usuario o null. */
    public function login(string $email, string $password): ?Usuario
    {
        $usuario = $this->repositorio->buscarPorEmail(strtolower(trim($email)));

        // Verificación en tiempo constante: si no existe, igual se
        // compara contra un hash ficticio para no filtrar información.
        if (!$usuario) {
            password_verify($password, '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinva');
            return null;
        }
        if (!$usuario->activo() || !$usuario->verificarPassword($password)) {
            return null;
        }

        $this->repositorio->registrarAcceso($usuario->id());
        Sesion::autenticar($usuario->id(), $usuario->nombreCompleto(), $usuario->rolNombre());
        return $usuario;
    }

    /** Registro público (rol solicitante fijo por seguridad). */
    public function registrarSolicitante(array $datos): int
    {
        $errores = $this->validarRegistro($datos);
        if ($errores) {
            throw new ValidacionException($errores);
        }

        $rolSolicitante = array_search('solicitante', $this->catalogos->roles(), true);
        if ($rolSolicitante === false) {
            throw new RuntimeException('Catálogo de roles inconsistente.');
        }

        $u = new Usuario();
        $u->setNombre(Validador::texto($datos['nombre']));
        $u->setApellido(Validador::texto($datos['apellido']));
        $u->setEmail(strtolower(trim($datos['email'])));
        $u->setPassword($datos['password']);
        $u->setRolId((int)$rolSolicitante);
        return $this->repositorio->insertar($u);
    }

    /** Alta interna por parte del administrador (cualquier rol). */
    public function crear(array $datos): int
    {
        $errores = $this->validarRegistro($datos);

        $rolesValidos = array_keys($this->catalogos->roles());
        if (!isset($datos['rol']) || !in_array((int)$datos['rol'], $rolesValidos, true)) {
            $errores['rol'] = 'Debe seleccionar un rol válido.';
        }
        if ($errores) {
            throw new ValidacionException($errores);
        }

        $u = new Usuario();
        $u->setNombre(Validador::texto($datos['nombre']));
        $u->setApellido(Validador::texto($datos['apellido']));
        $u->setEmail(strtolower(trim($datos['email'])));
        $u->setPassword($datos['password']);
        $u->setRolId((int)$datos['rol']);
        return $this->repositorio->insertar($u);
    }

    /** Edición de datos básicos/estado; opcionalmente nueva contraseña. */
    public function editar(int $id, array $datos): void
    {
        $v = new Validador();
        $v->requerido('nombre', $datos['nombre'] ?? '', 'El nombre es obligatorio.');
        $v->requerido('apellido', $datos['apellido'] ?? '', 'El apellido es obligatorio.');
        $v->email('email', $datos['email'] ?? '', 'Formato de email inválido.');

        $rolesValidos = array_keys($this->catalogos->roles());
        if (!isset($datos['rol']) || !in_array((int)$datos['rol'], $rolesValidos, true)) {
            $v->agregarError('rol', 'Debe seleccionar un rol válido.');
        }
        if (!empty($datos['password'])) {
            $v->largoMin('password', $datos['password'], 8, 'La nueva contraseña debe tener al menos 8 caracteres.');
        }
        if ($v->hayErrores()) {
            throw new ValidacionException($v->errores());
        }

        $u = $this->obtenerPorId($id);
        $nuevoEmail = strtolower(trim($datos['email']));
        if ($this->repositorio->emailEnUso($nuevoEmail, $id)) {
            throw new ValidacionException(['email' => 'Ese email ya está registrado.']);
        }

        $u->setNombre(Validador::texto($datos['nombre']));
        $u->setApellido(Validador::texto($datos['apellido']));
        $u->setEmail($nuevoEmail);
        $u->setRolId((int)$datos['rol']);
        $u->setActivo(!empty($datos['activo']));
        $this->repositorio->actualizar($u);

        if (!empty($datos['password'])) {
            $cambio = new Usuario();
            $cambio->setPassword($datos['password']); // genera hash bcrypt
            $this->repositorio->actualizarPassword($id, $cambio->passwordHash());
        }
    }

    /** @return array<int,Usuario> */
    public function listar(array $filtros = []): array
    {
        return $this->repositorio->listar($filtros);
    }

    public function obtenerPorId(int $id): Usuario
    {
        $u = $this->repositorio->buscarPorId($id);
        if (!$u) {
            throw new NoEncontradoException('El usuario no existe.');
        }
        return $u;
    }

    private function validarRegistro(array $datos): array
    {
        $v = new Validador();
        $v->requerido('nombre',   $datos['nombre']   ?? '', 'El nombre es obligatorio.');
        $v->largoMax('nombre',    $datos['nombre']   ?? '', 80, 'Máximo 80 caracteres.');
        $v->requerido('apellido', $datos['apellido'] ?? '', 'El apellido es obligatorio.');
        $v->largoMax('apellido',  $datos['apellido'] ?? '', 80, 'Máximo 80 caracteres.');
        $v->requerido('email',    $datos['email']    ?? '', 'El email es obligatorio.');
        $v->email('email',        $datos['email']    ?? '', 'Formato de email inválido.');
        $v->largoMin('password',  $datos['password'] ?? '', 8,  'La contraseña debe tener al menos 8 caracteres.');

        if (($datos['password'] ?? '') !== ($datos['password_confirm'] ?? '')) {
            $v->agregarError('password_confirm', 'Las contraseñas no coinciden.');
        }

        $email = strtolower(trim($datos['email'] ?? ''));
        if ($email !== '' && $this->repositorio->emailEnUso($email)) {
            $v->agregarError('email', 'Ese email ya está registrado.');
        }
        return $v->errores();
    }
}
