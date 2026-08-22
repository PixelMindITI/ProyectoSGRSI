# SGRSI — Sistema de Gestión de Recursos y Soporte de Informática

**Grupo PixelMind** · ITI 2026 · Programación Full Stack · **Segunda entrega**

---

## 1. Cómo ejecutar el proyecto (XAMPP)

1. Copiar `config/config.example.php` como `config/config.php` y ajustar credenciales
   (en XAMPP por defecto: usuario `root`, contraseña vacía).
2. Iniciar **Apache** y **MySQL** desde el panel de XAMPP.
3. Importar la base de datos en http://localhost/phpmyadmin :
   primero `database/ddl.sql` (crea el esquema) y luego `database/dml.sql` (datos de prueba).
4. Publicar el proyecto en Apache con enlace simbólico (CMD **como administrador**):

   ```cmd
   mklink /d "C:\xampp\htdocs\SGRSI" "C:\Users\Bruno\Desktop\SGRSI"
   ```

5. Abrir: **http://localhost/SGRSI/public/** (o `/SGRSI/` si Apache procesa el .htaccess raíz).

### Usuarios de prueba

| Email | Rol | Contraseña |
|---|---|---|
| admin@pixelmind.uy | administrador | `Pixel2026!` |
| tecnico1@pixelmind.uy | técnico | `Pixel2026!` |
| tecnico2@pixelmind.uy | técnico | `Pixel2026!` |
| docente1@iti.edu.uy | solicitante | `Docente2026!` |
| docente2@iti.edu.uy | solicitante | `Docente2026!` |
| estudiante1@iti.edu.uy | solicitante | `Docente2026!` |

---

## 2. Arquitectura en tres capas

```
┌───────────────────────────────────────────────┐
│ PRESENTACIÓN  public/*.php + assets (HTML/CSS/JS) │  ← vistas Bootstrap 5,
│                 formularios → POST al mismo script │    validaciones cliente
├───────────────────────────────────────────────┤
│ LÓGICA DE NEGOCIO  app/servicios/*Servicio.php     │  ← reglas del sistema,
│                 entidades del dominio app/entidades │    excepciones propias
├───────────────────────────────────────────────┤
│ ACCESO A DATOS  app/repositorios/*Repositorio.php  │  ← SQL 100% con sentencias
│                 core/Database.php (conexión)       │    preparadas (mysqli)
└───────────────────────────────────────────────┘
```

Las vistas nunca hablan con la base de datos: llaman a servicios; los servicios
aplican reglas y delegan en repositorios; los repositorios ejecutan SQL preparado.

## 3. Patrones de diseño utilizados (y por qué)

| Patrón | Dónde | Justificación |
|---|---|---|
| **Singleton** | `core/Database.php` | Una única conexión `mysqli` compartida por petición: evita conexiones duplicadas y centraliza configuración/errores. |
| **Repository / DAO** | `app/repositorios/*` | Aísla todo el SQL en una capa propia: si cambia el motor o un esquema, no se toca la lógica de negocio ni las vistas. Facilita pruebas. |
| **Service Layer** | `app/servicios/*` | Las reglas (disponibilidad de equipos, RBAC, estados válidos) viven en UN lugar, reutilizadas por todas las páginas. |
| **Page Controller** | cada página `public/*.php` | Cada URL tiene su controlador simple que valida entrada, invoca servicios y renderiza su vista — apropiado para el alcance del proyecto. |
| **Data Mapper (ligero)** | entidades ↔ filas | Las entidades (`Usuario`, `Equipo`, `Ticket`, `Prestamo`, `Solicitud`) no conocen la BD; los repositorios hacen el mapeo. |
| **PRG (Post-Redirect-Get)** | formularios | Tras un POST exitoso se redirige (303): evita reenvíos duplicados al recargar (F5). |
| **Front Controller (parcial)** | `public/_init.php` | Punto único de arranque: config, autoload, sesión, idioma y gestor global de errores. |

## 4. Conexión a la base de datos — justificación

Se usa **MySQLi en modo orientado a objetos**, porque:

1. Es la extensión **nativa** de PHP para MySQL y viene lista en XAMPP.
2. Soporta **sentencias preparadas** con parámetros vinculados (`bind_param`),
   defensa principal contra inyección SQL — usada en el 100% de las consultas.
3. Ofrece API orientada a objetos coherente con el paradigma exigido.
4. `mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)` convierte los
   errores SQL en excepciones → manejo centralizado.

Alternativas consideradas: PDO (portabilidad que este proyecto no necesita:
el motor es MySQL/XAMPP) y ORM (Eloquent/Doctrine: sobredimensionado para la
asignatura y oculta el SQL que se pide documentar).

## 5. Encriptación de credenciales — justificación

Las contraseñas se almacenan con `password_hash($pass, PASSWORD_BCRYPT)`:

- **Hash unidireccional**: incluso con acceso a la BD no se recupera la clave.
- **Salt automático por usuario**: dos usuarios con la misma clave tienen hashes distintos (anti rainbow tables).
- **Coste ajustable** (factor 10 por defecto): fuerza bruta lenta; bcrypt es el estándar recomendado por OWASP junto a Argon2.

Se descartaron MD5/SHA-1 (roto/rápido, sin salt nativo). La verificación usa
`password_verify()`, comparable en tiempo constante. Además: cuando el email
no existe, se igual verifica contra un hash ficticio para no filtrar qué
cuentas existen (enumeración de usuarios).

## 6. Seguridad implementada

| Requisito de la letra | Implementación |
|---|---|
| Validación obligatoria en servidor | `core/Validador.php`: requerido, largos, email, fechas, listas blancas de catálogos. Se repiten TODAS las reglas del cliente. |
| Sanitización de entrada | `Validador::texto()` = trim + strip_tags + htmlspecialchars antes de persistir. |
| Sanitización de salida | helper `e()` (htmlspecialchars ENT_QUOTES) usado al imprimir cualquier dato dinámico → anti XSS. |
| Inyección SQL | 100% sentencias preparadas (`RepositorioBase`). |
| CSRF | token por sesión (`core/Csrf.php`) en todos los formularios POST. |
| Sesiones | cookie httponly + samesite, `session_regenerate_id(true)` al loguear (fijación de sesión), expiración por inactividad configurable. |
| Control de acceso (RBAC) | `core/Auth.php`: `requerirLogin()` / `requerirRol([...])` al inicio de cada página + menú filtrado por rol + verificación de propiedad (un solicitante solo ve SUS tickets/solicitudes/préstamos). |
| Manejo de errores/excepciones | Excepciones propias (`AplicacionException`, `ValidacionException`, `NoEncontradoException`, `AccesoDenegadoException`) + handler global que registra en log y muestra página amigable. `display_errors` desactivado según config. |
| Credenciales fuera del repo | `config/config.php` excluido en `.gitignore`; solo viaja `config.example.php`. `.htaccess` bloquea `config/`, `core/`, `app/`, `database/`. |

## 7. Manejo de estados HTTP

| Código | Cuándo |
|---|---|
| 200 | Página normal. |
| 303 | Redirect tras POST (patrón PRG). |
| 400 | Validación de servidor rechazó el formulario (se re-muestra con errores). |
| 403 | Rol insuficiente (`Auth::requerirRol`), CSRF inválido, ticket/solicitud ajeno. |
| 404 | Registro inexistente en páginas de detalle/edición (`abortar(404)`). |
| 405 | Método HTTP incorrecto para la acción (`exigirMetodo`). |
| 500 | Excepción no capturada (página amigable + registro en log). |

## 8. Estructura del proyecto

```
SGRSI/
├── config/          configuración (config.php NO versionado)
├── core/            infraestructura transversal (Database, Sesion, Auth,
│                    Validador, Csrf, Idioma, autoload, helpers)
├── app/
│   ├── entidades/       Usuario, Equipo, Ticket, Prestamo, Solicitud
│   ├── repositorios/    acceso a datos (SQL preparado)
│   ├── servicios/       lógica de negocio
│   ├── excepciones/     jerarquía de excepciones propias
│   └── lang/            es.php / en.php (interfaz bilingüe)
├── public/          ÚNICA carpeta servida por Apache
│   ├── _init.php        arranque común
│   ├── index.php        login      · registro.php · logout.php
│   ├── dashboard.php    métricas (admin/técnico) o resumen personal
│   ├── equipos/         listar · alta · editar · detalle(+historial)
│   ├── prestamos/       listar · nuevo · devolver
│   ├── incidencias/     listar · nueva · detalle(+intervenciones)
│   ├── solicitudes/     listar · nueva · detalle(+atención)
│   ├── usuarios/        listar · alta · editar (solo administrador)
│   └── assets/          css/estilos.css · js/app.js
└── database/        ddl.sql (esquema 3FN) · dml.sql (datos de prueba)
```

## 9. Base de datos — normalización a 3FN

- **1NF**: todos los atributos son atómicos (sin listas ni grupos repetidos).
- **2NF**: toda tabla tiene clave primaria simple (`id`) → no hay dependencias parciales.
- **3NF**: los valores "descriptivos" que se repetirían (nombre de rol, tipo de equipo,
  nombre de estado, prioridad) viven en **tablas catálogo** referenciadas por FK;
  ningún atributo no-clave depende de otro atributo no-clave.
- Integridad referencial con `FOREIGN KEY` (RESTRICT / SET NULL según semántica).
- Índices únicos: `usuarios.email`, `equipos.codigo`, `equipos.numero_serie`.

Tablas: `roles, tipos_equipo, estados_equipo, prioridades, estados_ticket,
estados_prestamo, tipos_solicitud, estados_solicitud, usuarios, equipos,
asignaciones (trazabilidad), prestamos, tickets, intervenciones_ticket,
solicitudes_servicio`.

## 10. Responsive (mobile first)

CSS propio sobre Bootstrap 5 con media queries progresivas:
base (<576px móviles) → 576px → **768px tabletas** → 992px → **1024px+ monitores**
(exigido en esta entrega: layout ampliado, contenedor máx. 1400px, hover states)
→ 1400px+ (tipografía mayor).

## 11. Interfaz en dos idiomas

Selector ES/EN en el navbar (persistido en sesión): `app/lang/{es,en}.php`
+ helper global `t('clave')` (`core/Idioma.php`). Cumple el requisito general
de la letra: interfaz disponible en español e inglés.

## 12. Convenciones de Git

- Ramas: `main` (estable), `feature/<módulo>` por funcionalidad.
- Commits estilo *Conventional Commits*: `feat:`, `fix:`, `docs:`, `chore:` en español.
- Versionado SemVer con etiquetas (`v0.1.0`, ...).
