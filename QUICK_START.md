# Quick Start Guide - Deb's Health Tracker

Get up and running in 5 minutes!

## 🚀 Quick Installation

### Step 1: Upload Files
Upload all files to your web server in a directory (e.g., `/var/www/deb/`)

### Step 2: Configure Database
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_mysql_username');
define('DB_PASS', 'your_mysql_password');
define('DB_NAME', 'deb_health_tracker');
```

### Step 3: Point Web Server
Set your web server's document root to the `public` directory:
```
DocumentRoot "/var/www/deb/public"
```

### Step 4: Run Setup
Open your browser and visit:
```
http://your-domain.com/setup.php
```

This will:
- Create the database
- Create all tables
- Set up default users

### Step 5: Delete Setup File
**IMPORTANT**: After setup completes, delete `setup.php` for security:
```bash
rm setup.php
```

## 🔑 Login

### For Deb (Patient)
- URL: `http://your-domain.com/`
- Username: `deb`
- Access Code: `80087355`

### For Admin
- URL: `http://your-domain.com/`
- Username: `admin`
- Password: `admin123`
- ⚠️ **Change this password immediately!**

## 📝 First Steps After Login

### As Deb:
1. Read the welcome guide
2. Change your access code in Settings (optional)
3. Log your first health entry
4. Create viewer accounts for family/doctors

### As Admin:
1. Change your password immediately
2. Verify Deb's account is working
3. Review the admin panel
4. Configure any additional settings

## 🏥 Daily Use

### Morning Routine:
1. Log in to the app
2. Click "Log Health Data"
3. Enter your blood pressure reading
4. Record any symptoms
5. Note medications taken
6. Save the entry

### Weekly:
- Check Analytics to see trends
- Review any alerts
- Share insights with your doctor

### As Needed:
- Log stroke warning signs immediately
- Update medications when changed
- Create viewer accounts for new doctors

## 📱 Mobile Access

The app is fully mobile-friendly! Just visit the same URL on your phone or tablet.

## 🆘 Need Help?

- Click "Help" in the navigation menu
- Review the FAST protocol for stroke warnings
- Contact your system administrator
- **Emergency**: Call 911 for medical emergencies

## 🔒 Security Tips

1. **Change default passwords** immediately
2. Use a **strong password** (12+ characters)
3. **Don't share** your login credentials
4. **Log out** when done
5. Use **HTTPS** (secure connection)

## 📊 Understanding Your Data

### Blood Pressure Ranges:
- **Normal**: Less than 120/80
- **Elevated**: 120-129 / less than 80
- **High (Stage 1)**: 130-139 / 80-89
- **High (Stage 2)**: 140+ / 90+
- **Crisis**: 180+ / 120+ (Call 911!)

### FAST Stroke Warning Signs:
- **F**ace drooping
- **A**rm weakness
- **S**peech difficulty
- **T**ime to call 911

## 🎯 Pro Tips

1. **Log daily** at the same time for consistency
2. **Take BP reading** while seated and relaxed
3. **Record symptoms** even if they seem minor
4. **Share your dashboard** with your doctor before appointments
5. **Check alerts** regularly
6. **Keep medications** list up to date

## 📞 Support

For technical issues:
- Check the INSTALL.md file
- Review error logs
- Contact your system administrator

For medical emergencies:
- **Call 911 immediately**
- Don't wait to use the app

## ✅ Quick Checklist

After installation, verify:
- [ ] Can log in as admin
- [ ] Can log in as Deb
- [ ] Can create a health log entry
- [ ] Dashboard displays data
- [ ] Analytics page works
- [ ] Can change password
- [ ] Can create viewer account
- [ ] Mobile site works
- [ ] Deleted setup.php file
- [ ] Changed admin password

## 🎉 You're All Set!

The application is ready to use. Start tracking your health today!

**Remember**: This app supplements, not replaces, professional medical care. Always consult with healthcare providers for medical advice.

---

**Stay healthy! 💪💗**
