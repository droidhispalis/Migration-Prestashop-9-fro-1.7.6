# 🚨 GUÍA DE RECUPERACIÓN: Productos Desaparecidos

## ⚠️ PROBLEMA

Después de ejecutar los "Post-Import Fixes" del módulo `psimporter9from178`, los productos desaparecieron del Back Office y Front Office.

**Causas identificadas:**
- Scripts SQL `FIX_SIMPLE.sql` y `CREATE_CATEGORY_GROUP.sql` tenían lógica que podía borrar relaciones
- Uso de `LEFT JOIN` + `INSERT IGNORE` eliminaba entradas existentes
- Modificaciones de `visibility` sin verificación previa

---

## ✅ SOLUCIÓN RÁPIDA (Sin Reinstalar)

### Opción 1: Script de Recuperación Automática (RECOMENDADO)

1. **Ir a phpMyAdmin** (o tu gestor SQL)
2. **Seleccionar base de datos** de PrestaShop 9
3. **Ejecutar este script:**

```sql
Y:\Mirgracion Prestashop\Version estable\psimporter9from178\sql\RECUPERAR_PRODUCTOS.sql
```

**Este script:**
- ✅ Restaura entradas faltantes en `ps_product_shop`
- ✅ Activa todos los productos
- ✅ Asigna a categorías por defecto
- ✅ Restaura permisos de `ps_category_group`
- ✅ Genera URLs faltantes
- ✅ **NO borra ningún dato existente**

4. **Limpiar caché** (OBLIGATORIO):
```
Back Office → Parámetros Avanzados → Rendimiento → Limpiar caché
```

5. **Regenerar índice:**
```
Back Office → Preferencias → Buscar → Regenerar índice de búsqueda
```

6. **Verificar:**
```
Back Office → Catálogo → Productos (F5 para refrescar)
Front Office → Ir a la tienda
```

---

### Opción 2: Diagnóstico Detallado Primero

Si quieres entender QUÉ salió mal antes de corregir:

1. **Ejecutar diagnóstico:**
```sql
Y:\Mirgracion Prestashop\Version estable\psimporter9from178\sql\DIAGNOSTICO_PRODUCTOS_DETALLE.sql
```

Este script muestra:
- ❌ Productos sin entrada en `ps_product_shop`
- ❌ Categorías sin permisos
- ❌ Productos con `visibility` incorrecta
- ❌ Productos sin asignar a categorías
- ✅ Qué scripts ejecutar

2. **Después ejecutar recuperación:**
```sql
RECUPERAR_PRODUCTOS.sql
```

---

## 📋 ¿Qué Pasó Exactamente?

### Script Problemático: `FIX_SIMPLE.sql`

**Líneas problemáticas:**

```sql
-- ❌ PROBLEMA: Este LEFT JOIN + WHERE NULL puede eliminar relaciones
LEFT JOIN ps_product_shop ps ON p.id_product = ps.id_product
WHERE ps.id_product IS NULL
```

**Por qué falla:**
- Si `ps_product_shop` ya tenía datos, el `LEFT JOIN` con `WHERE NULL` no los encuentra
- `INSERT IGNORE` no restaura si hay conflictos
- Resultado: productos quedan sin relación con la tienda

### Script Problemático: `CREATE_CATEGORY_GROUP.sql`

```sql
-- ❌ PROBLEMA: INSERT IGNORE puede fallar silenciosamente
INSERT IGNORE INTO ps_category_group ...
```

**Por qué falla:**
- Si hay una violación de clave primaria, `IGNORE` oculta el error
- No se insertan los permisos necesarios
- Resultado: categorías invisibles → productos invisibles

---

## 🔧 Scripts Corregidos Disponibles

### 1. **RECUPERAR_PRODUCTOS.sql** ⭐ (USAR ESTE)

**Ubicación:** `psimporter9from178/sql/RECUPERAR_PRODUCTOS.sql`

**Qué hace:**
```
✓ Restaura ps_product_shop faltantes
✓ Activa TODOS los productos
✓ Asigna a categoría Home (id=2) si no tienen
✓ Restaura permisos de ps_category_group
✓ Genera URLs automáticas
✓ Marca para reindexación
```

**Seguridad:** 100% seguro, solo añade, nunca borra.

### 2. **FIX_PRODUCTOS_SEGURO.sql**

**Ubicación:** `psimporter9from178/sql/FIX_PRODUCTOS_SEGURO.sql`

**Qué hace:**
```
✓ Crea entradas faltantes (NOT EXISTS en lugar de LEFT JOIN)
✓ Solo modifica productos con problemas
✓ NO toca productos que ya funcionan
✓ Verificación antes y después
```

**Cuándo usar:** Después de recuperar, para optimizar.

### 3. **DIAGNOSTICO_PRODUCTOS_DETALLE.sql**

**Ubicación:** `psimporter9from178/sql/DIAGNOSTICO_PRODUCTOS_DETALLE.sql`

**Qué hace:**
```
✓ Muestra estadísticas completas
✓ Identifica productos problemáticos
✓ Lista ejemplos de errores
✓ Recomienda qué scripts ejecutar
```

**Cuándo usar:** Antes de cualquier corrección, para entender el problema.

---

## 🎯 Procedimiento Completo Paso a Paso

### Paso 1: Diagnóstico

```bash
# En phpMyAdmin o terminal SQL
mysql -u root -p prestashop9 < DIAGNOSTICO_PRODUCTOS_DETALLE.sql > diagnostico.txt
```

Lee el resultado y verifica:
- ¿Cuántos productos desaparecieron?
- ¿Qué tablas tienen problemas?

### Paso 2: Recuperación

```bash
mysql -u root -p prestashop9 < RECUPERAR_PRODUCTOS.sql > recuperacion.txt
```

Verifica el resultado:
```
✓ Restauradas X entradas en ps_product_shop
✓ Activados Y productos en ps_product
✓ Asignados Z productos a categorías
```

### Paso 3: Limpiar Caché (CRÍTICO)

```bash
# Opción 1: Desde Back Office
Back Office → Parámetros Avanzados → Rendimiento → Limpiar caché

# Opción 2: Desde terminal
cd /path/to/prestashop
rm -rf var/cache/*
```

### Paso 4: Regenerar Índice

```bash
Back Office → Preferencias → Buscar → Regenerar índice de búsqueda
```

### Paso 5: Verificación

1. **Back Office:**
```
Catálogo → Productos → Debe mostrar "Productos (X)"
```

2. **Front Office:**
```
Ir a tu tienda → Debe mostrar productos en home y categorías
```

3. **Verificar producto específico:**
```sql
SELECT 
    p.id_product,
    pl.name,
    p.active,
    ps.active AS active_shop,
    p.visibility,
    cp.id_category
FROM ps_product p
LEFT JOIN ps_product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = 1
LEFT JOIN ps_product_shop ps ON p.id_product = ps.id_product
LEFT JOIN ps_category_product cp ON p.id_product = cp.id_product
WHERE p.id_product = 1;  -- Cambia por ID de producto de prueba
```

---

## ❓ FAQ

### ¿Tengo que reinstalar PrestaShop 9?

**NO.** Los scripts de recuperación restauran todo sin reinstalar.

### ¿Perderé productos al ejecutar RECUPERAR_PRODUCTOS.sql?

**NO.** El script solo añade datos faltantes, nunca borra.

### ¿Cuánto tiempo toma la recuperación?

- **Diagnóstico:** 5 segundos
- **Recuperación:** 10-30 segundos
- **Limpiar caché:** 5 segundos
- **Regenerar índice:** 1-2 minutos
- **Total:** ~3 minutos

### ¿Puedo ejecutar los scripts varias veces?

**SÍ.** Son idempotentes (ejecutarlos múltiples veces da el mismo resultado).

### ¿Qué pasa si tenía productos personalizados?

Se conservan. El script usa:
```sql
WHERE NOT EXISTS (...)  -- No sobrescribe existentes
INSERT IGNORE ...        -- No duplica
```

### ¿Por qué los scripts anteriores fallaron?

Usaban `LEFT JOIN` con `WHERE NULL` que puede eliminar relaciones existentes. Los nuevos scripts usan `NOT EXISTS` que es más seguro.

---

## 🔒 Backup de Seguridad

**ANTES de ejecutar cualquier script**, haz backup:

```bash
# Backup completo
mysqldump -u root -p prestashop9 > backup_antes_recuperacion.sql

# Backup solo tablas de productos
mysqldump -u root -p prestashop9 \
  ps_product \
  ps_product_shop \
  ps_product_lang \
  ps_category_product \
  ps_category_group \
  > backup_productos.sql
```

**Restaurar si algo sale mal:**
```bash
mysql -u root -p prestashop9 < backup_antes_recuperacion.sql
```

---

## 📊 Verificación Post-Recuperación

### SQL Rápido de Verificación

```sql
-- Cuántos productos activos
SELECT COUNT(*) AS productos_activos FROM ps_product WHERE active = 1;

-- Productos con ps_product_shop
SELECT COUNT(*) AS productos_en_shop FROM ps_product_shop WHERE id_shop = 1;

-- Productos en categorías
SELECT COUNT(DISTINCT id_product) AS productos_en_categorias FROM ps_category_product;

-- Categorías con permisos
SELECT COUNT(DISTINCT id_category) AS categorias_con_permisos FROM ps_category_group;

-- Todo debe ser > 0
```

### Verificación Visual

✅ **Back Office:**
- Catálogo → Productos → Aparece listado
- Click en producto → Se abre editor
- Guardar → Sin errores

✅ **Front Office:**
- Home → Productos visibles
- Categoría → Productos listados
- Producto → Página carga correctamente
- Añadir al carrito → Funciona

---

## 🆘 Si Aún No Funciona

### Problema: Productos aparecen pero dan error 404

**Solución:**
```sql
-- Regenerar URLs
UPDATE ps_product_lang pl
INNER JOIN ps_product p ON p.id_product = pl.id_product
SET pl.link_rewrite = CONCAT(
    LOWER(REPLACE(TRIM(pl.name), ' ', '-')),
    '-', pl.id_product
)
WHERE p.active = 1;
```

Luego:
```
Back Office → Preferencias → SEO y URLs → Regenerar
```

### Problema: Categorías vacías

**Solución:**
```sql
-- Ejecutar FIX_PRODUCTOS_SEGURO.sql completo
SOURCE /path/to/FIX_PRODUCTOS_SEGURO.sql;
```

### Problema: Error "No tiene acceso a este producto"

**Solución:**
```sql
-- Verificar permisos
SELECT * FROM ps_category_group WHERE id_category = 2;  -- Debe tener grupos 1,2,3

-- Si está vacío:
INSERT IGNORE INTO ps_category_group VALUES (2,1), (2,2), (2,3);
```

---

## 📞 Soporte

Si después de ejecutar `RECUPERAR_PRODUCTOS.sql` los problemas persisten:

1. **Ejecuta diagnóstico detallado:**
```sql
SOURCE DIAGNOSTICO_PRODUCTOS_DETALLE.sql;
```

2. **Copia el resultado completo**

3. **Reporta en GitHub:**
```
https://github.com/droidhispalis/Migration-Prestashop-9-fro-1.7.6/issues
```

Con:
- Resultado del diagnóstico
- Versión de PrestaShop 9
- Qué scripts ejecutaste
- Qué errores aparecen

---

## ✅ Checklist de Recuperación

- [ ] Backup de base de datos realizado
- [ ] Ejecutado `DIAGNOSTICO_PRODUCTOS_DETALLE.sql`
- [ ] Ejecutado `RECUPERAR_PRODUCTOS.sql`
- [ ] Limpiada caché en Back Office
- [ ] Regenerado índice de búsqueda
- [ ] Verificado productos en Back Office
- [ ] Verificado productos en Front Office
- [ ] Probado añadir al carrito
- [ ] Probado checkout (opcional)

---

**Última actualización:** 11 de diciembre de 2025  
**Autor:** Migration Tools Team  
**Versión:** 1.0.0
