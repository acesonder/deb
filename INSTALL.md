# Installation Guide for Deb's Health Tracker

This guide will walk you through setting up the application on your server.

## System Requirements

- **PHP**: Version 7.4 or higher
  - Required extensions: mysqli, session
- **MySQL**: Version 5.7 or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Operating System**: Linux, Windows, or macOS

## Installation Steps

### 1. Download the Application

```bash
git clone https://github.com/acesonder/deb.git
cd deb
```

### 2. Configure Web Server

#### Apache Configuration

1. Point your document root to the `public` directory:
   ```apache
   DocumentRoot "/path/to/deb/public"
   <Directory "/path/to/deb/public">
       Options Indexes FollowSymLinks
       AllowOverride All
       Require all granted
   </Directory>
   ```

2. Create `.htaccess` file in the `public` directory (if needed):
   ```apache
   # Enable rewrite engine
   RewriteEngine On
   
   # Redirect to index.php if file doesn't exist
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php [QSA,L]
   ```

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/deb/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 3. Configure Database Connection

Edit `config/database.php` with your MySQL credentials:

```php
define('DB_HOST', 'localhost');      // Your MySQL host
define('DB_USER', 'your_username');  // Your MySQL username
define('DB_PASS', 'your_password');  // Your MySQL password
define('DB_NAME', 'deb_health_tracker');
```

### 4. Set File Permissions

```bash
# Make directories writable (if needed for logs/uploads in future)
chmod 755 public/
chmod 755 config/
chmod 755 includes/

# Make files readable
find . -type f -exec chmod 644 {} \;
```

### 5. Run Setup Script

1. Open your web browser
2. Navigate to: `http://your-domain.com/setup.php`
3. The setup script will:
   - Create the database
   - Create all necessary tables
   - Create default admin and patient users
   - Set up initial configurations

4. **IMPORTANT**: Delete `setup.php` after setup completes:
   ```bash
   rm setup.php
   ```

### 6. Access the Application

Navigate to: `http://your-domain.com/`

You'll be redirected to the login page.

## Default Login Credentials

### Patient (Deb)
- **Username**: `deb`
- **Access Code**: `80087355`

### Administrator
- **Username**: `admin`
- **Password**: `admin123`
- **⚠️ IMPORTANT**: Change this password immediately after first login!

## Post-Installation Steps

### 1. Change Admin Password
1. Log in as admin
2. Go to Settings
3. Change your password to something secure

### 2. Verify Deb's Account
1. Log in as Deb using her access code
2. Complete the welcome guide
3. Optionally change the access code in Settings

### 3. Test Core Features
- Log a health entry
- View the dashboard
- Check analytics (after some data is logged)
- Test alert system

### 4. Create Viewer Accounts (Optional)
1. Log in as Deb or Admin
2. Navigate to "Manage Access"
3. Create accounts for family members or doctors

## Security Recommendations

### For Production Use

1. **Enable HTTPS**
   ```bash
   # Using Let's Encrypt (free)
   certbot --apache -d your-domain.com
   ```

2. **Secure Database**
   - Use a strong database password
   - Restrict database access to localhost only
   - Regularly backup your database

3. **PHP Security**
   - Set `display_errors = Off` in production
   - Enable `error_log` for debugging
   - Keep PHP updated to latest stable version

4. **File Permissions**
   - Don't make files writable by web server unless necessary
   - Store sensitive files outside web root if possible

5. **Regular Updates**
   - Keep server software updated
   - Monitor for security patches

## Troubleshooting

### Database Connection Errors

**Problem**: "Connection failed" error

**Solution**:
1. Verify MySQL is running: `sudo service mysql status`
2. Check credentials in `config/database.php`
3. Ensure database user has proper permissions
4. Test connection: `mysql -u username -p`

### Permission Denied Errors

**Problem**: Cannot write to database or files

**Solution**:
1. Check file permissions: `ls -la`
2. Ensure web server user (www-data, apache) can access files
3. Fix permissions if needed:
   ```bash
   sudo chown -R www-data:www-data /path/to/deb
   ```

### Blank Page / White Screen

**Problem**: Page loads but shows nothing

**Solution**:
1. Enable error display temporarily in PHP:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
2. Check PHP error logs
3. Verify all required files exist
4. Check PHP version compatibility

### Session Errors

**Problem**: Login doesn't work or sessions not persisting

**Solution**:
1. Check PHP session configuration
2. Ensure session directory is writable:
   ```bash
   sudo chmod 1733 /var/lib/php/sessions
   ```
3. Verify session settings in php.ini

## Database Backup

### Manual Backup
```bash
mysqldump -u username -p deb_health_tracker > backup_$(date +%Y%m%d).sql
```

### Automated Backup (Cron Job)
```bash
# Add to crontab (crontab -e)
0 2 * * * mysqldump -u username -ppassword deb_health_tracker > /backups/deb_$(date +\%Y\%m\%d).sql
```

## Updating the Application

When updates are available:

```bash
# Backup database first
mysqldump -u username -p deb_health_tracker > backup_before_update.sql

# Pull latest changes
git pull origin main

# Check for database migrations (if any)
# Run any new SQL scripts if provided

# Clear cache if applicable
rm -rf cache/*
```

## Support

For issues or questions:
1. Check the README.md file
2. Review this installation guide
3. Check PHP and MySQL error logs
4. Contact the administrator

## Uninstalling

If you need to remove the application:

```bash
# 1. Backup data if needed
mysqldump -u username -p deb_health_tracker > final_backup.sql

# 2. Drop database
mysql -u username -p -e "DROP DATABASE deb_health_tracker;"

# 3. Remove files
rm -rf /path/to/deb

# 4. Remove web server configuration
# Remove Apache virtual host or Nginx server block
```

---

**Installation Complete!** 🎉

The application should now be running. If you encounter any issues, refer to the Troubleshooting section above.
