# Apache Deployment Troubleshooting Guide

*Last updated: 2023-12-20*

This guide provides solutions for common issues encountered when deploying the CarFuse project with Apache.

## Table of Contents
- [1. Common Permission Issues](#1-common-permission-issues)
- [2. HTTP Error Codes](#2-http-error-codes)
- [3. .htaccess Problems](#3-htaccess-problems)
- [4. PHP Configuration Issues](#4-php-configuration-issues)
- [5. Database Connection Problems](#5-database-connection-problems)
- [6. Log Files and Interpretation](#6-log-files-and-interpretation)
- [7. Security Configuration Verification](#7-security-configuration-verification)
- [8. Performance Issues](#8-performance-issues)

## 1. Common Permission Issues

### File Ownership Problems

Apache typically runs as the `www-data` user. Ensure proper ownership:

```bash
# Check current ownership
ls -la /home/dorian/carfuse

# Set correct ownership
sudo chown -R youruser:www-data /home/dorian/carfuse

# Or in production environments
sudo chown -R www-data:www-data /home/dorian/carfuse
```

### Write Permission Issues

Critical directories require write access:

```bash
# Storage, cache, and upload directories
sudo chmod -R 775 /home/dorian/carfuse/storage
sudo chmod -R 775 /home/dorian/carfuse/bootstrap/cache
sudo chmod -R 775 /home/dorian/carfuse/public/uploads

# Verify permissions
ls -la /home/dorian/carfuse/storage
ls -la /home/dorian/carfuse/bootstrap/cache
ls -la /home/dorian/carfuse/public/uploads
```

### SELinux Issues (CentOS/RHEL/Fedora)

If using SELinux, you may need to set proper contexts:

```bash
# Check SELinux status
getenforce

# Set appropriate context for web content
sudo semanage fcontext -a -t httpd_sys_content_t "/home/dorian/carfuse(/.*)?"
sudo semanage fcontext -a -t httpd_sys_rw_content_t "/home/dorian/carfuse/(storage|bootstrap/cache|public/uploads)(/.*)?"
sudo restorecon -Rv /home/dorian/carfuse
```

### Sticky Bit for Group Permissions

For collaborative development environments:

```bash
# Set sticky bit on directories that need group write permissions
sudo find /home/dorian/carfuse/storage -type d -exec chmod g+s {} \;
sudo find /home/dorian/carfuse/bootstrap/cache -type d -exec chmod g+s {} \;
```

## 2. HTTP Error Codes

### 403 Forbidden Errors

| Cause | Solution |
|-------|----------|
| Directory access denied | Check Apache `<Directory>` permissions in VirtualHost |
| .htaccess blocking access | Review .htaccess rules that may be denying access |
| File permissions too restrictive | `chmod 644` for files, `chmod 755` for directories |
| SELinux blocking access | Use `audit2allow` to diagnose and fix SELinux issues |

Diagnostic steps:
```bash
# Check Apache error logs
sudo tail -f /var/log/apache2/carfuse-error.log

# Test file access with Apache user
sudo -u www-data cat /home/dorian/carfuse/public/index.php
```

### 404 Not Found Errors

| Cause | Solution |
|-------|----------|
| Rewrite rules not working | Verify mod_rewrite is enabled and AllowOverride is set to All |
| Missing route in application | Check that the requested route is defined in your routing system |
| Wrong document root | Ensure DocumentRoot in VirtualHost points to correct directory |
| URL case sensitivity | Ensure URL case matches the actual file/directory case |

Diagnostic steps:
```bash
# Test if rewrites are working
curl -I http://carfuse.local/non-existent-page

# Should get a custom 404 from your app, not Apache's default 404
```

### 500 Internal Server Error

| Cause | Solution |
|-------|----------|
| PHP syntax errors | Check PHP error logs and fix syntax issues |
| Memory limit exceeded | Increase PHP memory_limit in php.ini or VirtualHost |
| Execution time limit | Increase max_execution_time in PHP settings |
| Module incompatibility | Check for module conflicts in PHP extensions |
| Database connection failure | Verify database credentials and connectivity |

Diagnostic steps:
```bash
# Enable error display temporarily (development only)
sudo nano /home/dorian/carfuse/public/index.php
# Add these lines at the top:
# ini_set('display_errors', 1);
# ini_set('display_startup_errors', 1);
# error_reporting(E_ALL);

# Check PHP error log
sudo tail -f /var/log/php8.1-fpm.log
```

## 3. .htaccess Problems

### Verify mod_rewrite is Enabled

```bash
# Check if mod_rewrite is enabled
apache2ctl -M | grep rewrite

# Enable if missing
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Check AllowOverride Setting

Ensure VirtualHost has proper AllowOverride:

```apache
<Directory /home/dorian/carfuse>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

### Test .htaccess Functionality

Create a test rule and verify it works:

```bash
# Add test rule to beginning of .htaccess
echo "# Test rule" > /tmp/htaccess_test
echo "RewriteRule ^htaccess-test$ test-success.html [L]" >> /tmp/htaccess_test
cat /home/dorian/carfuse/public/.htaccess >> /tmp/htaccess_test
sudo mv /tmp/htaccess_test /home/dorian/carfuse/public/.htaccess

# Create test file
echo "htaccess works" > /home/dorian/carfuse/public/test-success.html

# Test with curl
curl -I http://carfuse.local/htaccess-test
```

### Common .htaccess Issues

1. **Conflicting Rules**: Our project has multiple .htaccess files (root and public directory). Ensure they don't conflict:

   ```bash
   # Compare the RewriteBase directives
   grep -r "RewriteBase" /home/dorian/carfuse
   ```

2. **Syntax Errors**: Validate syntax:

   ```bash
   # Install Apache utilities
   sudo apt install apache2-utils
   
   # Check syntax (create temporary file with SetEnv directives)
   echo -e "<IfModule mod_env.c>\nSetEnv HTACCESS_TEST true\n</IfModule>" > /tmp/htaccess_wrapper
   cat /home/dorian/carfuse/public/.htaccess >> /tmp/htaccess_wrapper
   echo "</IfModule>" >> /tmp/htaccess_wrapper
   
   htparse /tmp/htaccess_wrapper
   ```

3. **RewriteBase Issues**: Ensure RewriteBase matches your setup:

   ```bash
   # For subdirectory installations, update RewriteBase
   # Example: if CarFuse is in /var/www/html/carfuse
   # RewriteBase /carfuse/
   ```

## 4. PHP Configuration Issues

### PHP-FPM Connection Problems

```bash
# Check if PHP-FPM is running
sudo systemctl status php8.1-fpm

# Check socket file exists
ls -la /var/run/php/php8.1-fpm.sock

# Check socket permissions
sudo chmod 660 /var/run/php/php8.1-fpm.sock
sudo chown www-data:www-data /var/run/php/php8.1-fpm.sock
```

### PHP Memory and Execution Limits

Check if PHP is hitting resource limits:

```bash
# Check current PHP settings
php -i | grep memory_limit
php -i | grep max_execution_time

# Update in VirtualHost (if using mod_php)
php_value memory_limit 256M
php_value max_execution_time 300

# Update in php.ini (if using PHP-FPM)
sudo nano /etc/php/8.1/fpm/php.ini
# Find and update:
# memory_limit = 256M
# max_execution_time = 300
# max_input_time = 300
# post_max_size = 20M
# upload_max_filesize = 20M

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

### Required PHP Extensions

CarFuse requires these PHP extensions:

```bash
# Check installed extensions
php -m

# Install missing extensions
sudo apt install php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd php8.1-intl php8.1-bcmath

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

### PHP Version Compatibility

Verify PHP version compatibility:

```bash
# Check PHP version
php -v

# CarFuse requires PHP 8.1+
# If using wrong version, install correct one:
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.1 php8.1-fpm
```

## 5. Database Connection Problems

### Verify Database Credentials

Check configuration in `.env` file:

```bash
# View database config
grep DB_ /home/dorian/carfuse/.env

# Ensure these match your database setup
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=carfuse
# DB_USERNAME=carfuse_user
# DB_PASSWORD=your_password
```

### Test Database Connection

```bash
# Test connection using credentials from .env
mysql -u carfuse_user -p -h 127.0.0.1 carfuse

# Create diagnostic script
cat > /tmp/db_test.php << 'EOF'
<?php
$host = '127.0.0.1';
$db   = 'carfuse';
$user = 'carfuse_user';
$pass = 'your_password'; // Replace with actual password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
    $pdo = new PDO($dsn, $user, $pass);
    echo "Connected successfully\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
EOF

# Run test
php /tmp/db_test.php
```

### MySQL/MariaDB Configuration

```bash
# Check database is running
sudo systemctl status mysql

# Start if not running
sudo systemctl start mysql

# Check for connection limits or firewall issues
sudo netstat -tlnp | grep mysql
sudo ufw status

# Check max_connections
mysql -u root -p -e "SHOW VARIABLES LIKE 'max_connections';"

# Allow remote connections if needed (for development)
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
# Change bind-address = 127.0.0.1 to bind-address = 0.0.0.0
```

### Database User Permissions

```bash
# Log into MySQL
mysql -u root -p

# Verify user and privileges
mysql> SELECT user, host FROM mysql.user WHERE user='carfuse_user';
mysql> SHOW GRANTS FOR 'carfuse_user'@'localhost';

# Grant proper privileges if needed
mysql> GRANT ALL PRIVILEGES ON carfuse.* TO 'carfuse_user'@'localhost';
mysql> FLUSH PRIVILEGES;
```

## 6. Log Files and Interpretation

### Apache Log Locations

```bash
# Apache error log (contains PHP errors when using mod_php)
sudo tail -f /var/log/apache2/carfuse-error.log

# Apache access log (shows HTTP requests)
sudo tail -f /var/log/apache2/carfuse-access.log
```

### PHP-FPM Log Locations

```bash
# PHP-FPM error log
sudo tail -f /var/log/php8.1-fpm.log

# PHP-FPM slow request log
sudo tail -f /var/log/php8.1-fpm-slow.log
```

### CarFuse Application Logs

```bash
# Application logs
sudo tail -f /home/dorian/carfuse/storage/logs/laravel.log
```

### MySQL/MariaDB Logs

```bash
# Database error log
sudo tail -f /var/log/mysql/error.log
```

### Log Interpretation Guide

| Log Pattern | Interpretation | Solution |
|-------------|----------------|----------|
| `PHP Fatal error: Allowed memory size of X bytes exhausted` | Memory limit reached | Increase `memory_limit` in PHP config |
| `Permission denied` in Apache logs | File permission issue | Check directories/file permissions |
| `No such file or directory` | Missing file or incorrect path | Check file paths and existence |
| `Call to undefined function` | Missing PHP extension | Install required PHP extension |
| `Maximum execution time exceeded` | Script timeout | Increase `max_execution_time` in PHP config |
| `Can't connect to MySQL server` | Database connection issue | Check credentials and MySQL service |
| `SQLSTATE[HY000]` | Database error | Check SQL syntax and database configuration |
| `404 Not Found` for existing files | Rewrite issue | Check .htaccess and mod_rewrite |
| `501 Not Implemented` | Method not allowed/supported | Check request method compatibility |

### Log Analysis Commands

```bash
# Count IP addresses in access log
sudo cat /var/log/apache2/carfuse-access.log | awk '{print $1}' | sort | uniq -c | sort -nr

# Find all POST requests
sudo grep "POST" /var/log/apache2/carfuse-access.log

# Find all 500 errors
sudo grep " 500 " /var/log/apache2/carfuse-access.log

# Find PHP errors from specific date
sudo grep "$(date +"%d-%b-%Y")" /var/log/php8.1-fpm.log | grep "ERROR"
```

## 7. Security Configuration Verification

### Check Security Headers

```bash
# Install curl with headers support
sudo apt install curl

# Test security headers
curl -I https://carfuse.local/

# Expected headers:
# X-Content-Type-Options: nosniff
# X-XSS-Protection: 1; mode=block
# X-Frame-Options: SAMEORIGIN
# Referrer-Policy: strict-origin-when-cross-origin
# Content-Security-Policy: present
```

### Verify SSL/TLS Configuration

```bash
# Check SSL configuration
openssl s_client -connect carfuse.local:443 -tls1_2

# Online SSL checker (for production sites)
# Visit: https://www.ssllabs.com/ssltest/
```

### Directory Protection Verification

```bash
# Test access to protected directories
curl -I http://carfuse.local/App/Controllers/
curl -I http://carfuse.local/App/Services/
curl -I http://carfuse.local/config/
curl -I http://carfuse.local/vendor/
curl -I http://carfuse.local/logs/

# All should return 403 Forbidden
```

### File Permission Audit

```bash
# Find world-writable files (potentially insecure)
find /home/dorian/carfuse -type f -perm -o+w -not -path "*/node_modules/*" -not -path "*/vendor/*"

# Find files not owned by proper user
find /home/dorian/carfuse -type f -not -user www-data -not -path "*/node_modules/*" -not -path "*/vendor/*"

# Find executable files
find /home/dorian/carfuse -type f -perm -o+x
```

### Apache Security Modules

```bash
# Check security modules
apache2ctl -M | grep security

# Consider installing mod_security
sudo apt install libapache2-mod-security2
```

### PHP Security Configuration

```bash
# Check PHP security settings
php -i | grep display_errors
php -i | grep expose_php
php -i | grep allow_url_include

# Secure settings in php.ini
# display_errors = Off (in production)
# expose_php = Off
# allow_url_include = Off
# allow_url_fopen = Off (if possible)
```

## 8. Performance Issues

### Apache Performance Tuning

```bash
# Check Apache MPM
apache2ctl -V | grep MPM

# For PHP-FPM, use event MPM
sudo a2dismod mpm_prefork
sudo a2enmod mpm_event

# Edit MPM config
sudo nano /etc/apache2/mods-available/mpm_event.conf

# Recommended settings for medium traffic:
# StartServers 2
# MinSpareThreads 25
# MaxSpareThreads 75
# ThreadLimit 64
# ThreadsPerChild 25
# MaxRequestWorkers 150
# MaxConnectionsPerChild 10000
```

### PHP-FPM Pool Optimization

```bash
sudo nano /etc/php/8.1/fpm/pool.d/www.conf

# Recommend settings for CarFuse:
# pm = dynamic
# pm.max_children = 50
# pm.start_servers = 5
# pm.min_spare_servers = 5
# pm.max_spare_servers = 35
# pm.max_requests = 500
```

### OpCache Configuration

```bash
sudo nano /etc/php/8.1/fpm/conf.d/10-opcache.conf

# Recommended settings:
# opcache.enable=1
# opcache.memory_consumption=128
# opcache.interned_strings_buffer=8
# opcache.max_accelerated_files=4000
# opcache.revalidate_freq=60
# opcache.fast_shutdown=1
```

### MySQL Optimization

```bash
sudo nano /etc/mysql/my.cnf

# Add under [mysqld]:
# innodb_buffer_pool_size = 256M
# innodb_log_file_size = 64M
# innodb_flush_log_at_trx_commit = 2
# innodb_flush_method = O_DIRECT
```

### Static Content Caching

Verify your caching settings in Apache:

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

---

For additional assistance:
- Contact: devops@carfuse.example.com
- Check the [CarFuse DevOps Knowledge Base](https://kb.carfuse.example.com)
- [Official Apache Documentation](https://httpd.apache.org/docs/)
