-- Migración: Análisis IE - Tablero de diseño y análisis estratégico

CREATE TABLE IF NOT EXISTS analisis_hojas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL DEFAULT 'Hoja 1',
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar hoja por defecto
INSERT INTO analisis_hojas (nombre, orden) VALUES ('Hoja 1', 0);

CREATE TABLE IF NOT EXISTS analisis_nodos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hoja_id INT NOT NULL DEFAULT 1,
    nombre VARCHAR(200) NOT NULL,
    tipo ENUM('iniciativa','plan','habilitador','riesgo','restriccion','kpi','entidad') NOT NULL,
    descripcion TEXT,
    estado VARCHAR(50) DEFAULT 'activo',
    observaciones TEXT,
    forma VARCHAR(30) DEFAULT 'box',
    color VARCHAR(20) DEFAULT '#4A90D9',
    tamano ENUM('S','M','L','XL') DEFAULT 'M',
    pos_x FLOAT DEFAULT NULL,
    pos_y FLOAT DEFAULT NULL,
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hoja_id) REFERENCES analisis_hojas(id) ON DELETE CASCADE
);

-- Extender ENUM de bitacora para incluir los nuevos tipos
ALTER TABLE bitacora MODIFY COLUMN entidad_tipo
    ENUM('iniciativa','plan_accion','actividad','kpi','proyecto','riesgo','reunion','compromiso','analisis_nodo','analisis_conexion') NOT NULL;

CREATE TABLE IF NOT EXISTS analisis_conexiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nodo_origen_id INT NOT NULL,
    nodo_destino_id INT NOT NULL,
    tipo_relacion ENUM('fortalece','debilita','activa','inhibe','genera','elimina','acelera','demora','expande','contrae','habilita','bloquea','aumenta','reduce','integra','fragmenta','depende','no_depende','amplifica','amortigua') NOT NULL,
    descripcion VARCHAR(200) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nodo_origen_id) REFERENCES analisis_nodos(id) ON DELETE CASCADE,
    FOREIGN KEY (nodo_destino_id) REFERENCES analisis_nodos(id) ON DELETE CASCADE
);
