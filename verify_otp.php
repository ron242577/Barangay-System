<?php
// verify_otp.php - Validates OTP and resets password

// Suppress output buffering to ensure clean JSON
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

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
$otp = trim($_POST["otp"] ?? "");
$newPassword = $_POST["new_password"] ?? "";
$confirmPassword = $_POST["confirm_password"] ?? "";

// Validate inputs
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid email address"]);
    exit;
}

if (empty($otp)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "OTP is required"]);
    exit;
}

if (empty($newPassword) || empty($confirmPassword)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Password fields cannot be empty"]);
    exit;
}

if ($newPassword !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Passwords do not match"]);
    exit;
}

if (strlen($newPassword) < 8) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Password must be at least 8 characters"]);
    exit;
}

// Step 1: Verify user exists
$userStmt = $conn->prepare("SELECT id FROM accounts WHERE email = ?");
if (!$userStmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}
$userStmt->bind_param("s", $email);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

if (!$user) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "No account found with this email"]);
    exit;
}

// Step 2: Fetch the most recent OTP for this email
$otpStmt = $conn->prepare("
    SELECT id, code, expires_at, used 
    FROM otp_codes 
    WHERE email = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
if (!$otpStmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}
$otpStmt->bind_param("s", $email);
$otpStmt->execute();
$otpResult = $otpStmt->get_result();
$otpRecord = $otpResult->fetch_assoc();
$otpStmt->close();

if (!$otpRecord) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "No OTP found. Please request a new one"]);
    exit;
}

// Step 3: Check if OTP has already been used
if ($otpRecord['used']) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "This OTP has already been used"]);
    exit;
}

// Step 4: Check if OTP has expired
$expiresAt = strtotime($otpRecord['expires_at']);
if (time() > $expiresAt) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "OTP has expired. Please request a new one"]);
    exit;
}

// Step 5: Verify the OTP matches the hashed code
if (!password_verify($otp, $otpRecord['code'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid OTP"]);
    exit;
}

// Step 6: Hash the new password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Step 7: Begin transaction for atomic operations
$conn->begin_transaction();

try {
    // Update password in accounts table
    $updateStmt = $conn->prepare("UPDATE accounts SET password = ? WHERE id = ?");
    if (!$updateStmt) {
        throw new Exception("Database error during password update");
    }
    $updateStmt->bind_param("si", $hashedPassword, $user['id']);
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update password");
    }
    $updateStmt->close();

    // Mark OTP as used
    $markUsedStmt = $conn->prepare("UPDATE otp_codes SET used = TRUE WHERE id = ?");
    if (!$markUsedStmt) {
        throw new Exception("Database error marking OTP as used");
    }
    $markUsedStmt->bind_param("i", $otpRecord['id']);
    if (!$markUsedStmt->execute()) {
        throw new Exception("Failed to mark OTP as used");
    }
    $markUsedStmt->close();

    // Commit transaction
    $conn->commit();

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Password successfully reset. You can now log in with your new password"
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}

$conn->close();
?>
