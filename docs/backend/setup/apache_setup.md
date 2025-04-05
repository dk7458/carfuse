# Apache Setup Guide for CarFuse

*Last updated: 2023-12-15*

This guide provides step-by-step instructions for setting up Apache to serve the CarFuse project locally.

## Table of Contents
- [Prerequisites](#prerequisites)
- [1. Apache Installation](#1-apache-installation)
- [2. Required Apache Modules](#2-required-apache-modules)
- [3. PHP-FPM Configuration](#3-php-fpm-configuration)
- [4. Directory Permissions](#4-directory-permissions)
- [5. VirtualHost Configuration](#5-virtualhost-configuration)
- [6. Testing the Setup](#6-testing-the-setup)
- [Troubleshooting](#troubleshooting)

## Prerequisites

Ensure you have already:
- Installed PHP 8.1+ and required extensions as per the [main setup guide](/docs/final-docs/development/guides/setup.md)
- Set up the CarFuse database
- Installed and configured project dependencies using Composer

## 1. Apache Installation

### Ubuntu/Debian

```bash
# Update package list
sudo apt update

# Install Apache
sudo apt install apache2

# Check Apache status
sudo systemctl status apache2

# Start Apache if not running
sudo systemctl start apache2

# Enable Apache to start on boot
sudo systemctl enable apache2
```

## 2. Required Apache Modules

Enable the necessary Apache modules:

```bash
# Enable required modules
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod proxy
sudo a2enmod proxy_fcgi
sudo a2enmod ssl
sudo a2enmod expires
sudo a2enmod deflate

# Restart Apache to apply changes
sudo systemctl restart apache2
```

## 3. PHP-FPM Configuration

Install and configure PHP-FPM for optimal performance:

```bash
# Install PHP-FPM
sudo apt install php8.1-fpm

# Check PHP-FPM status
sudo systemctl status php8.1-fpm

# Start PHP-FPM if not running
sudo systemctl start php8.1-fpm

# Enable PHP-FPM to start on boot
sudo systemctl enable php8.1-fpm
```

### Optimize PHP-FPM Configuration

Edit the PHP-FPM pool configuration:

```bash
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
```

Update the following settings for optimal performance:

```ini
; Process manager settings
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; Timeout settings
request_terminate_timeout = 300
```

Apply the changes:

```bash
sudo systemctl restart php8.1-fpm
```

## 4. Directory Permissions

Set proper permissions for the CarFuse project:

```bash
# Navigate to the project directory
cd /home/dorian/carfuse

# Set ownership
sudo chown -R $USER:www-data .

# Set directory permissions
sudo find . -type d -exec chmod 755 {} \;

# Set file permissions
sudo find . -type f -exec chmod 644 {} \;

# Set specific permissions for writable directories
sudo chmod -R 775 storage
sudo chmod -R 775 bootstrap/cache
sudo chmod -R 775 public/uploads
```

## 5. VirtualHost Configuration

Copy the provided VirtualHost configuration file to Apache's sites-available directory:

```bash
sudo cp /home/dorian/carfuse/config/apache/carfuse-vhost.conf /etc/apache2/sites-available/carfuse.conf
```

Enable the site and update your hosts file:

```bash
# Enable the site
sudo a2ensite carfuse.conf

# Disable the default site (optional)
sudo a2dissite 000-default.conf

# Restart Apache
sudo systemctl restart apache2

# Update hosts file
echo "127.0.0.1 carfuse.local" | sudo tee -a /etc/hosts
```

### Understanding the VirtualHost Configuration

The VirtualHost configuration:

1. Sets the document root to the project root directory
2. Enables .htaccess files with `AllowOverride All`
3. Blocks access to sensitive directories
4. Sets up PHP-FPM processing
5. Includes security headers
6. Configures caching for static assets
7. Enables compression for faster page loads

## 6. Testing the Setup

### Verify Apache Configuration

```bash
# Check Apache configuration syntax
sudo apache2ctl configtest
```

### Test PHP Processing

Create a test PHP file in the public directory:

```bash
echo "<?php phpinfo(); ?>" > /home/dorian/carfuse/public/phpinfo.php
```

Visit `http://carfuse.local/public/phpinfo.php` in your browser to verify PHP is working correctly.

### Test the Application

1. Ensure the application is properly configured:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

2. Visit the application URL: `http://carfuse.local`

3. Test API endpoints:
   ```bash
   curl -I http://carfuse.local/api/health
   ```

4. Test static content:
   ```bash
   # Create a test file
   echo "Test file" > /home/dorian/carfuse/public/test.txt
   curl -I http://carfuse.local/public/test.txt
   ```

5. Verify logging is working:
   ```bash
   tail -f /var/log/apache2/carfuse-error.log
   ```

## Troubleshooting

### Permissions Issues

If you encounter "Permission denied" errors:

```bash
# Verify the Apache user/group
grep www-data /etc/passwd

# Check and fix permissions again
sudo find /home/dorian/carfuse/storage -type d -exec chmod 775 {} \;
sudo find /home/dorian/carfuse/storage -type f -exec chmod 664 {} \;
```

### .htaccess Not Working

If the `.htaccess` rules aren't applying:

1. Confirm `mod_rewrite` is enabled:
   ```bash
   apache2ctl -M | grep rewrite
   ```

2. Check the VirtualHost configuration includes `AllowOverride All`

### PHP-FPM Connection Issues

If Apache can't connect to PHP-FPM:

```bash
# Check PHP-FPM socket exists
ls -la /var/run/php/php8.1-fpm.sock

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm

# Check PHP-FPM status
sudo systemctl status php8.1-fpm
```

### Apache Error Logs

View Apache error logs for troubleshooting:

```bash
sudo tail -f /var/log/apache2/carfuse-error.log
```

### PHP-FPM Error Logs

Check PHP-FPM logs for PHP errors:

```bash
sudo tail -f /var/log/php8.1-fpm.log
```
