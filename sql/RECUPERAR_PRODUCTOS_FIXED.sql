-- ============================================================================
-- SCRIPT DE RECUPERACION DE PRODUCTOS PERDIDOS - VERSION CORREGIDA
-- ============================================================================
-- INSTRUCCIONES:
-- 1. Ejecuta este script completo en phpMyAdmin
-- 2. Selecciona PRIMERO tu base de datos (migration, toprelieve, etc.)
-- 3. NO ejecutes línea por línea
-- 4. Después: Limpiar caché + Regenerar índice de búsqueda
-- ============================================================================

-- ============================================================================
-- PASO 0: CONFIGURACION
-- ============================================================================
-- Cambia estos valores según tu instalación:
SET @shop_id = 1;
SET @lang_id = 1;

-- ============================================================================
-- PASO 1: DIAGNOSTICO INICIAL
-- ============================================================================

SELECT '=== DIAGNOSTICO: ESTADO ACTUAL ===' AS Info;

SELECT 
    'Total productos' AS Descripcion,
    COUNT(*) AS Cantidad
FROM ps_product;

SELECT 
    'Productos activos (ps_product)' AS Descripcion,
    COUNT(*) AS Cantidad
FROM ps_product 
WHERE active = 1;

SELECT 
    'Productos en ps_product_shop' AS Descripcion,
    COUNT(DISTINCT id_product) AS Cantidad
FROM ps_product_shop
WHERE id_shop = @shop_id;

SELECT 
    'Productos SIN ps_product_shop' AS Descripcion,
    COUNT(*) AS Cantidad
FROM ps_product p
WHERE NOT EXISTS (
    SELECT 1 FROM ps_product_shop ps 
    WHERE ps.id_product = p.id_product 
    AND ps.id_shop = @shop_id
);

SELECT 
    'Productos con visibility=none' AS Descripcion,
    COUNT(*) AS Cantidad
FROM ps_product 
WHERE visibility = 'none';

SELECT 
    'Productos SIN categoría' AS Descripcion,
    COUNT(*) AS Cantidad
FROM ps_product p
WHERE NOT EXISTS (
    SELECT 1 FROM ps_category_product cp 
    WHERE cp.id_product = p.id_product
);

-- ============================================================================
-- PASO 2: RESTAURAR ps_product_shop (CRITICO)
-- ============================================================================

SELECT '=== PASO 2: Restaurando ps_product_shop ===' AS Accion;

-- Insertar SOLO productos que NO tienen entrada en ps_product_shop
INSERT INTO ps_product_shop (
    id_product,
    id_shop,
    id_category_default,
    id_tax_rules_group,
    price,
    wholesale_price,
    active,
    available_for_order,
    visibility,
    indexed,
    date_add,
    date_upd
)
SELECT 
    p.id_product,
    @shop_id AS id_shop,
    COALESCE(p.id_category_default, 2) AS id_category_default,
    COALESCE(p.id_tax_rules_group, 1) AS id_tax_rules_group,
    COALESCE(p.price, 0.000000) AS price,
    COALESCE(p.wholesale_price, 0.000000) AS wholesale_price,
    1 AS active,
    1 AS available_for_order,
    'both' AS visibility,
    0 AS indexed,
    COALESCE(p.date_add, NOW()) AS date_add,
    NOW() AS date_upd
FROM ps_product p
WHERE NOT EXISTS (
    SELECT 1 
    FROM ps_product_shop ps 
    WHERE ps.id_product = p.id_product 
    AND ps.id_shop = @shop_id
);

SELECT CONCAT('✓ Restauradas ', ROW_COUNT(), ' entradas en ps_product_shop') AS Resultado;

-- ============================================================================
-- PASO 3: ACTIVAR TODOS LOS PRODUCTOS
-- ============================================================================

SELECT '=== PASO 3: Activando productos ===' AS Accion;

-- Activar en ps_product
UPDATE ps_product
SET 
    active = 1,
    visibility = 'both',
    indexed = 0
WHERE active = 0 OR visibility = 'none';

SELECT CONCAT('✓ Activados ', ROW_COUNT(), ' productos en ps_product') AS Resultado;

-- Activar en ps_product_shop
UPDATE ps_product_shop
SET 
    active = 1,
    visibility = 'both',
    indexed = 0
WHERE id_shop = @shop_id 
AND (active = 0 OR visibility = 'none');

SELECT CONCAT('✓ Activados ', ROW_COUNT(), ' productos en ps_product_shop') AS Resultado;

-- ============================================================================
-- PASO 4: CORREGIR CATEGORIAS
-- ============================================================================

SELECT '=== PASO 4: Corrigiendo categorías ===' AS Accion;

-- Corregir productos sin categoría válida
UPDATE ps_product
SET id_category_default = 2
WHERE id_category_default IS NULL 
   OR id_category_default = 0 
   OR id_category_default NOT IN (SELECT id_category FROM ps_category);

SELECT CONCAT('✓ Corregidas ', ROW_COUNT(), ' categorías en ps_product') AS Resultado;

-- Sincronizar categoría en ps_product_shop
UPDATE ps_product_shop ps
INNER JOIN ps_product p ON p.id_product = ps.id_product
SET ps.id_category_default = p.id_category_default
WHERE ps.id_shop = @shop_id
AND ps.id_category_default != p.id_category_default;

SELECT CONCAT('✓ Sincronizadas ', ROW_COUNT(), ' categorías en ps_product_shop') AS Resultado;

-- ============================================================================
-- PASO 5: ASIGNAR A CATEGORIAS EN ps_category_product
-- ============================================================================

SELECT '=== PASO 5: Asignando a categorías ===' AS Accion;

-- Asignar productos sin categoría a su categoría por defecto
INSERT IGNORE INTO ps_category_product (id_category, id_product, position)
SELECT 
    p.id_category_default,
    p.id_product,
    0 AS position
FROM ps_product p
WHERE NOT EXISTS (
    SELECT 1 
    FROM ps_category_product cp 
    WHERE cp.id_product = p.id_product
);

SELECT CONCAT('✓ Asignados ', ROW_COUNT(), ' productos a ps_category_product') AS Resultado;

-- ============================================================================
-- PASO 6: RESTAURAR PERMISOS ps_category_group
-- ============================================================================

SELECT '=== PASO 6: Restaurando permisos de categorías ===' AS Accion;

-- Dar permisos a grupos 1, 2, 3 (Visitor, Guest, Customer)
INSERT IGNORE INTO ps_category_group (id_category, id_group)
SELECT DISTINCT c.id_category, 1
FROM ps_category c
WHERE c.active = 1;

INSERT IGNORE INTO ps_category_group (id_category, id_group)
SELECT DISTINCT c.id_category, 2
FROM ps_category c
WHERE c.active = 1;

INSERT IGNORE INTO ps_category_group (id_category, id_group)
SELECT DISTINCT c.id_category, 3
FROM ps_category c
WHERE c.active = 1;

SELECT '✓ Permisos restaurados para todas las categorías activas' AS Resultado;

-- ============================================================================
-- PASO 7: GENERAR link_rewrite PARA PRODUCTOS SIN URL
-- ============================================================================

SELECT '=== PASO 7: Generando URLs ===' AS Accion;

-- Generar link_rewrite simple usando REPLACE para caracteres no válidos
UPDATE ps_product_lang pl
SET pl.link_rewrite = LOWER(
    CONCAT(
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(TRIM(pl.name), ' ', '-'),
                        'á', 'a'
                    ),
                    'é', 'e'
                ),
                'í', 'i'
            ),
            'ó', 'o'
        ),
        '-', pl.id_product
    )
)
WHERE (pl.link_rewrite IS NULL OR pl.link_rewrite = '' OR pl.link_rewrite = '-')
AND pl.name IS NOT NULL 
AND pl.name != '';

SELECT CONCAT('✓ Generadas ', ROW_COUNT(), ' URLs de productos') AS Resultado;

-- ============================================================================
-- PASO 8: VERIFICACION FINAL
-- ============================================================================

SELECT '=== VERIFICACION POST-RECUPERACION ===' AS Info;

SELECT 
    'Productos activos (ps_product)' AS Estado,
    COUNT(*) AS Cantidad
FROM ps_product 
WHERE active = 1;

SELECT 
    'Productos activos (ps_product_shop)' AS Estado,
    COUNT(*) AS Cantidad
FROM ps_product_shop 
WHERE active = 1 AND id_shop = @shop_id;

SELECT 
    'Productos con visibility=both' AS Estado,
    COUNT(*) AS Cantidad
FROM ps_product 
WHERE visibility = 'both';

SELECT 
    'Productos asignados a categorías' AS Estado,
    COUNT(DISTINCT id_product) AS Cantidad
FROM ps_category_product;

SELECT 
    'Productos con URL válida' AS Estado,
    COUNT(*) AS Cantidad
FROM ps_product_lang
WHERE link_rewrite IS NOT NULL 
AND link_rewrite != '' 
AND link_rewrite != '-';

SELECT 
    'Categorías con permisos' AS Estado,
    COUNT(DISTINCT id_category) AS Cantidad
FROM ps_category_group;

-- ============================================================================
-- PASO 9: LISTADO DE PRODUCTOS RECUPERADOS (PRIMEROS 10)
-- ============================================================================

SELECT '=== PRODUCTOS RECUPERADOS (MUESTRA) ===' AS Info;

SELECT 
    p.id_product,
    pl.name,
    p.active,
    p.visibility,
    ps.id_shop,
    CASE 
        WHEN ps.id_product IS NOT NULL THEN 'SI'
        ELSE 'NO'
    END AS tiene_shop,
    c.id_category AS categoria
FROM ps_product p
INNER JOIN ps_product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = @lang_id
LEFT JOIN ps_product_shop ps ON p.id_product = ps.id_product AND ps.id_shop = @shop_id
LEFT JOIN ps_category_product cp ON p.id_product = cp.id_product
LEFT JOIN ps_category c ON cp.id_category = c.id_category
WHERE p.active = 1
LIMIT 10;

-- ============================================================================
-- INSTRUCCIONES FINALES
-- ============================================================================

SELECT '=== PASOS OBLIGATORIOS AHORA ===' AS Urgente;
SELECT '1. Back Office → Parámetros Avanzados → Rendimiento → LIMPIAR CACHE' AS Paso;
SELECT '2. Back Office → Preferencias → Buscar → REGENERAR INDICE' AS Paso;
SELECT '3. Back Office → Catálogo → Productos → Refrescar (F5)' AS Paso;
SELECT '4. Front Office → Verificar que productos son visibles' AS Paso;

SELECT '=== RECUPERACION COMPLETADA ===' AS Estado;
SELECT 'Si persiste problema: ejecuta DIAGNOSTICO_PRODUCTOS_DETALLE.sql' AS Nota;
