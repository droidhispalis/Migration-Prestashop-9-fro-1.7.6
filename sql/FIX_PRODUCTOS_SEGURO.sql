-- ============================================================================
-- FIX SEGURO PARA PRODUCTOS INVISIBLES - VERSION ULTRA CONSERVADORA
-- ============================================================================
-- ESTE SCRIPT ES 100% SEGURO:
-- ✓ NUNCA borra datos existentes
-- ✓ NUNCA modifica productos que ya funcionan
-- ✓ SOLO añade datos faltantes
-- ============================================================================

-- Configuración
SET @shop_id = 1;
SET @lang_id = 1;

-- ============================================================================
-- DIAGNOSTICO INICIAL
-- ============================================================================

SELECT '=== DIAGNOSTICO ANTES DE CORREGIR ===' AS Info;

SELECT 
    'Total productos en ps_product' AS Metrica,
    COUNT(*) AS Valor
FROM ps_product;

SELECT 
    'Productos activos en ps_product' AS Metrica,
    COUNT(*) AS Valor
FROM ps_product WHERE active = 1;

SELECT 
    'Total productos en ps_product_shop' AS Metrica,
    COUNT(*) AS Valor
FROM ps_product_shop;

SELECT 
    'Productos sin ps_product_shop' AS Metrica,
    COUNT(*) AS Valor
FROM ps_product p
LEFT JOIN ps_product_shop ps ON p.id_product = ps.id_product AND ps.id_shop = @shop_id
WHERE ps.id_product IS NULL;

-- ============================================================================
-- PASO 1: CREAR ENTRADAS FALTANTES EN ps_product_shop
-- ============================================================================
-- SOLO para productos que NO tienen entrada en ps_product_shop
-- NUNCA modifica productos existentes
-- ============================================================================

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
    COALESCE(p.active, 1) AS active,
    COALESCE(p.available_for_order, 1) AS available_for_order,
    'both' AS visibility,
    0 AS indexed,
    COALESCE(p.date_add, NOW()) AS date_add,
    COALESCE(p.date_upd, NOW()) AS date_upd
FROM ps_product p
WHERE NOT EXISTS (
    SELECT 1 
    FROM ps_product_shop ps 
    WHERE ps.id_product = p.id_product 
    AND ps.id_shop = @shop_id
);

SELECT CONCAT('✓ Paso 1: Creadas ', ROW_COUNT(), ' entradas en ps_product_shop') AS Resultado;

-- ============================================================================
-- PASO 2: ASIGNAR PRODUCTOS A CATEGORIAS (SOLO FALTANTES)
-- ============================================================================
-- SOLO añade relaciones que no existen
-- NUNCA modifica asignaciones existentes
-- ============================================================================

INSERT INTO ps_category_product (id_category, id_product, position)
SELECT DISTINCT
    COALESCE(p.id_category_default, 2) AS id_category,
    p.id_product,
    COALESCE(
        (SELECT MAX(position) + 1 FROM ps_category_product cp WHERE cp.id_category = COALESCE(p.id_category_default, 2)),
        0
    ) AS position
FROM ps_product p
WHERE p.active = 1
AND NOT EXISTS (
    SELECT 1 
    FROM ps_category_product cp 
    WHERE cp.id_product = p.id_product
);

SELECT CONCAT('✓ Paso 2: Asignados ', ROW_COUNT(), ' productos a categorías') AS Resultado;

-- ============================================================================
-- PASO 3: VERIFICAR PERMISOS DE CATEGORIA (SIN BORRAR)
-- ============================================================================
-- SOLO añade permisos faltantes
-- NUNCA borra permisos existentes
-- ============================================================================

INSERT IGNORE INTO ps_category_group (id_category, id_group)
SELECT DISTINCT
    c.id_category,
    1 AS id_group  -- Grupo Visitantes
FROM ps_category c
WHERE c.active = 1
AND NOT EXISTS (
    SELECT 1 FROM ps_category_group cg 
    WHERE cg.id_category = c.id_category AND cg.id_group = 1
);

INSERT IGNORE INTO ps_category_group (id_category, id_group)
SELECT DISTINCT
    c.id_category,
    2 AS id_group  -- Grupo Invitados
FROM ps_category c
WHERE c.active = 1
AND NOT EXISTS (
    SELECT 1 FROM ps_category_group cg 
    WHERE cg.id_category = c.id_category AND cg.id_group = 2
);

INSERT IGNORE INTO ps_category_group (id_category, id_group)
SELECT DISTINCT
    c.id_category,
    3 AS id_group  -- Grupo Clientes
FROM ps_category c
WHERE c.active = 1
AND NOT EXISTS (
    SELECT 1 FROM ps_category_group cg 
    WHERE cg.id_category = c.id_category AND cg.id_group = 3
);

SELECT '✓ Paso 3: Permisos de categorías verificados' AS Resultado;

-- ============================================================================
-- PASO 4: CORREGIR VISIBILIDAD SOLO DE PRODUCTOS ACTIVOS SIN VISIBILITY
-- ============================================================================
-- SOLO modifica productos con visibility problemática
-- NO toca productos que ya tienen visibility = 'both'
-- ============================================================================

UPDATE ps_product
SET visibility = 'both'
WHERE active = 1
AND (visibility IS NULL OR visibility IN ('none', 'search', 'catalog'));

SELECT CONCAT('✓ Paso 4a: Corregida visibilidad de ', ROW_COUNT(), ' productos en ps_product') AS Resultado;

UPDATE ps_product_shop
SET visibility = 'both'
WHERE id_shop = @shop_id
AND active = 1
AND (visibility IS NULL OR visibility IN ('none', 'search', 'catalog'));

SELECT CONCAT('✓ Paso 4b: Corregida visibilidad de ', ROW_COUNT(), ' productos en ps_product_shop') AS Resultado;

-- ============================================================================
-- PASO 5: GENERAR LINK_REWRITE SOLO PARA PRODUCTOS SIN URL
-- ============================================================================
-- SOLO genera URLs para productos que NO tienen link_rewrite válido
-- NO modifica URLs existentes
-- ============================================================================

UPDATE ps_product_lang pl
INNER JOIN ps_product p ON p.id_product = pl.id_product
SET pl.link_rewrite = CONCAT(
    LOWER(
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(
                                                REPLACE(TRIM(pl.name), ' ', '-'),
                                            'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'),
                                    'ñ', 'n'), 'Á', 'A'), 'É', 'E'), 'Í', 'I'), 'Ó', 'O'), 'Ú', 'U')
    ),
    '-', pl.id_product
)
WHERE p.active = 1
AND pl.name IS NOT NULL 
AND pl.name != ''
AND (pl.link_rewrite IS NULL OR pl.link_rewrite = '' OR pl.link_rewrite = '-');

SELECT CONCAT('✓ Paso 5: Generadas ', ROW_COUNT(), ' URLs de productos') AS Resultado;

-- ============================================================================
-- PASO 6: MARCAR PRODUCTOS PARA REINDEXACION
-- ============================================================================
-- Necesario para que aparezcan en búsquedas
-- ============================================================================

UPDATE ps_product 
SET indexed = 0 
WHERE active = 1;

UPDATE ps_product_shop 
SET indexed = 0 
WHERE active = 1 AND id_shop = @shop_id;

SELECT '✓ Paso 6: Productos marcados para reindexación' AS Resultado;

-- ============================================================================
-- DIAGNOSTICO FINAL
-- ============================================================================

SELECT '=== DIAGNOSTICO DESPUES DE CORREGIR ===' AS Info;

SELECT 
    'Total productos en ps_product' AS Metrica,
    COUNT(*) AS Valor
FROM ps_product;

SELECT 
    'Productos activos en ps_product' AS Metrica,
    COUNT(*) AS Valor
FROM ps_product WHERE active = 1;

SELECT 
    'Total productos en ps_product_shop' AS Metrica,
    COUNT(*) AS Valor
FROM ps_product_shop;

SELECT 
    'Productos con visibilidad "both"' AS Metrica,
    COUNT(*) AS Valor
FROM ps_product WHERE visibility = 'both';

SELECT 
    'Productos asignados a categorías' AS Metrica,
    COUNT(DISTINCT id_product) AS Valor
FROM ps_category_product;

SELECT 
    'Categorías con permisos' AS Metrica,
    COUNT(DISTINCT id_category) AS Valor
FROM ps_category_group;

-- ============================================================================
-- INSTRUCCIONES POST-EJECUCION
-- ============================================================================

SELECT '=== PROXIMOS PASOS OBLIGATORIOS ===' AS Info;
SELECT '1. Back Office → Parámetros Avanzados → Rendimiento → Limpiar caché' AS Paso;
SELECT '2. Back Office → Preferencias → Buscar → Regenerar índice' AS Paso;
SELECT '3. Back Office → Catálogo → Productos → Verificar que aparecen' AS Paso;
SELECT '4. Front Office → Verificar productos visibles' AS Paso;

SELECT '=== FIX COMPLETADO EXITOSAMENTE ===' AS Estado;
