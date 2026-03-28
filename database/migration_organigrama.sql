-- Migración: Agregar jerarquía al organigrama CODI
-- Ejecutar sobre la base de datos existente

ALTER TABLE responsables ADD COLUMN reporta_a_id INT DEFAULT NULL AFTER cargo;
ALTER TABLE responsables ADD CONSTRAINT fk_responsable_reporta_a FOREIGN KEY (reporta_a_id) REFERENCES responsables(id) ON DELETE SET NULL;
