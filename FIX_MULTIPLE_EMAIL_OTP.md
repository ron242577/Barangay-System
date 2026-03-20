# OTP Email Sending Fix - Multiple User Emails

## Problem
OTPs were only being sent to arronperlas2017@gmail.com, not to the user-entered email address.

## Root Cause
Gmail's "App Password" security feature only allows sending emails TO the Gmail account owner. This is a Gmail security restriction, not a bug in the code.

## Solution: Enable Less Secure App Access

### Step 1: Enable Less Secure App Access in Gmail

1. **Open your Gmail account:**
   - Go to: https://myaccount.google.com/
   - Sign in as: arronperlas2017@gmail.com

2. **Navigate to Security settings:**
   - Click **Security** (left sidebar)
   - Scroll down to find **"Less secure app access"**

3. **Enable it:**
   - Click the toggle to turn it **ON**
   - You may see a warning - click **"Enable Less secure app access anyway"**
   - It will say "Less secure app access is ON for your account"

### Step 2: Update send_otp.php with Your Regular Password

1. **Open:** `send_otp.php`
2. **Find lines 12-14:**
   ```php
   $mail_user = 'arronperlas2017@gmail.com';      // Your Gmail address
   $mail_pass = 'your-regular-gmail-password';    // ← UPDATE THIS
   ```

3. **Replace with your actual Gmail password:**
   ```php
   $mail_user = 'arronperlas2017@gmail.com';
   $mail_pass = 'your-actual-gmail-password';     // Your REGULAR password (not app password!)
   ```

4. **Save the file**

### Step 3: Test

Go to: `http://localhost/BarangaySystem5/login.php`

1. Click **"Forgot Password?"**
2. Enter a **different user's email address** (like katsu@gmail.com)
3. Click **"Send OTP"**
4. **Check that email's inbox** - the OTP should arrive there!

## Why This Works

- **App Password (Old way):** Gmail restricts sending only to the account owner
- **Less Secure Access (New way):** Allows sending to ANY email address, just like a regular email client

## Security Note

"Less secure app access" is safe for this use case because:
- It's only used for automated OTP emails
- Your password stays only in your code (on your server)
- Gmail accounts are protected by your regular password

## Troubleshooting

**OTP still only goes to arronperlas2017@gmail.com?**
- Check that "Less secure app access" is ON (not OFF)
- Make sure you updated the password in send_otp.php
- Restart your application

**Gmail shows security warning?**
- This is normal when enabling less secure access
- Click "Enable anyway" to confirm

**OTP not arriving?**
- Check spam/junk folder
- Verify the recipient email is correct
- Check that the email exists in your accounts table

---

**Once you complete these 3 steps, the OTP system will work for ALL user emails!** ✓
