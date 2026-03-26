-- =====================================================
-- BSC Temis Lostalo - Schema de Base de Datos
-- Temis Lostalo - Gestión Estratégica
-- =====================================================

CREATE DATABASE IF NOT EXISTS balance_score CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE balance_score;

-- Períodos estratégicos
CREATE TABLE periodos_estrategicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    activo TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Perspectivas del BSC
CREATE TABLE perspectivas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    color VARCHAR(7) NOT NULL DEFAULT '#0d6efd',
    icono VARCHAR(50) DEFAULT NULL,
    orden INT NOT NULL DEFAULT 0
);

-- Iniciativas Estratégicas
CREATE TABLE iniciativas_estrategicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    perspectiva_id INT NOT NULL,
    periodo_id INT DEFAULT NULL,
    codigo VARCHAR(10) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    orden INT NOT NULL DEFAULT 0,
    FOREIGN KEY (perspectiva_id) REFERENCES perspectivas(id) ON DELETE CASCADE,
    FOREIGN KEY (periodo_id) REFERENCES periodos_estrategicos(id) ON DELETE SET NULL
);

-- Planes de Acción (PDA)
CREATE TABLE planes_accion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    iniciativa_id INT NOT NULL,
    periodo_id INT DEFAULT NULL,
    codigo VARCHAR(10) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    owner VARCHAR(100) DEFAULT NULL,
    fecha_inicio DATE DEFAULT NULL,
    fecha_fin DATE DEFAULT NULL,
    avance INT DEFAULT 0,
    prioridad INT DEFAULT 1,
    peso DECIMAL(5,2) DEFAULT 0,
    estado ENUM('pendiente','en_progreso','completado','cancelado') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (iniciativa_id) REFERENCES iniciativas_estrategicas(id) ON DELETE CASCADE,
    FOREIGN KEY (periodo_id) REFERENCES periodos_estrategicos(id) ON DELETE SET NULL
);

-- Actividades
CREATE TABLE actividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_accion_id INT NOT NULL,
    codigo VARCHAR(10) NOT NULL,
    descripcion TEXT NOT NULL,
    responsable VARCHAR(100) DEFAULT NULL,
    estado ENUM('pendiente','en_progreso','completado','cancelado') DEFAULT 'pendiente',
    fecha_limite DATE DEFAULT NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_accion_id) REFERENCES planes_accion(id) ON DELETE CASCADE
);

-- Recursos
CREATE TABLE recursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actividad_id INT NOT NULL,
    tipo ENUM('humano','material','servicio','otro') DEFAULT 'humano',
    descripcion VARCHAR(200) NOT NULL,
    cantidad DECIMAL(10,2) DEFAULT NULL,
    unidad VARCHAR(50) DEFAULT NULL,
    costo_estimado DECIMAL(12,2) DEFAULT NULL,
    notas TEXT,
    FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE CASCADE
);

-- KPIs
CREATE TABLE kpis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    iniciativa_id INT DEFAULT NULL,
    plan_accion_id INT DEFAULT NULL,
    periodo_id INT DEFAULT NULL,
    nombre VARCHAR(200) NOT NULL,
    tipo ENUM('cuantitativo','cualitativo') DEFAULT 'cuantitativo',
    unidad VARCHAR(50) DEFAULT NULL,
    meta DECIMAL(15,2) DEFAULT NULL,
    valor_actual DECIMAL(15,2) DEFAULT NULL,
    umbral_verde DECIMAL(5,2) DEFAULT 90.00,
    umbral_amarillo DECIMAL(5,2) DEFAULT 70.00,
    direccion ENUM('mayor_mejor','menor_mejor') DEFAULT 'mayor_mejor',
    escala_cualitativa VARCHAR(50) DEFAULT NULL,
    opciones_cualitativas TEXT,
    valor_cualitativo VARCHAR(100) DEFAULT NULL,
    estado_semaforo ENUM('verde','amarillo','rojo') DEFAULT NULL,
    frecuencia ENUM('mensual','trimestral','anual') DEFAULT 'mensual',
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (iniciativa_id) REFERENCES iniciativas_estrategicas(id) ON DELETE SET NULL,
    FOREIGN KEY (plan_accion_id) REFERENCES planes_accion(id) ON DELETE SET NULL,
    FOREIGN KEY (periodo_id) REFERENCES periodos_estrategicos(id) ON DELETE SET NULL
);

-- Historial de KPIs
CREATE TABLE kpi_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kpi_id INT NOT NULL,
    valor DECIMAL(15,2) DEFAULT NULL,
    valor_cualitativo VARCHAR(100) DEFAULT NULL,
    semaforo ENUM('verde','amarillo','rojo') DEFAULT NULL,
    periodo DATE NOT NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kpi_id) REFERENCES kpis(id) ON DELETE CASCADE
);

-- Reuniones
CREATE TABLE reuniones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    iniciativa_id INT DEFAULT NULL,
    titulo VARCHAR(200) NOT NULL,
    fecha DATETIME NOT NULL,
    participantes TEXT,
    minuta TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (iniciativa_id) REFERENCES iniciativas_estrategicas(id) ON DELETE SET NULL
);

-- Compromisos
CREATE TABLE compromisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reunion_id INT NOT NULL,
    actividad_id INT DEFAULT NULL,
    plan_accion_id INT DEFAULT NULL,
    descripcion TEXT NOT NULL,
    responsable VARCHAR(100) DEFAULT NULL,
    fecha_limite DATE DEFAULT NULL,
    estado ENUM('pendiente','en_progreso','completado') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reunion_id) REFERENCES reuniones(id) ON DELETE CASCADE,
    FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE SET NULL,
    FOREIGN KEY (plan_accion_id) REFERENCES planes_accion(id) ON DELETE SET NULL
);

-- Riesgos
CREATE TABLE riesgos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    iniciativa_id INT DEFAULT NULL,
    plan_accion_id INT DEFAULT NULL,
    descripcion TEXT NOT NULL,
    probabilidad ENUM('alta','media','baja') DEFAULT 'media',
    impacto ENUM('alto','medio','bajo') DEFAULT 'medio',
    nivel ENUM('critico','alto','medio','bajo') DEFAULT 'medio',
    plan_mitigacion TEXT,
    responsable VARCHAR(100) DEFAULT NULL,
    estado ENUM('abierto','mitigado','cerrado','materializado') DEFAULT 'abierto',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (iniciativa_id) REFERENCES iniciativas_estrategicas(id) ON DELETE SET NULL,
    FOREIGN KEY (plan_accion_id) REFERENCES planes_accion(id) ON DELETE SET NULL
);

-- Hitos
CREATE TABLE hitos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_accion_id INT NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    fecha_prevista DATE DEFAULT NULL,
    fecha_real DATE DEFAULT NULL,
    estado ENUM('pendiente','alcanzado','retrasado') DEFAULT 'pendiente',
    observaciones TEXT,
    FOREIGN KEY (plan_accion_id) REFERENCES planes_accion(id) ON DELETE CASCADE
);

-- Relaciones causa-efecto
CREATE TABLE relaciones_causa_efecto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    iniciativa_origen_id INT NOT NULL,
    iniciativa_destino_id INT NOT NULL,
    descripcion VARCHAR(200) DEFAULT NULL,
    FOREIGN KEY (iniciativa_origen_id) REFERENCES iniciativas_estrategicas(id) ON DELETE CASCADE,
    FOREIGN KEY (iniciativa_destino_id) REFERENCES iniciativas_estrategicas(id) ON DELETE CASCADE
);

-- Archivos adjuntos
CREATE TABLE archivos_adjuntos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entidad_tipo ENUM('plan_accion','actividad','reunion','riesgo','proyecto') NOT NULL,
    entidad_id INT NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    tipo_mime VARCHAR(100) DEFAULT NULL,
    tamano INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seguimientos (eventos de calendario)
CREATE TABLE seguimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entidad_tipo ENUM('plan','actividad','hito') NOT NULL,
    entidad_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    fecha DATE NOT NULL,
    hora TIME DEFAULT NULL,
    completado TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notas de actividades (historial)
CREATE TABLE notas_actividad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actividad_id INT NOT NULL,
    texto TEXT NOT NULL,
    imagen VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE CASCADE
);

-- Responsables (lista maestra)
CREATE TABLE responsables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    cargo VARCHAR(100) DEFAULT NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Proyectos
CREATE TABLE proyectos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    responsable VARCHAR(100) DEFAULT NULL,
    estado ENUM('pendiente','en_progreso','completado','cancelado','suspendido') DEFAULT 'pendiente',
    fecha_inicio DATE DEFAULT NULL,
    fecha_fin DATE DEFAULT NULL,
    presupuesto DECIMAL(14,2) DEFAULT NULL,
    prioridad INT DEFAULT 1,
    avance INT DEFAULT 0,
    periodo_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (periodo_id) REFERENCES periodos_estrategicos(id) ON DELETE SET NULL
);

-- Vínculos de proyectos con IEs y Planes
CREATE TABLE proyecto_vinculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT NOT NULL,
    entidad_tipo ENUM('iniciativa','plan_accion') NOT NULL,
    entidad_id INT NOT NULL,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE
);

-- Entregables de proyectos
CREATE TABLE proyecto_entregables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    responsable VARCHAR(100) DEFAULT NULL,
    fecha_prevista DATE DEFAULT NULL,
    fecha_real DATE DEFAULT NULL,
    estado ENUM('pendiente','en_progreso','completado') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE
);

-- Actividades de proyectos
CREATE TABLE proyecto_actividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    grupo VARCHAR(150) DEFAULT NULL,
    fase VARCHAR(100) DEFAULT NULL,
    color VARCHAR(7) DEFAULT '#5b9bd5',
    responsable VARCHAR(100) DEFAULT NULL,
    fecha_inicio DATE DEFAULT NULL,
    fecha_fin DATE DEFAULT NULL,
    estado ENUM('pendiente','en_progreso','completado','cancelado') DEFAULT 'pendiente',
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE
);

-- Notas de proyectos (historial)
CREATE TABLE notas_proyecto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT NOT NULL,
    texto TEXT NOT NULL,
    imagen VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE
);

-- Evaluaciones IA
CREATE TABLE evaluaciones_ia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('general','iniciativa','plan_accion','reunion','riesgos','proyecto') NOT NULL,
    entidad_id INT DEFAULT NULL,
    prompt_enviado TEXT,
    respuesta TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Bitácora de cambios
CREATE TABLE bitacora (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entidad_tipo ENUM('iniciativa','plan_accion','actividad','kpi','proyecto','riesgo','reunion','compromiso') NOT NULL,
    entidad_id INT NOT NULL,
    entidad_nombre VARCHAR(255) NOT NULL,
    accion ENUM('creado','editado','eliminado','estado_cambiado') NOT NULL,
    descripcion TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =====================================================
-- DATOS SEMILLA
-- =====================================================

-- Período estratégico activo
INSERT INTO periodos_estrategicos (nombre, fecha_inicio, fecha_fin, activo) VALUES
('2025-2026', '2025-04-01', '2026-03-31', 1);

-- Responsables
INSERT INTO responsables (nombre, cargo) VALUES
('P. Serrago', 'Marketing'),
('G. Vigetti', 'Comercial'),
('A. Agliotta', 'Calidad'),
('N. Braulinese', 'Produccion'),
('E. Nordi', 'Sistemas'),
('D. Hernandez', 'Supply Chain'),
('E. Callisto', 'I+D'),
('M. Solda', 'Exportaciones'),
('E. Moyano', 'RRHH'),
('A. Aliano', 'Finanzas');

-- Perspectivas del BSC
INSERT INTO perspectivas (nombre, color, icono, orden) VALUES
('Financiera', '#198754', 'bi-cash-stack', 1),
('Clientes', '#0d6efd', 'bi-people-fill', 2),
('Procesos Internos', '#fd7e14', 'bi-gear-fill', 3),
('Aprendizaje y Crecimiento', '#6f42c1', 'bi-book-fill', 4);

-- Iniciativas Estratégicas
INSERT INTO iniciativas_estrategicas (perspectiva_id, periodo_id, codigo, nombre, descripcion, orden) VALUES
-- Clientes (perspectiva 2)
(2, 1, 'IE1', 'Transformación Comercial & Marketing', 'Transformación integral del área comercial y marketing para fortalecer la presencia en el mercado farmacéutico', 1),
-- Procesos Internos (perspectiva 3)
(3, 1, 'IE2', 'Optimización del Portfolio de Productos', 'Gestión estratégica del portfolio de productos incluyendo lanzamiento de nuevas moléculas', 2),
(3, 1, 'IE3', 'Desarrollo de Infraestructura, Capacidad de Producción y Técnica', 'Mejora de infraestructura, capacidad productiva y técnica del laboratorio', 3),
-- Clientes (perspectiva 2)
(2, 1, 'IE4', 'Exportaciones', 'Expansión a nuevos territorios y ampliación de oferta exportable', 4),
-- Aprendizaje y Crecimiento (perspectiva 4)
(4, 1, 'IE5', 'Transformación Cultural', 'Desarrollo de talento, capacitación y gestión del desempeño', 5),
-- Financiera (perspectiva 1)
(1, 1, 'IE6', 'Sostenibilidad Económica & Financiera', 'Asegurar la sostenibilidad económica y financiera del laboratorio', 6);

-- =====================================================
-- PLANES DE ACCIÓN Y ACTIVIDADES
-- =====================================================

-- IE1: Transformación Comercial & Marketing
INSERT INTO planes_accion (iniciativa_id, periodo_id, codigo, nombre, owner, estado, prioridad, peso) VALUES
(1, 1, 'P1', 'Diseño del Plan de Marketing', 'P. Serrago', 'en_progreso', 1, 35),
(1, 1, 'P2', 'Elaboración del Plan Comercial', 'G. Vigetti', 'en_progreso', 2, 35),
(1, 1, 'P3', 'Plan de Excelencia en servicios al Cliente (MKT, BI, Comercial)', 'P. Serrago', 'en_progreso', 3, 30);

-- Actividades IE1-P1
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(1, 'A1', 'Desarrollar Estrategia de Marketing', 'pendiente'),
(1, 'A2', 'Establecer Estrategia de Precios', 'pendiente'),
(1, 'A3', 'Plan de Inversión en material promocional y no promocional', 'pendiente'),
(1, 'A4', 'Desarrollar Canales de Comunicación', 'pendiente');

-- Actividades IE1-P2
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(2, 'A1', 'Modelo de Despliegue FV & APF', 'pendiente'),
(2, 'A2', 'Diseñar Plan de Formación FV', 'pendiente'),
(2, 'A3', 'Definición de objetivos de venta por APM/región y plan de incentivos & premios', 'pendiente'),
(2, 'A4', 'Desarrollo Propuesta de Valor enfocada en canales (Droguerías/Farmacias)', 'pendiente'),
(2, 'A5', 'Monitoreo de Productividad (KPIs cobertura, frecuencia, crecimiento %MS Rx y Médicos)', 'pendiente');

-- Actividades IE1-P3
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(3, 'A1', 'Generar reportes de mercado con el análisis de tendencia', 'pendiente'),
(3, 'A2', 'Seguimiento de la productividad de la Fuerza de venta', 'pendiente'),
(3, 'A3', 'Brindar servicio para la capacitación de la FF en acuerdo con MKT/Comercial', 'pendiente'),
(3, 'A4', 'Co-creación y gestión del modelo y gestión de los incentivos de la FF', 'pendiente'),
(3, 'A5', 'Controlling financiero de la ejecución presupuestaria de los planes de MKT', 'pendiente'),
(3, 'A6', 'Seguimiento y evolución de inversiones promocionales', 'pendiente');

-- IE2: Optimización del Portfolio de Productos
INSERT INTO planes_accion (iniciativa_id, periodo_id, codigo, nombre, owner, estado, prioridad, peso) VALUES
(2, 1, 'P1', 'Plan de Lanzamiento Nuevas Moléculas', 'P. Serrago', 'en_progreso', 1, 60),
(2, 1, 'P2', 'Asegurar el lanzamiento de Productos en el corto plazo', NULL, 'en_progreso', 2, 40);

-- Actividades IE2-P1
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(4, 'A1', 'Identificar, analizar y seleccionar las nuevas moléculas', 'pendiente'),
(4, 'A2', 'Regulación y Cumplimiento', 'pendiente'),
(4, 'A3', 'Identificar y evaluar posibles alianzas comerciales con terceros', 'pendiente'),
(4, 'A4', 'Realizar un análisis financiero de corto y mediano plazo incluyendo los parámetros críticos', 'pendiente'),
(4, 'A5', 'Armar un equipo interdisciplinario para el análisis de las nuevas moléculas (participante final legales)', 'pendiente'),
(4, 'A6', 'Entregable: proyecto final para la firma del Gte Gral y la aprobación del directorio', 'pendiente');

-- Actividades IE2-P2
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(5, 'A1', 'Plan lanzamiento ejercicio actual', 'pendiente'),
(5, 'A2', 'Definición de cada etapa con acciones / área involucrada', 'pendiente'),
(5, 'A3', 'Seguimiento de la puesta en el mercado del producto', 'pendiente');

-- IE3: Desarrollo de Infraestructura
INSERT INTO planes_accion (iniciativa_id, periodo_id, codigo, nombre, owner, estado, prioridad, peso) VALUES
(3, 1, 'P1', 'Validación de Limpieza', 'A. Agliotta', 'en_progreso', 1, 10),
(3, 1, 'P2', 'Validación de Procesos Productivos', NULL, 'en_progreso', 2, 10),
(3, 1, 'P3', 'Validación de Sistemas', NULL, 'en_progreso', 3, 10),
(3, 1, 'P4', 'Optimización de tamaño de Lotes', 'N. Braulinese', 'en_progreso', 4, 15),
(3, 1, 'P5', 'Incorporación Sistema LIMS', 'E. Nordi', 'en_progreso', 5, 15),
(3, 1, 'P6', 'Aumento de capacidad operativa', NULL, 'en_progreso', 6, 15),
(3, 1, 'P7', 'Adecuación de Infraestructura', NULL, 'en_progreso', 7, 15),
(3, 1, 'P8', 'Asegurar abastecimiento de acuerdo al plan de ventas', 'D. Hernandez', 'pendiente', 8, 5),
(3, 1, 'P9', 'Asegurar el lanzamiento de Nuevas moléculas', 'E. Callisto', 'pendiente', 9, 5);

-- Actividades IE3-P1
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(6, 'A1', 'Armado & ejecución del Plan de Limpieza', 'pendiente');

-- Actividades IE3-P2
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(7, 'A1', 'Armado & ejecución del Plan de Procesos Productivos', 'pendiente');

-- Actividades IE3-P3
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(8, 'A1', 'Actualizar el inventario de sistemas de TL (GMP pendientes)', 'pendiente'),
(8, 'A2', 'Verificar que la calificación de la infraestructura se encuentre vigente', 'pendiente'),
(8, 'A3', 'Seguimiento de la puesta en el mercado del producto', 'pendiente');

-- Actividades IE3-P4
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(9, 'A1', 'Analizar tamaños de lote óptimo propuestos en función de las líneas de producción', 'pendiente'),
(9, 'A2', 'Armado de plan estratégico en función del plan de marketing', 'pendiente'),
(9, 'A3', 'Ejecución del Plan', 'pendiente');

-- Actividades IE3-P5
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(10, 'A1', 'Conciliación de GANTT con las partes involucradas', 'pendiente'),
(10, 'A2', 'Seguimiento de etapas de implementación de fase 1', 'pendiente'),
(10, 'A3', 'Gestión y adquisición de infraestructura y hardware', 'pendiente'),
(10, 'A4', 'Seguimiento de etapas de implementación de fase 2', 'pendiente');

-- Actividades IE3-P6
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(11, 'A1', 'Actualización de línea de óvulos hormonales', 'pendiente'),
(11, 'A2', 'Incorporación de Mezclador de Bines y generación de local de Tamizado/Molienda', 'pendiente'),
(11, 'A3', 'Generación de Turno Tarde de producción en centros críticos', 'pendiente');

-- Actividades IE3-P7
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(12, 'A1', 'Realizar la ingeniería de Instalación de Unidades de Tratamiento de Aire (UTA)', 'pendiente'),
(12, 'A2', 'Sustitución de 2 autoelevadores y rediseño del depósito', 'pendiente'),
(12, 'A3', 'Pintura y reparación de fachada, techos, canaletas', 'pendiente'),
(12, 'A4', 'Comedor', 'pendiente'),
(12, 'A5', 'Puesta en valor de recepción y oficinas', 'pendiente');

-- Actividades IE3-P8
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(13, 'A1', 'Generar una propuesta estratégica de política de inventario', 'pendiente');

-- Actividades IE3-P9
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(14, 'A1', 'Planificar la Investigación y el Desarrollo de los nuevos productos', 'pendiente');

-- IE4: Exportaciones
INSERT INTO planes_accion (iniciativa_id, periodo_id, codigo, nombre, owner, estado, prioridad, peso) VALUES
(4, 1, 'P1', 'Plan de expansión a nuevos territorios', 'M. Solda', 'en_progreso', 1, 60),
(4, 1, 'P2', 'Plan ampliación de oferta', NULL, 'pendiente', 2, 40);

-- Actividades IE4-P1
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(15, 'A1', 'Tener mapeado todo lo que hay que hacer para estar listos para el último Q 2026', 'pendiente');

-- IE5: Transformación Cultural
INSERT INTO planes_accion (iniciativa_id, periodo_id, codigo, nombre, owner, estado, prioridad, peso) VALUES
(5, 1, 'P1', 'Gestión de Desempeño', 'E. Moyano', 'en_progreso', 1, 30),
(5, 1, 'P2', 'Programas de Capacitación', NULL, 'en_progreso', 2, 25),
(5, 1, 'P3', 'Acciones de fidelización', NULL, 'en_progreso', 3, 20),
(5, 1, 'P4', 'Gestión de talento', NULL, 'en_progreso', 4, 25);

-- Actividades IE5-P1
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(17, 'A1', 'Lanzar el proceso de evaluación de desempeños (MVP reportes Gte Gral)', 'pendiente');

-- Actividades IE5-P2
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(18, 'A1', 'Presentar e implementar programa de capacitación para Líderes', 'pendiente');

-- Actividades IE5-P3
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(19, 'A1', 'Desayunos con Gte Gral', 'pendiente'),
(19, 'A2', 'Fortalecer Onboarding', 'pendiente'),
(19, 'A3', 'Focus group colaboradores', 'pendiente');

-- Actividades IE5-P4
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(20, 'A1', 'Plan de Sucesión & Talento (Mapear Posiciones Críticas, Talentos e identificar Cuadros de Reemplazo)', 'pendiente'),
(20, 'A2', 'Programa Equipo de Proyectos', 'pendiente'),
(20, 'A3', 'Programa de Pasantías universidades', 'pendiente');

-- IE6: Sostenibilidad Económica & Financiera
INSERT INTO planes_accion (iniciativa_id, periodo_id, codigo, nombre, owner, estado, prioridad, peso) VALUES
(6, 1, 'P1', 'Nuevo Presupuesto 2025/2026', 'A. Aliano', 'en_progreso', 1, 25),
(6, 1, 'P2', 'Plan de Optimización del Capital de trabajo', NULL, 'pendiente', 2, 20),
(6, 1, 'P3', 'Eficiencia de Costos', NULL, 'pendiente', 3, 25),
(6, 1, 'P4', 'Seguimiento de Inversiones', NULL, 'pendiente', 4, 15),
(6, 1, 'P5', 'Gestión de Deuda', NULL, 'pendiente', 5, 15);

-- Actividades IE6-P1
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(21, 'A1', 'Armar Presupuesto en función del plan Comercial & Marketing cross campaña. Presentación y Aprobación.', 'pendiente');

-- Actividades IE6-P2
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(22, 'A1', 'Diseñar reporte de monitoreo de Bienes de Cambio, Materia primas y Productos terminados', 'pendiente'),
(22, 'A2', 'Plan de mejora de la eficiencia en la gestión de Proveedores con compras', 'pendiente');

-- Actividades IE6-P3
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(23, 'A1', 'Evaluar alternativas de consultoras y avanzar con la contratación para contar con un plan de costeo', 'pendiente'),
(23, 'A2', 'Desarrollar un plan estratégico junto con el área de Planta, enfocado en la reducción de costos', 'pendiente'),
(23, 'A3', 'Desarrollar un plan de eficiencia en costos de insumos y materia prima en conjunto con el área de Compras', 'pendiente');

-- Actividades IE6-P4
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(24, 'A1', 'Diseñar e implementar un reporte de seguimiento. Monitorear repago de inversiones.', 'pendiente');

-- Actividades IE6-P5
INSERT INTO actividades (plan_accion_id, codigo, descripcion, estado) VALUES
(25, 'A1', 'Gestionar la negociación y renovación de préstamos con bancos de las mejores tasas disponibles', 'pendiente');

-- =====================================================
-- RELACIONES CAUSA-EFECTO entre IEs
-- =====================================================
INSERT INTO relaciones_causa_efecto (iniciativa_origen_id, iniciativa_destino_id, descripcion) VALUES
(5, 3, 'Transformación Cultural habilita mejor ejecución de Infraestructura'),
(5, 1, 'Capacitación y talento mejoran desempeño Comercial'),
(3, 2, 'Infraestructura productiva habilita nuevos productos'),
(2, 1, 'Nuevo portfolio fortalece oferta comercial'),
(1, 4, 'Fortaleza comercial habilita expansión exportadora'),
(1, 6, 'Transformación comercial impulsa resultados financieros'),
(4, 6, 'Exportaciones contribuyen a sostenibilidad financiera'),
(3, 6, 'Eficiencia productiva mejora resultados financieros');

-- =====================================================
-- KPIs de ejemplo
-- =====================================================
INSERT INTO kpis (iniciativa_id, periodo_id, nombre, tipo, unidad, meta, valor_actual, umbral_verde, umbral_amarillo, direccion, frecuencia) VALUES
-- IE1
(1, 1, 'Cobertura de Fuerza de Venta', 'cuantitativo', '%', 85.00, NULL, 90, 70, 'mayor_mejor', 'mensual'),
(1, 1, 'Frecuencia de visitas médicas', 'cuantitativo', 'visitas/mes', 12.00, NULL, 90, 70, 'mayor_mejor', 'mensual'),
(1, 1, 'Crecimiento Market Share Rx', 'cuantitativo', '%', 5.00, NULL, 90, 70, 'mayor_mejor', 'trimestral'),
-- IE2
(2, 1, 'Nuevas moléculas en pipeline', 'cuantitativo', 'unidades', 3.00, NULL, 90, 70, 'mayor_mejor', 'anual'),
(2, 1, 'Cumplimiento plan de lanzamientos', 'cuantitativo', '%', 100.00, NULL, 90, 70, 'mayor_mejor', 'trimestral'),
-- IE3
(3, 1, 'Cumplimiento GMP', 'cuantitativo', '%', 100.00, NULL, 95, 85, 'mayor_mejor', 'mensual'),
(3, 1, 'Tasa de error analítico', 'cuantitativo', '%', 0.50, NULL, 90, 70, 'menor_mejor', 'mensual'),
(3, 1, 'Avance implementación LIMS', 'cuantitativo', '%', 100.00, NULL, 90, 70, 'mayor_mejor', 'trimestral'),
-- IE4
(4, 1, 'Nuevos territorios habilitados', 'cuantitativo', 'países', 2.00, NULL, 90, 70, 'mayor_mejor', 'anual'),
-- IE5
(5, 1, 'Horas de capacitación por empleado', 'cuantitativo', 'horas', 40.00, NULL, 90, 70, 'mayor_mejor', 'anual'),
(5, 1, 'Evaluaciones de desempeño completadas', 'cuantitativo', '%', 100.00, NULL, 90, 70, 'mayor_mejor', 'anual'),
(5, 1, 'Clima laboral', 'cualitativo', NULL, NULL, NULL, NULL, NULL, NULL, 'anual'),
-- IE6
(6, 1, 'Crecimiento de ingresos', 'cuantitativo', '%', 15.00, NULL, 90, 70, 'mayor_mejor', 'trimestral'),
(6, 1, 'Margen operativo', 'cuantitativo', '%', 20.00, NULL, 90, 70, 'mayor_mejor', 'trimestral'),
(6, 1, 'ROI', 'cuantitativo', '%', 12.00, NULL, 90, 70, 'mayor_mejor', 'anual');

-- Configurar escala cualitativa para Clima laboral
UPDATE kpis SET escala_cualitativa = 'alto_medio_bajo', opciones_cualitativas = 'Alto,Medio,Bajo' WHERE nombre = 'Clima laboral';
