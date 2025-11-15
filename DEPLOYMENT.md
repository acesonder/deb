# Deployment Checklist

Use this checklist when deploying Deb's Health Tracker to production.

## Pre-Deployment

### 1. Server Requirements
- [ ] PHP 7.4+ installed
- [ ] MySQL 5.7+ installed
- [ ] Apache/Nginx web server configured
- [ ] SSL certificate installed (for HTTPS)
- [ ] Required PHP extensions enabled (mysqli, session)

### 2. File Preparation
- [ ] Clone repository to server
- [ ] Set correct file permissions (755 for directories, 644 for files)
- [ ] Ensure web server user can read files
- [ ] Configure document root to `/path/to/deb/public`

### 3. Database Configuration
- [ ] Create MySQL database
- [ ] Create MySQL user with appropriate privileges
- [ ] Update `config/database.php` with credentials
- [ ] Test database connection

## Deployment Steps

### 1. Initial Setup
```bash
# Navigate to project directory
cd /path/to/deb

# Set permissions
chmod 755 public/
chmod 755 config/
chmod 755 includes/
find . -type f -exec chmod 644 {} \;
```

### 2. Database Initialization
- [ ] Access `http://your-domain.com/setup.php` in browser
- [ ] Verify database tables are created
- [ ] Verify default users are created
- [ ] **IMPORTANT**: Delete `setup.php` after setup:
  ```bash
  rm setup.php
  ```

### 3. Security Hardening

#### Update Default Passwords
- [ ] Log in as admin (username: `admin`, password: `admin123`)
- [ ] Go to Settings → Change Password
- [ ] Set a strong password (minimum 12 characters)
- [ ] Log out and log back in to verify

#### Optionally Update Deb's Access Code
- [ ] Log in as Deb (username: `deb`, access code: `80087355`)
- [ ] Go to Settings → Change Password
- [ ] Set a new access code
- [ ] Share new code securely with Deb

#### PHP Security Settings
Add to `php.ini` or `.htaccess`:
```ini
# Disable error display in production
display_errors = Off
display_startup_errors = Off
error_reporting = E_ALL

# Enable error logging
log_errors = On
error_log = /path/to/logs/php_error.log

# Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_only_cookies = 1
```

#### Database Security
- [ ] Use strong database password
- [ ] Restrict database access to localhost
- [ ] Regularly backup database
- [ ] Keep MySQL updated

#### File Security
```bash
# Protect config files
chmod 600 config/database.php

# Prevent directory listing
echo "Options -Indexes" > public/.htaccess
```

### 4. SSL/HTTPS Configuration

#### Using Let's Encrypt (Free)
```bash
# Install certbot
sudo apt-get install certbot python3-certbot-apache

# Get certificate
sudo certbot --apache -d your-domain.com

# Auto-renewal is configured automatically
```

#### Force HTTPS
Add to `public/.htaccess`:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 5. Web Server Configuration

#### Apache
Create virtual host in `/etc/apache2/sites-available/deb.conf`:
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/deb/public
    
    <Directory /path/to/deb/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/deb_error.log
    CustomLog ${APACHE_LOG_DIR}/deb_access.log combined
</VirtualHost>
```

Enable site:
```bash
sudo a2ensite deb
sudo systemctl reload apache2
```

#### Nginx
Create config in `/etc/nginx/sites-available/deb`:
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

    location ~ /\.ht {
        deny all;
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/deb /etc/nginx/sites-enabled/
sudo systemctl reload nginx
```

### 6. Backup Configuration

#### Automated Database Backup
Create backup script `/usr/local/bin/backup-deb.sh`:
```bash
#!/bin/bash
BACKUP_DIR="/backups/deb"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="deb_health_tracker"
DB_USER="your_db_user"
DB_PASS="your_db_password"

mkdir -p $BACKUP_DIR
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/backup_$DATE.sql.gz

# Keep only last 30 days of backups
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +30 -delete
```

Make executable:
```bash
chmod +x /usr/local/bin/backup-deb.sh
```

Add to crontab (daily at 2 AM):
```bash
0 2 * * * /usr/local/bin/backup-deb.sh
```

## Post-Deployment

### 1. Testing
- [ ] Test login as admin
- [ ] Test login as Deb (patient)
- [ ] Create a test health log entry
- [ ] Verify dashboard displays correctly
- [ ] Test analytics page
- [ ] Create a test viewer account
- [ ] Test viewer login and permissions
- [ ] Verify alerts are working
- [ ] Test password change functionality
- [ ] Test stroke warning logging
- [ ] Test medication management
- [ ] Verify all navigation links work
- [ ] Test on mobile device
- [ ] Test with screen reader (accessibility)

### 2. Monitoring Setup
- [ ] Set up server monitoring (uptime, CPU, memory)
- [ ] Configure log monitoring
- [ ] Set up database backup verification
- [ ] Configure email alerts for critical errors (optional)

### 3. User Training
- [ ] Walk Deb through the application
- [ ] Show how to log health data
- [ ] Explain the FAST protocol
- [ ] Demonstrate how to create viewer accounts
- [ ] Show how to change password
- [ ] Provide help documentation

### 4. Documentation
- [ ] Document server configuration
- [ ] Document backup procedures
- [ ] Document restore procedures
- [ ] Create user guide for Deb
- [ ] Document admin procedures

## Maintenance

### Regular Tasks

#### Daily
- [ ] Check application is accessible
- [ ] Review error logs for issues

#### Weekly
- [ ] Verify backups are running
- [ ] Check database size
- [ ] Review user activity

#### Monthly
- [ ] Update PHP packages
- [ ] Update MySQL
- [ ] Review and rotate logs
- [ ] Test backup restoration
- [ ] Review security updates

### Emergency Procedures

#### If Application is Down
1. Check web server status: `sudo systemctl status apache2` or `nginx`
2. Check PHP-FPM status: `sudo systemctl status php7.4-fpm`
3. Check MySQL status: `sudo systemctl status mysql`
4. Review error logs: `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
5. Check PHP error log: `/path/to/php_error.log`

#### If Database is Corrupted
1. Stop web server
2. Restore from latest backup:
   ```bash
   gunzip < /backups/deb/backup_YYYYMMDD_HHMMSS.sql.gz | mysql -u username -p deb_health_tracker
   ```
3. Verify restoration
4. Restart web server

#### If Critical Security Issue
1. Take application offline immediately
2. Assess the vulnerability
3. Apply patches/updates
4. Review logs for breach
5. Change all passwords
6. Notify users if data was compromised
7. Bring application back online

## Security Best Practices

### Ongoing Security
- [ ] Keep all software updated (PHP, MySQL, OS)
- [ ] Use strong passwords (minimum 12 characters)
- [ ] Enable two-factor authentication (future enhancement)
- [ ] Regular security audits
- [ ] Monitor access logs for suspicious activity
- [ ] Keep backups encrypted and off-site
- [ ] Review user accounts regularly
- [ ] Remove inactive accounts

### Data Privacy
- [ ] Ensure HIPAA compliance if required
- [ ] Implement data retention policy
- [ ] Provide data export functionality for users
- [ ] Allow users to delete their data
- [ ] Keep audit logs of data access

## Troubleshooting

### Common Issues

**Problem**: White screen / blank page
- **Solution**: Enable error display temporarily, check PHP error logs

**Problem**: Database connection failed
- **Solution**: Verify credentials in `config/database.php`, check MySQL is running

**Problem**: Login not working
- **Solution**: Check session configuration, verify PHP sessions are enabled

**Problem**: Styles not loading
- **Solution**: Check file permissions, verify paths in HTML, clear browser cache

**Problem**: AJAX requests failing
- **Solution**: Check browser console, verify API endpoints exist, check CORS settings

## Rollback Procedure

If deployment fails:

1. Restore previous version:
   ```bash
   cd /path/to/deb
   git checkout previous-stable-version
   ```

2. Restore database backup:
   ```bash
   gunzip < /backups/deb/backup_before_update.sql.gz | mysql -u username -p deb_health_tracker
   ```

3. Clear cache and restart services:
   ```bash
   sudo systemctl restart apache2
   sudo systemctl restart mysql
   ```

## Support Contacts

- **Server Admin**: [Your contact]
- **Database Admin**: [Your contact]
- **Developer**: [Your contact]
- **Emergency**: 911 (for medical emergencies)

---

**Deployment Date**: _________________

**Deployed By**: _________________

**Version**: 1.0.0

**Next Review Date**: _________________
