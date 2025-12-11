-- ============================================================================
-- DIAGNOSTICO DETALLADO DE PRODUCTOS
-- ============================================================================
-- Este script NO modifica nada, solo muestra información
-- Úsalo para entender QUÉ salió mal
-- ============================================================================

SET @shop_id = 1;

SELECT '╔═══════════════════════════════════════════════════════════════╗' AS '';
SELECT '║  DIAGNOSTICO COMPLETO DE PRODUCTOS - MODO LECTURA SOLO      ║' AS '';
SELECT '╚═══════════════════════════════════════════════════════════════╝' AS '';

-- ============================================================================
-- 1. RESUMEN GENERAL
-- ============================================================================

SELECT '--- 1. RESUMEN GENERAL ---' AS '';

SELECT 
    'Total productos' AS Metrica,
    COUNT(*) AS Valor,
    CONCAT('IDs: ', MIN(id_product), ' a ', MAX(id_product)) AS Rango
FROM ps_product;

SELECT 
    'Productos activos (active=1)' AS Metrica,
    COUNT(*) AS Valor,
    CONCAT(ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM ps_product), 1), '%') AS Porcentaje
FROM ps_product WHERE active = 1;

SELECT 
    'Productos inactivos (active=0)' AS Metrica,
    COUNT(*) AS Valor,
    CONCAT(ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM ps_product), 1), '%') AS Porcentaje
FROM ps_product WHERE active = 0;

-- ============================================================================
-- 2. PROBLEMAS EN ps_product
-- ============================================================================

SELECT '--- 2. PROBLEMAS EN ps_product ---' AS '';

SELECT 
    'Productos sin id_category_default' AS Problema,
    COUNT(*) AS Afectados,
    CASE WHEN COUNT(*) > 0 THEN '⚠️ CRITICO' ELSE '✓ OK' END AS Estado
FROM ps_product
WHERE id_category_default IS NULL OR id_category_default = 0;

SELECT 
    'Productos con visibility = "none"' AS Problema,
    COUNT(*) AS Afectados,
    CASE WHEN COUNT(*) > 0 THEN '⚠️ PROBLEMA' ELSE '✓ OK' END AS Estado
FROM ps_product
WHERE visibility = 'none';

SELECT 
    'Productos con visibility != "both"' AS Problema,
    COUNT(*) AS Afectados,
    GROUP_CONCAT(DISTINCT visibility) AS Valores
FROM ps_product
WHERE visibility != 'both' AND active = 1;

SELECT 
    'Productos activos sin available_for_order' AS Problema,
    COUNT(*) AS Afectados,
    CASE WHEN COUNT(*) > 0 THEN '⚠️ PROBLEMA' ELSE '✓ OK' END AS Estado
FROM ps_product
WHERE active = 1 AND (available_for_order IS NULL OR available_for_order = 0);

-- ============================================================================
-- 3. PROBLEMAS EN ps_product_shop
-- ============================================================================

SELECT '--- 3. PROBLEMAS EN ps_product_shop ---' AS '';

SELECT 
    'Productos SIN entrada en ps_product_shop' AS Problema,
    COUNT(*) AS Afectados,
    CASE WHEN COUNT(*) > 0 THEN '🔴 CRITICO - Productos invisibles' ELSE '✓ OK' END AS Estado
FROM ps_product p
LEFT JOIN ps_product_shop ps ON p.id_product = ps.id_product AND ps.id_shop = @shop_id
WHERE ps.id_product IS NULL;

SELECT 
    'Productos con diferente "active" entre ps_product y ps_product_shop' AS Problema,
    COUNT(*) AS Afectados
FROM ps_product p
INNER JOIN ps_product_shop ps ON p.id_product = ps.id_product AND ps.id_shop = @shop_id
WHERE p.active != ps.active;

SELECT 
    'Productos en ps_product_shop con visibility != "both"' AS Problema,
    COUNT(*) AS Afectados,
    GROUP_CONCAT(DISTINCT ps.visibility) AS Valores
FROM ps_product_shop ps
WHERE ps.id_shop = @shop_id AND ps.visibility != 'both' AND ps.active = 1;

-- ============================================================================
-- 4. PROBLEMAS EN ps_category_product
-- ============================================================================

SELECT '--- 4. PROBLEMAS EN ps_category_product ---' AS '';

SELECT 
    'Productos SIN asignación a categoría' AS Problema,
    COUNT(*) AS Afectados,
    CASE WHEN COUNT(*) > 0 THEN '🔴 CRITICO - No aparecerán en catálogo' ELSE '✓ OK' END AS Estado
FROM ps_product p
LEFT JOIN ps_category_product cp ON p.id_product = cp.id_product
WHERE cp.id_product IS NULL AND p.active = 1;

SELECT 
    'Productos activos con categorías' AS Info,
    COUNT(DISTINCT cp.id_product) AS Total
FROM ps_category_product cp
INNER JOIN ps_product p ON p.id_product = cp.id_product
WHERE p.active = 1;

-- ============================================================================
-- 5. PROBLEMAS EN ps_category_group (PERMISOS)
-- ============================================================================

SELECT '--- 5. PROBLEMAS EN ps_category_group ---' AS '';

SELECT 
    'Tabla ps_category_group existe?' AS Verificacion,
    CASE 
        WHEN COUNT(*) > 0 THEN CONCAT('✓ SI - ', COUNT(*), ' permisos configurados')
        ELSE '🔴 NO EXISTE - Productos invisibles'
    END AS Estado
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ps_category_group';

SELECT 
    'Categorías activas SIN permisos' AS Problema,
    COUNT(*) AS Afectadas,
    CASE WHEN COUNT(*) > 0 THEN '🔴 CRITICO - Categorías invisibles' ELSE '✓ OK' END AS Estado
FROM ps_category c
LEFT JOIN ps_category_group cg ON c.id_category = cg.id_category
WHERE c.active = 1 AND cg.id_category IS NULL;

SELECT 
    'Total permisos por grupo' AS Info,
    id_group,
    COUNT(DISTINCT id_category) AS Categorias_Con_Permiso,
    CASE 
        WHEN id_group = 1 THEN 'Visitantes'
        WHEN id_group = 2 THEN 'Invitados'
        WHEN id_group = 3 THEN 'Clientes'
        ELSE CONCAT('Grupo ', id_group)
    END AS Nombre_Grupo
FROM ps_category_group
GROUP BY id_group;

-- ============================================================================
-- 6. PROBLEMAS EN ps_product_lang
-- ============================================================================

SELECT '--- 6. PROBLEMAS EN ps_product_lang ---' AS '';

SELECT 
    'Productos sin nombre (name vacío)' AS Problema,
    COUNT(DISTINCT pl.id_product) AS Afectados
FROM ps_product_lang pl
INNER JOIN ps_product p ON p.id_product = pl.id_product
WHERE (pl.name IS NULL OR pl.name = '') AND p.active = 1;

SELECT 
    'Productos sin link_rewrite (URL)' AS Problema,
    COUNT(DISTINCT pl.id_product) AS Afectados,
    CASE WHEN COUNT(*) > 0 THEN '⚠️ PROBLEMA - URLs no funcionarán' ELSE '✓ OK' END AS Estado
FROM ps_product_lang pl
INNER JOIN ps_product p ON p.id_product = pl.id_product
WHERE (pl.link_rewrite IS NULL OR pl.link_rewrite = '' OR pl.link_rewrite = '-') 
AND p.active = 1;

-- ============================================================================
-- 7. LISTADO DE PRODUCTOS PROBLEMÁTICOS (PRIMEROS 10)
-- ============================================================================

SELECT '--- 7. EJEMPLOS DE PRODUCTOS CON PROBLEMAS ---' AS '';

SELECT 
    p.id_product,
    pl.name AS Nombre,
    p.active AS Activo_Product,
    COALESCE(ps.active, 'SIN_ENTRADA') AS Activo_ProductShop,
    p.visibility AS Visibility,
    p.id_category_default AS Categoria,
    CASE WHEN cp.id_product IS NULL THEN 'NO' ELSE 'SI' END AS Tiene_Categoria_Asignada,
    CASE WHEN cg.id_category IS NULL THEN 'NO' ELSE 'SI' END AS Categoria_Tiene_Permisos
FROM ps_product p
LEFT JOIN ps_product_shop ps ON p.id_product = ps.id_product AND ps.id_shop = @shop_id
LEFT JOIN ps_product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = 1
LEFT JOIN ps_category_product cp ON p.id_product = cp.id_product
LEFT JOIN ps_category_group cg ON p.id_category_default = cg.id_category AND cg.id_group = 1
WHERE p.active = 1
AND (
    ps.id_product IS NULL  -- Sin entrada en ps_product_shop
    OR p.visibility != 'both'
    OR cp.id_product IS NULL  -- Sin categoría asignada
    OR cg.id_category IS NULL  -- Categoría sin permisos
)
LIMIT 10;

-- ============================================================================
-- 8. RESUMEN DE SOLUCIONES NECESARIAS
-- ============================================================================

SELECT '╔═══════════════════════════════════════════════════════════════╗' AS '';
SELECT '║  RESUMEN: QUE SCRIPTS EJECUTAR                               ║' AS '';
SELECT '╚═══════════════════════════════════════════════════════════════╝' AS '';

SELECT 
    CASE 
        WHEN (SELECT COUNT(*) FROM ps_product p LEFT JOIN ps_product_shop ps ON p.id_product = ps.id_product AND ps.id_shop = @shop_id WHERE ps.id_product IS NULL) > 0
        THEN '🔴 EJECUTAR: RECUPERAR_PRODUCTOS.sql (productos sin ps_product_shop)'
        ELSE '✓ OK: Todos los productos tienen entrada en ps_product_shop'
    END AS Accion_1;

SELECT 
    CASE 
        WHEN (SELECT COUNT(*) FROM ps_category c LEFT JOIN ps_category_group cg ON c.id_category = cg.id_category WHERE c.active = 1 AND cg.id_category IS NULL) > 0
        THEN '🔴 EJECUTAR: CREATE_CATEGORY_GROUP.sql (categorías sin permisos)'
        ELSE '✓ OK: Todas las categorías tienen permisos'
    END AS Accion_2;

SELECT 
    CASE 
        WHEN (SELECT COUNT(*) FROM ps_product WHERE active = 1 AND visibility != 'both') > 0
        THEN '⚠️ EJECUTAR: FIX_PRODUCTOS_SEGURO.sql (corregir visibilidad)'
        ELSE '✓ OK: Visibilidad correcta'
    END AS Accion_3;

SELECT '--- ORDEN DE EJECUCION RECOMENDADO ---' AS '';
SELECT '1. RECUPERAR_PRODUCTOS.sql (SI productos desaparecieron)' AS Paso;
SELECT '2. FIX_PRODUCTOS_SEGURO.sql (corregir visibilidad y asignaciones)' AS Paso;
SELECT '3. Back Office → Limpiar caché' AS Paso;
SELECT '4. Back Office → Regenerar índice de búsqueda' AS Paso;

SELECT '╔═══════════════════════════════════════════════════════════════╗' AS '';
SELECT '║  DIAGNOSTICO COMPLETADO                                      ║' AS '';
SELECT '╚═══════════════════════════════════════════════════════════════╝' AS '';
