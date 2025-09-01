# Hall Reservation System - Pondicherry University

A comprehensive web-based hall booking and management system for Pondicherry University. This system allows users to book halls, manage bookings, and provides administrative features for hall and user management.

## 🚀 Features

### User Features
- **User Authentication**: Secure login/registration with email verification
- **Hall Booking**: Browse and book available halls with conflict detection
- **Booking Management**: View, modify, and cancel bookings
- **Timetable View**: Calendar view of hall schedules
- **Password Recovery**: Email-based password reset functionality

### Admin Features
- **Hall Management**: Add, modify, and archive halls
- **User Management**: Manage employees and departments
- **School/Department Management**: Organize users by schools and departments
- **Booking Approval**: Approve or reject booking requests
- **Conflict Resolution**: Handle booking conflicts and scheduling

### System Features
- **Email Notifications**: Automated email notifications for bookings and status updates
- **Conflict Detection**: Prevents double bookings and scheduling conflicts
- **Role-based Access**: Different interfaces for admins and regular users
- **Responsive Design**: Mobile-friendly interface using Bootstrap

## 📋 Prerequisites

Before running this project, make sure you have the following installed:

- **XAMPP** (or any local server stack with PHP and MySQL)
  - Download from: [https://www.apachefriends.org/index.html](https://www.apachefriends.org/index.html)
- **PHP** (version 7.0 or higher)
- **MySQL** (version 5.6 or higher)
- **Web Browser** (Chrome, Firefox, Safari, Edge)

## 🛠️ Installation

### Step 1: Download and Extract
1. Download the project files
2. Extract the project folder to your XAMPP `htdocs` directory
   ```
   C:\xampp\htdocs\hall-reservation-system\
   ```

### Step 2: Start XAMPP Services
1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL** services
3. Ensure both services are running (green status)

### Step 3: Set Up Database
1. Open your web browser and go to: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Create a new database named: `hbs_run`
3. Import the database schema (if SQL file is provided)
   - Go to the `hbs_run` database
   - Click on "Import" tab
   - Choose the SQL file (e.g., `database_schema.sql`)
   - Click "Go" to import

### Step 4: Configure Database Connection
The database connection is already configured in `assets/conn.php`:
```php
$servername = "localhost";
$username = "root";
$password = "";
$connname = "hbs_run";
```

**Note**: If your MySQL has a password, update the `$password` variable in `assets/conn.php`.

### Step 5: Configure Email (Optional)
For email notifications to work properly:

1. **For Gmail users**:
   - Enable "Less secure app access" in your Google account settings
   - Or use App Passwords for better security

2. **Update email credentials** in:
   - `login/recover_psw.php`
   - `login/register.php`
   - `status_update_mail.php`

Replace the placeholder credentials with your actual email settings:
```php
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';
```

## 🚀 Running the Application

1. **Access the application**:
   - Open your web browser
   - Navigate to: [http://localhost/hall-reservation-system/index.php](http://localhost/hall-reservation-system/index.php)

2. **First-time setup**:
   - Register a new user account
   - Verify your email (if email is configured)
   - Log in with your credentials

3. **Admin access**:
   - Contact the system administrator for admin credentials
   - Admin users have access to hall management and user administration

## 📁 Project Structure

```
hall-reservation-system/
├── assets/
│   ├── conn.php              # Database connection
│   ├── design.css            # Main stylesheet
│   ├── script.js             # JavaScript functions
│   ├── header.php            # Common header
│   └── footer.php            # Common footer
├── login/
│   ├── index.php             # Login page
│   ├── register.php          # User registration
│   ├── recover_psw.php       # Password recovery
│   └── Mail/                 # PHPMailer files
├── image/
│   ├── logo/                 # University logos
│   ├── event/                # Event images
│   └── halls/                # Hall images
├── index.php                 # Main entry point
├── home.php                  # Dashboard
├── book_hall.php             # Hall booking interface
├── view_bookings.php         # Booking management
├── add_hall.php              # Admin: Add halls
├── add_employees.php         # Admin: Add employees
└── README.md                 # This file
```

## 🔧 Configuration

### Database Configuration
Edit `assets/conn.php` to match your database settings:
```php
$servername = "localhost";
$username = "your_db_username";
$password = "your_db_password";
$connname = "hbs_run";
```

### Email Configuration
Update SMTP settings in email-related files:
```php
$mail->Host = 'smtp.gmail.com';
$mail->Port = 587;
$mail->SMTPAuth = true;
$mail->SMTPSecure = 'tls';
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';
```

## 👥 User Roles

### Regular User
- Book halls for events or classes
- View personal bookings
- Modify/cancel own bookings
- View hall timetables

### Admin User
- All regular user features
- Manage halls (add, edit, archive)
- Manage employees and departments
- Approve/reject booking requests
- View all bookings and conflicts
- System administration

## 📧 Email Features

The system sends automated emails for:
- **Account verification** (OTP code)
- **Password reset** (reset link)
- **Booking confirmations** (status updates)
- **Booking notifications** (approval/rejection)

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Ensure MySQL is running in XAMPP
   - Check database credentials in `assets/conn.php`
   - Verify database `hbs_run` exists

2. **Email Not Working**
   - Check SMTP settings in email files
   - Ensure "Less secure apps" is enabled for Gmail
   - Verify email credentials are correct

3. **Page Not Found (404)**
   - Ensure Apache is running in XAMPP
   - Check file paths and permissions
   - Verify project is in correct `htdocs` folder

4. **Permission Denied**
   - Check file permissions for upload directories
   - Ensure `image/event/` directory is writable

### Error Logs
- Check XAMPP error logs: `C:\xampp\apache\logs\error.log`
- Check PHP error logs in XAMPP control panel

## 🔒 Security Features

- **Password Hashing**: All passwords are hashed using PHP's `password_hash()`
- **SQL Injection Prevention**: Prepared statements used throughout
- **Session Management**: Secure session handling
- **Input Validation**: Server-side validation for all inputs
- **CSRF Protection**: Form tokens for security

## 📱 Browser Compatibility

- Chrome (recommended)
- Firefox
- Safari
- Edge
- Mobile browsers (responsive design)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is developed for Pondicherry University. All rights reserved.

## 📞 Support

For technical support or questions:
- Contact the system administrator
- Check the troubleshooting section above
- Review error logs for specific issues

## 🔄 Updates

Keep the system updated by:
- Regularly backing up the database
- Monitoring error logs
- Updating dependencies when needed
- Testing new features before deployment

---

**Happy Booking! 🎉** 