-- Agregar campo es_entero a kpis para controlar si el valor se muestra como entero o decimal
ALTER TABLE kpis ADD COLUMN es_entero TINYINT(1) NOT NULL DEFAULT 0 AFTER direccion;
