# ⚠️ ERRORES COMUNES Y SOLUCIONES

## Error #1146: Table 'database.ps_product' doesn't exist

### 🔴 Causa
No seleccionaste la base de datos correcta en phpMyAdmin antes de ejecutar el script.

### ✅ Solución
1. **Identifica tu base de datos**:
   - Abre phpMyAdmin
   - Mira el panel izquierdo
   - Busca bases de datos con nombres como:
     * `migration`
     * `toprelieve`
     * `prestashop9`
     * `topreileve3d`
     * O cualquier nombre personalizado

2. **Selecciona la base de datos**:
   - Click en el nombre de la base de datos
   - Debe quedar marcada/seleccionada en panel izquierdo
   - En la parte superior debe aparecer: "Base de datos: [nombre]"

3. **Ahora sí ejecuta el script**:
   - Pestaña "SQL"
   - Pega `RECUPERAR_PRODUCTOS_FIXED.sql`
   - Click "Continuar"

---

## Error: MySQL version incompatible / Syntax error

### 🔴 Causa
Tu versión de MySQL es muy antigua (< 5.6) o usas MariaDB antigua.

### ✅ Solución
Los scripts están diseñados para:
- MySQL 5.6+
- MariaDB 10.0+
- MySQL 8.0+ (recomendado para PS9)

Si tienes versión antigua:
1. Actualiza MySQL/MariaDB
2. O usa el script alternativo simplificado (próximamente)

---

## Error: Unknown column 'indexed' in field list

### 🔴 Causa
Tu versión de PrestaShop no tiene el campo `indexed` en `ps_product_shop`.

### ✅ Solución
Edita `RECUPERAR_PRODUCTOS_FIXED.sql`:

**Busca (línea ~63):**
```sql
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
    indexed,    <-- QUITAR ESTA LINEA
    date_add,
    date_upd
)
```

**Reemplaza por:**
```sql
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
    date_add,
    date_upd
)
```

**Y en el SELECT (línea ~80):**
```sql
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
    0 AS indexed,    <-- QUITAR ESTA LINEA
    COALESCE(p.date_add, NOW()) AS date_add,
    NOW() AS date_upd
FROM ps_product p
```

**Reemplaza por:**
```sql
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
    COALESCE(p.date_add, NOW()) AS date_add,
    NOW() AS date_upd
FROM ps_product p
```

---

## Error: Duplicate entry '1-1' for key 'PRIMARY'

### 🔴 Causa
Ya ejecutaste el script antes y algunos productos ya tienen entrada en `ps_product_shop`.

### ✅ Solución
**Esto es NORMAL y NO es un error grave.**

El script usa `INSERT` sin `IGNORE`, por lo que puede fallar si ya existe la entrada.

**Opciones:**

1. **Opción A: Ignorar el error** (el script continúa con los productos faltantes)

2. **Opción B: Ejecutar solo diagnóstico**
   ```sql
   -- Ejecuta este script primero:
   DIAGNOSTICO_SIMPLE_FIXED.sql
   ```
   
   Si muestra "Productos SIN ps_product_shop: 0" → Ya está todo OK

3. **Opción C: Limpiar ps_product_shop completamente**
   ```sql
   -- ⚠️ PELIGROSO: Solo si sabes lo que haces
   TRUNCATE TABLE ps_product_shop;
   -- Luego ejecuta RECUPERAR_PRODUCTOS_FIXED.sql
   ```

---

## Error: ROW_COUNT() returns 0 / Rows affected: 0

### 🔴 Causa
No hay productos que necesiten recuperación.

### ✅ Solución
**Esto puede significar dos cosas:**

1. **Todo está OK** → Los productos ya están correctos
   - Verifica: Back Office → Catálogo → Productos
   - Si aparecen: Solo falta limpiar caché

2. **Problema diferente** → Ejecuta diagnóstico:
   ```sql
   DIAGNOSTICO_SIMPLE_FIXED.sql
   ```
   
   Revisa qué sección muestra cantidades > 0:
   - "Productos SIN ps_product_shop"
   - "Productos con visibility=none"
   - "Productos SIN categoría"
   
   Si todas son 0 → El problema es de caché o permisos, NO de base de datos

---

## Error: Access denied for user 'root'@'localhost'

### 🔴 Causa
No tienes permisos para modificar la base de datos.

### ✅ Solución
1. Verifica que eres usuario `root` o tienes permisos `ALTER`, `INSERT`, `UPDATE`
2. Si usas cPanel/hosting compartido:
   - No uses `root`
   - Usa el usuario de la base de datos de PrestaShop
   - Ejecuta desde phpMyAdmin del hosting

---

## Productos siguen sin aparecer después del script

### ✅ Solución paso a paso:

**1. Verifica que el script se ejecutó completo**
```sql
-- Debe mostrar cantidades > 0:
SELECT COUNT(*) FROM ps_product WHERE active = 1;
SELECT COUNT(*) FROM ps_product_shop WHERE id_shop = 1;
```

Si ambos son > 0 → El script funcionó

**2. Limpia caché (CRÍTICO)**
```
Back Office → Parámetros Avanzados → Rendimiento → Limpiar caché
```

**3. Regenera índice de búsqueda**
```
Back Office → Preferencias → Buscar → Regenerar índice
```

**4. Verifica permisos de archivos** (si usas servidor Linux)
```bash
chmod 755 -R var/cache
chown www-data:www-data -R var/cache
```

**5. Verifica configuración de tienda**
```sql
-- ¿Cuál es tu id_shop?
SELECT * FROM ps_shop;

-- Si tu id_shop NO es 1, cambia en el script:
SET @shop_id = 2; -- O el número correcto
```

**6. Verifica configuración de categorías**
```
Back Office → Catálogo → Categorías
-- Asegúrate que categoría "Home" (id=2) está activa
```

**7. Verifica modo mantenimiento**
```
Back Office → Preferencias → Tienda
-- Desactiva "Modo mantenimiento"
```

**8. Verifica errores PHP**
```
Back Office → Parámetros Avanzados → Logs
-- Revisa si hay errores de PHP
```

---

## Productos aparecen en Back Office pero NO en Front Office

### ✅ Solución:

**1. Verifica visibilidad**
```sql
UPDATE ps_product SET visibility = 'both' WHERE visibility = 'none';
UPDATE ps_product_shop SET visibility = 'both' WHERE visibility = 'none';
```

**2. Verifica categorías activas**
```sql
-- Todas las categorías deben estar activas
UPDATE ps_category SET active = 1;
```

**3. Verifica permisos de grupos**
```sql
-- Ejecuta CREATE_CATEGORY_GROUP.sql
-- O manualmente:
INSERT IGNORE INTO ps_category_group (id_category, id_group)
SELECT c.id_category, 1 FROM ps_category c;
INSERT IGNORE INTO ps_category_group (id_category, id_group)
SELECT c.id_category, 2 FROM ps_category c;
INSERT IGNORE INTO ps_category_group (id_category, id_group)
SELECT c.id_category, 3 FROM ps_category c;
```

**4. Regenera URLs**
```
Back Office → Preferencias → SEO y URLs → Regenerar URLs
```

**5. Limpia caché del navegador**
```
Ctrl + Shift + R (Chrome/Firefox)
Ctrl + F5 (Edge)
```

---

## ¿Necesitas más ayuda?

1. **Ejecuta diagnóstico completo**:
   ```sql
   DIAGNOSTICO_SIMPLE_FIXED.sql
   ```

2. **Captura pantalla** del resultado

3. **Abre issue en GitHub** con:
   - Captura diagnóstico
   - Error exacto que recibes
   - Versión PrestaShop
   - Versión MySQL/MariaDB
   - Nombre de tu base de datos

GitHub: https://github.com/droidhispalis/Migration-Prestashop-9-fro-1.7.6/issues

---

**Última actualización:** 11 de diciembre de 2025
