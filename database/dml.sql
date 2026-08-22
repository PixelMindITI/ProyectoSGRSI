-- =====================================================================
-- SGRSI - PixelMind - Segunda entrega
-- DML: Datos de prueba
-- Contraseñas de TODOS los usuarios de prueba: Pixel2026!
-- (hash bcrypt generado con password_hash(..., PASSWORD_BCRYPT))
-- =====================================================================

USE sgrsi;

-- Catálogos -----------------------------------------------------------
INSERT INTO roles (id, nombre) VALUES
  (1, 'administrador'),
  (2, 'tecnico'),
  (3, 'solicitante');

INSERT INTO tipos_equipo (id, nombre) VALUES
  (1,'Notebook'),(2,'PC de escritorio'),(3,'Monitor'),(4,'Proyector'),
  (5,'Impresora'),(6,'Switch / Router'),(7,'Otro');

INSERT INTO estados_equipo (id, nombre) VALUES
  (1,'disponible'),(2,'prestado'),(3,'en_mantenimiento'),(4,'baja');

INSERT INTO prioridades (id, nombre) VALUES
  (1,'baja'),(2,'media'),(3,'alta');

INSERT INTO estados_ticket (id, nombre) VALUES
  (1,'pendiente'),(2,'en_proceso'),(3,'resuelto');

INSERT INTO estados_prestamo (id, nombre) VALUES
  (1,'activo'),(2,'devuelto');

INSERT INTO tipos_solicitud (id, nombre) VALUES
  (1,'Preparación de laboratorio'),(2,'Instalación de software'),
  (3,'Configuración de equipo'),(4,'Otro');

INSERT INTO estados_solicitud (id, nombre) VALUES
  (1,'pendiente'),(2,'en_proceso'),(3,'completada'),(4,'rechazada');

-- Usuarios (rol: administrador / tecnico / solicitante) -----------------
INSERT INTO usuarios (nombre, apellido, email, password_hash, rol_id, activo) VALUES
  ('Bruno','Coordinador','admin@pixelmind.uy','$2y$10$K9OmRdpGpWDxvS5vKllRB.KtVF.k4IHMljKU7M/SmaiMIU8Mm.Hdu',1,1),
  ('Andrés','Pérez','tecnico1@pixelmind.uy','$2y$10$K9OmRdpGpWDxvS5vKllRB.KtVF.k4IHMljKU7M/SmaiMIU8Mm.Hdu',2,1),
  ('Lucía','Gómez','tecnico2@pixelmind.uy','$2y$10$K9OmRdpGpWDxvS5vKllRB.KtVF.k4IHMljKU7M/SmaiMIU8Mm.Hdu',2,1),
  ('María','Fernández','docente1@iti.edu.uy','$2y$10$nW/zQaJx4z9l6C0ZdKZIPeH/JirUZ//A2LaanA9zS2mrpGvJP6IpC',3,1),
  ('Diego','Rodríguez','docente2@iti.edu.uy','$2y$10$nW/zQaJx4z9l6C0ZdKZIPeH/JirUZ//A2LaanA9zS2mrpGvJP6IpC',3,1),
  ('Sofía','Martínez','estudiante1@iti.edu.uy','$2y$10$nW/zQaJx4z9l6C0ZdKZIPeH/JirUZ//A2LaanA9zS2mrpGvJP6IpC',3,1);

-- Equipos ---------------------------------------------------------------
INSERT INTO equipos (codigo, nombre, tipo_id, marca, modelo, numero_serie, estado_id, ubicacion, fecha_adquisicion, observaciones) VALUES
  ('INV-0001','Notebook HP ProBook',1,'HP','ProBook 450 G9','SN-HP-99231',2,'Laboratorio A','2024-03-15',NULL),
  ('INV-0002','Notebook Lenovo ThinkPad',1,'Lenovo','ThinkPad E14','SN-LN-45120',1,'Depósito informática','2024-05-02',NULL),
  ('INV-0003','PC Sala de profesores',2,'Dell','OptiPlex 7010','SN-DE-78123',1,'Sala de profesores','2023-11-20',NULL),
  ('INV-0004','Monitor Dell 24"',3,'Dell','P2422H','SN-DE-90342',1,'Laboratorio B','2024-01-10',NULL),
  ('INV-0005','Proyector Epson',4,'Epson','EB-X51','SN-EP-11209',2,'Aula 12','2023-08-30','Controladora HDMI reemplazada en 2025'),
  ('INV-0006','Impresora HP LaserJet',5,'HP','LaserJet Pro M404','SN-HP-33871',3,'Secretaría','2022-06-14','En revisión por atascos frecuentes'),
  ('INV-0007','Switch TP-Link 24p',6,'TP-Link','TL-SG1024','SN-TP-55401',1,'Rack servidor','2023-02-01',NULL),
  ('INV-0008','Notebook Acer Aspire',1,'Acer','Aspire 5','SN-AC-77410',4,'Depósito informática','2021-09-18','Dado de baja por falla de placa'),
  ('INV-0009','Notebook Asus Vivobook',1,'Asus','Vivobook 15','SN-AS-60988',1,'Laboratorio A','2025-02-25',NULL),
  ('INV-0010','Proyector BenQ',4,'BenQ','MX560','SN-BQ-20455',1,'Aula 8','2024-07-19',NULL);

-- Asignaciones (trazabilidad) --------------------------------------------
INSERT INTO asignaciones (equipo_id, usuario_id, registrado_por, fecha_asignacion, fecha_devolucion, observaciones) VALUES
  (1,4,2,'2025-03-03','2025-06-27','Asignada a docente por semestre'),
  (3,5,2,'2025-04-14',NULL,'Equipo fijo de sala de profesores'),
  (5,4,1,'2026-03-02',NULL,'Proyector a cargo del docente del aula 12');

-- Préstamos ----------------------------------------------------------------
INSERT INTO prestamos (equipo_id, usuario_solicitante_id, usuario_registra_id, fecha_prestamo, fecha_devolucion_esperada, fecha_devolucion_real, estado_id, observaciones) VALUES
  (1,4,2,NOW() - INTERVAL 5 DAY, CURDATE() + INTERVAL 9 DAY,NULL,1,NULL),
  (5,5,2,NOW() - INTERVAL 12 DAY,CURDATE() - INTERVAL 2 DAY,NULL,1,'Vencido, recordar reclamo'),
  (2,4,1,NOW() - INTERVAL 40 DAY,CURDATE() - INTERVAL 26 DAY,NOW() - INTERVAL 27 DAY,2,NULL),
  (10,6,2,NOW() - INTERVAL 70 DAY,CURDATE() - INTERVAL 63 DAY,NOW() - INTERVAL 65 DAY,2,NULL);

-- Tickets -------------------------------------------------------------------
INSERT INTO tickets (titulo, descripcion, usuario_solicitante_id, tecnico_asignado_id, equipo_id, prioridad_id, estado_id, fecha_creacion, fecha_resolucion) VALUES
  ('Notebook no enciende','La notebook INV-0001 no da señales de vida al presionar el botón de encendido.',4,2,1,3,2,NOW() - INTERVAL 2 DAY,NULL),
  ('Internet lento en Laboratorio B','Navegación muy lenta en las máquinas del laboratorio B durante la mañana.',5,3,7,2,1,NOW() - INTERVAL 1 DAY,NULL),
  ('Tóner agotado impresora secretaría','La impresora INV-0006 indica tóner agotado.',6,1,6,1,3,NOW() - INTERVAL 20 DAY,NOW() - INTERVAL 17 DAY),
  ('Software de diseño para clase','Solicito instalación de GIMP en la PC de la sala de profesores.',4,2,3,2,3,NOW() - INTERVAL 30 DAY,NOW() - INTERVAL 28 DAY);

INSERT INTO intervenciones_ticket (ticket_id, tecnico_id, diagnostico, es_resolucion, fecha) VALUES
  (3,1,'Se reemplazó el cartucho de tóner y se realizó limpieza de rodillos.',1,NOW() - INTERVAL 17 DAY),
  (4,2,'GIMP 2.10 instalado y verificado junto al docente.',1,NOW() - INTERVAL 28 DAY),
  (1,2,'Se descarta falla de cargador. Se sospecha falla de batería interna; pendiente prueba con batería de repuesto.',0,NOW() - INTERVAL 1 DAY);

-- Solicitudes de servicio ------------------------------------------------------
INSERT INTO solicitudes_servicio (usuario_solicitante_id, tipo_id, titulo, descripcion, laboratorio, fecha_necesidad, estado_id, respuesta, atendida_por, fecha_creacion, fecha_cierre) VALUES
  (4,1,'Preparar laboratorio A para examen','Necesito 15 notebooks con acceso a internet y proyector funcionando.','Laboratorio A',CURDATE()+INTERVAL 7 DAY,2,'Equipos verificados, falta probar red. En proceso.',2,NOW()-INTERVAL 3 DAY,NULL),
  (5,2,'Instalar Python 3 en Laboratorio B','Para el taller de programación de la próxima semana.','Laboratorio B',CURDATE()+INTERVAL 10 DAY,1,NULL,NULL,NOW()-INTERVAL 1 DAY,NULL),
  (6,3,'Configurar doble monitor','Solicito configuración de segundo monitor en mi puesto de trabajo.',NULL,CURDATE()-INTERVAL 5 DAY,3,'Realizado: se conectó y configuró el monitor INV-0004.',2,NOW()-INTERVAL 8 DAY,NOW()-INTERVAL 5 DAY),
  (4,4,'Reserva de proyector evento institucional','Evento de cierre de cursos, se necesita proyector en el salón principal.',NULL,CURDATE()+INTERVAL 15 DAY,4,'No se puede cumplir: el proyector del aula 12 está prestado. Se sugirió alternativa.',1,NOW()-INTERVAL 2 DAY,NOW()-INTERVAL 1 DAY);
