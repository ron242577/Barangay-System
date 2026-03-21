<?php
session_start();
include "db.php";

$error = "";
$resetMessage = "";

// clear follow-up session by default
unset($_SESSION["followup_account_id"]);

// OTP-based password reset is handled by send_otp.php and verify_otp.php

if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['reset_password'])) {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare("SELECT id, fullname, password, role, status FROM accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {

        if ($user["status"] === "pending") {
            $error = "pending";
            $_SESSION["followup_account_id"] = (int)$user["id"];

        } elseif ($user["status"] === "declined") {
            $error = "declined";
            $_SESSION["followup_account_id"] = (int)$user["id"];

        } elseif ($user["status"] === "inactive") {
            $error = "inactive";
            $_SESSION["followup_account_id"] = (int)$user["id"];

        } else {
            $storedPassword = (string)$user["password"];
            $loginSuccess = false;

            // Check hashed password first
            if (password_verify($password, $storedPassword)) {
                $loginSuccess = true;
            }
            // Legacy plaintext password: migrate to hash on first successful login
            elseif ($password === $storedPassword) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE accounts SET password = ? WHERE id = ?");
                $update->bind_param("si", $newHash, $user["id"]);
                $update->execute();
                $update->close();

                $loginSuccess = true;
            }

            if ($loginSuccess) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["fullname"] = $user["fullname"];
                $_SESSION["email"] = $email;
                $_SESSION["role"] = $user["role"];

                // Log login activity
                $ip = $_SERVER["REMOTE_ADDR"];
                $log = $conn->prepare("INSERT INTO login_logs (user_id, email, ip_address) VALUES (?, ?, ?)");
                $log->bind_param("iss", $user["id"], $email, $ip);
                $log->execute();
                $log->close();

                // Role-based redirect
                if ($user["role"] === "SuperAdmin") {
                    header("Location: SuperAdmin_Dashboard.php");
                } elseif ($user["role"] === "Admin") {
                    header("Location: Admin_Dashboard.php");
                } else {
                    header("Location: homepage.php");
                }
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        }

    } else {
        $error = "Invalid email or password.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Barangay 292 E-Services</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #2d7a3e 0%, #1a4d2e 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            display: flex;
            min-height: 600px;
        }

        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #2d7a3e 0%, #1a4d2e 100%);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
        }

        .logo-container { margin-bottom: 30px; }

        .logo {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            padding: 15px;
            margin-bottom: 20px;
        }

        .login-left h1 { font-size: 28px; margin-bottom: 15px; font-weight: 700; }
        .login-left p { font-size: 16px; line-height: 1.6; opacity: 0.9; }

        .login-right {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header { margin-bottom: 40px; }
        .login-header h2 { font-size: 32px; color: #1a4d2e; margin-bottom: 10px; font-weight: 700; }
        .login-header p { color: #666; font-size: 14px; }

        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px; }

        .input-wrapper { position: relative; }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: 'DM Sans', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2d7a3e;
            box-shadow: 0 0 0 3px rgba(45, 122, 62, 0.1);
        }

        .error-message { color: #dc3545; font-size: 12px; margin-top: 5px; display: none; }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .remember-me { display: flex; align-items: center; gap: 8px; font-size: 14px; color: #666; }
        .remember-me input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }

        .forgot-password {
            color: #2d7a3e;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-password:hover { color: #1a4d2e; }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: #fff;
            padding: 24px;
            border-radius: 16px;
            width: 92%;
            max-width: 480px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.25);
            position: relative;
        }

        .modal-content .close {
            position: absolute;
            top: 14px;
            right: 16px;
            font-size: 28px;
            cursor: pointer;
        }

        .modal-content h2 {
            margin-top: 0;
        }

        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #2d7a3e 0%, #1a4d2e 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            font-family: 'DM Sans', sans-serif;
        }

        .login-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(45, 122, 62, 0.3); }
        .login-btn:active { transform: translateY(0); }

        .register-link { text-align: center; margin-top: 25px; font-size: 14px; color: #666; }
        .register-link a { color: #2d7a3e; text-decoration: none; font-weight: 600; transition: color 0.3s ease; }
        .register-link a:hover { color: #1a4d2e; }

        @media (max-width: 768px) {
            .login-container { flex-direction: column; }
            .login-left { padding: 40px 30px; }
            .login-right { padding: 40px 30px; }
            .login-header h2 { font-size: 26px; }
            .login-left h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="logo-container">
                <img class="logo" src="images/logo2.png" alt="Barangay 292 Logo" onerror="this.style.display='none'">
            </div>
            <h1>Barangay 292</h1>
            <p>Management & E-Services Platform</p>
            <p style="margin-top: 20px; font-size: 14px;">Access community services, submit requests, and stay connected with your barangay.</p>
        </div>

        <div class="login-right">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Please login to access your account</p>
                <?php if(isset($error) && $error !== "pending" && $error !== "declined" && $error !== "inactive") { echo "<div style='color: #dc3545; font-size: 14px; margin-top: 10px; text-align: center;'>" . htmlspecialchars($error) . "</div>"; } ?>
            </div>

            <form id="loginForm" method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        <span class="error-message" id="emailError">Please enter your email</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <span class="error-message" id="passwordError">Please enter your password</span>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-password" id="forgotPasswordLink">Forgot Password?</a>
                </div>

                <button type="submit" class="login-btn">Login</button>

                <div class="register-link">
                    Don't have an account? <a href="register.php">Register here</a>
                </div>
            </form>

            <!-- Forgot Password Modal -->
            <div id="resetModal" class="modal" style="display:none;">
                <div class="modal-content" style="max-width: 450px;">
                    <span class="close" id="resetClose">&times;</span>
                    <h2>Reset Password</h2>
                    
                    <div id="resetStep1" style="display: block;">
                        <p>Enter your email to receive an OTP code.</p>
                        <div class="form-group">
                            <label for="reset_email">Email</label>
                            <input type="email" id="reset_email" placeholder="Enter your email" required>
                        </div>
                        <div id="step1Message" style="margin:10px 0; padding:10px; border-radius:8px; display:none;"></div>
                        <button type="button" class="login-btn" id="sendOtpBtn" onclick="sendOTP()">Send OTP</button>
                    </div>

                    <div id="resetStep2" style="display: none;">
                        <p>Enter the OTP sent to your email and set a new password.</p>
                        <div class="form-group">
                            <label for="otp_code">OTP Code</label>
                            <input type="text" id="otp_code" placeholder="Enter 6-digit OTP" maxlength="6" required>
                        </div>
                        <div class="form-group">
                            <label for="reset_new_password">New Password</label>
                            <input type="password" id="reset_new_password" placeholder="New password (min 8 characters)" required>
                        </div>
                        <div class="form-group">
                            <label for="reset_confirm_password">Confirm Password</label>
                            <input type="password" id="reset_confirm_password" placeholder="Confirm new password" required>
                        </div>
                        <div id="step2Message" style="margin:10px 0; padding:10px; border-radius:8px; display:none;"></div>
                        <button type="button" class="login-btn" id="verifyOtpBtn" onclick="verifyOTP()">Reset Password</button>
                        <button type="button" class="login-btn" style="background: #999; margin-top: 10px;" onclick="resetForm()">Back</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const emailError = document.getElementById('emailError');
        const passwordError = document.getElementById('passwordError');

        loginForm.addEventListener('submit', function(e) {
            let isValid = true;

            emailError.style.display = 'none';
            passwordError.style.display = 'none';
            emailInput.style.borderColor = '#e0e0e0';
            passwordInput.style.borderColor = '#e0e0e0';

            if (emailInput.value.trim() === '') {
                emailError.style.display = 'block';
                emailInput.style.borderColor = '#dc3545';
                isValid = false;
            }

            if (passwordInput.value.trim() === '') {
                passwordError.style.display = 'block';
                passwordInput.style.borderColor = '#dc3545';
                isValid = false;
            }

            if (!isValid) e.preventDefault();
        });

        emailInput.addEventListener('input', function() {
            emailError.style.display = 'none';
            emailInput.style.borderColor = '#e0e0e0';
        });

        passwordInput.addEventListener('input', function() {
            passwordError.style.display = 'none';
            passwordInput.style.borderColor = '#e0e0e0';
        });

        const inputs = document.querySelectorAll('input[type="email"], input[type="password"]');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.01)';
            });
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        const forgotLink = document.getElementById('forgotPasswordLink');
        const resetModal = document.getElementById('resetModal');
        const resetClose = document.getElementById('resetClose');

        if (forgotLink) {
            forgotLink.addEventListener('click', function(e) {
                e.preventDefault();
                resetModal.style.display = 'flex';
                resetForm();
            });
        }

        if (resetClose) {
            resetClose.addEventListener('click', function() {
                resetModal.style.display = 'none';
                resetForm();
            });
        }

        window.addEventListener('click', function(e) {
            if (e.target === resetModal) {
                resetModal.style.display = 'none';
                resetForm();
            }
        });

        let resetEmail = '';

        function resetForm() {
            document.getElementById('resetStep1').style.display = 'block';
            document.getElementById('resetStep2').style.display = 'none';
            document.getElementById('reset_email').value = '';
            document.getElementById('otp_code').value = '';
            document.getElementById('reset_new_password').value = '';
            document.getElementById('reset_confirm_password').value = '';
            document.getElementById('step1Message').style.display = 'none';
            document.getElementById('step2Message').style.display = 'none';
            document.getElementById('sendOtpBtn').disabled = false;
            document.getElementById('sendOtpBtn').textContent = 'Send OTP';
            document.getElementById('verifyOtpBtn').disabled = false;
            document.getElementById('verifyOtpBtn').textContent = 'Reset Password';
            resetEmail = '';
        }

        function sendOTP() {
            const email = document.getElementById('reset_email').value.trim();
            const msgDiv = document.getElementById('step1Message');
            const btn = document.getElementById('sendOtpBtn');

            msgDiv.style.display = 'none';

            if (!email) {
                msgDiv.style.background = '#f8d7da';
                msgDiv.style.color = '#721c24';
                msgDiv.style.borderLeft = '4px solid #f5c6cb';
                msgDiv.textContent = 'Please enter a valid email';
                msgDiv.style.display = 'block';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Sending...';

            fetch('send_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resetEmail = email;
                    msgDiv.style.background = '#d4edda';
                    msgDiv.style.color = '#155724';
                    msgDiv.style.borderLeft = '4px solid #c3e6cb';
                    msgDiv.textContent = data.message;
                    msgDiv.style.display = 'block';

                    setTimeout(() => {
                        document.getElementById('resetStep1').style.display = 'none';
                        document.getElementById('resetStep2').style.display = 'block';
                        msgDiv.style.display = 'none';
                    }, 1000);
                } else {
                    msgDiv.style.background = '#f8d7da';
                    msgDiv.style.color = '#721c24';
                    msgDiv.style.borderLeft = '4px solid #f5c6cb';
                    msgDiv.textContent = data.message || 'Failed to send OTP';
                    msgDiv.style.display = 'block';
                    btn.disabled = false;
                    btn.textContent = 'Send OTP';
                }
            })
            .catch(error => {
                msgDiv.style.background = '#f8d7da';
                msgDiv.style.color = '#721c24';
                msgDiv.style.borderLeft = '4px solid #f5c6cb';
                msgDiv.textContent = 'Error: ' + error.message;
                msgDiv.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Send OTP';
            });
        }

        function verifyOTP() {
            const otp = document.getElementById('otp_code').value.trim();
            const newPassword = document.getElementById('reset_new_password').value;
            const confirmPassword = document.getElementById('reset_confirm_password').value;
            const msgDiv = document.getElementById('step2Message');
            const btn = document.getElementById('verifyOtpBtn');

            msgDiv.style.display = 'none';

            if (!otp || otp.length !== 6 || isNaN(otp)) {
                msgDiv.style.background = '#f8d7da';
                msgDiv.style.color = '#721c24';
                msgDiv.style.borderLeft = '4px solid #f5c6cb';
                msgDiv.textContent = 'OTP must be exactly 6 digits';
                msgDiv.style.display = 'block';
                return;
            }

            if (!newPassword || !confirmPassword) {
                msgDiv.style.background = '#f8d7da';
                msgDiv.style.color = '#721c24';
                msgDiv.style.borderLeft = '4px solid #f5c6cb';
                msgDiv.textContent = 'Please fill in all password fields';
                msgDiv.style.display = 'block';
                return;
            }

            if (newPassword !== confirmPassword) {
                msgDiv.style.background = '#f8d7da';
                msgDiv.style.color = '#721c24';
                msgDiv.style.borderLeft = '4px solid #f5c6cb';
                msgDiv.textContent = 'Passwords do not match';
                msgDiv.style.display = 'block';
                return;
            }

            if (newPassword.length < 8) {
                msgDiv.style.background = '#f8d7da';
                msgDiv.style.color = '#721c24';
                msgDiv.style.borderLeft = '4px solid #f5c6cb';
                msgDiv.textContent = 'Password must be at least 8 characters';
                msgDiv.style.display = 'block';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Verifying...';

            fetch('verify_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'email=' + encodeURIComponent(resetEmail) +
                      '&otp=' + encodeURIComponent(otp) +
                      '&new_password=' + encodeURIComponent(newPassword) +
                      '&confirm_password=' + encodeURIComponent(confirmPassword)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    msgDiv.style.background = '#d4edda';
                    msgDiv.style.color = '#155724';
                    msgDiv.style.borderLeft = '4px solid #c3e6cb';
                    msgDiv.textContent = data.message;
                    msgDiv.style.display = 'block';

                    setTimeout(() => {
                        resetModal.style.display = 'none';
                        resetForm();
                    }, 2000);
                } else {
                    msgDiv.style.background = '#f8d7da';
                    msgDiv.style.color = '#721c24';
                    msgDiv.style.borderLeft = '4px solid #f5c6cb';
                    msgDiv.textContent = data.message || 'Password reset failed';
                    msgDiv.style.display = 'block';
                    btn.disabled = false;
                    btn.textContent = 'Reset Password';
                }
            })
            .catch(error => {
                msgDiv.style.background = '#f8d7da';
                msgDiv.style.color = '#721c24';
                msgDiv.style.borderLeft = '4px solid #f5c6cb';
                msgDiv.textContent = 'Error: ' + error.message;
                msgDiv.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Reset Password';
            });
        }

        document.getElementById('reset_email').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && document.getElementById('resetStep1').style.display !== 'none') {
                sendOTP();
            }
        });

        document.getElementById('reset_confirm_password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && document.getElementById('resetStep2').style.display !== 'none') {
                verifyOTP();
            }
        });

        <?php if (!empty($resetMessage)): ?>
            resetModal.style.display = 'flex';
        <?php endif; ?>

        function closeModal(){
            document.getElementById("statusModal").style.display = "none";
        }

        function openFollowUpModal(){
            document.getElementById("statusModal").style.display = "none";
            document.getElementById("followUpModal").style.display = "flex";
        }

        function closeFollowUp(){
            document.getElementById("followUpModal").style.display = "none";
            document.getElementById("followUpMessage").value = "";
            document.getElementById("followUpStatus").innerText = "";
        }

        function sendFollowUp(){
            const message = document.getElementById("followUpMessage").value.trim();

            if(message === ""){
                alert("Please enter a message.");
                return;
            }

            fetch("contact_admin.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "message=" + encodeURIComponent(message)
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById("followUpStatus").innerText = "Message sent! The admin will follow up soon.";
                document.getElementById("followUpMessage").value = "";
            })
            .catch(err => {
                document.getElementById("followUpStatus").innerText = "Error sending message. Please try again.";
            });
        }

        <?php if($error === "pending" || $error === "declined" || $error === "inactive"): ?>
        document.addEventListener("DOMContentLoaded", function(){
            document.getElementById("statusModal").style.display = "flex";

            <?php if($error === "pending"): ?>
                document.getElementById("modalTitle").innerText = "Account Pending";
                document.getElementById("modalMessage").innerText = "Your account is still pending approval. Please wait for the administrator.";
            <?php elseif($error === "declined"): ?>
                document.getElementById("modalTitle").innerText = "Account Declined";
                document.getElementById("modalMessage").innerText = "Your account has been declined. You can contact the admin for follow-up.";
            <?php elseif($error === "inactive"): ?>
                document.getElementById("modalTitle").innerText = "Account Deactivated";
                document.getElementById("modalMessage").innerText = "Your account is deactivated. You can contact the admin for follow-up.";
            <?php endif; ?>

            const contactBtn = document.getElementById("contactBtn");
            contactBtn.style.display = "inline-block";
            contactBtn.onclick = openFollowUpModal;
        });
        <?php endif; ?>

        window.addEventListener('click', function(e) {
            const statusModal = document.getElementById("statusModal");
            const followUpModal = document.getElementById("followUpModal");

            if (e.target === statusModal) {
                statusModal.style.display = "none";
            }

            if (e.target === followUpModal) {
                followUpModal.style.display = "none";
            }
        });
    </script>

    <!-- STATUS MODAL -->
    <div id="statusModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:9999;">
        <div style="background:white; padding:30px; border-radius:10px; max-width:400px; width:90%; text-align:center;">
            <h3 id="modalTitle" style="margin-bottom:15px; color:#1a4d2e;"></h3>
            <p id="modalMessage" style="margin-bottom:20px;"></p>
            <div id="modalButtons" style="display:flex; justify-content:center; gap:10px; flex-wrap:wrap;">
                <button id="okBtn" onclick="closeModal()" style="padding:10px 20px; background:#2d7a3e; color:white; border:none; border-radius:5px; cursor:pointer;">
                    OK
                </button>
                <button id="contactBtn" style="padding:10px 20px; background:#1a73e8; color:white; border:none; border-radius:5px; cursor:pointer; display:none;">
                    Contact the Admin
                </button>
            </div>
        </div>
    </div>

    <!-- FOLLOW-UP MODAL -->
    <div id="followUpModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:9999;">
        <div style="background:white; padding:30px; border-radius:10px; max-width:500px; width:90%; text-align:center;">
            <h3 style="margin-bottom:15px; color:#1a4d2e;">Contact Admin</h3>
            <p style="margin-bottom:20px;">Send a message to follow up on your account approval:</p>
            <textarea id="followUpMessage" placeholder="Type your message here..." style="width:100%; padding:10px; border:1px solid #ccc; border-radius:5px; margin-bottom:15px; min-height:100px;"></textarea>
            <div style="display:flex; justify-content:center; gap:10px;">
                <button onclick="sendFollowUp()" style="padding:10px 20px; background:#2d7a3e; color:white; border:none; border-radius:5px; cursor:pointer;">Send</button>
                <button onclick="closeFollowUp()" style="padding:10px 20px; background:#dc3545; color:white; border:none; border-radius:5px; cursor:pointer;">Cancel</button>
            </div>
            <p id="followUpStatus" style="margin-top:15px; color:#1a4d2e;"></p>
        </div>
    </div>
</body>
</html>