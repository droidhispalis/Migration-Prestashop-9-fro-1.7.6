<?php
/**
 * Diagnostic script to compare table structures between PS 1.7.6 export and PS 9
 * Place this file in the module folder and access via browser
 */

require_once(dirname(__FILE__) . '/../../../config/config.inc.php');

header('Content-Type: text/html; charset=utf-8');

echo '<h1>PrestaShop 9 Table Structure Diagnostic</h1>';
echo '<p>Current DB Prefix: <strong>' . _DB_PREFIX_ . '</strong></p>';
echo '<p>PS Version: <strong>' . _PS_VERSION_ . '</strong></p>';

$db = Db::getInstance();

// Critical tables to check
$criticalTables = array(
    'product',
    'product_lang',
    'product_shop',
    'category',
    'category_lang',
    'category_shop',
    'orders',
    'order_detail',
    'customer',
    'cart',
    'cart_product'
);

echo '<h2>Table Structures</h2>';

foreach ($criticalTables as $table) {
    $fullTable = _DB_PREFIX_ . $table;
    
    echo '<h3>' . $fullTable . '</h3>';
    
    try {
        $result = $db->executeS('DESCRIBE `' . $fullTable . '`');
        
        if ($result) {
            echo '<table border="1" cellpadding="5" style="border-collapse:collapse; font-family:monospace; font-size:12px;">';
            echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
            
            foreach ($result as $row) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['Field']) . '</td>';
                echo '<td>' . htmlspecialchars($row['Type']) . '</td>';
                echo '<td>' . htmlspecialchars($row['Null']) . '</td>';
                echo '<td>' . htmlspecialchars($row['Key']) . '</td>';
                echo '<td>' . htmlspecialchars($row['Default']) . '</td>';
                echo '<td>' . htmlspecialchars($row['Extra']) . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            
            // Count rows
            $count = $db->getValue('SELECT COUNT(*) FROM `' . $fullTable . '`');
            echo '<p><strong>Rows:</strong> ' . $count . '</p>';
            
        } else {
            echo '<p style="color:red;">Error getting table structure</p>';
        }
    } catch (Exception $e) {
        echo '<p style="color:red;">Error: ' . $e->getMessage() . '</p>';
    }
    
    echo '<hr>';
}

// Check for missing required fields in imported data
echo '<h2>Data Integrity Checks</h2>';

try {
    // Check products without localized names
    $query = 'SELECT p.id_product FROM `' . _DB_PREFIX_ . 'product` p 
              LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON p.id_product = pl.id_product 
              WHERE pl.id_product IS NULL LIMIT 10';
    $productsNoLang = $db->executeS($query);
    
    if ($productsNoLang) {
        echo '<p style="color:orange;"><strong>⚠ Products without language data:</strong> ' . count($productsNoLang) . '</p>';
        echo '<pre>' . print_r($productsNoLang, true) . '</pre>';
    }
    
    // Check categories without localized names
    $query = 'SELECT c.id_category FROM `' . _DB_PREFIX_ . 'category` c 
              LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON c.id_category = cl.id_category 
              WHERE cl.id_category IS NULL AND c.id_category > 2 LIMIT 10';
    $categoriesNoLang = $db->executeS($query);
    
    if ($categoriesNoLang) {
        echo '<p style="color:orange;"><strong>⚠ Categories without language data:</strong> ' . count($categoriesNoLang) . '</p>';
        echo '<pre>' . print_r($categoriesNoLang, true) . '</pre>';
    }
    
    // Check products without shop association
    $query = 'SELECT p.id_product FROM `' . _DB_PREFIX_ . 'product` p 
              LEFT JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON p.id_product = ps.id_product 
              WHERE ps.id_product IS NULL LIMIT 10';
    $productsNoShop = $db->executeS($query);
    
    if ($productsNoShop) {
        echo '<p style="color:orange;"><strong>⚠ Products without shop association:</strong> ' . count($productsNoShop) . '</p>';
        echo '<pre>' . print_r($productsNoShop, true) . '</pre>';
    }
    
} catch (Exception $e) {
    echo '<p style="color:red;">Error in integrity checks: ' . $e->getMessage() . '</p>';
}

echo '<h2>Sample Data</h2>';

// Show sample product
try {
    $sampleProduct = $db->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'product` LIMIT 1');
    echo '<h3>Sample Product Row</h3>';
    echo '<pre>' . print_r($sampleProduct, true) . '</pre>';
    
    if ($sampleProduct && isset($sampleProduct['id_product'])) {
        $productLang = $db->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'product_lang` WHERE id_product = ' . (int)$sampleProduct['id_product']);
        echo '<h4>Language Data for Product ' . $sampleProduct['id_product'] . '</h4>';
        echo '<pre>' . print_r($productLang, true) . '</pre>';
        
        $productShop = $db->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'product_shop` WHERE id_product = ' . (int)$sampleProduct['id_product']);
        echo '<h4>Shop Data for Product ' . $sampleProduct['id_product'] . '</h4>';
        echo '<pre>' . print_r($productShop, true) . '</pre>';
    }
} catch (Exception $e) {
    echo '<p style="color:red;">Error: ' . $e->getMessage() . '</p>';
}

// Show sample category
try {
    $sampleCategory = $db->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'category` WHERE id_category > 2 LIMIT 1');
    echo '<h3>Sample Category Row</h3>';
    echo '<pre>' . print_r($sampleCategory, true) . '</pre>';
    
    if ($sampleCategory && isset($sampleCategory['id_category'])) {
        $categoryLang = $db->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'category_lang` WHERE id_category = ' . (int)$sampleCategory['id_category']);
        echo '<h4>Language Data for Category ' . $sampleCategory['id_category'] . '</h4>';
        echo '<pre>' . print_r($categoryLang, true) . '</pre>';
    }
} catch (Exception $e) {
    echo '<p style="color:red;">Error: ' . $e->getMessage() . '</p>';
}
