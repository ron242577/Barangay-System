<?php
session_start();
include 'db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION["role"]) || !in_array($_SESSION["role"], ['resident', 'Admin', 'SuperAdmin'])) {
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| PHPMailer loader
|--------------------------------------------------------------------------
| Try Composer autoload first.
| If unavailable or incomplete, load PHPMailer manually.
*/
$phpMailerLoaded = false;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    $phpMailerLoaded = class_exists('PHPMailer\\PHPMailer\\PHPMailer');
}

if (!$phpMailerLoaded) {
    $phpMailerFiles = [
        __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php',
        __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php',
        __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php',
    ];

    $allFilesExist = true;
    foreach ($phpMailerFiles as $file) {
        if (!file_exists($file)) {
            $allFilesExist = false;
            break;
        }
    }

    if ($allFilesExist) {
        require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
        require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
        $phpMailerLoaded = class_exists('PHPMailer\\PHPMailer\\PHPMailer');
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ========================================
// FUNCTION: Send Request Approval Email
// ========================================
function sendRequestApprovalEmail($email, $fullname, $document_type) {
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        error_log("PHPMailer is not installed or not loaded.");
        return false;
    }

    try {
        $mail_user = 'pikachu242577@gmail.com';
        $mail_pass = 'zjlxukghikzifknf';

        $mail = new PHPMailer(true);

        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $mail_user;
        $mail->Password   = $mail_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Sender and recipient
        $mail->setFrom($mail_user, 'Barangay 292 E-Services');
        $mail->addAddress($email, $fullname);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Request Approved - Document Ready for Pick Up - Barangay 292 E-Services';
        $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; }
                    .container { max-width: 500px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 8px; }
                    .header { background: linear-gradient(135deg, #2d7a3e 0%, #1a4d2e 100%); color: white; padding: 20px; text-align: center; border-radius: 8px; }
                    .message-box { background: white; border: 2px solid #2d7a3e; padding: 25px; margin: 20px 0; border-radius: 8px; }
                    .approval-text { font-size: 18px; font-weight: bold; color: #2d7a3e; }
                    .document-detail { font-size: 14px; color: #555; margin-top: 15px; }
                    .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Barangay 292 E-Services</h1>
                        <p>Request Approval Notification</p>
                    </div>
                    <p>Dear " . htmlspecialchars($fullname) . ",</p>
                    <div class='message-box'>
                        <div class='approval-text'>Your requested document is ready for pick up.</div>
                        <div class='document-detail'>
                            <strong>Document Type:</strong> " . htmlspecialchars($document_type) . "<br>
                            <strong>Status:</strong> Approved
                        </div>
                    </div>
                    <p>Your " . htmlspecialchars($document_type) . " is now ready for collection. Please visit the Barangay Office during office hours to pick up your document.</p>
                    <p><strong>Office Hours:</strong> Monday - Friday, 8:00 AM - 5:00 PM</p>
                    <div class='footer'>
                        <p>© 2026 Barangay 292 Management System. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        $mail->AltBody = "Your requested document is ready for pick up. Please visit the Barangay Office to collect it.";

        return $mail->send();

    } catch (Exception $e) {
        error_log("Error sending request approval email: " . $e->getMessage());
        return false;
    }
}

// TOTAL REGISTERED ACCOUNTS
$totalAccountsQuery = "SELECT COUNT(*) AS total FROM accounts";
$totalAccountsResult = $conn->query($totalAccountsQuery);
$totalAccounts = $totalAccountsResult ? $totalAccountsResult->fetch_assoc()['total'] : 0;

// PENDING ACCOUNTS
$pendingAccountsQuery = "SELECT COUNT(*) AS total FROM accounts WHERE status = 'pending' AND role = 'resident'";
$pendingAccountsResult = $conn->query($pendingAccountsQuery);
$pendingAccounts = $pendingAccountsResult ? $pendingAccountsResult->fetch_assoc()['total'] : 0;

// PENDING REQUESTS
$pendingRequestsQuery = "SELECT COUNT(*) AS total FROM requests WHERE status IN ('pending','Pending')";
$pendingRequestsResult = $conn->query($pendingRequestsQuery);
$pendingRequests = $pendingRequestsResult ? $pendingRequestsResult->fetch_assoc()['total'] : 0;

// PENDING REPORTS
$pendingReportsQuery = "SELECT COUNT(*) AS total FROM reports WHERE status IN ('pending','Pending')";
$pendingReportsResult = $conn->query($pendingReportsQuery);
$pendingReports = $pendingReportsResult ? $pendingReportsResult->fetch_assoc()['total'] : 0;

// PENDING DONATIONS
$pendingDonationsQuery = "SELECT COUNT(*) AS total FROM donations WHERE status = 'New'";
$pendingDonationsResult = $conn->query($pendingDonationsQuery);
$pendingDonations = $pendingDonationsResult ? $pendingDonationsResult->fetch_assoc()['total'] : 0;

// PENDING FEEDBACKS
$pendingFeedbacksQuery = "SELECT COUNT(*) AS total FROM feedbacks WHERE status = 'New'";
$pendingFeedbacksResult = $conn->query($pendingFeedbacksQuery);
$pendingFeedbacks = $pendingFeedbacksResult ? $pendingFeedbacksResult->fetch_assoc()['total'] : 0;

// PENDING FOLLOW-UP MESSAGES
$pendingFollowUpsQuery = "
  SELECT COUNT(*) AS total
  FROM admin_followups af
  LEFT JOIN accounts a ON af.sender_email = a.email
  WHERE af.status = 'New'
    AND (a.status IS NULL OR a.status != 'active')
";
$pendingFollowUpsResult = $conn->query($pendingFollowUpsQuery);
$pendingFollowUps = $pendingFollowUpsResult ? $pendingFollowUpsResult->fetch_assoc()['total'] : 0;

// TOTAL PENDING NOTIFICATIONS
$totalPending = $pendingRequests + $pendingReports + $pendingDonations + $pendingFeedbacks + $pendingAccounts + $pendingFollowUps;

// RECENT NOTIFICATIONS
$recentNotifications = [];

// Recent Requests
$recentRequestsQuery = "SELECT 'Request' AS type, r.request_id AS id, r.document_type AS details, r.created_at AS date, a.fullname AS user
                        FROM requests r
                        JOIN accounts a ON r.user_id = a.id
                        WHERE r.status IN ('pending','Pending')
                        ORDER BY r.created_at DESC LIMIT 5";
$recentRequestsResult = $conn->query($recentRequestsQuery);
while ($recentRequestsResult && ($row = $recentRequestsResult->fetch_assoc())) {
    $recentNotifications[] = $row;
}

// Recent Reports
$recentReportsQuery = "SELECT 'Report' AS type, r.id AS id, r.reason AS details, r.created_at AS date, a.fullname AS user
                       FROM reports r
                       JOIN accounts a ON r.user_id = a.id
                       WHERE r.status IN ('pending','Pending')
                       ORDER BY r.created_at DESC LIMIT 5";
$recentReportsResult = $conn->query($recentReportsQuery);
while ($recentReportsResult && ($row = $recentReportsResult->fetch_assoc())) {
    $recentNotifications[] = $row;
}

// Recent Donations
$recentDonationsQuery = "SELECT 'Donation' AS type, d.id AS id, d.message AS details, d.created_at AS date, a.fullname AS user
                         FROM donations d
                         JOIN accounts a ON d.user_id = a.id
                         WHERE d.status = 'New'
                         ORDER BY d.created_at DESC LIMIT 5";
$recentDonationsResult = $conn->query($recentDonationsQuery);
while ($recentDonationsResult && ($row = $recentDonationsResult->fetch_assoc())) {
    $recentNotifications[] = $row;
}

// Recent Feedbacks
$recentFeedbacksQuery = "SELECT 'Feedback' AS type, f.id AS id, f.feedback_text AS details, f.created_at AS date, a.fullname AS user
                         FROM feedbacks f
                         JOIN accounts a ON f.user_id = a.id
                         WHERE f.status = 'New'
                         ORDER BY f.created_at DESC LIMIT 5";
$recentFeedbacksResult = $conn->query($recentFeedbacksQuery);
while ($recentFeedbacksResult && ($row = $recentFeedbacksResult->fetch_assoc())) {
    $recentNotifications[] = $row;
}

// Recent Accounts
$recentAccountsQuery = "SELECT 'Account' AS type, a.id AS id, CONCAT('New account: ', a.fullname) AS details, a.created_at AS date, a.fullname AS user
                        FROM accounts a
                        WHERE a.status = 'pending' AND a.role = 'resident'
                        ORDER BY a.created_at DESC LIMIT 5";
$recentAccountsResult = $conn->query($recentAccountsQuery);
while ($recentAccountsResult && ($row = $recentAccountsResult->fetch_assoc())) {
    $recentNotifications[] = $row;
}

// Recent Follow-ups
$recentFollowUpsQuery = "
  SELECT 'FollowUp' AS type, af.id AS id, af.message AS details, af.created_at AS date, af.sender_email AS user
  FROM admin_followups af
  LEFT JOIN accounts a ON af.sender_email = a.email
  WHERE af.status = 'New'
    AND (a.status IS NULL OR a.status != 'active')
  ORDER BY af.created_at DESC
  LIMIT 5
";
$recentFollowUpsResult = $conn->query($recentFollowUpsQuery);
while ($recentFollowUpsResult && ($row = $recentFollowUpsResult->fetch_assoc())) {
    $recentNotifications[] = $row;
}

// Sort notifications
usort($recentNotifications, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
$recentNotifications = array_slice($recentNotifications, 0, 10);

/* =========================
   RESIDENT SUBMIT REQUEST
========================= */
if (isset($_POST['submit'])) {

    $user_id = $_SESSION['user_id'];
    $document = $_POST['docs'];
    $purpose = $_POST['age'];

    $guardian_name = $_POST['guardian_name'] ?? '';
    $guardian_address = $_POST['guardian_address'] ?? '';
    $guardian_contact = $_POST['guardian_contact'] ?? '';

    $stmt = $conn->prepare("SELECT fullname, address FROM accounts WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $sql = "INSERT INTO requests 
            (user_id, fullname, address, document_type, purpose, guardian_name, guardian_address, guardian_contact)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isssssss",
        $user_id,
        $user['fullname'],
        $user['address'],
        $document,
        $purpose,
        $guardian_name,
        $guardian_address,
        $guardian_contact
    );
    $stmt->execute();
}

/* =========================
   ADMIN APPROVE REQUEST
========================= */
if (isset($_POST['approve']) && $_SESSION['role'] === 'Admin') {
    $id = (int)$_POST['request_id'];

    $getInfo = $conn->prepare("SELECT r.document_type, a.email, a.fullname FROM requests r INNER JOIN accounts a ON r.user_id = a.id WHERE r.request_id = ?");
    $getInfo->bind_param("i", $id);
    $getInfo->execute();
    $result = $getInfo->get_result();
    $requestInfo = $result->fetch_assoc();
    $getInfo->close();

    $conn->query("UPDATE requests SET status='Approved' WHERE request_id=$id");

    if ($requestInfo && !empty($requestInfo['email'])) {
        sendRequestApprovalEmail(
            $requestInfo['email'],
            $requestInfo['fullname'],
            $requestInfo['document_type']
        );
    }

    header("Location: Request.php");
    exit;
}

/* =========================
   ADMIN DECLINE REQUEST
========================= */
if (isset($_POST['decline']) && $_SESSION['role'] === 'Admin') {
    $id = (int)$_POST['request_id'];
    $decline_reason = isset($_POST['decline_reason']) ? trim($_POST['decline_reason']) : '';

    $colCheck = $conn->query("SHOW COLUMNS FROM requests LIKE 'decline_reason'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE requests ADD COLUMN decline_reason TEXT NULL");
    }

    $conn->query("UPDATE requests SET status='Declined', decline_reason='" . $conn->real_escape_string($decline_reason) . "' WHERE request_id=$id");
    header("Location: Request.php");
    exit;
}

// Fetch requests with optional filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$day_filter = isset($_GET['day']) ? $_GET['day'] : '';
$date_from = isset($_GET['from']) ? $_GET['from'] : '';
$date_to = isset($_GET['to']) ? $_GET['to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

/* ==========================
   GENERATE REPORT
========================== */
if (isset($_GET['export']) && $_GET['export'] === 'pdf' && $_SESSION['role'] === 'Admin') {

    $sql = "SELECT 
                r.request_id,
                r.document_type,
                r.status,
                r.date_requested,
                r.purpose,
                a.fullname,
                a.address,
                a.id AS resident_id
            FROM requests r
            INNER JOIN accounts a ON r.user_id = a.id
            WHERE 1=1";

    $params = [];
    $types = '';

    if ($status_filter) {
        $sql .= " AND r.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }

    if ($type_filter && $type_filter !== 'All') {
        $sql .= " AND r.document_type = ?";
        $params[] = $type_filter;
        $types .= 's';
    }

    if ($day_filter === '7') {
        $sql .= " AND DATE(r.date_requested) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($day_filter === '30') {
        $sql .= " AND DATE(r.date_requested) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    } elseif ($day_filter === '365') {
        $sql .= " AND DATE(r.date_requested) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
    } elseif ($day_filter === 'custom' && $date_from && $date_to) {
        $sql .= " AND DATE(r.date_requested) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
        $types .= 'ss';
    } elseif ($date_from && $date_to) {
        $sql .= " AND DATE(r.date_requested) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
        $types .= 'ss';
    }

    if ($search) {
        $sql .= " AND (a.fullname LIKE ? OR r.document_type LIKE ? OR r.purpose LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'sss';
    }

    $sql .= " ORDER BY r.date_requested DESC";

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    $totalRequested = 0;
    $totalBrgyID = 0;
    $totalIndigency = 0;
    $totalClearance = 0;
    $totalFirstTime = 0;

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        $totalRequested++;

        if ($row['document_type'] === 'Barangay ID') $totalBrgyID++;
        if ($row['document_type'] === 'Barangay Indigency') $totalIndigency++;
        if ($row['document_type'] === 'Barangay Clearance') $totalClearance++;
        if ($row['document_type'] === 'First Time Job Seeker') $totalFirstTime++;
    }

    $stmt->close();
    $qs = $_GET;
    unset($qs['export']);
    $returnUrl = 'Request.php' . (!empty($qs) ? ('?' . http_build_query($qs)) : '');

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Report</title>
    <style>
        body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; padding: 20px; }
        .report-wrap { max-width: 950px; margin: 0 auto; border: 2px solid #6a5acd; padding: 20px; }
        .report-header { text-align: center; }
        .report-header img { width: 90px; height: auto; display: block; margin: 0 auto 8px auto; }
        .report-header h2 { margin: 0; font-size: 20px; font-weight: bold; }
        .report-meta { margin-top: 14px; font-size: 14px; }
        .report-meta div { margin: 6px 0; }
        .table-title { text-align: center; font-weight: bold; margin: 18px 0 10px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f2f2f2; }
        .no-print { margin-top: 15px; text-align: right; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .report-wrap { border: 2px solid #6a5acd; }
        }
    </style>
    </head>
    <body>
        <div class="report-wrap">
            <div class="report-header">
                <img src="images/logo2.png" alt="Logo">
                <h2>Logo Barangay 290</h2>
            </div>

            <div class="report-meta">
                <div><strong>Report of Document Requested</strong> (<?= date('M d, Y') ?>)</div>
                <div><strong>Type of document:</strong> <?= htmlspecialchars($type_filter ? $type_filter : 'All') ?></div>
                <div><strong>Total of Barangay Id:</strong> <?= $totalBrgyID ?></div>
                <div><strong>Total of Barangay Indigency:</strong> <?= $totalIndigency ?></div>
                <div><strong>Total of Barangay Clearance:</strong> <?= $totalClearance ?></div>
                <div><strong>Total of First time employee</strong> <?= $totalFirstTime ?></div>
                <div><strong>Total Requested</strong> <?= $totalRequested ?></div>
            </div>

            <div class="table-title">Table</div>

            <table>
                <tr>
                    <th>Request ID</th>
                    <th>Resident ID</th>
                    <th>Resident Name</th>
                    <th>Resident Address</th>
                    <th>Requested Document</th>
                    <th>Purpose</th>
                    <th>Status</th>
                    <th>Date Requested</th>
                </tr>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8">No requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td>REQ-<?= str_pad($r['request_id'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td>RES-<?= str_pad($r['resident_id'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($r['fullname']) ?></td>
                            <td><?= htmlspecialchars($r['address']) ?></td>
                            <td><?= htmlspecialchars($r['document_type']) ?></td>
                            <td><?= htmlspecialchars($r['purpose']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($r['status'])) ?></td>
                            <td><?= htmlspecialchars($r['date_requested']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </table>

            <div class="no-print">
                <button onclick="window.location.href='<?= htmlspecialchars($returnUrl) ?>'">Cancel</button>
                <button onclick="window.print()">Print / Save as PDF</button>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="secretary.css">
<title>Admin Dashboard</title>

<style>
.notification-container {
    position: relative;
    display: inline-block;
    cursor: pointer;
}
.notification-badge {
    position: absolute;
    top: -5px;
    right: 18px;
    background: red;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 12px;
    font-weight: bold;
}
.notification-dropdown {
    position: absolute;
    top: 50px;
    right: 0;
    background: white;
    border: 1px solid #ccc;
    padding: 10px;
    width: 350px;
    max-height: 400px;
    overflow-y: auto;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    z-index: 1000;
}
.notification-dropdown h4 { margin: 0 0 10px 0; font-size: 16px; }
.notification-dropdown ul { list-style: none; padding: 0; margin: 0; }
.notification-dropdown li { margin-bottom: 10px; padding: 8px; border-bottom: 1px solid #eee; }
.notification-dropdown li:last-child { border-bottom: none; }
.notification-dropdown .notification-item { text-decoration: none; color: #333; display: block; }
.notification-dropdown .notification-item:hover { background: #f0f0f0; }
.notification-dropdown .notification-type { font-weight: bold; color: #007bff; }
.notification-dropdown .notification-details { font-size: 14px; margin: 5px 0; }
.notification-dropdown .notification-date { font-size: 12px; color: #666; }

.modal {
    display:none;
    position:fixed;
    z-index:5000;
    left:0; top:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.5);
}
.modal-content {
    background:#fff;
    margin: 6% auto;
    padding: 20px;
    width:92%;
    max-width: 600px;
    border-radius:10px;
    box-shadow:0 8px 25px rgba(0,0,0,0.2);
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}
.close {
    float:right;
    font-size:28px;
    cursor:pointer;
    line-height:1;
}
.btn-row { display:flex; gap:10px; justify-content:flex-end; margin-top: 14px; flex-wrap:wrap; }
.btn-basic { padding:10px 14px; border:none; border-radius:8px; cursor:pointer; font-weight:800; }
.btn-gray { background:#eee; color:#333; }
.btn-blue { background:#1a73e8; color:#fff; }

.notif-info { margin-top: 10px; font-size: 14px; line-height: 1.5; }
.notif-info .row { margin: 8px 0; }
.notif-info .label { font-weight: 800; color: #333; }
.notif-info .value { color: #444; white-space: pre-wrap; word-break: break-word; }
</style>
</head>

<body>

<div class="sidebar">
    <div class="sideflex">
        <div class="usercontainer">
            <img class="user" src="images/usericon.png" alt="Notification" />
        </div>

        <div class="info">
            <h4 class="username"><?= htmlspecialchars($_SESSION["fullname"]) ?></h4>
            <h4 class="usertype"><?= htmlspecialchars($_SESSION["role"]) ?></h4>
        </div>
    </div>

    <hr class="hrside">

    <?php if ($_SESSION["role"] === "SuperAdmin"): ?>
        <div class="btncontainer" onclick="window.location.href='SuperAdmin_Dashboard.php'">
            <img class="icon" src="images/dashboard.png" alt="home" />
            <h4 class="text">Dashboard</h4>
        </div>
    <?php elseif ($_SESSION["role"] === "Admin"): ?>
        <div class="btncontainer" onclick="window.location.href='Admin_Dashboard.php'">
            <img class="icon" src="images/dashboard.png" alt="home" />
            <h4 class="text">Dashboard</h4>
        </div>
    <?php endif; ?>
<?php if ($_SESSION["role"] === "SuperAdmin"): ?>
        <div class="btncontainer" onclick="window.location.href='Manage_Accounts.php'">
            <img class="icon" src="images/add-user.png" alt="manage accounts" />
            <h4 class="text">Manage Accounts</h4>
        </div>
    <?php endif; ?>
    <div class="btncontainer" onclick="window.location.href='Resident_User.php'">
        <img class="icon" src="images/add-user.png" alt="home" />
        <h4 class="text">Pending Accounts</h4>
    </div>

    <div class="btncontainer" onclick="window.location.href='Request.php'">
        <img class="icon" src="images/reqicon.png" alt="request" />
        <h4 class="text">Request</h4>
    </div>

    <div class="btncontainer" onclick="window.location.href='Report.php'">
        <img class="icon" src="images/repicon.png" alt="request" />
        <h4 class="text">Report</h4>
    </div>

    <div class="btncontainer" onclick="window.location.href='Donate.php'">
        <img class="icon" src="images/dicon.png" alt="request" />
        <h4 class="text">Donate</h4>
    </div>

    <div class="btncontainer" onclick="window.location.href='Feedback.php'">
        <img class="icon" src="images/fbicon.png" alt="request" />
        <h4 class="text">Feedback</h4>
    </div>

    

    <hr style="width: 100%; border: 0.5px solid rgba(255, 255, 255, 0.4); margin-top: 0px;">

    <div class="address1">
        <img class="logoadd" src="images/pin.png" alt="Feedback">
        <div class="addtext">
            <p>Zone 28 Disctrict 3 <br>808 Reigna Regente St. <br>Binondo, Manila</p>
        </div>
    </div>

    <div class="logoutcontainer" onclick="window.location.href='logout.php'">
        <img class="logolog" src="images/logout.png" alt="Feedback">
        <h4 class="logout">Logout</h4>
    </div>
</div>

<div class="content">
<div class="dashboard-header">
    <div class="header-left">
        <img src="images/logo2.png" class="logo">
        <div class="header-text">
            <h1 class="res1">Barangay Management & E-Services Platform</h1>
        </div>
    </div>

    <div class="notification-container" onclick="toggleNotifications()">
        <img src="images/notification.png" class="header-icon" alt="Notifications">
        <?php if ($totalPending > 0): ?>
            <span class="notification-badge"><?= $totalPending ?></span>
        <?php endif; ?>
    </div>
</div>

<div id="notificationDropdown" class="notification-dropdown" style="display:none;">
    <h4>Recent Notifications</h4>
    <ul>
        <?php if (empty($recentNotifications)): ?>
            <li>No new notifications.</li>
        <?php else: ?>
            <?php foreach ($recentNotifications as $notif): ?>
                <?php
                $type = (string)($notif['type'] ?? '');
                $id = (int)($notif['id'] ?? 0);
                $user = (string)($notif['user'] ?? '');
                $details = (string)($notif['details'] ?? '');
                $date = (string)($notif['date'] ?? '');

                $typeAttr = htmlspecialchars($type, ENT_QUOTES);
                $userAttr = htmlspecialchars($user, ENT_QUOTES);
                $detailsAttr = htmlspecialchars($details, ENT_QUOTES);
                $dateAttr = htmlspecialchars($date, ENT_QUOTES);
                ?>
                <li>
                    <a href="#"
                       class="notification-item"
                       data-type="<?= $typeAttr ?>"
                       data-id="<?= $id ?>"
                       data-user="<?= $userAttr ?>"
                       data-details="<?= $detailsAttr ?>"
                       data-date="<?= $dateAttr ?>"
                       onclick="openNotificationModal(event, this)">
                        <div class="notification-type"><?= htmlspecialchars($type) ?> from <?= htmlspecialchars($user) ?></div>
                        <div class="notification-details"><?= htmlspecialchars(substr($details, 0, 50)) ?>...</div>
                        <div class="notification-date"><?= date('M d, Y H:i', strtotime($date)) ?></div>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>

<div id="notificationModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeNotificationModal()">&times;</span>
        <div style="display: flex; align-items:flex-start; margin-left:0px;">
            <div class="logo-containerinfo">
                <img class="logoa" src="images/info1.png" alt="Logo">
            </div>
            <h2 class="notiftitle" style="color: #443d3d;" id="notifTitle">Notification Details</h2>
        </div>
        <hr style="width:99%;">

        <div class="notif-info">
            <div class="row"><span class="label">Type:</span> <span class="value" id="notifType"></span></div>
            <div class="row"><span class="label">From:</span> <span class="value" id="notifFrom"></span></div>
            <div class="row"><span class="label">Date:</span> <span class="value" id="notifDate"></span></div>
            <div class="row"><span class="label">Details:</span></div>
            <div class="row"><div class="value" id="notifDetails"></div></div>
        </div>

        <div class="btn-row">
            <button type="button" class="btn-basic btn-blue" id="notifGoBtn">Go to Page</button>
        </div>
    </div>
</div>

<hr class="green-line">

<div id="cardrequest" class="cardrequest">
    <div class="flex1" style="align-items:center; gap: 10px; margin-bottom:10px; flex-wrap:wrap;">
        <div style="display:flex; align-items:flex-start; margin-top: 8px;">
            <div class="logo-containeradmin">
                <img class="logoadmin" src="images/report.png" alt="Logo">
            </div>
            <h3 style="margin-top: 8px; margin-left:5px; color: #443d3d;">Request Table</h3>
        </div>

        <div class="flex3">
            <form class="resform" method="GET" action="Request.php" id="filterForm">
                <div>
                    <label>Status:</label>
                    <select class="custom3" name="status" onchange="document.getElementById('filterForm').submit()">
                        <option value="">All</option>
                        <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Approved" <?= $status_filter === 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Declined" <?= $status_filter === 'Declined' ? 'selected' : '' ?>>Declined</option>
                    </select>
                </div>

                <div>
                    <label>Type:</label>
                    <select class="custom5" name="type" onchange="document.getElementById('filterForm').submit()">
                        <option value="All" <?= $type_filter === 'All' || !$type_filter ? 'selected' : '' ?>>All</option>
                        <option value="Barangay Clearance" <?= $type_filter === 'Barangay Clearance' ? 'selected' : '' ?>>Barangay Clearance</option>
                        <option value="Barangay Indigency" <?= $type_filter === 'Barangay Indigency' ? 'selected' : '' ?>>Barangay Indigency</option>
                        <option value="Barangay ID" <?= $type_filter === 'Barangay ID' ? 'selected' : '' ?>>Barangay ID</option>
                        <option value="First Time Job Seeker" <?= $type_filter === 'First Time Job Seeker' ? 'selected' : '' ?>>First Time Job Seeker</option>
                    </select>
                </div>

                <div>
                    <label class="day">Day:</label>
                    <select class="custom4" name="day" id="requestDayFilter" onchange="filterByRequestDay(this.value)">
                        <option value="" <?= $day_filter === '' ? 'selected' : '' ?>>Select</option>
                        <option value="7" <?= $day_filter === '7' ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="30" <?= $day_filter === '30' ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="365" <?= $day_filter === '365' ? 'selected' : '' ?>>Last Year</option>
                        <option value="custom" <?= $day_filter === 'custom' ? 'selected' : '' ?>>Custom</option>
                    </select>

                    <label id="reqfr" style="display:none;">From:</label>
                    <input type="date" id="requestFrom" name="from" value="<?= htmlspecialchars($date_from) ?>" style="display:none;" onchange="applyRequestCustomDateFilter()">

                    <label id="reqtoo" style="display:none;">To:</label>
                    <input class="input1" type="date" id="requestTo" name="to" value="<?= htmlspecialchars($date_to) ?>" style="display:none;" onchange="applyRequestCustomDateFilter()">
                </div>
            </form>
        </div>

        <form method="GET" action="Request.php" id="searchForm">
            <input
                class="searches"
                placeholder="Search"
                type="search"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
                onchange="document.getElementById('searchForm').submit()">
        </form>
    </div>

    <?php
    if (in_array($_SESSION['role'], ['Admin', 'SuperAdmin'])) {

        $sql = "SELECT 
                    r.request_id,
                    r.document_type,
                    r.status,
                    r.date_requested,
                    r.purpose,
                    r.guardian_name,
                    r.guardian_address,
                    r.guardian_contact,
                    a.fullname,
                    a.address,
                    a.id AS resident_id
                FROM requests r
                INNER JOIN accounts a ON r.user_id = a.id
                WHERE 1=1";

        $params = [];
        $types = '';

        if ($status_filter) {
            $sql .= " AND r.status = ?";
            $params[] = $status_filter;
            $types .= 's';
        }

        if ($type_filter && $type_filter !== 'All') {
            $sql .= " AND r.document_type = ?";
            $params[] = $type_filter;
            $types .= 's';
        }

        if ($day_filter === '7') {
            $sql .= " AND DATE(r.date_requested) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        } elseif ($day_filter === '30') {
            $sql .= " AND DATE(r.date_requested) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        } elseif ($day_filter === '365') {
            $sql .= " AND DATE(r.date_requested) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        } elseif ($day_filter === 'custom' && $date_from && $date_to) {
            $sql .= " AND DATE(r.date_requested) BETWEEN ? AND ?";
            $params[] = $date_from;
            $params[] = $date_to;
            $types .= 'ss';
        } elseif ($date_from && $date_to) {
            $sql .= " AND DATE(r.date_requested) BETWEEN ? AND ?";
            $params[] = $date_from;
            $params[] = $date_to;
            $types .= 'ss';
        }

        if ($search) {
            $sql .= " AND (a.fullname LIKE ? OR r.document_type LIKE ? OR r.purpose LIKE ?)";
            $search_term = "%$search%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $types .= 'sss';
        }

        $sql .= " ORDER BY r.date_requested DESC";

        $stmt = $conn->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $requestRows = [];
        while ($result && $row = $result->fetch_assoc()) {
            $requestRows[] = $row;
        }

        echo "<div class='table-container'>";
        echo "<table border='1' cellpadding='10'>";

        echo "
            <tr>
                <th>Resident Name / ID</th>
                <th>Requested Document</th>
                <th>Date Requested</th>
                <th>Request Status</th>
                <th>Actions</th>
            </tr>
        ";

        if (!empty($requestRows)) {
            foreach ($requestRows as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['fullname']) . " (RES-" . str_pad($row['resident_id'], 3, '0', STR_PAD_LEFT) . ")</td>";
                echo "<td>" . htmlspecialchars($row['document_type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['date_requested']) . "</td>";
                echo "<td>" . htmlspecialchars(ucfirst($row['status'])) . "</td>";
                if ($_SESSION['role'] === 'Admin') {
                    echo "<td>
                        <button class='btn-view' onclick='viewRequest(" . (int)$row['request_id'] . ")'>View</button>
                        <button class='btn-approve' onclick='openApprovalModal(" . (int)$row['request_id'] . ")'>Approve</button>
                        <button class='btn-decline' onclick='openDeclineModal(" . (int)$row['request_id'] . ")'>Decline</button>
                    </td>";
                } elseif ($_SESSION['role'] === 'SuperAdmin') {
                    echo "<td>
                        <button class='btn-view' onclick='viewRequest(" . (int)$row['request_id'] . ")'>View</button>
                    </td>";
                }
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No requests found</td></tr>";
        }

        echo "</table>";
        echo "</div>";

        echo "<div style='display: flex;'>";
        echo "  <div class='botbtn'>";
        echo "  <button class='btn-report' onclick='generateReport()'>Generate Report</button>";
        echo "  </div>";
        echo "</div>";

        foreach ($requestRows as $row) {

            echo "
            <div id='modal-{$row['request_id']}' class='modal'>
                <div class='modal-content'>
                    <span class='close' onclick='closeModal({$row['request_id']})'>&times;</span>
                    <h2 style='margin-top:0px; margin-bottom:10px;'>Request Details</h2>
                    <hr style='width:100%;'>
                    <p><strong>Resident Full Name:</strong> " . htmlspecialchars($row['fullname']) . "</p>
                    <p><strong>Resident Address:</strong> " . htmlspecialchars($row['address']) . "</p>
                    <p><strong>Purpose:</strong> " . htmlspecialchars($row['purpose']) . "</p>
                    <p><strong>Requested Document:</strong> " . htmlspecialchars($row['document_type']) . "</p>";

            if (!empty($row['guardian_name'])) {
                echo "<p><strong>Guardian Name:</strong> " . htmlspecialchars($row['guardian_name']) . "</p>";
                echo "<p><strong>Guardian Address:</strong> " . htmlspecialchars($row['guardian_address']) . "</p>";
                echo "<p><strong>Guardian Contact:</strong> " . htmlspecialchars($row['guardian_contact']) . "</p>";
            }

            echo "
                </div>
            </div>";

            echo "
            <div id='approveModal-{$row['request_id']}' class='modal'>
                <div class='modal-content' style='max-width: 400px;'>
                    <span class='close' onclick='closeApprovalModal({$row['request_id']})'>&times;</span>
                    <h2>Approve Request</h2>
                    <p>Are you sure you want to approve this request?</p>
                    <p><strong>Resident:</strong> " . htmlspecialchars($row['fullname']) . "</p>
                    <p><strong>Document:</strong> " . htmlspecialchars($row['document_type']) . "</p>
                    <div style='display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end;'>
                        <button type='button' class='btn-decline' onclick='closeApprovalModal({$row['request_id']})'>Cancel</button>
                        <button type='button' class='btn-approve' onclick='submitApprovalForm({$row['request_id']})'>Confirm Approve</button>
                    </div>
                </div>
            </div>

            <form id='approveForm-{$row['request_id']}' method='POST' style='display:none;'>
                <input type='hidden' name='request_id' value='" . (int)$row['request_id'] . "'>
                <input type='hidden' name='approve' value='1'>
            </form>";

            echo "
            <div id='declineModal-{$row['request_id']}' class='modal'>
                <div class='modal-content' style='max-width: 450px;'>
                    <span class='close' onclick='closeDeclineModal({$row['request_id']})'>&times;</span>
                    <h2>Decline Request</h2>
                    <p>Are you sure you want to decline this request?</p>
                    <p><strong>Resident:</strong> " . htmlspecialchars($row['fullname']) . "</p>
                    <p><strong>Document:</strong> " . htmlspecialchars($row['document_type']) . "</p>

                    <label style='display:block; font-weight:700; margin-top:15px; margin-bottom:8px;'>Reason for Decline:</label>
                    <textarea id='declineReason-{$row['request_id']}' style='width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; font-family:inherit; resize:vertical; min-height:80px;' placeholder='Provide a reason for declining this request...'></textarea>

                    <div style='display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end;'>
                        <button type='button' class='btn-approve' onclick='closeDeclineModal({$row['request_id']})'>Cancel</button>
                        <button type='button' class='btn-decline' onclick='submitDeclineForm({$row['request_id']})'>Confirm Decline</button>
                    </div>
                </div>
            </div>

            <form id='declineForm-{$row['request_id']}' method='POST' style='display:none;'>
                <input type='hidden' name='request_id' value='" . (int)$row['request_id'] . "'>
                <input type='hidden' name='decline' value='1'>
                <input type='hidden' id='declineReasonInput-{$row['request_id']}' name='decline_reason' value=''>
            </form>";
        }
    }

    if ($_SESSION['role'] === 'resident') {
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT request_id, document_type, status, date_requested FROM requests WHERE user_id = ? ORDER BY date_requested DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        echo "<div class='table-container'>";
        echo "<h2>My Document Requests</h2>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Request ID</th><th>Document</th><th>Status</th><th>Date Requested</th></tr>";

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>REQ-" . str_pad($row['request_id'], 3, '0', STR_PAD_LEFT) . "</td>";
                echo "<td>" . htmlspecialchars($row['document_type']) . "</td>";
                echo "<td>" . htmlspecialchars(ucfirst($row['status'])) . "</td>";
                echo "<td>" . htmlspecialchars($row['date_requested']) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No requests submitted yet</td></tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    ?>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 5000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}
.modal-content {
    background-color: #fefefe;
    margin: 6% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 92%;
    max-width: 600px;
}
.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}
.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}
.btn-view {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 5px 10px;
    cursor: pointer;
    border-radius: 4px;
    margin-right: 5px;
}
.btn-approve {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 5px 10px;
    cursor: pointer;
    border-radius: 4px;
    margin-right: 5px;
}
.btn-decline {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 5px 10px;
    cursor: pointer;
    border-radius: 4px;
}
.btn-view:hover { background-color: #0056b3; }
.btn-approve:hover { background-color: #218838; }
.btn-decline:hover { background-color: #c82333; }
</style>

<script>
document.getElementById("cardrequest").style.display = "block";

function toggleNotifications() {
  const dropdown = document.getElementById('notificationDropdown');
  dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

function getPageByType(type) {
  if (type === 'Request') return 'Request.php';
  if (type === 'Report') return 'Report.php';
  if (type === 'Donation') return 'Donate.php';
  if (type === 'Feedback') return 'Feedback.php';
  if (type === 'Account') return 'Resident_User.php';
  if (type === 'FollowUp') return 'Resident_U.php';
  return 'Admin_Dashboard.php';
}

function openNotificationModal(e, el) {
  e.preventDefault();

  const type = el.getAttribute('data-type') || '';
  const user = el.getAttribute('data-user') || '';
  const details = el.getAttribute('data-details') || '';
  const dateRaw = el.getAttribute('data-date') || '';

  document.getElementById('notifType').innerText = type;
  document.getElementById('notifFrom').innerText = user;

  try {
    const d = new Date(dateRaw.replace(' ', 'T'));
    if (!isNaN(d.getTime())) {
      const options = { year:'numeric', month:'short', day:'2-digit', hour:'2-digit', minute:'2-digit' };
      document.getElementById('notifDate').innerText = d.toLocaleString(undefined, options);
    } else {
      document.getElementById('notifDate').innerText = dateRaw;
    }
  } catch {
    document.getElementById('notifDate').innerText = dateRaw;
  }

  document.getElementById('notifDetails').innerText = details;

  const goBtn = document.getElementById('notifGoBtn');
  goBtn.onclick = function() {
    window.location.href = getPageByType(type);
  };

  document.getElementById('notificationDropdown').style.display = 'none';
  document.getElementById('notificationModal').style.display = 'block';
}

function closeNotificationModal() {
  document.getElementById('notificationModal').style.display = 'none';
}

window.addEventListener('click', function(e){
  const notifModal = document.getElementById('notificationModal');
  if (e.target === notifModal) notifModal.style.display = 'none';
});

function filterByRequestDay(day) {
  const url = new URL(window.location.href);
  const fromInput = document.getElementById('requestFrom');
  const toInput = document.getElementById('requestTo');
  const fromLabel = document.getElementById('reqfr');
  const toLabel = document.getElementById('reqtoo');

  if (day === 'custom') {
    fromLabel.style.display = 'inline-block';
    toLabel.style.display = 'inline-block';
    fromInput.style.display = 'inline-block';
    toInput.style.display = 'inline-block';

    url.searchParams.set('day', 'custom');
    if (fromInput.value) url.searchParams.set('from', fromInput.value);
    else url.searchParams.delete('from');

    if (toInput.value) url.searchParams.set('to', toInput.value);
    else url.searchParams.delete('to');

    window.history.replaceState({}, '', url.toString());
    return;
  }

  fromLabel.style.display = 'none';
  toLabel.style.display = 'none';
  fromInput.style.display = 'none';
  toInput.style.display = 'none';

  url.searchParams.delete('from');
  url.searchParams.delete('to');

  if (day === '') {
    url.searchParams.delete('day');
  } else {
    url.searchParams.set('day', day);
  }

  window.location.href = url.toString();
}

function applyRequestCustomDateFilter() {
  const from = document.getElementById('requestFrom').value;
  const to = document.getElementById('requestTo').value;

  if (from && to) {
    const url = new URL(window.location.href);
    url.searchParams.set('day', 'custom');
    url.searchParams.set('from', from);
    url.searchParams.set('to', to);
    window.location.href = url.toString();
  }
}

function viewRequest(id) {
  document.getElementById('modal-' + id).style.display = 'block';
}
function closeModal(id) {
  document.getElementById('modal-' + id).style.display = 'none';
}

function generateReport() {
  const url = new URL(window.location.href);
  url.searchParams.set('export', 'pdf');
  window.location.href = url.toString();
}

function openApprovalModal(requestId) {
  document.getElementById('approveModal-' + requestId).style.display = 'block';
}
function closeApprovalModal(requestId) {
  document.getElementById('approveModal-' + requestId).style.display = 'none';
}
function submitApprovalForm(requestId) {
  document.getElementById('approveForm-' + requestId).submit();
}

function openDeclineModal(requestId) {
  document.getElementById('declineModal-' + requestId).style.display = 'block';
}
function closeDeclineModal(requestId) {
  document.getElementById('declineModal-' + requestId).style.display = 'none';
}
function submitDeclineForm(requestId) {
  const reason = document.getElementById('declineReason-' + requestId).value;
  document.getElementById('declineReasonInput-' + requestId).value = reason;
  document.getElementById('declineForm-' + requestId).submit();
}

document.addEventListener('DOMContentLoaded', function() {
  const daySelect = document.getElementById('requestDayFilter');
  const fromInput = document.getElementById('requestFrom');
  const toInput = document.getElementById('requestTo');
  const fromLabel = document.getElementById('reqfr');
  const toLabel = document.getElementById('reqtoo');

  if (daySelect && daySelect.value === 'custom') {
    fromLabel.style.display = 'inline-block';
    toLabel.style.display = 'inline-block';
    fromInput.style.display = 'inline-block';
    toInput.style.display = 'inline-block';
  }
});

window.onclick = function(event) {
  if (event.target.className === 'modal') {
    event.target.style.display = 'none';
  }
}
</script>

</div>
</body>
</html>