# Plan: Sección de Proyectos

## Concepto
Proyectos como entidades independientes que opcionalmente se vinculan a Iniciativas Estratégicas y/o Planes de Acción para trazabilidad.

## Base de datos

### Tabla `proyectos`
- id, nombre, descripcion, responsable, estado (pendiente/en_progreso/completado/cancelado/suspendido)
- fecha_inicio, fecha_fin, presupuesto (DECIMAL), prioridad (1-5), avance (INT 0-100)
- periodo_id (FK opcional a periodos_estrategicos)
- created_at

### Tabla `proyecto_vinculos` (relación muchos-a-muchos)
- id, proyecto_id (FK), entidad_tipo ENUM('iniciativa','plan_accion'), entidad_id

### Tabla `proyecto_entregables`
- id, proyecto_id (FK), nombre, descripcion, fecha_prevista, fecha_real
- estado (pendiente/en_progreso/completado), responsable

## Archivos a crear/modificar

### Crear:
1. `models/Proyecto.php` — CRUD + vínculos + entregables
2. `views/proyectos/index.php` — Lista de proyectos con cards o tabla
3. `views/proyectos/form.php` — Formulario crear/editar
4. `views/proyectos/detalle.php` — Vista detalle con vínculos, entregables, adjuntos

### Modificar:
5. `database/schema.sql` — Agregar las 3 tablas
6. `index.php` — Agregar case 'proyectos' y 'proyecto_entregables'
7. `views/layout/header.php` — Agregar item en sidebar
