<?php
// send_otp.php - Generates and sends OTP to user's email

// Suppress output buffering to ensure clean JSON
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');  // Don't display errors, we'll catch them

header('Content-Type: application/json; charset=utf-8');

// ========================================
// GMAIL SMTP CONFIGURATION
// ========================================
$mail_user = 'pikachu242577@gmail.com';      // Your Gmail address
$mail_pass = 'zjlxukghikzifknf';          // Your App Password
// ========================================

// Catch all errors and return as JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "PHP Error: " . $errstr . " (Line " . $errline . ")"
    ]);
    exit;
});

// Catch fatal errors too
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Fatal Error: " . $error['message']
        ]);
        exit;
    }
});

ob_end_clean();

include "db.php";

// Verify database connection
if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// Verify otp_codes table exists
$checkTable = $conn->query("SHOW TABLES LIKE 'otp_codes'");
if (!$checkTable || $checkTable->num_rows === 0) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Database table 'otp_codes' not found. Please create it first."
    ]);
    exit;
}


// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
}

$email = trim($_POST["email"] ?? "");

// Validate email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid email address"]);
    exit;
}

// Check if user exists
$stmt = $conn->prepare("SELECT id, fullname FROM accounts WHERE email = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "No account found with this email"]);
    exit;
}

// Generate 6-digit OTP
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Hash the OTP for storage
$hashedOtp = password_hash($otp, PASSWORD_DEFAULT);

// Calculate expiration (10 minutes from now)
$expiresAt = date('Y-m-d H:i:s', time() + 600);

// Delete any previous unused OTPs for this email to prevent clutter
$deleteStmt = $conn->prepare("DELETE FROM otp_codes WHERE email = ? AND used = FALSE");
if (!$deleteStmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}
$deleteStmt->bind_param("s", $email);
$deleteStmt->execute();
$deleteStmt->close();

// Save OTP to database
$insertStmt = $conn->prepare("INSERT INTO otp_codes (email, code, expires_at) VALUES (?, ?, ?)");
if (!$insertStmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}

$insertStmt->bind_param("sss", $email, $hashedOtp, $expiresAt);
if (!$insertStmt->execute()) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to generate OTP"]);
    $insertStmt->close();
    exit;
}
$insertStmt->close();

// Send OTP via PHPMailer
try {
    // Check if vendor/autoload.php exists
    if (!file_exists('vendor/autoload.php')) {
        http_response_code(500);
        echo json_encode([
            "success" => false, 
            "message" => "PHPMailer not installed. Run: composer require phpmailer/phpmailer"
        ]);
        exit;
    }

    // Include PHPMailer
    require 'vendor/autoload.php';

    // Verify Gmail credentials are not empty
    if (empty($mail_user) || empty($mail_pass)) {
        http_response_code(500);
        echo json_encode([
            "success" => false, 
            "message" => "Gmail credentials not set in send_otp.php"
        ]);
        exit;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    // SMTP configuration
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $mail_user;
    $mail->Password   = $mail_pass;
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    // Clear any previous recipients
    $mail->clearAddresses();
    $mail->clearReplyTos();

    // Sender and Recipient
    $mail->setFrom($mail_user, 'Barangay 292 E-Services');
    $mail->addAddress($email, $user['fullname']);

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'Password Reset OTP - Barangay 292 E-Services';
    $mail->Body    = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 500px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 8px; }
                .header { background: linear-gradient(135deg, #2d7a3e 0%, #1a4d2e 100%); color: white; padding: 20px; text-align: center; border-radius: 8px; }
                .otp-box { background: white; border: 2px solid #2d7a3e; padding: 25px; margin: 20px 0; text-align: center; border-radius: 8px; }
                .otp-code { font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #2d7a3e; }
                .expiry { color: #666; font-size: 12px; margin-top: 15px; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Barangay 292 E-Services</h1>
                    <p>Password Reset Request</p>
                </div>
                <p>Hello " . htmlspecialchars($user['fullname']) . ",</p>
                <p>You requested a password reset for your account. Please use the OTP code below to proceed:</p>
                <div class='otp-box'>
                    <div class='otp-code'>$otp</div>
                    <div class='expiry'>This code expires in 10 minutes</div>
                </div>
                <p><strong>Security Notice:</strong> If you did not request this password reset, please ignore this email. Your account remains secure.</p>
                <div class='footer'>
                    <p>© 2026 Barangay 292 Management System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
    ";
    $mail->AltBody = "Your OTP code is: $otp. This code expires in 10 minutes.";

    // Send email
    if ($mail->send()) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "OTP sent successfully to your email",
            "email" => $email
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to send OTP email: " . $mail->ErrorInfo]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error sending email: " . $e->getMessage()]);
}

$conn->close();
?>
