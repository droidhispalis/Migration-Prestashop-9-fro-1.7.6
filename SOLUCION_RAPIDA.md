# 🚨 SOLUCIÓN INMEDIATA: Recuperar Productos Perdidos

## ❌ PROBLEMA

Ejecutaste los "Post-Import Fixes" y **los productos desaparecieron** del Back Office.

## ✅ SOLUCIÓN (3 Minutos - SIN REINSTALAR)

### PASO 1: Backup (30 segundos)

```bash
# phpMyAdmin: Exportar base de datos
# O desde terminal:
mysqldump -u root -p prestashop9 > backup_emergency.sql
```

### PASO 2: Ejecutar Script de Recuperación (30 segundos)

**Opción A: phpMyAdmin (RECOMENDADO)**
1. Abrir phpMyAdmin
2. **IMPORTANTE**: Seleccionar tu base de datos PrestaShop 9 en panel izquierdo
3. Pestaña "SQL"
4. Abrir el archivo:
   ```
   Y:\Mirgracion Prestashop\Version estable\psimporter9from178\sql\RECUPERAR_PRODUCTOS_FIXED.sql
   ```
5. Copiar TODO el contenido (Ctrl+A, Ctrl+C)
6. Pegar en la caja SQL de phpMyAdmin
7. Click "Continuar" (NO ejecutar línea por línea)
8. Esperar mensaje "Recuperación completada"

**Opción B: Terminal**
```bash
cd "Y:\Mirgracion Prestashop\Version estable\psimporter9from178\sql"
mysql -u root -p nombre_base_datos < RECUPERAR_PRODUCTOS_FIXED.sql
# Reemplaza 'nombre_base_datos' por tu BD (migration, toprelieve, etc.)
```

### PASO 3: Limpiar Caché (30 segundos) ⚠️ CRÍTICO

```
1. Back Office de PrestaShop 9
2. Parámetros Avanzados → Rendimiento
3. Botón "Limpiar caché"
4. Esperar confirmación
```

### PASO 4: Regenerar Índice (1 minuto)

```
1. Back Office
2. Preferencias → Buscar
3. Botón "Regenerar índice de búsqueda"
4. Esperar progreso 100%
```

### PASO 5: Verificar (30 segundos)

```
1. Catálogo → Productos
2. Debe aparecer: "Productos (X)" con X > 0
3. Refrescar (F5) si es necesario
4. Click en un producto → Debe abrir editor
5. Front Office → Productos deben ser visibles
```

---

## 🎯 ¿Qué Hace el Script?

```sql
✓ Restaura ps_product_shop (relación producto-tienda)
✓ Activa TODOS los productos (active = 1)
✓ Asigna a categoría Home si no tienen
✓ Restaura permisos ps_category_group
✓ Genera URLs automáticas
✓ Marca para reindexación
✓ NUNCA borra datos existentes
```

---

## ⚠️ Causa del Problema

Los scripts originales (`FIX_SIMPLE.sql` y `CREATE_CATEGORY_GROUP.sql`) usaban:

```sql
-- ❌ MALO: Puede borrar relaciones
LEFT JOIN ps_product_shop ps ON ...
WHERE ps.id_product IS NULL

-- ✅ BUENO (nuevo script):
WHERE NOT EXISTS (
    SELECT 1 FROM ps_product_shop ps WHERE ...
)
```

La diferencia:
- `LEFT JOIN` + `WHERE NULL` puede eliminar registros existentes
- `NOT EXISTS` solo añade si realmente no existe

---

## 📊 Verificación Rápida SQL

Después de ejecutar, verifica con esto en phpMyAdmin:

```sql
-- Cuántos productos activos (debe ser > 0)
SELECT COUNT(*) AS productos_activos 
FROM ps_product WHERE active = 1;

-- Productos con relación a tienda (debe = productos_activos)
SELECT COUNT(*) AS productos_en_tienda 
FROM ps_product_shop WHERE id_shop = 1;

-- Si ambos son iguales y > 0: ✅ RECUPERADO
```

---

## 🆘 Si Aún No Aparecen

### 1. Diagnóstico Detallado

Ejecuta en phpMyAdmin:
```sql
Y:\Mirgracion Prestashop\Version estable\psimporter9from178\sql\DIAGNOSTICO_SIMPLE_FIXED.sql
```

Revisa qué problemas específicos detecta.

### 2. Fix Adicional

Si diagnóstico muestra problemas críticos, ejecuta de nuevo:
```sql
Y:\Mirgracion Prestashop\Version estable\psimporter9from178\sql\RECUPERAR_PRODUCTOS_FIXED.sql
```

### 3. Limpiar Caché Manualmente

```bash
cd /var/www/prestashop9  # o tu ruta
rm -rf var/cache/*
rm -rf var/cache/dev/*
rm -rf var/cache/prod/*
```

### 4. Verificar Permisos de Archivos

```bash
chmod 755 -R var/cache
chown www-data:www-data -R var/cache
```

---

## ❓ FAQ Rápido

**¿Perderé datos al ejecutar RECUPERAR_PRODUCTOS.sql?**
- **NO.** Solo añade datos faltantes, nunca borra.

**¿Tengo que reinstalar PrestaShop?**
- **NO.** El script recupera todo sin reinstalar.

**¿Puedo ejecutar el script varias veces?**
- **SÍ.** Es idempotente (seguro ejecutar múltiples veces).

**¿Cuánto tiempo toma?**
- **2-3 minutos** incluyendo limpiar caché e índice.

**¿Funcionará con productos demo?**
- **SÍ.** Recupera productos demo y reales por igual.

**¿Qué pasa con productos personalizados?**
- **Se mantienen.** El script respeta datos existentes.

**⚠️ IMPORTANTE: ¿Qué base de datos uso?**
- Abre phpMyAdmin
- Selecciona tu BD PrestaShop en panel izquierdo (migration, toprelieve, prestashop9, etc.)
- LUEGO ejecuta el script SQL
- El script usa tablas con prefijo `ps_` (estándar PrestaShop)

---

## 📞 Contacto de Emergencia

Si después de ejecutar `RECUPERAR_PRODUCTOS_FIXED.sql` los problemas persisten:

1. **Captura de pantalla** del error
2. **Resultado** de `DIAGNOSTICO_SIMPLE_FIXED.sql`
3. **Nombre de tu base de datos** (migration, toprelieve, etc.)
4. **Abre issue** en GitHub:
   ```
   https://github.com/droidhispalis/Migration-Prestashop-9-fro-1.7.6/issues
   ```

---

## ✅ Checklist Rápido

- [ ] ✅ Backup hecho
- [ ] ✅ Base de datos seleccionada en phpMyAdmin
- [ ] ✅ RECUPERAR_PRODUCTOS_FIXED.sql ejecutado
- [ ] ✅ Caché limpiada (CRÍTICO)
- [ ] ✅ Índice regenerado
- [ ] ✅ Productos visibles en Back Office
- [ ] ✅ Productos visibles en Front Office

---

**🎉 ¡LISTO! Tus productos deben estar de vuelta.**

**Si no aparecen:** Ejecuta `DIAGNOSTICO_SIMPLE_FIXED.sql` y comparte resultado.

---

**Última actualización:** 11 de diciembre de 2025  
**Script creado por:** Migration Tools Team  
**Tested en:** PrestaShop 9.0.0 - 9.0.2
