-- Renombrar perspectiva "Cliente" a "Negocio"
UPDATE perspectivas SET nombre = 'Negocio' WHERE nombre = 'Cliente';
