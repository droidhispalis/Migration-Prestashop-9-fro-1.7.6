-- ============================================================================
-- SCRIPT DE RECUPERACION DE PRODUCTOS PERDIDOS
-- ============================================================================
-- SI EJECUTASTE FIX_SIMPLE.sql Y LOS PRODUCTOS DESAPARECIERON
-- ESTE SCRIPT RESTAURA LA VISIBILIDAD SIN REINSTALAR
-- ============================================================================

SET @shop_id = 1;
SET @lang_id = 1;

-- ============================================================================
-- DIAGNOSTICO: ¿QUE PASO?
-- ============================================================================

SELECT '=== DIAGNOSTICO: PRODUCTOS EN BASE DE DATOS ===' AS Info;

SELECT 
    'Productos en ps_product (tabla principal)' AS Tabla,
    COUNT(*) AS Total,
    SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) AS Activos,
    SUM(CASE WHEN active = 0 THEN 1 ELSE 0 END) AS Inactivos
FROM ps_product;

SELECT 
    'Productos en ps_product_shop (relación con tienda)' AS Tabla,
    COUNT(*) AS Total,
    SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) AS Activos,
    SUM(CASE WHEN active = 0 THEN 1 ELSE 0 END) AS Inactivos
FROM ps_product_shop
WHERE id_shop = @shop_id;

SELECT 
    'Productos SIN entrada en ps_product_shop' AS Problema,
    COUNT(*) AS Afectados
FROM ps_product p
LEFT JOIN ps_product_shop ps ON p.id_product = ps.id_product AND ps.id_shop = @shop_id
WHERE ps.id_product IS NULL;

SELECT 
    'Productos con visibility = "none"' AS Problema,
    COUNT(*) AS Afectados
FROM ps_product
WHERE visibility = 'none';

SELECT 
    'Productos SIN categoría asignada' AS Problema,
    COUNT(DISTINCT p.id_product) AS Afectados
FROM ps_product p
LEFT JOIN ps_category_product cp ON p.id_product = cp.id_product
WHERE cp.id_product IS NULL;

-- ============================================================================
-- SOLUCION 1: RESTAURAR ENTRADAS EN ps_product_shop
-- ============================================================================

SELECT '=== SOLUCION 1: Restaurando ps_product_shop ===' AS Accion;

-- Crear entradas para TODOS los productos que no tienen
INSERT INTO ps_product_shop (
    id_product,
    id_shop,
    id_category_default,
    id_tax_rules_group,
    price,
    wholesale_price,
    active,
    available_for_order,
    show_price,
    visibility,
    indexed,
    date_add,
    date_upd
)
SELECT 
    p.id_product,
    @shop_id,
    COALESCE(p.id_category_default, 2),
    COALESCE(p.id_tax_rules_group, 1),
    COALESCE(p.price, 0.000000),
    COALESCE(p.wholesale_price, 0.000000),
    1,  -- FORZAR ACTIVO
    1,  -- DISPONIBLE PARA PEDIDO
    1,  -- MOSTRAR PRECIO
    'both',  -- VISIBLE EN BUSQUEDA Y CATALOGO
    0,
    COALESCE(p.date_add, NOW()),
    NOW()
FROM ps_product p
WHERE NOT EXISTS (
    SELECT 1 FROM ps_product_shop ps 
    WHERE ps.id_product = p.id_product AND ps.id_shop = @shop_id
);

SELECT CONCAT('✓ Restauradas ', ROW_COUNT(), ' entradas en ps_product_shop') AS Resultado;

-- ============================================================================
-- SOLUCION 2: ACTIVAR TODOS LOS PRODUCTOS
-- ============================================================================

SELECT '=== SOLUCION 2: Activando productos ===' AS Accion;

UPDATE ps_product
SET 
    active = 1,
    visibility = 'both',
    available_for_order = 1,
    show_price = 1,
    indexed = 0
WHERE id_product > 0;

SELECT CONCAT('✓ Activados ', ROW_COUNT(), ' productos en ps_product') AS Resultado;

UPDATE ps_product_shop
SET 
    active = 1,
    visibility = 'both',
    available_for_order = 1,
    show_price = 1,
    indexed = 0
WHERE id_shop = @shop_id;

SELECT CONCAT('✓ Activados ', ROW_COUNT(), ' productos en ps_product_shop') AS Resultado;

-- ============================================================================
-- SOLUCION 3: ASIGNAR A CATEGORIA POR DEFECTO
-- ============================================================================

SELECT '=== SOLUCION 3: Asignando categorías ===' AS Accion;

-- Corregir categoria_default en productos sin categoría
UPDATE ps_product
SET id_category_default = 2
WHERE id_category_default IS NULL OR id_category_default = 0 OR id_category_default NOT IN (SELECT id_category FROM ps_category);

SELECT CONCAT('✓ Corregidas ', ROW_COUNT(), ' categorías por defecto en ps_product') AS Resultado;

-- Sincronizar en ps_product_shop
UPDATE ps_product_shop ps
INNER JOIN ps_product p ON p.id_product = ps.id_product
SET ps.id_category_default = p.id_category_default
WHERE ps.id_shop = @shop_id
AND (ps.id_category_default IS NULL OR ps.id_category_default = 0 OR ps.id_category_default != p.id_category_default);

SELECT CONCAT('✓ Sincronizadas ', ROW_COUNT(), ' categorías en ps_product_shop') AS Resultado;

-- Asignar productos a sus categorías en ps_category_product
INSERT IGNORE INTO ps_category_product (id_category, id_product, position)
SELECT 
    p.id_category_default,
    p.id_product,
    COALESCE((SELECT MAX(position) + 1 FROM ps_category_product cp WHERE cp.id_category = p.id_category_default), 0)
FROM ps_product p
WHERE NOT EXISTS (
    SELECT 1 FROM ps_category_product cp WHERE cp.id_product = p.id_product
);

SELECT CONCAT('✓ Asignados ', ROW_COUNT(), ' productos a ps_category_product') AS Resultado;

-- ============================================================================
-- SOLUCION 4: RESTAURAR PERMISOS DE CATEGORIAS
-- ============================================================================

SELECT '=== SOLUCION 4: Restaurando permisos de categorías ===' AS Accion;

-- Asegurar que ps_category_group existe
CREATE TABLE IF NOT EXISTS ps_category_group (
  `id_category` int(10) unsigned NOT NULL,
  `id_group` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id_category`,`id_group`),
  KEY `id_category` (`id_category`),
  KEY `id_group` (`id_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dar permisos a TODAS las categorías (grupos 1, 2, 3)
INSERT IGNORE INTO ps_category_group (id_category, id_group)
SELECT c.id_category, 1 FROM ps_category c WHERE c.active = 1;

INSERT IGNORE INTO ps_category_group (id_category, id_group)
SELECT c.id_category, 2 FROM ps_category c WHERE c.active = 1;

INSERT IGNORE INTO ps_category_group (id_category, id_group)
SELECT c.id_category, 3 FROM ps_category c WHERE c.active = 1;

SELECT '✓ Permisos restaurados para todas las categorías' AS Resultado;

-- ============================================================================
-- SOLUCION 5: GENERAR URLs PARA PRODUCTOS SIN LINK_REWRITE
-- ============================================================================

SELECT '=== SOLUCION 5: Generando URLs ===' AS Accion;

UPDATE ps_product_lang pl
INNER JOIN ps_product p ON p.id_product = pl.id_product
SET pl.link_rewrite = CONCAT(
    LOWER(
        REGEXP_REPLACE(
            TRIM(pl.name),
            '[^a-zA-Z0-9]+',
            '-'
        )
    ),
    '-', pl.id_product
)
WHERE (pl.link_rewrite IS NULL OR pl.link_rewrite = '' OR pl.link_rewrite = '-')
AND pl.name IS NOT NULL AND pl.name != '';

SELECT CONCAT('✓ Generadas ', ROW_COUNT(), ' URLs de productos') AS Resultado;

-- ============================================================================
-- VERIFICACION FINAL
-- ============================================================================

SELECT '=== VERIFICACION POST-RECUPERACION ===' AS Info;

SELECT 
    'Productos activos en ps_product' AS Estado,
    COUNT(*) AS Cantidad
FROM ps_product WHERE active = 1;

SELECT 
    'Productos activos en ps_product_shop' AS Estado,
    COUNT(*) AS Cantidad
FROM ps_product_shop WHERE active = 1 AND id_shop = @shop_id;

SELECT 
    'Productos con visibility = "both"' AS Estado,
    COUNT(*) AS Cantidad
FROM ps_product WHERE visibility = 'both';

SELECT 
    'Productos asignados a categorías' AS Estado,
    COUNT(DISTINCT id_product) AS Cantidad
FROM ps_category_product;

SELECT 
    'Categorías con permisos (ps_category_group)' AS Estado,
    COUNT(DISTINCT id_category) AS Cantidad,
    COUNT(*) AS Total_Permisos
FROM ps_category_group;

-- ============================================================================
-- INSTRUCCIONES CRITICAS
-- ============================================================================

SELECT '=== PASOS OBLIGATORIOS AHORA ===' AS Info;
SELECT '1. Back Office → Parámetros Avanzados → Rendimiento → Limpiar caché' AS Paso;
SELECT '2. Back Office → Preferencias → Buscar → Regenerar índice de búsqueda' AS Paso;
SELECT '3. Back Office → Catálogo → Productos → Refrescar página (F5)' AS Paso;
SELECT '4. Front Office → Ir a la tienda → Verificar productos visibles' AS Paso;
SELECT '5. Si persiste problema, ejecuta: DIAGNOSTIC_PRODUCTOS_DETALLE.sql' AS Paso;

SELECT '=== RECUPERACION COMPLETADA ===' AS Estado;
SELECT 'IMPORTANTE: Limpia caché AHORA antes de verificar' AS Aviso;
