-- ============================================================================
-- DIAGNOSTICO SIMPLE - VERSION CORREGIDA
-- ============================================================================
-- Solo lectura, NO modifica datos
-- Ejecutar PRIMERO para ver qué problemas hay
-- ============================================================================

SET @shop_id = 1;
SET @lang_id = 1;

-- ============================================================================
-- 1. RESUMEN GENERAL
-- ============================================================================

SELECT '=== RESUMEN GENERAL ===' AS Seccion;

SELECT 
    'Total productos en base de datos' AS Metrica,
    COUNT(*) AS Cantidad
FROM ps_product;

SELECT 
    'Productos activos' AS Metrica,
    COUNT(*) AS Cantidad
FROM ps_product 
WHERE active = 1;

SELECT 
    'Productos inactivos' AS Metrica,
    COUNT(*) AS Cantidad
FROM ps_product 
WHERE active = 0;

-- ============================================================================
-- 2. PROBLEMAS EN ps_product_shop (CRITICO)
-- ============================================================================

SELECT '=== PROBLEMA 1: ps_product_shop ===' AS Seccion;

SELECT 
    'Productos CON ps_product_shop' AS Estado,
    COUNT(DISTINCT id_product) AS Cantidad
FROM ps_product_shop
WHERE id_shop = @shop_id;

SELECT 
    'Productos SIN ps_product_shop' AS Estado,
    COUNT(*) AS Cantidad
FROM ps_product p
WHERE NOT EXISTS (
    SELECT 1 FROM ps_product_shop ps 
    WHERE ps.id_product = p.id_product 
    AND ps.id_shop = @shop_id
);

-- ============================================================================
-- 3. PROBLEMAS DE VISIBILIDAD
-- ============================================================================

SELECT '=== PROBLEMA 2: Visibilidad ===' AS Seccion;

SELECT 
    visibility AS Tipo_Visibilidad,
    COUNT(*) AS Cantidad
FROM ps_product
GROUP BY visibility;

SELECT 
    'Productos con visibility=none (OCULTOS)' AS Problema,
    COUNT(*) AS Cantidad
FROM ps_product 
WHERE visibility = 'none';

-- ============================================================================
-- 4. PROBLEMAS DE CATEGORIAS
-- ============================================================================

SELECT '=== PROBLEMA 3: Categorías ===' AS Seccion;

SELECT 
    'Productos SIN categoría asignada' AS Problema,
    COUNT(*) AS Cantidad
FROM ps_product p
WHERE NOT EXISTS (
    SELECT 1 FROM ps_category_product cp 
    WHERE cp.id_product = p.id_product
);

SELECT 
    'Productos con categoría inválida' AS Problema,
    COUNT(*) AS Cantidad
FROM ps_product p
WHERE p.id_category_default NOT IN (SELECT id_category FROM ps_category)
   OR p.id_category_default IS NULL
   OR p.id_category_default = 0;

-- ============================================================================
-- 5. PROBLEMAS DE PERMISOS
-- ============================================================================

SELECT '=== PROBLEMA 4: Permisos de categorías ===' AS Seccion;

SELECT 
    'Categorías activas' AS Tipo,
    COUNT(*) AS Cantidad
FROM ps_category
WHERE active = 1;

SELECT 
    'Categorías CON permisos' AS Tipo,
    COUNT(DISTINCT id_category) AS Cantidad
FROM ps_category_group;

SELECT 
    'Categorías SIN permisos (invisibles)' AS Problema,
    COUNT(*) AS Cantidad
FROM ps_category c
WHERE c.active = 1
AND NOT EXISTS (
    SELECT 1 FROM ps_category_group cg 
    WHERE cg.id_category = c.id_category
);

-- ============================================================================
-- 6. PROBLEMAS DE URLs
-- ============================================================================

SELECT '=== PROBLEMA 5: URLs ===' AS Seccion;

SELECT 
    'Productos CON URL válida' AS Estado,
    COUNT(*) AS Cantidad
FROM ps_product_lang
WHERE link_rewrite IS NOT NULL 
AND link_rewrite != '' 
AND link_rewrite != '-';

SELECT 
    'Productos SIN URL (no accesibles)' AS Problema,
    COUNT(*) AS Cantidad
FROM ps_product_lang
WHERE link_rewrite IS NULL 
   OR link_rewrite = '' 
   OR link_rewrite = '-';

-- ============================================================================
-- 7. LISTADO DE PRODUCTOS PROBLEMATICOS
-- ============================================================================

SELECT '=== PRODUCTOS CON PROBLEMAS (PRIMEROS 5) ===' AS Seccion;

SELECT 
    p.id_product,
    pl.name AS nombre,
    p.active AS activo,
    p.visibility AS visibilidad,
    CASE 
        WHEN ps.id_product IS NULL THEN 'NO (CRITICO)'
        ELSE 'SI'
    END AS tiene_shop,
    CASE 
        WHEN cp.id_product IS NULL THEN 'NO (CRITICO)'
        ELSE 'SI'
    END AS tiene_categoria,
    CASE 
        WHEN pl.link_rewrite IS NULL OR pl.link_rewrite = '' THEN 'NO'
        ELSE 'SI'
    END AS tiene_url
FROM ps_product p
LEFT JOIN ps_product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = @lang_id
LEFT JOIN ps_product_shop ps ON p.id_product = ps.id_product AND ps.id_shop = @shop_id
LEFT JOIN ps_category_product cp ON p.id_product = cp.id_product
WHERE ps.id_product IS NULL
   OR cp.id_product IS NULL
   OR p.visibility = 'none'
   OR p.active = 0
LIMIT 5;

-- ============================================================================
-- 8. RECOMENDACION
-- ============================================================================

SELECT '=== RECOMENDACION ===' AS Seccion;

SELECT 
    CASE
        WHEN (SELECT COUNT(*) FROM ps_product p WHERE NOT EXISTS (
            SELECT 1 FROM ps_product_shop ps WHERE ps.id_product = p.id_product AND ps.id_shop = @shop_id
        )) > 0 THEN 'EJECUTA: RECUPERAR_PRODUCTOS_FIXED.sql'
        WHEN (SELECT COUNT(*) FROM ps_product WHERE visibility = 'none') > 0 THEN 'EJECUTA: RECUPERAR_PRODUCTOS_FIXED.sql'
        WHEN (SELECT COUNT(*) FROM ps_product WHERE active = 0) > 0 THEN 'EJECUTA: RECUPERAR_PRODUCTOS_FIXED.sql'
        ELSE 'Todo OK - Solo limpia cache y regenera indice'
    END AS Accion_Recomendada;

SELECT '=== FIN DIAGNOSTICO ===' AS Fin;
