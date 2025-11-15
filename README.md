# Deb's Health Tracker 💗

A comprehensive, accessible health tracking application designed to help monitor vital signs, track symptoms, and prevent strokes. Built with love and care for Deb.

## 🌟 Features

### Core Functionality
- **Health Logging**: Track blood pressure, heart rate, weight, temperature, and more
- **Stroke Warning Monitoring**: Track and monitor stroke warning signs (FAST)
- **Medication Management**: Keep track of medications and schedules
- **Analytics Dashboard**: View trends and patterns in health data
- **Alerts System**: Get notified of critical health changes
- **Role-Based Access**: Admin, Patient, and Viewer roles for family/doctors

### User Experience
- **Professional & Futuristic Design**: Clean, modern interface with calming medical theme
- **Fully Accessible**: WCAG compliant with keyboard navigation, screen reader support
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile
- **Welcome Guide**: First-time user onboarding
- **Real-time Updates**: AJAX-powered smooth interactions

### Security & Privacy
- **Secure Authentication**: Password hashing with bcrypt
- **Session Management**: Secure PHP sessions
- **Role-Based Permissions**: Control who can view and manage data
- **Access Control**: Create viewer accounts for family/doctors

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/acesonder/deb.git
   cd deb
   ```

2. **Configure Database**
   - Edit `config/database.php` with your MySQL credentials
   - Default settings:
     ```php
     DB_HOST: localhost
     DB_USER: root
     DB_PASS: (empty)
     DB_NAME: deb_health_tracker
     ```

3. **Run Setup Script**
   - Open your browser and navigate to: `http://your-server/setup.php`
   - This will create the database, tables, and default users
   - **IMPORTANT**: Delete `setup.php` after setup is complete for security

4. **Access the Application**
   - Navigate to: `http://your-server/public/`
   - You'll be redirected to the login page

## 🔐 Default Login Credentials

### For Deb (Patient)
- **Username**: `deb`
- **Access Code**: `80087355`

### For Admin
- **Username**: `admin`
- **Password**: `admin123`
- ⚠️ **Change this password immediately after first login!**

## 📁 Project Structure

```
deb/
├── config/
│   ├── database.php          # Database configuration
│   └── init_db.sql           # Database schema
├── includes/
│   └── auth.php              # Authentication functions
├── public/
│   ├── api/                  # AJAX API endpoints
│   │   ├── get-latest-bp.php
│   │   ├── get-latest-hr.php
│   │   ├── get-logs-count.php
│   │   ├── get-alerts-count.php
│   │   └── get-recent-activity.php
│   ├── css/
│   │   └── style.css         # Main stylesheet
│   ├── js/
│   │   ├── main.js           # Common JavaScript
│   │   └── dashboard.js      # Dashboard specific JS
│   ├── includes/
│   │   ├── header.php        # Header component
│   │   └── sidebar.php       # Sidebar navigation
│   ├── index.php             # Entry point
│   ├── login.php             # Login page
│   ├── logout.php            # Logout handler
│   ├── dashboard.php         # Main dashboard
│   ├── log-health.php        # Health logging form
│   └── settings.php          # User settings
├── setup.php                 # Initial setup script
├── futureideas.md           # Future enhancements
└── README.md                # This file
```

## 💡 Usage

### For Deb (Patient)
1. **Log In**: Use username "deb" and access code "80087355"
2. **Dashboard**: View your health overview and statistics
3. **Log Health Data**: Click "Log Health Data" to record vitals
4. **View Analytics**: Track trends in your health metrics
5. **Manage Access**: Create viewer accounts for family/doctors
6. **Change Password**: Update your access code in Settings

### For Admin
1. **Full Access**: View and manage all users and data
2. **User Management**: Create, edit, or deactivate user accounts
3. **System Monitoring**: Monitor all health logs and alerts
4. **Access Control**: Manage permissions for all users

### For Viewers (Family/Doctors)
1. **Read-Only Access**: View patient's health data
2. **Analytics**: See trends and patterns
3. **Alerts**: Get notified of critical health changes

## 🏥 Health Tracking

### Vital Signs Tracked
- Blood Pressure (Systolic/Diastolic)
- Heart Rate
- Weight
- Temperature
- Sleep Hours
- Activity Level
- Stress Level
- Mood

### Stroke Warning Signs (FAST)
- Face drooping
- Arm weakness
- Speech difficulty
- Time to call 911
- Additional symptoms: confusion, vision problems, severe headache, dizziness

### Automatic Alerts
- Critical blood pressure readings (≥180/120)
- Stroke warning signs
- Medication reminders (future feature)
- Checkup due notifications (future feature)

## 🎨 Accessibility Features

- **Keyboard Navigation**: Full keyboard support
- **Screen Reader Compatible**: ARIA labels and semantic HTML
- **Focus Indicators**: Clear focus states for all interactive elements
- **High Contrast Support**: Readable text and clear UI elements
- **Reduced Motion**: Respects `prefers-reduced-motion` setting
- **Large Touch Targets**: Easy to tap buttons and links on mobile

## 🔒 Security

- Password hashing with PHP's `password_hash()`
- Prepared statements to prevent SQL injection
- Session-based authentication
- Role-based access control
- HTTPS recommended for production

## 🛠️ Technology Stack

- **Backend**: PHP
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **AJAX**: XMLHttpRequest for dynamic updates
- **Design**: Custom CSS with CSS Variables for theming

## 📊 Database Schema

### Main Tables
- `users` - User accounts and authentication
- `health_logs` - Daily health tracking data
- `stroke_warnings` - Stroke warning sign records
- `medications` - Medication tracking
- `access_permissions` - Viewer access control
- `alerts` - System notifications
- `user_preferences` - User settings

See `config/init_db.sql` for complete schema.

## 🚧 Future Enhancements

See [futureideas.md](futureideas.md) for a comprehensive list of planned features including:
- Data export (PDF, CSV)
- Advanced analytics with charts
- Medication reminders
- Wearable device integration
- Telehealth integration
- Mobile app
- And much more!

## 🤝 Contributing

This is a personal project for Deb, but suggestions and improvements are welcome.

## 📝 License

This project is created for personal use. All rights reserved.

## 🙏 Acknowledgments

Built with assistance from GitHub Copilot to help Deb track her health and prevent future strokes. Stay healthy, Deb! 💪

## 📞 Support

For issues or questions, please contact the administrator.

## ⚠️ Medical Disclaimer

This application is designed to supplement, not replace, professional medical care. Always consult with healthcare professionals for medical advice. In case of emergency, call 911 immediately.

---

**Made with ❤️ for Deb**