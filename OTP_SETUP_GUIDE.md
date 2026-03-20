# OTP-Based Password Reset - Setup Guide

This guide walks you through setting up the OTP-based password reset system for your Barangay System 5 PHP application.

## Prerequisites
- PHP 7.4+
- MySQLi extension enabled
- Composer (for PHPMailer installation)
- Gmail account (or any SMTP provider)

---

## Step 1: Install PHPMailer via Composer

### Option A: If Composer is already installed
Open PowerShell/Command Prompt in your project root (`c:\xampp\htdocs\BarangaySystem5`) and run:

```bash
composer require phpmailer/phpmailer
```

This will create:
- A `vendor/` folder
- A `composer.json` file
- A `composer.lock` file

### Option B: If Composer is NOT installed
1. Download Composer from: https://getcomposer.org/download/
2. Install it globally on your system
3. Run the command above

### Verify Installation
Check that the `send_otp.php` file can autoload PHPMailer:
```
vendor/autoload.php  ← This file should exist after composer install
```

---

## Step 2: Create the OTP Codes Table

Run this SQL query in your **barangay-system** database:

### Option A: Using phpMyAdmin
1. Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
2. Select your `barangay-system` database
3. Go to the **SQL** tab
4. Copy and paste the SQL from `sql_snippets.sql` (first 13 lines)
5. Click **Execute**

### Option B: Using Command Line
Open Command Prompt and run:
```bash
mysql -u root -p barangay-system < sql_snippets.sql
```
(Leave the password empty if you don't have one)

### SQL Query:
```sql
CREATE TABLE IF NOT EXISTS otp_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    code VARCHAR(255) NOT NULL,         -- Hashed OTP code
    expires_at DATETIME NOT NULL,       -- Expiration timestamp (10 minutes)
    used BOOLEAN DEFAULT FALSE,         -- Flag to mark OTP as used/invalidated
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(email),
    INDEX(expires_at)
);
```

---

## Step 3: Configure Gmail SMTP Credentials

### Get Your Gmail App Password

1. **Enable 2-Factor Authentication** on your Gmail account:
   - Go to https://myaccount.google.com/
   - Select **Security** (left sidebar)
   - Enable **2-Step Verification** if not already enabled

2. **Generate App Password**:
   - Go back to Security settings
   - Scroll to **App passwords**
   - Select **Mail** and **Windows Computer** (or your OS)
   - Google will generate a 16-character password
   - Copy this password

### Update send_otp.php with Your Credentials

Open `send_otp.php` and find the SMTP configuration section (around line 70):

```php
// SMTP configuration
$mail->isSMTP();
$mail->Host       = 'smtp.gmail.com';
$mail->SMTPAuth   = true;
$mail->Username   = 'your-gmail@gmail.com';      // ← Replace with your Gmail
$mail->Password   = 'your-app-password';         // ← Replace with your 16-char app password
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = 587;
```

**Example:**
```php
$mail->Username   = 'barangay292@gmail.com';
$mail->Password   = 'bksd qwer tyui oplk';  // 16-character app password
```

---

## Step 4: Verify Files Are in Place

Ensure you have these files in your project root:
```
c:\xampp\htdocs\BarangaySystem5\
├── send_otp.php          ← Sends 6-digit OTP to email
├── verify_otp.php        ← Validates OTP and resets password
├── login.php             ← Updated with 2-step OTP modal
├── db.php                ← Your database connection
├── vendor/               ← PHPMailer (created by Composer)
│   └── autoload.php
└── composer.json         ← Created by Composer
```

---

## Step 5: Test the OTP System

1. Start your XAMPP server (Apache + MySQL)
2. Navigate to: `http://localhost/BarangaySystem5/login.php`
3. Click **"Forgot Password?"** link
4. Enter an email address for a valid account in your database
5. Click **"Send OTP"**
6. Check the email inbox for the OTP code
7. Enter the OTP, new password, and confirm password
8. Click **"Reset Password"**
9. You should receive a success message
10. Try logging in with the new password

---

## Security Features

✅ **OTP Expires in 10 minutes** - Automatically invalidates after expiry
✅ **OTP Hashed in Database** - Passwords are hashed with `password_hash()`
✅ **One-Use Only** - OTP is marked as `used` after successful verification
✅ **Atomic Transactions** - Password update and OTP marking happen together or roll back
✅ **Email Validation** - Input validation at multiple points
✅ **Password Requirements** - Minimum 8 characters enforced
✅ **Gmail SMTP Encryption** - Uses STARTTLS protocol

---

## Troubleshooting

### "Class 'PHPMailer\PHPMailer\PHPMailer' not found"
- **Solution:** Run `composer require phpmailer/phpmailer` in your project directory

### "Failed to send OTP email"
- **Check:** Gmail credentials are correct in `send_otp.php`
- **Check:** App passwords are used (not regular Gmail password)
- **Check:** 2-Factor Authentication is enabled on Gmail
- **Check:** SMTP settings are correct (Host: smtp.gmail.com, Port: 587)

### "OTP expired" message when user enters valid OTP
- **Check:** Server timezone is correct (`php.ini` - date.timezone setting)
- **Check:** Database server time is synchronized

### "Invalid OTP" message but user entered correct OTP
- **Check:** OTP expires after 10 minutes
- **Check:** Email and OTP combination must match exactly
- **Check:** Database stores hashed OTP correctly

### AJAX 404 errors
- **Check:** `send_otp.php` and `verify_otp.php` are in the same directory as `login.php`
- **Check:** File permissions allow PHP execution

---

## File Structure

### send_otp.php
- Receives email via POST
- Generates 6-digit OTP
- Hashes and stores in `otp_codes` table (10-minute expiry)
- Sends HTML email via PHPMailer
- Returns JSON response

### verify_otp.php
- Receives: email, otp, new_password, confirm_password
- Validates user exists
- Checks OTP validity, expiry, and usage status
- Verifies hashed OTP with submitted OTP
- Updates password in `accounts` table
- Marks OTP as used
- Uses database transaction for atomicity

### login.php (Updated)
- 2-step modal form
- Step 1: Send OTP (email input only)
- Step 2: Verify OTP + set new password
- AJAX calls to send_otp.php and verify_otp.php
- Real-time validation and error messages
- Enter key support for quick submission

---

## API Endpoints

### POST /send_otp.php
**Input:**
```
email=user@example.com
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "OTP sent successfully to your email",
    "email": "user@example.com"
}
```

**Error Response (400/404/500):**
```json
{
    "success": false,
    "message": "Descriptive error message"
}
```

---

### POST /verify_otp.php
**Input:**
```
email=user@example.com
otp=123456
new_password=MyNewPassword123
confirm_password=MyNewPassword123
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Password successfully reset. You can now log in with your new password"
}
```

**Error Response (400/404/500):**
```json
{
    "success": false,
    "message": "Descriptive error message"
}
```

---

## Security Configuration Checklist

- [ ] Database credentials in `db.php` are secure (not exposed in version control)
- [ ] Gmail app password is used (not regular Gmail password)
- [ ] `send_otp.php` and `verify_otp.php` validate all inputs
- [ ] Password hashing uses `PASSWORD_DEFAULT` algorithm
- [ ] OTP is hashed before storage
- [ ] Database transactions ensure atomicity
- [ ] Error messages don't leak system details to attackers
- [ ] SMTP connection uses TLS encryption

---

## Performance Notes

- OTP codes table has indexes on `email` and `expires_at` for fast lookups
- Expired OTPs should be periodically cleaned up (optional scheduled task)
- Hashing operations use PHP's native `password_hash()` and `password_verify()`

---

## Future Enhancements (Optional)

- Add a "Resend OTP" button if user doesn't receive email
- Implement rate limiting to prevent OTP spam abuse
- Log all password reset attempts for security audit
- Add SMS OTP alternative (requires SMS provider API)
- Cleanup expired OTPs automatically with cron job

---

## Support

If you encounter issues, check:
1. Browser console for JavaScript errors (F12)
2. Network tab in browser DevTools to see AJAX requests
3. PHP error logs in `c:\xampp\logs\`
4. MySQL error logs

---

**Setup Complete!** Your OTP-based password reset system is ready to use.
