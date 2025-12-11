-- ============================================================================
-- CREAR TABLA ps_category_group SI NO EXISTE
-- ============================================================================
-- Esta tabla es CRITICA para los permisos de visualización de productos
-- Si no existe, los productos darán error "No tiene acceso a este producto"
-- ============================================================================

-- Crear tabla si no existe (CREATE TABLE IF NOT EXISTS es seguro)
CREATE TABLE IF NOT EXISTS `ps_category_group` (
  `id_category` int(10) unsigned NOT NULL,
  `id_group` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id_category`,`id_group`),
  KEY `id_category` (`id_category`),
  KEY `id_group` (`id_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Tabla ps_category_group creada correctamente' AS resultado;

-- ============================================================================
-- ASIGNAR PERMISOS A TODAS LAS CATEGORIAS
-- ============================================================================
-- Dar acceso a los 3 grupos principales: Visitantes (1), Invitados (2), Clientes (3)
-- ============================================================================

-- Insertar permisos para todas las categorías activas (INSERT IGNORE previene errores de duplicados)
INSERT IGNORE INTO ps_category_group (id_category, id_group)
SELECT 
    c.id_category,
    g.id_group
FROM ps_category c
CROSS JOIN (
    SELECT 1 AS id_group
    UNION ALL SELECT 2
    UNION ALL SELECT 3
) g
WHERE c.active = 1;

SELECT 'Permisos asignados a todas las categorias activas' AS resultado;

-- ============================================================================
-- VERIFICACION
-- ============================================================================

SELECT 
    COUNT(DISTINCT id_category) AS categorias_con_permisos,
    COUNT(*) AS total_permisos,
    COUNT(DISTINCT id_group) AS grupos_activos
FROM ps_category_group;

-- ============================================================================
-- INSTRUCCIONES POST-EJECUCION
-- ============================================================================
-- 
-- PERMISOS ASIGNADOS:
-- - Grupo 1 (Visitantes): Usuarios no registrados
-- - Grupo 2 (Invitados): Usuarios sin cuenta  
-- - Grupo 3 (Clientes): Clientes registrados
--
-- PROXIMOS PASOS OBLIGATORIOS:
--
-- 1. LIMPIAR CACHE:
--    Back Office > Parametros Avanzados > Rendimiento > Limpiar cache
--
-- 2. REGENERAR INDICE:
--    Back Office > Preferencias > Buscar > Regenerar indice
--
-- 3. VERIFICAR:
--    - Ve al Front Office
--    - Los productos deben ser visibles ahora
--
-- 4. Si persisten problemas, ejecuta FIX_SIMPLE.sql
--
-- ============================================================================
