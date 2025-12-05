<?php
/**
 * PrestaShop Importer for PS 9
 * Imports data exported from PrestaShop 1.7.6
 *
 * @author    Migration Tools
 * @copyright 2025
 * @license   MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Psimporter9from178 extends Module
{
    public function __construct()
    {
        $this->name = 'psimporter9from178';
        $this->tab = 'migration_tools';
        $this->version = '1.0.0';
        $this->author = 'Migration Tools';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '9.0.0', 'max' => '9.99.99');
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Migration Importer from PS 1.7.8', [], 'Modules.Psimporter9from178.Admin');
        $this->description = $this->trans('Import database, images and themes exported from PrestaShop 1.7.6/1.7.8 into PrestaShop 9', [], 'Modules.Psimporter9from178.Admin');
        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall this module?', [], 'Modules.Psimporter9from178.Admin');
        
        // Aumentar límites PHP al máximo
        @ini_set('memory_limit', '2048M');
        @ini_set('max_execution_time', '3600');
        @ini_set('post_max_size', '512M');
        @ini_set('upload_max_filesize', '512M');
        @set_time_limit(3600);
    }

    public function install()
    {
        return parent::install();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function getContent()
    {
        // Renderizar directamente en la configuración del módulo
        $output = '';
        
        // Procesar formularios
        if (Tools::isSubmit('submitImportDatabase')) {
            $output .= $this->processImportDatabase();
        }
        
        if (Tools::isSubmit('submitImportImages')) {
            $output .= $this->processImportImages();
        }
        
        if (Tools::isSubmit('submitDiagnostic')) {
            $output .= $this->runDiagnostic();
        }
        
        if (Tools::isSubmit('submitFixData')) {
            $output .= $this->manualFixData();
        }
        
        // Mostrar formularios
        $output .= $this->renderInfo();
        $output .= $this->renderDiagnosticButton();
        $output .= $this->renderImportDatabaseForm();
        $output .= $this->renderImportImagesForm();
        
        return $output;
    }
    
    private function renderInfo()
    {
        $html = '<div class="panel">';
        $html .= '<div class="panel-heading"><h3>Migration Importer from PS 1.7.8</h3></div>';
        $html .= '<div class="alert alert-info">';
        $html .= '<h4>How to use:</h4>';
        $html .= '<ol>';
        $html .= '<li>Export data from PrestaShop 1.7.6 using ps178to9migration module</li>';
        $html .= '<li>Import Database (SQL file)</li>';
        $html .= '<li>Import Images (ZIP file)</li>';
        $html .= '</ol>';
        $html .= '</div>';
        $html .= '<p><strong>PrestaShop:</strong> ' . _PS_VERSION_ . '</p>';
        $html .= '<p><strong>DB Prefix:</strong> ' . _DB_PREFIX_ . '</p>';
        $html .= '</div>';
        return $html;
    }
    
    private function renderDiagnosticButton()
    {
        $html = '<div class="panel">';
        $html .= '<div class="panel-heading"><h3>🔍 Database Diagnostic & Repair</h3></div>';
        $html .= '<div class="panel-body">';
        $html .= '<p><strong>Step 1:</strong> Run diagnostic to check for issues</p>';
        $html .= '<form method="POST" style="display:inline-block; margin-right:10px;">';
        $html .= '<button type="submit" name="submitDiagnostic" class="btn btn-info btn-lg">📊 Run Diagnostic</button>';
        $html .= '</form>';
        $html .= '<p style="margin-top:15px;"><strong>Step 2:</strong> If issues detected, fix them automatically</p>';
        $html .= '<form method="POST" style="display:inline-block;">';
        $html .= '<button type="submit" name="submitFixData" class="btn btn-warning btn-lg">🔧 Fix Missing Data</button>';
        $html .= '</form>';
        $html .= '<p class="help-block" style="margin-top:15px;">The fix will create missing language data, shop associations, stock records, and category permissions.</p>';
        $html .= '</div></div>';
        return $html;
    }
    
    private function renderImportDatabaseForm()
    {
        $html = '<div class="panel">';
        $html .= '<div class="panel-heading"><h3>Import Database</h3></div>';
        $html .= '<form method="POST" enctype="multipart/form-data">';
        $html .= '<div class="form-group">';
        $html .= '<label>SQL File from PS 1.7.6</label>';
        $html .= '<input type="file" name="database_file" class="form-control" accept=".sql" required />';
        $html .= '</div>';
        $html .= '<div class="panel panel-default">';
        $html .= '<div class="panel-heading"><strong>Clean Options</strong></div>';
        $html .= '<div class="panel-body">';
        $html .= '<div class="checkbox"><label><input type="checkbox" name="clean_products" value="1" checked /> Clean demo products & images</label></div>';
        $html .= '<div class="checkbox"><label><input type="checkbox" name="clean_categories" value="1" checked /> Clean demo categories (keep Home/Root)</label></div>';
        $html .= '<div class="checkbox"><label><input type="checkbox" name="clean_customers" value="1" checked /> Clean demo customers</label></div>';
        $html .= '<div class="checkbox"><label><input type="checkbox" name="clean_orders" value="1" /> Clean demo orders, invoices & carts</label></div>';
        $html .= '<p class="help-block"><strong>Note:</strong> Only check "Clean orders" if you want to remove demo data. Uncheck to keep existing orders.</p>';
        $html .= '</div></div>';
        $html .= '<div class="alert alert-warning"><strong>Important:</strong> Make sure you have a backup! This will modify your database.</div>';
        $html .= '<button type="submit" name="submitImportDatabase" class="btn btn-primary btn-lg">Import Database</button>';
        $html .= '</form>';
        $html .= '</div>';
        return $html;
    }
    
    private function renderImportImagesForm()
    {
        $maxUpload = ini_get('upload_max_filesize');
        $maxPost = ini_get('post_max_size');
        
        $html = '<div class="panel">';
        $html .= '<div class="panel-heading"><h3>Import Images</h3></div>';
        
        $html .= '<div class="alert alert-info">';
        $html .= '<strong>Server Limits:</strong><br>';
        $html .= 'Max Upload: ' . $maxUpload . ' | Max Post: ' . $maxPost . '<br><br>';
        $html .= '<strong>If file is too large (413 error):</strong><br>';
        $html .= '1. Extract the ZIP manually<br>';
        $html .= '2. Upload via FTP to /img/ directory<br>';
        $html .= '3. Or split ZIP into smaller parts';
        $html .= '</div>';
        
        $html .= '<form method="POST" enctype="multipart/form-data">';
        $html .= '<div class="form-group">';
        $html .= '<label>Images ZIP File (max ' . $maxUpload . ')</label>';
        $html .= '<input type="file" name="images_file" class="form-control" accept=".zip" required />';
        $html .= '</div>';
        $html .= '<button type="submit" name="submitImportImages" class="btn btn-success btn-lg">Import Images</button>';
        $html .= '</form>';
        $html .= '</div>';
        return $html;
    }
    
    private function processImportDatabase()
    {
        if (!isset($_FILES['database_file']) || $_FILES['database_file']['error'] !== UPLOAD_ERR_OK) {
            return '<div class="alert alert-danger">No file uploaded or upload error</div>';
        }

        $sqlContent = file_get_contents($_FILES['database_file']['tmp_name']);
        if (!$sqlContent) {
            return '<div class="alert alert-danger">Could not read SQL file</div>';
        }

        try {
            // Get current PrestaShop prefix
            $currentPrefix = _DB_PREFIX_;
            
            // Simple and effective SQL parser
            $db = Db::getInstance();
            
            // Clean demo data if requested
            $cleanedTables = array();
            $cleanProducts = isset($_POST['clean_products']) && $_POST['clean_products'] == '1';
            $cleanCategories = isset($_POST['clean_categories']) && $_POST['clean_categories'] == '1';
            $cleanCustomers = isset($_POST['clean_customers']) && $_POST['clean_customers'] == '1';
            $cleanOrders = isset($_POST['clean_orders']) && $_POST['clean_orders'] == '1';
            
            if ($cleanProducts || $cleanCategories || $cleanCustomers || $cleanOrders) {
                $cleanedTables = $this->cleanDemoData($db, $currentPrefix, $cleanProducts, $cleanCategories, $cleanCustomers, $cleanOrders);
            }
            
            // Remove block comments /* ... */
            $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);
            
            // Detect old prefix from SQL file (look for common tables)
            $oldPrefix = 'ps_'; // default
            if (preg_match('/(?:INSERT INTO|CREATE TABLE|DROP TABLE)\s+`?([a-z_]+)_configuration/i', $sqlContent, $matches)) {
                $oldPrefix = $matches[1] . '_';
            }
            
            // Replace old prefix with current PrestaShop prefix
            if ($oldPrefix !== $currentPrefix) {
                // Replace in table names: `old_table` -> `new_table`
                $sqlContent = preg_replace('/`' . preg_quote($oldPrefix, '/') . '([a-z_0-9]+)`/i', '`' . $currentPrefix . '$1`', $sqlContent);
                // Replace without backticks: old_table -> new_table (for some SQL syntaxes)
                $sqlContent = preg_replace('/\s' . preg_quote($oldPrefix, '/') . '([a-z_0-9]+)\s/i', ' ' . $currentPrefix . '$1 ', $sqlContent);
            }
            
            // Split into lines
            $lines = explode("\n", $sqlContent);
            $statements = [];
            $current = '';
            $skipCurrentStatement = false;
            
            foreach ($lines as $line) {
                $trimmed = trim($line);
                
                // Skip empty lines
                if (empty($trimmed)) {
                    continue;
                }
                
                // Check if this is a DROP TABLE or CREATE TABLE - skip them completely
                // We want to keep PS 9 table structures, only import data
                if (preg_match('/(?:DROP TABLE|CREATE TABLE)/i', $trimmed)) {
                    $skipCurrentStatement = true;
                    continue;
                }
                
                // If we're skipping, wait for the statement to end
                if ($skipCurrentStatement) {
                    if (substr($trimmed, -1) === ';') {
                        $skipCurrentStatement = false;
                    }
                    continue;
                }
                
                // Skip comment-only lines (but not lines with -- followed by SQL)
                if (substr($trimmed, 0, 2) === '--' && 
                    stripos($trimmed, 'INSERT') === false && 
                    stripos($trimmed, 'CREATE') === false && 
                    stripos($trimmed, 'DROP') === false) {
                    continue;
                }
                
                // Skip # comments
                if (substr($trimmed, 0, 1) === '#') {
                    continue;
                }
                
                // Skip DELIMITER commands
                if (stripos($trimmed, 'DELIMITER') === 0) {
                    continue;
                }
                
                // Add line to current statement
                $current .= ' ' . $trimmed;
                
                // Check if statement ends (semicolon at end of line)
                if (substr($trimmed, -1) === ';') {
                    $stmt = trim($current);
                    if (!empty($stmt)) {
                        $statements[] = $stmt;
                    }
                    $current = '';
                }
            }
            
            // Add last statement if any
            if (!empty(trim($current))) {
                $statements[] = trim($current);
            }
            
            $imported = 0;
            $errors = array();
            $tableStats = array();
            $detailedLog = array(); // New: detailed logging for debugging
            $criticalTables = array('orders', 'order_detail', 'category_lang', 'product_lang');
            
            foreach ($statements as $statement) {
                if (empty($statement)) {
                    continue;
                }
                
                // Extract table name for statistics
                $tableName = '';
                if (preg_match('/(?:INSERT INTO|REPLACE INTO)\s+`?(\w+)`?/i', $statement, $matches)) {
                    $tableName = $matches[1];
                }
                
                // Use INSERT IGNORE for _lang tables (they have foreign keys that may fail with REPLACE)
                // Use REPLACE INTO for other main tables
                $langTables = array('_lang', '_shop');
                $isLangTable = false;
                foreach ($langTables as $suffix) {
                    if (stripos($tableName, $suffix) !== false) {
                        $statement = preg_replace('/^(?:INSERT|REPLACE) INTO/i', 'INSERT IGNORE INTO', $statement);
                        $isLangTable = true;
                        break;
                    }
                }
                
                if (!$isLangTable) {
                    $mainTables = array('product', 'category', 'customer', 'orders', 'cart', 'manufacturer', 'supplier');
                    foreach ($mainTables as $mainTable) {
                        if (stripos($tableName, $mainTable) !== false) {
                            $statement = preg_replace('/^INSERT INTO/i', 'REPLACE INTO', $statement);
                            break;
                        }
                    }
                }
                
                try {
                    $result = $db->execute($statement);
                    $affectedRows = $db->Affected_Rows();
                    $dbError = $db->getMsgError(); // Get last database error
                    
                    // Log critical tables with detailed info
                    if ($tableName) {
                        foreach ($criticalTables as $critTable) {
                            if (stripos($tableName, $critTable) !== false) {
                                $logMsg = sprintf(
                                    '[%s] Execute: %s | Affected: %d',
                                    $tableName,
                                    $result ? 'SUCCESS' : 'FAILED',
                                    $affectedRows
                                );
                                if (!$result || $affectedRows == 0) {
                                    $logMsg .= ' | Error: ' . ($dbError ? $dbError : 'None reported');
                                    $logMsg .= ' | SQL: ' . substr($statement, 0, 200);
                                }
                                $detailedLog[] = $logMsg;
                                break;
                            }
                        }
                    }
                    
                    if ($result) {
                        $imported++;
                        if ($tableName) {
                            if (!isset($tableStats[$tableName])) {
                                $tableStats[$tableName] = array('statements' => 0, 'rows' => 0);
                            }
                            $tableStats[$tableName]['statements']++;
                            $tableStats[$tableName]['rows'] += $affectedRows;
                        }
                        
                        // If result is TRUE but no rows affected, log as warning for critical tables
                        if ($affectedRows == 0) {
                            foreach ($criticalTables as $critTable) {
                                if (stripos($tableName, $critTable) !== false) {
                                    $errorMsg = '[' . $tableName . '] Execute SUCCESS but 0 rows affected';
                                    if ($dbError) {
                                        $errorMsg .= ' | DB Error: ' . $dbError;
                                    }
                                    $errorMsg .= ' | SQL: ' . substr($statement, 0, 150) . '...';
                                    $errors[] = $errorMsg;
                                    break;
                                }
                            }
                        }
                    } else {
                        $errorMsg = 'Execute returned FALSE';
                        if ($tableName) {
                            $errorMsg .= ' [' . $tableName . ']';
                        }
                        if ($dbError) {
                            $errorMsg .= ' | DB Error: ' . $dbError;
                        }
                        $errorMsg .= ' | Affected Rows: ' . $affectedRows;
                        $errorMsg .= ' | SQL: ' . substr($statement, 0, 150) . '...';
                        $errors[] = $errorMsg;
                    }
                } catch (Exception $e) {
                    $errorMsg = $e->getMessage();
                    if ($tableName) {
                        $errorMsg = '[' . $tableName . '] ' . $errorMsg;
                    }
                    $errorMsg .= ' | SQL: ' . substr($statement, 0, 150) . '...';
                    $errors[] = $errorMsg;
                    
                    // Also log to detailed log for critical tables
                    foreach ($criticalTables as $critTable) {
                        if (stripos($tableName, $critTable) !== false) {
                            $detailedLog[] = '[' . $tableName . '] EXCEPTION: ' . $e->getMessage();
                            break;
                        }
                    }
                }
            }

            // Get counts from database
            $dbCounts = array();
            $importantTables = array('product', 'product_lang', 'product_shop', 'category', 'customer', 'orders', 'cart', 'cart_product', 'image');
            foreach ($importantTables as $table) {
                $fullTable = $currentPrefix . $table;
                try {
                    $count = $db->getValue('SELECT COUNT(*) FROM `' . $fullTable . '`');
                    $dbCounts[$table] = (int)$count;
                } catch (Exception $e) {
                    $dbCounts[$table] = 'Error: ' . $e->getMessage();
                }
            }
            
            // Check data integrity
            $integrityIssues = array();
            
            // Check products without prices in product_shop
            try {
                $productsNoPriceShop = $db->getValue('SELECT COUNT(DISTINCT p.id_product) FROM `' . $currentPrefix . 'product` p 
                    LEFT JOIN `' . $currentPrefix . 'product_shop` ps ON p.id_product = ps.id_product 
                    WHERE ps.id_product IS NULL');
                if ($productsNoPriceShop > 0) {
                    $integrityIssues[] = $productsNoPriceShop . ' products missing in product_shop (no prices!)';
                }
            } catch (Exception $e) {}
            
            // Check cart products with invalid product_id
            try {
                $invalidCartProducts = $db->getValue('SELECT COUNT(*) FROM `' . $currentPrefix . 'cart_product` cp 
                    LEFT JOIN `' . $currentPrefix . 'product` p ON cp.id_product = p.id_product 
                    WHERE p.id_product IS NULL');
                if ($invalidCartProducts > 0) {
                    $integrityIssues[] = $invalidCartProducts . ' cart items reference non-existent products';
                }
            } catch (Exception $e) {}
            
            // Check products without lang data
            try {
                $productsNoLang = $db->getValue('SELECT COUNT(DISTINCT p.id_product) FROM `' . $currentPrefix . 'product` p 
                    LEFT JOIN `' . $currentPrefix . 'product_lang` pl ON p.id_product = pl.id_product 
                    WHERE pl.id_product IS NULL');
                if ($productsNoLang > 0) {
                    $integrityIssues[] = $productsNoLang . ' products missing in product_lang (no names!)';
                }
            } catch (Exception $e) {}

            // Auto-fix missing data after import
            $fixedRecords = $this->fixMissingData($db, $currentPrefix);

            $html = '<div class="alert alert-success">';
            $html .= '<strong>' . $imported . ' SQL statements imported successfully</strong><br>';
            $html .= 'Old prefix: <code>' . htmlspecialchars($oldPrefix) . '</code> → New prefix: <code>' . htmlspecialchars($currentPrefix) . '</code>';
            if (count($cleanedTables) > 0) {
                $html .= '<br><strong>Cleaned tables:</strong> ' . count($cleanedTables) . ' tables (demo data removed)';
            }
            if ($fixedRecords > 0) {
                $html .= '<br><strong>Auto-fixed records:</strong> ' . $fixedRecords . ' missing data entries created';
            }
            $html .= '</div>';
            
            // Show table statistics
            if (count($tableStats) > 0) {
                $html .= '<div class="panel"><div class="panel-heading"><h4>Tables Imported</h4></div>';
                $html .= '<div class="panel-body"><table class="table table-striped table-condensed">';
                $html .= '<tr><th>Table</th><th>Statements</th><th>Rows Affected</th><th>Current Count</th></tr>';
                
                foreach ($tableStats as $table => $stats) {
                    $shortTable = str_replace($currentPrefix, '', $table);
                    $currentCount = isset($dbCounts[$shortTable]) ? $dbCounts[$shortTable] : '-';
                    
                    // Highlight important tables with issues
                    $rowClass = '';
                    if ($stats['rows'] == 0 || (($shortTable == 'category_lang' || $shortTable == 'product_lang') && $currentCount == 0)) {
                        $rowClass = ' class="danger"';
                    } elseif ($stats['rows'] < $stats['statements']) {
                        $rowClass = ' class="warning"';
                    }
                    
                    $html .= '<tr' . $rowClass . '><td>' . htmlspecialchars($table) . '</td><td>' . $stats['statements'] . '</td><td><strong>' . $stats['rows'] . '</strong></td><td>' . $currentCount . '</td></tr>';
                }
                $html .= '</table></div></div>';
            }
            
            // Show detailed log for critical tables
            if (count($detailedLog) > 0) {
                $html .= '<div class="panel panel-info"><div class="panel-heading"><h4>Detailed Import Log (Critical Tables)</h4></div>';
                $html .= '<div class="panel-body"><pre style="max-height:400px;overflow-y:auto;font-size:11px;">';
                foreach ($detailedLog as $logEntry) {
                    $html .= htmlspecialchars($logEntry) . "\n";
                }
                $html .= '</pre></div></div>';
            }
            
            // Show important tables status
            $html .= '<div class="panel"><div class="panel-heading"><h4>Important Tables Status</h4></div>';
            $html .= '<div class="panel-body"><table class="table table-bordered">';
            $html .= '<tr><th>Table</th><th>Count</th></tr>';
            foreach ($dbCounts as $table => $count) {
                $color = (is_numeric($count) && $count > 0) ? 'success' : 'danger';
                $html .= '<tr class="' . $color . '"><td>' . htmlspecialchars($currentPrefix . $table) . '</td><td><strong>' . $count . '</strong></td></tr>';
            }
            $html .= '</table></div></div>';
            
            // Show integrity issues
            if (count($integrityIssues) > 0) {
                $html .= '<div class="alert alert-danger"><strong>⚠ Data Integrity Issues Detected:</strong><ul>';
                foreach ($integrityIssues as $issue) {
                    $html .= '<li>' . htmlspecialchars($issue) . '</li>';
                }
                $html .= '</ul><p><strong>Recommendation:</strong> The SQL file may be incomplete or corrupted. Re-export from PS 1.7.6 and ensure all related tables are included (product, product_shop, product_lang).</p></div>';
            }
            if (count($errors) > 0) {
                // Group errors by table
                $errorsByTable = array();
                foreach ($errors as $error) {
                    if (preg_match('/\[(\w+)\]/', $error, $matches)) {
                        $table = $matches[1];
                        if (!isset($errorsByTable[$table])) {
                            $errorsByTable[$table] = array();
                        }
                        $errorsByTable[$table][] = $error;
                    } else {
                        if (!isset($errorsByTable['unknown'])) {
                            $errorsByTable['unknown'] = array();
                        }
                        $errorsByTable['unknown'][] = $error;
                    }
                }
                
                $html .= '<div class="alert alert-danger"><strong>' . count($errors) . ' errors occurred</strong><br>';
                
                // Show errors for critical tables first
                $criticalTables = array('category_lang', 'product_lang', 'orders', 'order_detail');
                foreach ($criticalTables as $critTable) {
                    foreach ($errorsByTable as $table => $tableErrors) {
                        if (stripos($table, $critTable) !== false) {
                            $html .= '<h5 style="color:#721c24;margin-top:15px;">Errors in ' . htmlspecialchars($table) . ' (' . count($tableErrors) . ')</h5>';
                            $html .= '<pre style="max-height:200px;overflow-y:auto;font-size:11px;">';
                            foreach (array_slice($tableErrors, 0, 10) as $error) {
                                $html .= htmlspecialchars($error) . "\n";
                            }
                            if (count($tableErrors) > 10) {
                                $html .= '... and ' . (count($tableErrors) - 10) . ' more errors in this table';
                            }
                            $html .= '</pre>';
                            unset($errorsByTable[$table]);
                        }
                    }
                }
                
                // Show remaining errors collapsed
                if (count($errorsByTable) > 0) {
                    $html .= '<details><summary>Other errors (' . array_sum(array_map('count', $errorsByTable)) . ' total)</summary>';
                    foreach ($errorsByTable as $table => $tableErrors) {
                        $html .= '<h6>' . htmlspecialchars($table) . ' (' . count($tableErrors) . ')</h6>';
                        $html .= '<pre style="max-height:150px;overflow-y:auto;font-size:10px;">';
                        foreach (array_slice($tableErrors, 0, 5) as $error) {
                            $html .= htmlspecialchars($error) . "\n";
                        }
                        if (count($tableErrors) > 5) {
                            $html .= '... ' . (count($tableErrors) - 5) . ' more';
                        }
                        $html .= '</pre>';
                    }
                    $html .= '</details>';
                }
                
                $html .= '</div>';
            }
            
            return $html;
            
        } catch (Exception $e) {
            return '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
    
    private function processImportImages()
    {
        if (!isset($_FILES['images_file']) || $_FILES['images_file']['error'] !== UPLOAD_ERR_OK) {
            return '<div class="alert alert-danger">No file uploaded</div>';
        }

        try {
            $zip = new ZipArchive();
            if ($zip->open($_FILES['images_file']['tmp_name']) !== true) {
                return '<div class="alert alert-danger">Could not open ZIP file</div>';
            }

            $extracted = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (substr($filename, -1) === '/' || strpos($filename, 'img/') !== 0) {
                    continue;
                }

                $targetPath = _PS_ROOT_DIR_ . '/' . $filename;
                $targetDir = dirname($targetPath);

                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                $content = $zip->getFromIndex($i);
                if ($content !== false && file_put_contents($targetPath, $content)) {
                    $extracted++;
                    @chmod($targetPath, 0644);
                }
            }

            $zip->close();
            return '<div class="alert alert-success">' . $extracted . ' images imported successfully</div>';
        } catch (Exception $e) {
            return '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
    
    private function cleanDemoData($db, $prefix, $cleanProducts = true, $cleanCategories = true, $cleanCustomers = true, $cleanOrders = true)
    {
        $cleanedTables = array();
        
        // Product-related tables
        if ($cleanProducts) {
            $productTables = array(
                'product', 'product_shop', 'product_lang', 'product_attribute', 'product_attribute_shop',
                'product_attribute_combination', 'product_attribute_image', 'product_carrier',
                'product_country_tax', 'product_download', 'product_group_reduction_cache',
                'product_sale', 'product_supplier', 'product_tag', 'product_attachment',
                'stock_available', 'stock', 'stock_mvt',
                'image', 'image_lang', 'image_shop',
                'category_product',
                'feature_product', 'feature_value', 'feature_value_lang',
                'attribute', 'attribute_lang', 'attribute_shop',
                'manufacturer', 'manufacturer_lang', 'manufacturer_shop',
                'supplier', 'supplier_lang', 'supplier_shop',
                'specific_price', 'specific_price_priority', 'specific_price_rule',
                'specific_price_rule_condition', 'specific_price_rule_condition_group',
                'tag', 'tag_count', 'compare', 'compare_product', 'search_index', 'search_word',
                'customization', 'customization_field', 'customization_field_lang', 'customized_data'
            );
            
            foreach ($productTables as $table) {
                try {
                    $fullTable = $prefix . $table;
                    $db->execute('TRUNCATE TABLE `' . $fullTable . '`');
                    $cleanedTables[] = $table;
                } catch (Exception $e) {}
            }
        }
        
        // Customer-related tables
        if ($cleanCustomers) {
            $customerTables = array(
                'customer', 'customer_group', 'customer_message', 'customer_thread', 'address', 'guest'
            );
            
            foreach ($customerTables as $table) {
                try {
                    $fullTable = $prefix . $table;
                    $db->execute('TRUNCATE TABLE `' . $fullTable . '`');
                    $cleanedTables[] = $table;
                } catch (Exception $e) {}
            }
        }
        
        // Orders, carts, invoices - only if explicitly requested
        if ($cleanOrders) {
            $orderTables = array(
                'orders', 'order_detail', 'order_detail_tax', 'order_carrier', 'order_cart_rule',
                'order_history', 'order_invoice', 'order_invoice_payment', 'order_invoice_tax',
                'order_payment', 'order_return', 'order_return_detail', 'order_slip', 'order_slip_detail',
                'cart', 'cart_product', 'cart_cart_rule',
                'cart_rule', 'cart_rule_carrier', 'cart_rule_combination', 'cart_rule_country',
                'cart_rule_group', 'cart_rule_lang', 'cart_rule_product_rule',
                'cart_rule_product_rule_group', 'cart_rule_product_rule_value', 'cart_rule_shop',
                'message', 'message_readed'
            );
            
            foreach ($orderTables as $table) {
                try {
                    $fullTable = $prefix . $table;
                    $db->execute('TRUNCATE TABLE `' . $fullTable . '`');
                    $cleanedTables[] = $table;
                } catch (Exception $e) {}
            }
        }
        
        // Categories - special handling
        if ($cleanCategories) {
            try {
                $db->execute('DELETE FROM `' . $prefix . 'category_lang` WHERE id_category > 2');
                $cleanedTables[] = 'category_lang (partial)';
            } catch (Exception $e) {}
            
            try {
                $db->execute('DELETE FROM `' . $prefix . 'category_shop` WHERE id_category > 2');
                $cleanedTables[] = 'category_shop (partial)';
            } catch (Exception $e) {}
            
            try {
                $db->execute('DELETE FROM `' . $prefix . 'category_group` WHERE id_category > 2');
                $cleanedTables[] = 'category_group (partial)';
            } catch (Exception $e) {}
            
            try {
                $db->execute('DELETE FROM `' . $prefix . 'category` WHERE id_category > 2');
                $cleanedTables[] = 'category (partial - kept Home/Root)';
            } catch (Exception $e) {}
        }
        
        return $cleanedTables;
    }
    
    private function fixMissingData($db, $prefix)
    {
        $fixed = 0;
        
        try {
            // 1. Fix products without language data
            $sql = "INSERT IGNORE INTO `{$prefix}product_lang` 
                    (id_product, id_shop, id_lang, description, description_short, link_rewrite, meta_description, meta_keywords, meta_title, name, available_now, available_later)
                    SELECT p.id_product, 1, 1, 
                           '', '', 
                           CONCAT('product-', p.id_product),
                           '', '', '',
                           CONCAT('Product ', p.id_product),
                           '', ''
                    FROM `{$prefix}product` p
                    LEFT JOIN `{$prefix}product_lang` pl ON p.id_product = pl.id_product AND pl.id_lang = 1
                    WHERE pl.id_product IS NULL";
            $db->execute($sql);
            $fixed += $db->Affected_Rows();
            
            // 2. Fix categories without language data
            $sql = "INSERT IGNORE INTO `{$prefix}category_lang`
                    (id_category, id_shop, id_lang, name, description, link_rewrite, meta_title, meta_keywords, meta_description)
                    SELECT c.id_category, 1, 1,
                           CONCAT('Category ', c.id_category),
                           '',
                           CONCAT('category-', c.id_category),
                           '', '', ''
                    FROM `{$prefix}category` c
                    LEFT JOIN `{$prefix}category_lang` cl ON c.id_category = cl.id_category AND cl.id_lang = 1
                    WHERE cl.id_category IS NULL AND c.id_category > 2";
            $db->execute($sql);
            $fixed += $db->Affected_Rows();
            
            // 3. Fix products without shop association
            $sql = "INSERT IGNORE INTO `{$prefix}product_shop`
                    (id_product, id_shop, id_category_default, price, active, available_for_order, show_price, visibility)
                    SELECT p.id_product, 1,
                           COALESCE(p.id_category_default, 2),
                           0.00, 1, 1, 1, 'both'
                    FROM `{$prefix}product` p
                    LEFT JOIN `{$prefix}product_shop` ps ON p.id_product = ps.id_product
                    WHERE ps.id_product IS NULL";
            $db->execute($sql);
            $fixed += $db->Affected_Rows();
            
            // 4. Fix categories without shop association
            $sql = "INSERT IGNORE INTO `{$prefix}category_shop`
                    (id_category, id_shop, position)
                    SELECT c.id_category, 1, c.position
                    FROM `{$prefix}category` c
                    LEFT JOIN `{$prefix}category_shop` cs ON c.id_category = cs.id_category
                    WHERE cs.id_category IS NULL AND c.id_category > 2";
            $db->execute($sql);
            $fixed += $db->Affected_Rows();
            
            // 5. Fix stock_available
            $sql = "INSERT IGNORE INTO `{$prefix}stock_available`
                    (id_product, id_product_attribute, id_shop, id_shop_group, quantity, depends_on_stock, out_of_stock)
                    SELECT p.id_product, 0, 1, 0, 0, 0, 2
                    FROM `{$prefix}product` p
                    LEFT JOIN `{$prefix}stock_available` sa ON p.id_product = sa.id_product AND sa.id_product_attribute = 0
                    WHERE sa.id_product IS NULL";
            $db->execute($sql);
            $fixed += $db->Affected_Rows();
            
            // 6. Fix category_group permissions
            foreach (array(1, 2, 3) as $idGroup) {
                $sql = "INSERT IGNORE INTO `{$prefix}category_group` (id_category, id_group)
                        SELECT c.id_category, {$idGroup}
                        FROM `{$prefix}category` c
                        LEFT JOIN `{$prefix}category_group` cg ON c.id_category = cg.id_category AND cg.id_group = {$idGroup}
                        WHERE cg.id_category IS NULL AND c.id_category > 2";
                $db->execute($sql);
                $fixed += $db->Affected_Rows();
            }
            
            // 7. Fix orphan products
            $db->execute("UPDATE `{$prefix}product` p
                         SET p.id_category_default = 2
                         WHERE p.id_category_default NOT IN (SELECT id_category FROM `{$prefix}category`)");
            
            // 8. Clear cache
            $db->execute("TRUNCATE TABLE `{$prefix}smarty_cache`");
            $db->execute("TRUNCATE TABLE `{$prefix}smarty_lazy_cache`");
            
        } catch (Exception $e) {
            // Continue even if some fixes fail
        }
        
        return $fixed;
    }
    
    private function runDiagnostic()
    {
        $db = Db::getInstance();
        $prefix = _DB_PREFIX_;
        
        $html = '<div class="panel">';
        $html .= '<div class="panel-heading"><h3>📊 Diagnostic Results</h3></div>';
        $html .= '<div class="panel-body" style="max-height:600px; overflow-y:auto;">';
        
        // Table counts
        $html .= '<h4>Table Row Counts</h4>';
        $html .= '<table class="table table-bordered table-striped">';
        $html .= '<tr><th>Table</th><th>Rows</th><th>Status</th></tr>';
        
        $tables = array(
            'product' => 'Products',
            'product_lang' => 'Product Languages',
            'product_shop' => 'Product Shop',
            'category' => 'Categories',
            'category_lang' => 'Category Languages',
            'category_shop' => 'Category Shop',
            'customer' => 'Customers',
            'orders' => 'Orders',
            'order_detail' => 'Order Details',
            'cart' => 'Carts',
            'cart_product' => 'Cart Products',
            'image' => 'Images',
            'manufacturer' => 'Manufacturers',
            'supplier' => 'Suppliers'
        );
        
        foreach ($tables as $table => $label) {
            try {
                $count = $db->getValue('SELECT COUNT(*) FROM `' . $prefix . $table . '`');
                $status = ($count > 0) ? '<span style="color:green;">✓</span>' : '<span style="color:orange;">Empty</span>';
                $html .= '<tr><td>' . $label . '</td><td><strong>' . $count . '</strong></td><td>' . $status . '</td></tr>';
            } catch (Exception $e) {
                $html .= '<tr><td>' . $label . '</td><td colspan="2" style="color:red;">Error: ' . $e->getMessage() . '</td></tr>';
            }
        }
        $html .= '</table>';
        
        // Integrity checks
        $html .= '<h4>Data Integrity Checks</h4>';
        $html .= '<table class="table table-bordered">';
        $html .= '<tr><th>Check</th><th>Result</th></tr>';
        
        // Products without language
        try {
            $count = $db->getValue('SELECT COUNT(DISTINCT p.id_product) FROM `' . $prefix . 'product` p 
                LEFT JOIN `' . $prefix . 'product_lang` pl ON p.id_product = pl.id_product 
                WHERE pl.id_product IS NULL');
            $status = ($count == 0) ? '<span style="color:green;">✓ OK</span>' : '<span style="color:red;">✗ ' . $count . ' products</span>';
            $html .= '<tr><td>Products without language data</td><td>' . $status . '</td></tr>';
        } catch (Exception $e) {
            $html .= '<tr><td>Products without language data</td><td style="color:red;">Error</td></tr>';
        }
        
        // Products without shop
        try {
            $count = $db->getValue('SELECT COUNT(DISTINCT p.id_product) FROM `' . $prefix . 'product` p 
                LEFT JOIN `' . $prefix . 'product_shop` ps ON p.id_product = ps.id_product 
                WHERE ps.id_product IS NULL');
            $status = ($count == 0) ? '<span style="color:green;">✓ OK</span>' : '<span style="color:red;">✗ ' . $count . ' products</span>';
            $html .= '<tr><td>Products without shop association</td><td>' . $status . '</td></tr>';
        } catch (Exception $e) {
            $html .= '<tr><td>Products without shop association</td><td style="color:red;">Error</td></tr>';
        }
        
        // Categories without language
        try {
            $count = $db->getValue('SELECT COUNT(DISTINCT c.id_category) FROM `' . $prefix . 'category` c 
                LEFT JOIN `' . $prefix . 'category_lang` cl ON c.id_category = cl.id_category 
                WHERE cl.id_category IS NULL AND c.id_category > 2');
            $status = ($count == 0) ? '<span style="color:green;">✓ OK</span>' : '<span style="color:red;">✗ ' . $count . ' categories</span>';
            $html .= '<tr><td>Categories without language data</td><td>' . $status . '</td></tr>';
        } catch (Exception $e) {
            $html .= '<tr><td>Categories without language data</td><td style="color:red;">Error</td></tr>';
        }
        
        // Products without stock
        try {
            $count = $db->getValue('SELECT COUNT(DISTINCT p.id_product) FROM `' . $prefix . 'product` p 
                LEFT JOIN `' . $prefix . 'stock_available` sa ON p.id_product = sa.id_product 
                WHERE sa.id_product IS NULL');
            $status = ($count == 0) ? '<span style="color:green;">✓ OK</span>' : '<span style="color:red;">✗ ' . $count . ' products</span>';
            $html .= '<tr><td>Products without stock data</td><td>' . $status . '</td></tr>';
        } catch (Exception $e) {
            $html .= '<tr><td>Products without stock data</td><td style="color:red;">Error</td></tr>';
        }
        
        // Cart products with invalid product_id
        try {
            $count = $db->getValue('SELECT COUNT(*) FROM `' . $prefix . 'cart_product` cp 
                LEFT JOIN `' . $prefix . 'product` p ON cp.id_product = p.id_product 
                WHERE p.id_product IS NULL');
            $status = ($count == 0) ? '<span style="color:green;">✓ OK</span>' : '<span style="color:red;">✗ ' . $count . ' cart items</span>';
            $html .= '<tr><td>Cart items with invalid products</td><td>' . $status . '</td></tr>';
        } catch (Exception $e) {
            $html .= '<tr><td>Cart items with invalid products</td><td style="color:red;">Error</td></tr>';
        }
        
        $html .= '</table>';
        
        // Sample data
        $html .= '<h4>Sample Data</h4>';
        
        try {
            $sampleProduct = $db->getRow('SELECT * FROM `' . $prefix . 'product` LIMIT 1');
            if ($sampleProduct) {
                $html .= '<h5>Sample Product (ID: ' . $sampleProduct['id_product'] . ')</h5>';
                $html .= '<pre style="font-size:11px; max-height:200px; overflow-y:auto;">' . print_r($sampleProduct, true) . '</pre>';
                
                $productLang = $db->getRow('SELECT * FROM `' . $prefix . 'product_lang` WHERE id_product = ' . (int)$sampleProduct['id_product'] . ' LIMIT 1');
                if ($productLang) {
                    $html .= '<h5>Product Language Data</h5>';
                    $html .= '<pre style="font-size:11px; max-height:200px; overflow-y:auto;">' . print_r($productLang, true) . '</pre>';
                } else {
                    $html .= '<p style="color:red;">⚠ No language data for this product!</p>';
                }
            }
        } catch (Exception $e) {
            $html .= '<p style="color:red;">Error getting sample data: ' . $e->getMessage() . '</p>';
        }
        
        $html .= '</div></div>';
        
        return $html;
    }
    
    private function manualFixData()
    {
        $db = Db::getInstance();
        $prefix = _DB_PREFIX_;
        
        $html = '<div class="alert alert-info"><strong>🔧 Running data repair...</strong></div>';
        
        $fixed = $this->fixMissingData($db, $prefix);
        
        $html .= '<div class="alert alert-success">';
        $html .= '<h4>✅ Data Repair Complete!</h4>';
        $html .= '<p><strong>' . $fixed . ' records</strong> were created or updated.</p>';
        $html .= '<p>Run diagnostic again to verify all issues are resolved.</p>';
        $html .= '</div>';
        
        // Clear PrestaShop cache
        try {
            Tools::clearCache();
            $html .= '<div class="alert alert-success">Cache cleared successfully</div>';
        } catch (Exception $e) {
            $html .= '<div class="alert alert-warning">Could not clear cache automatically. Please clear it manually from Performance settings.</div>';
        }
        
        return $html;
    }
    
    /**
     * Remove a column from INSERT statement (both column name and its value)
     * @param string $statement SQL INSERT statement
     * @param string $columnName Column to remove (without backticks)
     * @param array $tablePatterns Array of table name patterns to match
     * @return string Modified statement
     */
    private function removeColumnFromInsert($statement, $columnName, $tablePatterns = array())
    {
        // Check if this statement affects one of the target tables
        $matchesTable = empty($tablePatterns);
        if (!$matchesTable) {
            foreach ($tablePatterns as $pattern) {
                if (stripos($statement, $pattern) !== false) {
                    $matchesTable = true;
                    break;
                }
            }
        }
        
        if (!$matchesTable) {
            return $statement;
        }
        
        // Check if column exists in statement
        if (stripos($statement, '`' . $columnName . '`') === false && 
            stripos($statement, $columnName) === false) {
            return $statement;
        }
        
        // Parse INSERT statement: INSERT INTO table (col1, col2, col3) VALUES (val1, val2, val3)
        if (!preg_match('/^(INSERT(?:\s+IGNORE)?(?:\s+INTO)?)\s+(`?\w+`?)\s*\((.*?)\)\s*VALUES\s*(.*)$/is', $statement, $matches)) {
            return $statement; // Can't parse, return as-is
        }
        
        $insertType = $matches[1];
        $tableName = $matches[2];
        $columnsPart = $matches[3];
        $valuesPart = $matches[4];
        
        // Split columns
        $columns = array_map('trim', explode(',', $columnsPart));
        
        // Find index of column to remove
        $columnIndex = -1;
        foreach ($columns as $index => $col) {
            $cleanCol = trim($col, '` ');
            if (strcasecmp($cleanCol, $columnName) === 0) {
                $columnIndex = $index;
                break;
            }
        }
        
        if ($columnIndex === -1) {
            return $statement; // Column not found
        }
        
        // Remove column from list
        unset($columns[$columnIndex]);
        $newColumnsPart = implode(', ', $columns);
        
        // Parse VALUES part - can have multiple rows: (val1, val2), (val1, val2), ...
        // This is complex because values can contain commas inside strings
        // For safety, we'll use a simpler approach: remove the Nth value from each row
        
        // Split by "), (" to get individual rows
        $valuesPart = trim($valuesPart);
        preg_match_all('/\([^)]*\)(?:,|;|$)/s', $valuesPart, $rowMatches);
        
        $newRows = array();
        foreach ($rowMatches[0] as $row) {
            $row = trim($row, " ,;");
            // Remove outer parentheses
            $row = trim($row, '()');
            
            // Split values - this is tricky with quoted strings
            // Use a simple state machine
            $values = $this->splitValues($row);
            
            if (count($values) > $columnIndex) {
                unset($values[$columnIndex]);
                $newRows[] = '(' . implode(', ', $values) . ')';
            } else {
                $newRows[] = '(' . $row . ')'; // Couldn't parse, keep as-is
            }
        }
        
        $newValuesPart = implode(', ', $newRows);
        
        // Reconstruct statement
        return $insertType . ' ' . $tableName . ' (' . $newColumnsPart . ') VALUES ' . $newValuesPart . ';';
    }
    
    /**
     * Split comma-separated values respecting quotes
     * @param string $str Values string
     * @return array Array of values
     */
    private function splitValues($str)
    {
        $values = array();
        $current = '';
        $inQuote = false;
        $quoteChar = '';
        $escaped = false;
        
        for ($i = 0; $i < strlen($str); $i++) {
            $char = $str[$i];
            
            if ($escaped) {
                $current .= $char;
                $escaped = false;
                continue;
            }
            
            if ($char === '\\') {
                $current .= $char;
                $escaped = true;
                continue;
            }
            
            if (($char === "'" || $char === '"') && !$inQuote) {
                $inQuote = true;
                $quoteChar = $char;
                $current .= $char;
                continue;
            }
            
            if ($char === $quoteChar && $inQuote) {
                $inQuote = false;
                $current .= $char;
                continue;
            }
            
            if ($char === ',' && !$inQuote) {
                $values[] = trim($current);
                $current = '';
                continue;
            }
            
            $current .= $char;
        }
        
        if (!empty(trim($current))) {
            $values[] = trim($current);
        }
        
        return $values;
    }
}

