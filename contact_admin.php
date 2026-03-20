<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo "Invalid request.";
    exit;
}

$message = trim($_POST["message"] ?? "");
if ($message === "") {
    http_response_code(400);
    echo "Message is required.";
    exit;
}

// ✅ Get the account id we saved during pending/declined login attempt
$accountId = isset($_SESSION["followup_account_id"]) ? (int)$_SESSION["followup_account_id"] : 0;

if ($accountId <= 0) {
    http_response_code(401);
    echo "Follow-up session expired. Please login again.";
    exit;
}

// ✅ Fetch the real email from accounts table
$stmt = $conn->prepare("SELECT email FROM accounts WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo "DB error: " . $conn->error;
    exit;
}

$stmt->bind_param("i", $accountId);
$stmt->execute();
$res = $stmt->get_result();
$user = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$user || empty($user["email"])) {
    http_response_code(404);
    echo "Account not found.";
    exit;
}

$sender_email = $user["email"];

// ✅ Save follow-up
$ins = $conn->prepare("INSERT INTO admin_followups (sender_email, message, status) VALUES (?, ?, 'New')");
if (!$ins) {
    http_response_code(500);
    echo "DB error: " . $conn->error;
    exit;
}

$ins->bind_param("ss", $sender_email, $message);

if ($ins->execute()) {
    // optional: clear session follow-up id after sending to avoid reuse
    unset($_SESSION["followup_account_id"]);
    echo "OK";
} else {
    http_response_code(500);
    echo "Insert failed: " . $ins->error;
}

$ins->close();