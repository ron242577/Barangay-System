<?php
session_start();
if (!isset($_SESSION["role"]) || !in_array($_SESSION["role"], ["Admin", "SuperAdmin"])) {
    header("Location: login.php");
    exit;
}

include "db.php";

// TOTAL REGISTERED ACCOUNTS
$totalAccountsQuery = "SELECT COUNT(*) AS total FROM accounts";
$totalAccountsResult = $conn->query($totalAccountsQuery);
$totalAccounts = $totalAccountsResult ? $totalAccountsResult->fetch_assoc()['total'] : 0;

// PENDING ACCOUNTS (like Admin Dashboard: residents only)
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

// ✅ PENDING FOLLOW-UP MESSAGES (hide if sender email already active)
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

// Recent Accounts (resident pending only)
$recentAccountsQuery = "SELECT 'Account' AS type, a.id AS id, CONCAT('New account: ', a.fullname) AS details, a.created_at AS date, a.fullname AS user
                        FROM accounts a
                        WHERE a.status = 'pending' AND a.role = 'resident'
                        ORDER BY a.created_at DESC LIMIT 5";
$recentAccountsResult = $conn->query($recentAccountsQuery);
while ($recentAccountsResult && ($row = $recentAccountsResult->fetch_assoc())) {
    $recentNotifications[] = $row;
}

// ✅ Recent Follow-ups (hide if sender email already active)
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

/* =========================
   ADMIN RESOLVE REPORT
========================= */
if (isset($_POST['resolve']) && in_array($_SESSION['role'], ['Admin', 'SuperAdmin'])) {
    $id = (int)$_POST['report_id'];
    $conn->query("UPDATE reports SET status='Resolved' WHERE id=$id");
    header("Location: Report.php");
    exit;
}

/* =========================
   ADMIN DECLINE REPORT
========================= */
if (isset($_POST['decline']) && in_array($_SESSION['role'], ['Admin', 'SuperAdmin'])) {
    $id = (int)$_POST['report_id'];
    $decline_reason = isset($_POST['decline_reason']) ? trim($_POST['decline_reason']) : '';
    
    // Add decline_reason column if it doesn't exist
    $colCheck = $conn->query("SHOW COLUMNS FROM reports LIKE 'decline_reason'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE reports ADD COLUMN decline_reason TEXT NULL");
    }
    
    // Update report with status and reason
    $conn->query("UPDATE reports SET status='Declined', decline_reason='" . $conn->real_escape_string($decline_reason) . "' WHERE id=$id");
    header("Location: Report.php");
    exit;
}

// Fetch reports with optional filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$day_filter = isset($_GET['day']) ? $_GET['day'] : '';
$date_from = isset($_GET['from']) ? $_GET['from'] : '';
$date_to = isset($_GET['to']) ? $_GET['to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

/* ==========================
   GENERATE REPORT (PRINTABLE PDF VIA BROWSER PRINT)
   ========================== */
if (isset($_GET['export']) && $_GET['export'] === 'pdf' && in_array($_SESSION['role'], ['Admin', 'SuperAdmin'])) {

    $sql = "SELECT r.id, r.reason, r.person_reported, r.address, r.proof, r.specify, r.status, r.created_at, a.fullname AS reporter_name 
            FROM reports r 
            JOIN accounts a ON r.user_id = a.id 
            WHERE 1=1";

    $params = [];
    $types = '';

    if ($status_filter) {
        $sql .= " AND r.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }

    if ($type_filter && $type_filter !== 'All') {
        $sql .= " AND r.reason = ?";
        $params[] = $type_filter;
        $types .= 's';
    }

    if ($day_filter === '7') {
        $sql .= " AND DATE(r.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($day_filter === '30') {
        $sql .= " AND DATE(r.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    } elseif ($day_filter === '365') {
        $sql .= " AND DATE(r.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
    } elseif ($day_filter === 'custom' && $date_from && $date_to) {
        $sql .= " AND DATE(r.created_at) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
        $types .= 'ss';
    } elseif ($date_from && $date_to) {
        $sql .= " AND DATE(r.created_at) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
        $types .= 'ss';
    }

    if ($search) {
        $sql .= " AND (a.fullname LIKE ? OR r.reason LIKE ? OR r.person_reported LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'sss';
    }

    $sql .= " ORDER BY r.created_at DESC";

    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    $totalReports = 0;
    $totalsByReason = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        $totalReports++;

        $reasonKey = $row['reason'] ?: 'N/A';
        if (!isset($totalsByReason[$reasonKey])) $totalsByReason[$reasonKey] = 0;
        $totalsByReason[$reasonKey]++;
    }

    $stmt->close();

    $qs = $_GET;
    unset($qs['export']);
    $returnUrl = 'Report.php' . (!empty($qs) ? ('?' . http_build_query($qs)) : '');

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Report</title>
    <style>
        body {   font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; padding: 20px; }
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
                <div><strong>Report of Reports Submitted</strong> (<?= date('M d, Y') ?>)</div>
                <div><strong>Type of report:</strong> <?= htmlspecialchars($type_filter ? $type_filter : 'All') ?></div>
                <div><strong>Total Reports:</strong> <?= (int)$totalReports ?></div>

                <?php if (!empty($totalsByReason)): ?>
                    <div style="margin-top:8px;"><strong>Totals by Reason:</strong></div>
                    <?php foreach ($totalsByReason as $k => $v): ?>
                        <div><?= htmlspecialchars($k) ?>: <?= (int)$v ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="table-title">Table</div>

            <table>
                <tr>
                    <th>Report ID</th>
                    <th>Reporter Name</th>
                    <th>Reason</th>
                    <th>Person Reported</th>
                    <th>Address</th>
                    <th>Date</th>
                </tr>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7">No reports found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td>REP-<?= str_pad($r['id'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($r['reporter_name']) ?></td>
                            <td><?= htmlspecialchars($r['reason']) ?></td>
                            <td><?= htmlspecialchars($r['person_reported'] ?: 'N/A') ?></td>
                            <td><?= htmlspecialchars($r['address'] ?: 'N/A') ?></td>
                            <td><?= htmlspecialchars($r['created_at']) ?></td>
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
   NORMAL PAGE QUERY
   ========================== */

$sql = "SELECT r.id, r.reason, r.person_reported, r.address, r.proof, r.specify, r.status, r.created_at, a.fullname AS reporter_name 
        FROM reports r 
        JOIN accounts a ON r.user_id = a.id 
        WHERE 1=1";

$params = [];
$types = '';

if ($status_filter) {
    $sql .= " AND r.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
if ($type_filter && $type_filter !== 'All') {
    $sql .= " AND r.reason = ?";
    $params[] = $type_filter;
    $types .= 's';
}
if ($day_filter === '7') {
    $sql .= " AND DATE(r.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($day_filter === '30') {
    $sql .= " AND DATE(r.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
} elseif ($day_filter === '365') {
    $sql .= " AND DATE(r.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
} elseif ($day_filter === 'custom' && $date_from && $date_to) {
    $sql .= " AND DATE(r.created_at) BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
    $types .= 'ss';
} elseif ($date_from && $date_to) {
    $sql .= " AND DATE(r.created_at) BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
    $types .= 'ss';
}
if ($search) {
    $sql .= " AND (a.fullname LIKE ? OR r.reason LIKE ? OR r.person_reported LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$reports = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="secretary.css">
<title>Admin Dashboard - Reports</title>

<style>
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

/* ✅ Notification details modal */
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
.close { float:right; font-size:28px; cursor:pointer; line-height:1; }
.btn-row { display:flex; gap:10px; justify-content:flex-end; margin-top: 14px; flex-wrap:wrap; }
.btn-basic { padding:10px 14px; border:none; border-radius:8px; cursor:pointer; font-weight:800; }
.btn-gray { background:#eee; color:#333; }
.btn-blue { background:#1a73e8; color:#fff; }
.notif-info { margin-top: 10px; font-size: 14px; line-height: 1.5; }
.notif-info .row { margin: 8px 0; }
.notif-info .label { font-weight: 800; color: #333; }
.notif-info .value { color: #444; white-space: pre-wrap; word-break: break-word; }

/* Textarea styling for report details */
.report-textarea {
  width: 100%;
  height: 120px;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-family: Arial, sans-serif;
  font-size: 14px;
  resize: vertical;
  background-color: #f9f9f9;
  color: #333;
}
.report-textarea:disabled {
  background-color: #f5f5f5;
  cursor: not-allowed;
}
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

   <div class="btncontainer" onclick="window.location.href='Resident_User.php'">
     <img class="icon" src="images/add-user.png" alt="home" /> 
     <h4 class="text">Accounts</h4>
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
   <?php if ($_SESSION["role"] === "SuperAdmin"): ?>
<div class="btncontainer" onclick="window.location.href='Manage_Accounts.php'">
    <img class="icon" src="images/add-user.png" alt="manage accounts" />
    <h4 class="text">Manage Accounts</h4>
</div>
<?php endif; ?>
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

<!-- ✅ Notification Details Modal -->
<div id="notificationModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeNotificationModal()">&times;</span>
     <div style="display: flex; align-items:flex-start; margin-left:0px;">
         <div class="logo-containerinfo">
    <img class="logoa" src="images/info1.png" alt="Logo">
</div>
          <h2 class="notiftitle" style=" color: #443d3d;" id="notifTitle">Notification</h2>
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

<!--REPORT-->
<div id="cardreport" class="cardreport">
  <div class="flex1" style="align-items:center; gap: 10px; margin-bottom:10px; flex-wrap:wrap;">
     <div style="display:flex; align-items:flex-start; margin-top: 8px;">
       <div class="logo-containeradmin">
    <img class="logoadmin" src="images/application.png" alt="Logo">
</div>
  <h3 style="margin-top: 8px; margin-left:5px;  color: #443d3d;">Report Table</h3>
</div>

    <div class="flex3">
    <form class="resform" method="GET" action="Report.php" id="filterForm">

        <div>
      <label>Status:</label>
      <select class="custom3" name="status" onchange="document.getElementById('filterForm').submit()">
        <option value="">All</option>
        <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
        <option value="Resolved" <?= $status_filter === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
        <option value="Ongoing" <?= $status_filter === 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
        <option value="Declined" <?= $status_filter === 'Declined' ? 'selected' : '' ?>>Declined</option>
      </select>
  </div>

       <div>
        <label>Day:</label>
        <select class="custom4" name="day" id="reportDayFilter" onchange="filterByReportDay(this.value)">
          <option value="" <?= $day_filter === '' ? 'selected' : '' ?>>Select</option>
          <option value="7" <?= $day_filter === '7' ? 'selected' : '' ?>>Last 7 Days</option>
          <option value="30" <?= $day_filter === '30' ? 'selected' : '' ?>>Last 30 Days</option>
          <option value="365" <?= $day_filter === '365' ? 'selected' : '' ?>>Last Year</option>
          <option value="custom" <?= $day_filter === 'custom' ? 'selected' : '' ?>>Custom</option>
        </select>
    </div>

        <label id="repfr" style="display:none;">From:</label>
        <input type="date" id="reportFrom" name="from" value="<?= htmlspecialchars($date_from) ?>" style="display:none;" onchange="applyReportCustomDateFilter()">

        <label id="reptoo" style="display:none;">To:</label>
        <input class="input1" type="date" id="reportTo" name="to" value="<?= htmlspecialchars($date_to) ?>" style="display:none;" onchange="applyReportCustomDateFilter()">
   
     <div>
      <label>Type:</label>
      <select class="custom5" name="type" onchange="document.getElementById('filterForm').submit()">
        <option value="All" <?= $type_filter === 'All' || !$type_filter ? 'selected' : '' ?>>All</option>
        <option value="Noise Disturbance" <?= $type_filter === 'Noise Disturbance' ? 'selected' : '' ?>>Noise Disturbance</option>
        <option value="Illegal Parking" <?= $type_filter === 'Illegal Parking' ? 'selected' : '' ?>>Illegal Parking</option>
        <option value="Loitering" <?= $type_filter === 'Loitering' ? 'selected' : '' ?>>Loitering</option>
        <option value="Vandalism" <?= $type_filter === 'Vandalism' ? 'selected' : '' ?>>Vandalism</option>
        <option value="Domestic Dispute" <?= $type_filter === 'Domestic Dispute' ? 'selected' : '' ?>>Domestic Dispute</option>
        <option value="Suspicious Activity" <?= $type_filter === 'Suspicious Activity' ? 'selected' : '' ?>>Suspicious Activity</option>
        <option value="Others" <?= $type_filter === 'Others' ? 'selected' : '' ?>>Others</option>
      </select>
  </div>
</form>
    <form method="GET" action="Report.php" id="filterForm">
      <input
        placeholder="Search"
        type="search"
        name="search"
        value="<?= htmlspecialchars($search) ?>"
        onchange="document.getElementById('filterForm').submit()">
    </form>
   
  </div>
</div>

  <div class="table-container">
    <table>
      <tr>
        <th>Report ID</th>
        <th>Reporter Name</th>
        <th>Status</th>
        <th>Reason</th>
        <th>Action</th>
      </tr>

      <?php if (empty($reports)): ?>
        <tr><td colspan="5">No reports found.</td></tr>
      <?php else: ?>
        <?php foreach ($reports as $report): ?>
          <tr>
            <td>REP-<?= str_pad($report['id'], 3, '0', STR_PAD_LEFT) ?></td>
            <td><?= htmlspecialchars($report['reporter_name']) ?></td>
            <td><?= htmlspecialchars($report['status']) ?></td>
            <td><?= htmlspecialchars($report['reason']) ?></td>
            <td style="text-align:center;">
              <button class="btn-view" onclick="viewReport(<?= htmlspecialchars(json_encode($report)) ?>)">View</button>
              <button class="btn-approve" onclick="openResolveModal(<?= (int)$report['id'] ?>)">Resolve</button>
              <button class="btn-decline" onclick="openDeclineModal(<?= (int)$report['id'] ?>)">Decline</button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>
  </div>

  <div style="display: flex;">
    <div class="botbtn">
      <button class="btn-report" onclick="generateReport()">Generate Report</button>
    </div>
  </div>

  <!-- View report modal (your existing) -->
  <div id="reportModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:20px; border-radius:5px; width:92%; max-width:600px;">
      <span class="close" onclick="closeModal()">&times;</span>
      <h2 style="margin-top:0px; margin-bottom:10px;">Report Details</h2>
       <hr style="width:100%;">
      <div id="reportDetails"></div>
     
    </div>
  </div>

  <?php foreach ($reports as $report): ?>
  <!-- Resolve Modal -->
  <div id="resolveModal-<?= (int)$report['id'] ?>" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 400px;">
      <span class="close" onclick="closeResolveModal(<?= (int)$report['id'] ?>)">&times;</span>
      <h2>Resolve Report</h2>
      <p>Are you sure you want to resolve this report?</p>
      <p><strong>Reporter:</strong> <?= htmlspecialchars($report['reporter_name']) ?></p>
      <p><strong>Reason:</strong> <?= htmlspecialchars($report['reason']) ?></p>
      <div style="display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end;">
        <button class="btn-decline" onclick="closeResolveModal(<?= (int)$report['id'] ?>)">Cancel</button>
        <button class="btn-approve" onclick="submitResolveForm(<?= (int)$report['id'] ?>)">Confirm Resolve</button>
      </div>
    </div>
  </div>

  <form id="resolveForm-<?= (int)$report['id'] ?>" method="POST" style="display:none;">
    <input type="hidden" name="report_id" value="<?= (int)$report['id'] ?>">
    <input type="hidden" name="resolve" value="1">
  </form>

  <!-- Decline Modal -->
  <div id="declineModal-<?= (int)$report['id'] ?>" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 450px;">
      <span class="close" onclick="closeDeclineModal(<?= (int)$report['id'] ?>)">&times;</span>
      <h2>Decline Report</h2>
      <p>Are you sure you want to decline this report?</p>
      <p><strong>Reporter:</strong> <?= htmlspecialchars($report['reporter_name']) ?></p>
      <p><strong>Reason:</strong> <?= htmlspecialchars($report['reason']) ?></p>

      <label style="display:block; font-weight:700; margin-top:15px; margin-bottom:8px;">Reason for Decline:</label>
      <textarea id="declineReason-<?= (int)$report['id'] ?>" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; font-family:inherit; resize:vertical; min-height:80px;" placeholder="Provide a reason for declining this report..."></textarea>

      <div style="display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end;">
        <button class="btn-approve" onclick="closeDeclineModal(<?= (int)$report['id'] ?>)">Cancel</button>
        <button class="btn-decline" onclick="submitDeclineForm(<?= (int)$report['id'] ?>)">Confirm Decline</button>
      </div>
    </div>
  </div>

  <form id="declineForm-<?= (int)$report['id'] ?>" method="POST" style="display:none;">
    <input type="hidden" name="report_id" value="<?= (int)$report['id'] ?>">
    <input type="hidden" name="decline" value="1">
    <input type="hidden" id="declineReasonInput-<?= (int)$report['id'] ?>" name="decline_reason" value="">
  </form>
  <?php endforeach; ?>

</div>

<script>
document.getElementById("cardreport").style.display = "block";

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

function filterByReportDay(day) {
  const url = new URL(window.location.href);
  const fromInput = document.getElementById('reportFrom');
  const toInput = document.getElementById('reportTo');
  const fromLabel = document.getElementById('repfr');
  const toLabel = document.getElementById('reptoo');

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

function applyReportCustomDateFilter() {
  const from = document.getElementById('reportFrom').value;
  const to = document.getElementById('reportTo').value;

  if (from && to) {
    const url = new URL(window.location.href);
    url.searchParams.set('day', 'custom');
    url.searchParams.set('from', from);
    url.searchParams.set('to', to);
    window.location.href = url.toString();
  }
}

function viewReport(reportData) {
  document.getElementById('reportDetails').innerHTML = `
    <p><strong>Reporter:</strong> ${reportData.reporter_name}</p>
    <p><strong>Reason:</strong> ${reportData.reason}</p>
    <p><strong>Person Reported:</strong> ${reportData.person_reported || 'N/A'}</p>
    <p><strong>Address:</strong> ${reportData.address || 'N/A'}</p>
    <p><strong>Specify:</strong></p>
    <textarea class="report-textarea" disabled>${reportData.specify || ''}</textarea>
    <p style="margin-top:12px;"><strong>Status:</strong> ${reportData.status}</p>
    <p><strong>Proof:</strong> ${reportData.proof ? '<a href="' + reportData.proof + '" target="_blank">View Proof</a>' : 'N/A'}</p>
  `;
  document.getElementById('reportModal').style.display = 'block';
}

function closeModal() {
  document.getElementById('reportModal').style.display = 'none';
}

function generateReport() {
  const url = new URL(window.location.href);
  url.searchParams.set('export', 'pdf');
  window.location.href = url.toString();
}

function openResolveModal(reportId) {
  document.getElementById('resolveModal-' + reportId).style.display = 'block';
}
function closeResolveModal(reportId) {
  document.getElementById('resolveModal-' + reportId).style.display = 'none';
}
function submitResolveForm(reportId) {
  document.getElementById('resolveForm-' + reportId).submit();
}

function openDeclineModal(reportId) {
  document.getElementById('declineModal-' + reportId).style.display = 'block';
}
function closeDeclineModal(reportId) {
  document.getElementById('declineModal-' + reportId).style.display = 'none';
}
function submitDeclineForm(reportId) {
  const reason = document.getElementById('declineReason-' + reportId).value;
  document.getElementById('declineReasonInput-' + reportId).value = reason;
  document.getElementById('declineForm-' + reportId).submit();
}

document.addEventListener('DOMContentLoaded', function() {
  const daySelect = document.getElementById('reportDayFilter');
  const fromInput = document.getElementById('reportFrom');
  const toInput = document.getElementById('reportTo');
  const fromLabel = document.getElementById('repfr');
  const toLabel = document.getElementById('reptoo');

  if (daySelect && daySelect.value === 'custom') {
    fromLabel.style.display = 'inline-block';
    toLabel.style.display = 'inline-block';
    fromInput.style.display = 'inline-block';
    toInput.style.display = 'inline-block';
  }
});
</script>

</body>
</html>