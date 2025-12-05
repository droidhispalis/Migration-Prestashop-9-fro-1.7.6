# Installation Guide - PrestaShop 9 Database Importer

## 📋 Prerequisites

Before installing this module, ensure you have:

### Server Requirements
- ✅ **PrestaShop**: 9.0 or higher
- ✅ **PHP**: 8.1 or higher
- ✅ **MySQL**: 5.7+ or MariaDB 10.3+
- ✅ **Apache/Nginx**: With mod_rewrite enabled
- ✅ **Disk Space**: At least 2x your SQL file size

### PHP Configuration
```ini
max_execution_time = 3600
max_input_time = 3600
memory_limit = 512M
upload_max_filesize = 512M
post_max_size = 512M
```

### Required PHP Extensions
- mysqli or PDO_MySQL
- zip
- mbstring
- json
- curl

---

## 🚀 Installation Methods

### Method 1: Via PrestaShop Back Office (Recommended)

1. **Download the module:**
   - Go to the [Releases page](https://github.com/droidhispalis/Migration-Prestashop-9-fro-1.7.6/releases)
   - Download the latest `psimporter9from178.zip`

2. **Install in PrestaShop:**
   - Login to your PrestaShop 9 Back Office
   - Go to **Modules** → **Module Manager**
   - Click **Upload a module** (top right)
   - Drag and drop or select `psimporter9from178.zip`
   - Wait for upload to complete
   - Click **Install**
   - Click **Configure** when prompted

3. **Verify installation:**
   - The module should appear in your modules list
   - Status should be "Enabled"

---

### Method 2: Via FTP/SFTP

1. **Download and extract:**
   ```bash
   wget https://github.com/droidhispalis/Migration-Prestashop-9-fro-1.7.6/archive/refs/heads/main.zip
   unzip main.zip
   ```

2. **Upload via FTP:**
   - Connect to your server via FTP
   - Navigate to `/modules/`
   - Upload the `psimporter9from178` folder
   - Ensure correct permissions:
     ```bash
     chmod -R 755 modules/psimporter9from178
     chown -R www-data:www-data modules/psimporter9from178
     ```

3. **Install via Back Office:**
   - Go to **Modules** → **Module Manager**
   - Search for "PrestaShop 9 Importer"
   - Click **Install**
   - Click **Configure**

---

### Method 3: Via Git Clone (For Developers)

1. **Clone the repository:**
   ```bash
   cd /path/to/prestashop/modules/
   git clone https://github.com/droidhispalis/Migration-Prestashop-9-fro-1.7.6.git psimporter9from178
   ```

2. **Set permissions:**
   ```bash
   chmod -R 755 psimporter9from178
   chown -R www-data:www-data psimporter9from178
   ```

3. **Install via Back Office:**
   - Go to **Modules** → **Module Manager**
   - Search for "PrestaShop 9 Importer"
   - Click **Install**

---

## ⚙️ Post-Installation Configuration

### 1. Run Diagnostic

Before your first import, run the diagnostic tool:

```
https://your-store.com/modules/psimporter9from178/diagnostic.php
```

This will check:
- PHP version and configuration
- Database connectivity
- File permissions
- Server limits
- Required extensions

### 2. Configure Server Settings

If diagnostic shows any issues, configure your server:

**Apache (.htaccess in PrestaShop root):**
```apache
<IfModule mod_php.c>
php_value max_execution_time 3600
php_value max_input_time 3600
php_value memory_limit 512M
php_value upload_max_filesize 512M
php_value post_max_size 512M
</IfModule>
```

**Nginx (in your server block):**
```nginx
location ~ \.php$ {
    fastcgi_read_timeout 3600;
    fastcgi_param PHP_VALUE "max_execution_time=3600
                              max_input_time=3600
                              memory_limit=512M
                              upload_max_filesize=512M
                              post_max_size=512M";
}
```

**PHP-FPM (pool configuration):**
```ini
php_admin_value[max_execution_time] = 3600
php_admin_value[max_input_time] = 3600
php_admin_value[memory_limit] = 512M
php_admin_value[upload_max_filesize] = 512M
php_admin_value[post_max_size] = 512M
request_terminate_timeout = 3600
```

### 3. Restart Services

After configuration changes:

```bash
# Apache
sudo systemctl restart apache2

# Nginx
sudo systemctl restart nginx

# PHP-FPM
sudo systemctl restart php8.1-fpm
```

---

## 🔧 Troubleshooting Installation

### Module not appearing after upload

**Solution:**
```bash
# Clear PrestaShop cache
cd /path/to/prestashop
rm -rf var/cache/*

# Check file permissions
chmod -R 755 modules/psimporter9from178
chown -R www-data:www-data modules/psimporter9from178
```

### Installation fails with error

1. **Check PHP error logs:**
   ```bash
   tail -f /var/log/php8.1-fpm.log
   tail -f /var/log/apache2/error.log
   ```

2. **Enable PrestaShop debug mode:**
   Edit `config/defines.inc.php`:
   ```php
   define('_PS_MODE_DEV_', true);
   ```

3. **Check file permissions:**
   ```bash
   ls -la modules/psimporter9from178/
   ```

### Cannot access diagnostic.php

**Check file exists:**
```bash
ls -la modules/psimporter9from178/diagnostic.php
```

**If missing, download directly:**
```bash
cd modules/psimporter9from178/
wget https://raw.githubusercontent.com/droidhispalis/Migration-Prestashop-9-fro-1.7.6/main/diagnostic.php
chmod 644 diagnostic.php
```

---

## 📦 Module File Structure

After installation, verify this structure:

```
modules/psimporter9from178/
├── config.json               # Module configuration
├── diagnostic.php            # Diagnostic tool
├── index.php                 # Security index
├── logo.png                  # Module logo
├── psimporter9from178.php    # Main module file
├── .gitignore               # Git ignore rules
├── README.md                # Documentation
├── CHANGELOG.md             # Version history
├── LICENSE                  # License file
└── INSTALL.md              # This file
```

---

## 🔐 Security Considerations

### File Permissions

Recommended permissions:
```bash
# Directories
find modules/psimporter9from178/ -type d -exec chmod 755 {} \;

# Files
find modules/psimporter9from178/ -type f -exec chmod 644 {} \;

# Executable (if needed)
chmod 755 modules/psimporter9from178/diagnostic.php
```

### Database Security

- Use a dedicated database user with limited privileges
- Never use root/admin accounts for imports
- Test on staging before production

### Firewall Rules

If using UFW (Ubuntu):
```bash
# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

---

## ✅ Verification Checklist

After installation, verify:

- [ ] Module appears in Module Manager
- [ ] Module status is "Enabled"
- [ ] Diagnostic page is accessible
- [ ] All diagnostic checks pass
- [ ] PHP limits are adequate
- [ ] Database connection works
- [ ] File upload works (test with small file)
- [ ] Cache has been cleared

---

## 🔄 Updating the Module

### Via Back Office
1. Uninstall the old version
2. Delete the module folder via FTP
3. Install the new version

### Via Git
```bash
cd modules/psimporter9from178/
git pull origin main
```

Then reset the module in Back Office.

---

## 🗑️ Uninstallation

### Via Back Office
1. Go to **Modules** → **Module Manager**
2. Search for "PrestaShop 9 Importer"
3. Click **Uninstall**
4. Confirm uninstallation

### Manual Uninstallation
```bash
cd /path/to/prestashop/modules/
rm -rf psimporter9from178/
```

Then clear cache:
```bash
rm -rf /path/to/prestashop/var/cache/*
```

---

## 📞 Need Help?

- 📖 [Full Documentation](https://github.com/droidhispalis/Migration-Prestashop-9-fro-1.7.6/blob/main/README.md)
- 🐛 [Report Issues](https://github.com/droidhispalis/Migration-Prestashop-9-fro-1.7.6/issues)
- 💬 [Community Support](https://github.com/droidhispalis/Migration-Prestashop-9-fro-1.7.6/discussions)

---

**Ready to import?** Proceed to the [Usage Guide](README.md#-usage) →
