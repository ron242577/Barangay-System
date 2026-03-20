<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include "db.php";

// ========================================
// FUNCTION: Send Approval Email
// ========================================
function sendApprovalEmail($email, $fullname) {
    try {
        // Gmail SMTP Configuration
        $mail_user = 'pikachu242577@gmail.com';
        $mail_pass = 'zjlxukghikzifknf';

        // Check if PHPMailer exists
        if (!file_exists('vendor/autoload.php')) {
            error_log("PHPMailer not found for approval email to: $email");
            return false;
        }

        require 'vendor/autoload.php';
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $mail_user;
        $mail->Password   = $mail_pass;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Clear previous recipients
        $mail->clearAddresses();
        $mail->clearReplyTos();

        // Sender and Recipient
        $mail->setFrom($mail_user, 'Barangay 292 E-Services');
        $mail->addAddress($email, $fullname);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Account Approved - Barangay 292 E-Services';
        $mail->Body    = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; }
                    .container { max-width: 500px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 8px; }
                    .header { background: linear-gradient(135deg, #2d7a3e 0%, #1a4d2e 100%); color: white; padding: 20px; text-align: center; border-radius: 8px; }
                    .message-box { background: white; border: 2px solid #2d7a3e; padding: 25px; margin: 20px 0; border-radius: 8px; }
                    .approval-text { font-size: 18px; font-weight: bold; color: #2d7a3e; }
                    .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Barangay 292 E-Services</h1>
                        <p>Account Approval Notification</p>
                    </div>
                    <p>Dear " . htmlspecialchars($fullname) . ",</p>
                    <div class='message-box'>
                        <div class='approval-text'>Your account is approve, You can now access the website.</div>
                    </div>
                    <p>You can now log in to your account and start using the Barangay 292 E-Services platform.</p>
                    <p><strong>Next Steps:</strong> Visit our website and log in with your email address and password.</p>
                    <div class='footer'>
                        <p>© 2026 Barangay 292 Management System. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        $mail->AltBody = "Your account is approve, You can now access the website.";

        // Send email
        return $mail->send();

    } catch (Exception $e) {
        error_log("Error sending approval email: " . $e->getMessage());
        return false;
    }
}

// Handle status updates (Approve/Decline)
if (isset($_POST['action']) && isset($_POST['account_id'])) {
    $account_id = (int)$_POST['account_id'];
    $action = $_POST['action'];

    // ✅ get the email and fullname first (so we can send approval email)
    $email = '';
    $fullname = '';
    $getEmail = $conn->prepare("SELECT email, fullname FROM accounts WHERE id = ?");
    $getEmail->bind_param("i", $account_id);
    $getEmail->execute();
    $resEmail = $getEmail->get_result();
    if ($resEmail && ($rowEmail = $resEmail->fetch_assoc())) {
        $email = $rowEmail['email'];
        $fullname = $rowEmail['fullname'];
    }
    $getEmail->close();

    $status = ($action === 'approve') ? 'active' : 'declined';

    // ✅ Get the role from the form if approving
    $role = 'resident'; // default role
    if ($action === 'approve' && isset($_POST['selected_role'])) {
        $role = $_POST['selected_role'];
    }

    $sql = "UPDATE accounts SET status = ?, role = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $status, $role, $account_id);
    $stmt->execute();
    $stmt->close();

    // ✅ If approved, send approval email and remove FollowUp notifications
    if ($action === 'approve' && $email !== '') {
        // Send approval email
        sendApprovalEmail($email, $fullname);

        // Remove their FollowUp notifications by marking them Read
        $upd = $conn->prepare("UPDATE admin_followups SET status = 'Read' WHERE sender_email = ? AND status = 'New'");
        $upd->bind_param("s", $email);
        $upd->execute();
        $upd->close();
    }

    header("Location: Resident_User.php");
    exit;
}

// Fetch accounts with optional filters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

/* ==========================
   GENERATE REPORT (PRINT TO PDF)
   ========================== */
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {

    // Build SAME query logic (no LIMIT) for report
    $query = "SELECT 
                id,
                fullname,
                address,
                phone,
                email,
                pwd,
                isf,
                solo_parent,
                status,
                created_at
              FROM accounts
              WHERE role = 'resident'";

    $params = [];
    $types  = '';

    if ($status_filter && $status_filter !== 'all') {
        $query .= " AND status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }

    if ($category_filter && $category_filter !== 'All') {
        if ($category_filter === 'PWD') {
            $query .= " AND pwd = 1";
        } elseif ($category_filter === 'ISF') {
            $query .= " AND isf = 1";
        } elseif ($category_filter === 'Solo Parent') {
            $query .= " AND solo_parent = 1";
        } elseif ($category_filter === 'Regular') {
            $query .= " AND pwd = 0 AND isf = 0 AND solo_parent = 0";
        }
    }

    if ($search) {
        $query .= " AND (fullname LIKE ? OR address LIKE ? OR email LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'sss';
    }

    $query .= " ORDER BY id DESC";

    $stmt = $conn->prepare($query);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    // Totals for header
    $totalRequested = 0;
    $totalPWD = 0;
    $totalISF = 0;
    $totalSolo = 0;
    $totalRegular = 0;

    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
        $totalRequested++;

        if ((int)$r['pwd'] === 1) $totalPWD++;
        else if ((int)$r['isf'] === 1) $totalISF++;
        else if ((int)$r['solo_parent'] === 1) $totalSolo++;
        else $totalRegular++;
    }
    $stmt->close();

    // return to same page with same filters (remove export)
    $qs = $_GET;
    unset($qs['export']);
    $returnUrl = 'Resident_User.php' . (!empty($qs) ? ('?' . http_build_query($qs)) : '');

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printable Report</title>
    <style>
        body {  font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; padding: 20px; }
        .report-wrap { max-width: 900px; margin: 0 auto; border: 2px solid #6a5acd; padding: 20px; }
        .report-header { text-align: center; margin-bottom: 20px; }
        .report-header img { width: 80px; height: auto; display: block; margin: 0 auto 10px auto; }
        .report-header h2 { margin: 0; font-size: 20px; }
        .report-sub { margin-top: 15px; font-size: 14px; }
        .report-sub div { margin: 6px 0; }

        .report-table-title { text-align: center; font-weight: bold; margin: 18px 0 10px 0; }

        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
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

            <div class="report-sub">
                <div><strong>Report of Registered Users</strong> (<?= date('M d, Y') ?>)</div>
                <div><strong>Filters:</strong>
                    Status: <?= htmlspecialchars($status_filter) ?> |
                    Category: <?= htmlspecialchars($category_filter ? $category_filter : 'All') ?> |
                    Search: <?= htmlspecialchars($search ? $search : 'None') ?>
                </div>

                <div><strong>Total of Regular:</strong> <?= $totalRegular ?></div>
                <div><strong>Total of PWD:</strong> <?= $totalPWD ?></div>
                <div><strong>Total of ISF:</strong> <?= $totalISF ?></div>
                <div><strong>Total of Solo Parent:</strong> <?= $totalSolo ?></div>
                <div><strong>Total Requested:</strong> <?= $totalRequested ?></div>
            </div>

            <div class="report-table-title">Table</div>

            <table>
                <tr>
                    <th>Resident ID</th>
                    <th>Full Name</th>
                    <th>Category</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Date Created</th>
                </tr>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8">No data found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                            if ((int)$row['pwd'] === 1) $cat = "PWD";
                            else if ((int)$row['isf'] === 1) $cat = "ISF";
                            else if ((int)$row['solo_parent'] === 1) $cat = "Solo Parent";
                            else $cat = "Regular";
                        ?>
                        <tr>
                            <td>RES-<?= str_pad($row['id'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($row['fullname']) ?></td>
                            <td><?= htmlspecialchars($cat) ?></td>
                            <td><?= htmlspecialchars($row['address']) ?></td>
                            <td><?= htmlspecialchars($row['phone']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
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

/* ==========================
   NOTIFICATIONS (COMBINED) + FOLLOWUP
   ========================== */

// TOTAL REGISTERED ACCOUNTS
$totalAccountsQuery = "SELECT COUNT(*) AS total FROM accounts";
$totalAccountsResult = $conn->query($totalAccountsQuery);
$totalAccounts = $totalAccountsResult ? $totalAccountsResult->fetch_assoc()['total'] : 0;

// PENDING ACCOUNTS (resident only)
$pendingAccountsQuery = "SELECT COUNT(*) AS total FROM accounts WHERE status = 'pending' AND role = 'resident'";
$pendingAccountsResult = $conn->query($pendingAccountsQuery);
$pendingAccounts = $pendingAccountsResult ? $pendingAccountsResult->fetch_assoc()['total'] : 0;

// PENDING REQUESTS
$pendingRequestsQuery = "SELECT COUNT(*) AS total FROM requests WHERE status = 'pending'";
$pendingRequestsResult = $conn->query($pendingRequestsQuery);
$pendingRequests = $pendingRequestsResult ? $pendingRequestsResult->fetch_assoc()['total'] : 0;

// PENDING REPORTS
$pendingReportsQuery = "SELECT COUNT(*) AS total FROM reports WHERE status = 'pending'";
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

// ✅ PENDING FOLLOW-UPS (same as Admin Dashboard)
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

// RECENT NOTIFICATIONS (last 10 items across all types)
$recentNotifications = [];

// Recent Requests
$recentRequestsQuery = "SELECT 'Request' AS type, r.request_id AS id, r.document_type AS details, r.created_at AS date, a.fullname AS user
                        FROM requests r
                        JOIN accounts a ON r.user_id = a.id
                        WHERE r.status = 'pending'
                        ORDER BY r.created_at DESC LIMIT 5";
$recentRequestsResult = $conn->query($recentRequestsQuery);
while ($recentRequestsResult && ($row = $recentRequestsResult->fetch_assoc())) {
    $recentNotifications[] = $row;
}

// Recent Reports
$recentReportsQuery = "SELECT 'Report' AS type, r.id AS id, r.reason AS details, r.created_at AS date, a.fullname AS user
                       FROM reports r
                       JOIN accounts a ON r.user_id = a.id
                       WHERE r.status = 'pending'
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

// Recent Accounts (pending resident)
$recentAccountsQuery = "SELECT 'Account' AS type, a.id AS id, CONCAT('New account: ', a.fullname) AS details, a.created_at AS date, a.fullname AS user
                        FROM accounts a
                        WHERE a.status = 'pending' AND a.role = 'resident'
                        ORDER BY a.created_at DESC LIMIT 5";
$recentAccountsResult = $conn->query($recentAccountsQuery);
while ($recentAccountsResult && ($row = $recentAccountsResult->fetch_assoc())) {
    $recentNotifications[] = $row;
}

// ✅ Recent Follow-ups (same as Admin Dashboard)
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

// Sort all notifications by date descending
usort($recentNotifications, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
$recentNotifications = array_slice($recentNotifications, 0, 10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="secretary.css">
<title>Resident Accounts</title>

<style>
/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 5000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}
.modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 20px;
    border-radius: 10px;
    width: 80%;
    max-width: 600px;
    max-height: 80%;
    overflow-y: auto;
    overflow-x: hidden;
}
.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}
.close:hover { color: black; }

.modal-info { margin-top: 20px; }
.modal-info p { margin: 10px 0; line-height: 1.5; }
.modal-info strong { display: inline-block; width: 150px; }

/* Inline styles for notification badge and dropdown */
.notification-container { position: relative; display: inline-block; cursor: pointer; }
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

/* Notification details UI */
.notif-info { margin-top: 10px; font-size: 14px; line-height: 1.5; }
.notif-info .row { margin: 8px 0; }
.notif-info .label { font-weight: 800; color: #333; }
.notif-info .value { color: #444; white-space: pre-wrap; word-break: break-word; }

/* Buttons */
.btn-row { display:flex; gap:10px; justify-content:flex-end; margin-top: 14px; flex-wrap:wrap; }
.btn-basic { padding:10px 14px; border:none; border-radius:8px; cursor:pointer; font-weight:800; }
.btn-gray { background:#eee; color:#333; }
.btn-blue { background:#1a73e8; color:#fff; }

@media print { .no-print { display:none; } }
</style>
</head>

<body>

<div class="sidebar">
     <div class="sideflex">
   <div class="usercontainer">
        <img class="user" src="images/usericon.png" alt="User" /> 
   </div>
       <div class="info">
   <h4 class="username"><?= htmlspecialchars($_SESSION["fullname"]) ?></h4> 

        <h4 class="usertype"><?= htmlspecialchars($_SESSION["role"]) ?></h4>
   </div>
</div>

   <hr class="hrside">

   <div class="btncontainer" onclick="window.location.href='Admin_Dashboard.php'">
     <img class="icon" src="images/dashboard.png" alt="home" /> 
     <h4 class="text">Dashboard</h4>
   </div>

   <div class="btncontainer" onclick="window.location.href='Resident_User.php'">
     <img class="icon" src="images/add-user.png" alt="home" /> 
     <h4 class="text">Accounts</h4>
   </div>

   <div class="btncontainer" onclick="window.location.href='Request.php'">
     <img class="icon" src="images/reqicon.png" alt="request" /> 
     <h4 class="text">Request</h4>
   </div>

   <div class="btncontainer" onclick="window.location.href='Report.php'">
     <img class="icon" src="images/repicon.png" alt="report" /> 
     <h4 class="text">Report</h4>
   </div>

   <div class="btncontainer" onclick="window.location.href='Donate.php'">
     <img class="icon" src="images/dicon.png" alt="donate" /> 
     <h4 class="text">Donate</h4>
   </div>

   <div class="btncontainer" onclick="window.location.href='Feedback.php'">
     <img class="icon" src="images/fbicon.png" alt="feedback" /> 
     <h4 class="text">Feedback</h4>
   </div>
     <hr style="width: 100%; border: 0.5px solid rgba(255, 255, 255, 0.4); margin-top: 0px;">
     
<div class="address1" >
             <img class="logoadd" src="images/pin.png" alt="Feedback">
             <div class="addtext">
              <p class=> Zone 28 Disctrict 3 <br>808 Reigna Regente St. <br>Binondo, Manila</p>
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

<!-- Notification Dropdown -->
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

<hr class="green-line">

<!-- ACCOUNTS -->
<div id="cardaccounts" class="cardaccounts">
  <div class="flex1" style="align-items:center; gap: 10px; margin-bottom:10px; flex-wrap:wrap;">
    <div style="display:flex; align-items:flex-start; margin-top: 8px;">
       <div class="logo-containeradmin">
    <img class="logoadmin" src="images/pendingaccs.png" alt="Logo">
</div>
  <h3 style="margin-top: 10px; margin-left:5px;  color: #443d3d; margin-bottom:0px;">Pending Accounts</h3>
</div>

    <div class="flex3">
      <form class="resform"method="GET" action="Resident_User.php" id="filterForm" >
        
     

       
      </form>
    </div>
  </div>

  <!-- ACCOUNTS TABLE -->
  <div class="table-container">
    <table>
      <tr>
        <th>Resident ID</th>
        <th>Full Name</th>
        <th>Category</th>
        <th>Address</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Action</th>
      </tr>

      <?php
      $query = "SELECT 
                  id,
                  fullname,
                  address,
                  phone,
                  email,
                  pwd,
                  isf,
                  solo_parent,
                  status,
                  birthdate,
                  gender,
                  civil_status,
                  household_head,
                  pwd_proof,
                  solo_parent_proof,
                  proof_of_residency
                FROM accounts
                WHERE role = 'resident' AND status != 'active'";

      $params = [];
      $types = '';

      if ($status_filter && $status_filter !== 'all') {
          $query .= " AND status = ?";
          $params[] = $status_filter;
          $types .= 's';
      }

      if ($category_filter && $category_filter !== 'All') {
          if ($category_filter === 'PWD') $query .= " AND pwd = 1";
          elseif ($category_filter === 'ISF') $query .= " AND isf = 1";
          elseif ($category_filter === 'Solo Parent') $query .= " AND solo_parent = 1";
          elseif ($category_filter === 'Regular') $query .= " AND pwd = 0 AND isf = 0 AND solo_parent = 0";
      }

      if ($search) {
          $query .= " AND (fullname LIKE ? OR address LIKE ? OR email LIKE ?)";
          $search_term = "%$search%";
          $params[] = $search_term;
          $params[] = $search_term;
          $params[] = $search_term;
          $types .= 'sss';
      }

      $query .= " ORDER BY id DESC";

      $stmt = $conn->prepare($query);
      if ($params) $stmt->bind_param($types, ...$params);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result && $result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {

              if ($row['pwd'] == 1) $category = "PWD";
              elseif ($row['isf'] == 1) $category = "ISF";
              elseif ($row['solo_parent'] == 1) $category = "Solo Parent";
              else $category = "Regular";

              echo "<tr>";
              echo "<td>RES-" . str_pad($row['id'], 3, '0', STR_PAD_LEFT) . "</td>";
              echo "<td>{$row['fullname']}</td>";
              echo "<td>{$category}</td>";
              echo "<td>{$row['address']}</td>";
              echo "<td>{$row['phone']}</td>";
              echo "<td>{$row['email']}</td>";
              echo "<td>
                  <button class='btn-view' onclick='viewAccount(" . htmlspecialchars(json_encode($row)) . ")'>View</button>
                  <button type='button' class='btn-approve' onclick='openApproveModal(" . $row['id'] . ", " . htmlspecialchars(json_encode($row['fullname'])) . ")'>Approve</button>
                  <button type='button' class='btn-decline' onclick='openDeclineModal(" . $row['id'] . ", " . htmlspecialchars(json_encode($row['fullname'])) . ")'>Decline</button>
                </td>";
              echo "</tr>";
          }
      } else {
          echo "<tr><td colspan='7'>No registered users found</td></tr>";
      }
      ?>
    </table>
  </div>

  <!-- Modal for viewing resident details -->
  <div id="viewModal" class="modal">
      <div class="modal-content">
          <span class="close" onclick="closeModal()">&times;</span>
             <div style="display:flex; align-items:flex-start;">
     

  <h2 style="margin-top: 3px; margin-left:0px; margin-bottom:10px; color: gree;">User Details</h2>
</div>
<hr style="width:100%;">
          <div class="modal-info" id="modalInfo"></div>
      </div>
  </div>

  <!-- ✅ Notification Details Modal (same behavior as Admin Dashboard) -->
  <div id="notificationModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeNotificationModal()">&times;</span>
       <div style="display: flex; align-items:flex-start; margin-left:0px;">
         <div class="logo-containerinfo">
    <img class="logoa" src="images/info1.png" alt="Logo">
</div>
          <h2 class="notiftitle" style=" color: #443d3d;" id="notifTitle">Notification Details</h2>
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

  <!-- ✅ Approve Modal with Role Selection -->
  <div id="approveModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeApproveModal()">&times;</span>
      <h2 style="margin-top:0px; margin-bottom:10px;">Approve Account</h2>
      <hr style="width:100%;">
      <div class="modal-info1">
        <p ><strong style="margin-right:10px;">Account Name:</strong><span id="approveAccountName"></span></p>
  
        <div style="margin: 15px 0; display: flex; gap: 15px; flex-wrap: wrap;">
                <p style="margin-top: 5px;"><strong>Select Role:</strong></p>
          <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
            <input type="radio" name="approveRole" value="resident" checked>
            <span>Resident</span>
          </label>
          <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
            <input type="radio" name="approveRole" value="admin">
            <span>Admin</span>
          </label>
        </div>
      </div>

      <div class="btn-row">
        <button type="button" class="btn-basic btn-gray" onclick="closeApproveModal()">Cancel</button>
        <button type="button" class="btn-basic btn-blue" onclick="submitApprove()">Approve</button>
      </div>
    </div>
  </div>

  <!-- ✅ Decline Modal with Confirmation -->
  <div id="declineModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeDeclineModal()">&times;</span>
      <h2 style="margin-top:0px; margin-bottom:10px;">Decline Account</h2>
      <hr style="width:100%;">
      <div class="modal-info1">
        <p><strong style="margin-right:10px;">Account Name:</strong> <span id="declineAccountName"></span></p>
        <p style="margin-top: 20px; font-size: 16px; font-weight: bold;">Are you sure you want to decline this account?</p>
      </div>

      <div class="btn-row">
        <button type="button" class="btn-basic btn-gray" onclick="closeDeclineModal()">No</button>
        <button type="button" class="btn-basic btn-blue" onclick="submitDecline()">Yes</button>
      </div>
    </div>
  </div>

  <div style="display: flex;">
    <div class="botbtn">
    
    </div>
  </div>

</div>

<script>
document.getElementById("cardaccounts").style.display = "block";

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
  if (type === 'FollowUp') return 'Resident_User.php'; 
  return 'Resident_User.php';
}

function openNotificationModal(e, el) {
  e.preventDefault();

  const type = el.getAttribute('data-type') || '';
  const user = el.getAttribute('data-user') || '';
  const details = el.getAttribute('data-details') || '';
  const dateRaw = el.getAttribute('data-date') || '';

  document.getElementById('notifType').innerText = type;
  document.getElementById('notifFrom').innerText = user;
  document.getElementById('notifDate').innerText = dateRaw;
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

/* Report button -> printable report */
document.addEventListener("click", function(e){
  const btn = e.target.closest(".btn-report");
  if(!btn) return;

  const url = new URL(window.location.href);
  url.searchParams.set("export", "pdf");
  window.location.href = url.toString();
});

function viewAccount(data) {
    const modal = document.getElementById('viewModal');
    const modalInfo = document.getElementById('modalInfo');

    let category = "Regular";
    if (data.pwd == 1) category = "PWD";
    else if (data.isf == 1) category = "ISF";
    else if (data.solo_parent == 1) category = "Solo Parent";

    const info = `
        <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;" ><strong>Resident ID:</strong> RES-${String(data.id).padStart(3, '0')}</p>
        <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Full Name:</strong> ${data.fullname}</p>
        <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Birthdate:</strong> ${data.birthdate || 'N/A'}</p>
        <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Gender:</strong> ${data.gender || 'N/A'}</p>
        <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Civil Status:</strong> ${data.civil_status || 'N/A'}</p>
        <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Address:</strong> ${data.address}</p>
        <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Phone:</strong> ${data.phone}</p>
        <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Email:</strong> ${data.email}</p>
        <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;" ><strong>Category:</strong> ${category}</p>
        <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Household Head:</strong> ${data.household_head || 'N/A'}</p>
        <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>PWD:</strong> ${data.pwd == 1 ? 'Yes' : 'No'}</p>
        <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>ISF:</strong> ${data.isf == 1 ? 'Yes' : 'No'}</p>
        <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Solo Parent:</strong> ${data.solo_parent == 1 ? 'Yes' : 'No'}</p>
        <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Status:</strong> ${data.status}</p>
        ${data.pwd_proof ? `<p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;" ><strong>PWD Proof:</strong> <a href="${data.pwd_proof}" target="_blank">View Proof</a></p>` : ''}
        ${data.solo_parent_proof ? `<p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Solo Parent Proof:</strong> <a href="${data.solo_parent_proof}" target="_blank">View Proof</a></p>` : ''}
        ${data.proof_of_residency ? `<p><strong>Proof of Residency:</strong> <a href="${data.proof_of_residency}" target="_blank">View Proof</a></p>` : ''}
    `;

    modalInfo.innerHTML = info;
    modal.style.display = 'block';
}

function closeModal() {
    document.getElementById('viewModal').style.display = 'none';
}

// ✅ Approve Modal Functions
let currentApproveAccountId = null;

function openApproveModal(accountId, accountName) {
    currentApproveAccountId = accountId;
    document.getElementById('approveAccountName').innerText = accountName;
    document.querySelector('input[name="approveRole"][value="resident"]').checked = true;
    document.getElementById('approveModal').style.display = 'block';
}

function closeApproveModal() {
    document.getElementById('approveModal').style.display = 'none';
    currentApproveAccountId = null;
}

function submitApprove() {
    const selectedRole = document.querySelector('input[name="approveRole"]:checked').value;
    
    // Create a form and submit it
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'Resident_User.php';
    
    const accountIdInput = document.createElement('input');
    accountIdInput.type = 'hidden';
    accountIdInput.name = 'account_id';
    accountIdInput.value = currentApproveAccountId;
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'approve';
    
    const roleInput = document.createElement('input');
    roleInput.type = 'hidden';
    roleInput.name = 'selected_role';
    roleInput.value = selectedRole;
    
    form.appendChild(accountIdInput);
    form.appendChild(actionInput);
    form.appendChild(roleInput);
    
    document.body.appendChild(form);
    form.submit();
}

// ✅ Decline Modal Functions
let currentDeclineAccountId = null;

function openDeclineModal(accountId, accountName) {
    currentDeclineAccountId = accountId;
    document.getElementById('declineAccountName').innerText = accountName;
    document.getElementById('declineModal').style.display = 'block';
}

function closeDeclineModal() {
    document.getElementById('declineModal').style.display = 'none';
    currentDeclineAccountId = null;
}

function submitDecline() {
    // Create a form and submit it
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'Resident_User.php';
    
    const accountIdInput = document.createElement('input');
    accountIdInput.type = 'hidden';
    accountIdInput.name = 'account_id';
    accountIdInput.value = currentDeclineAccountId;
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'decline';
    
    form.appendChild(accountIdInput);
    form.appendChild(actionInput);
    
    document.body.appendChild(form);
    form.submit();
}

// Close modals when clicking outside
window.onclick = function(event) {
    const viewModal = document.getElementById('viewModal');
    const notifModal = document.getElementById('notificationModal');
    const approveModal = document.getElementById('approveModal');
    const declineModal = document.getElementById('declineModal');
    if (event.target == viewModal) viewModal.style.display = 'none';
    if (event.target == notifModal) notifModal.style.display = 'none';
    if (event.target == approveModal) approveModal.style.display = 'none';
    if (event.target == declineModal) declineModal.style.display = 'none';
};
</script>

</div>
</body>
</html>