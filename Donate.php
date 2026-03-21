<?php
session_start();
include "db.php";
if (!isset($_SESSION["role"]) || !in_array($_SESSION["role"], ["Admin", "SuperAdmin"])) {
    header("Location: login.php");
    exit;
}



// TOTAL REGISTERED ACCOUNTS
$totalAccountsQuery = "SELECT COUNT(*) AS total FROM accounts";
$totalAccountsResult = $conn->query($totalAccountsQuery);
$totalAccounts = $totalAccountsResult ? $totalAccountsResult->fetch_assoc()['total'] : 0;

// PENDING ACCOUNTS (match admin dashboard rule: residents pending)
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

// Recent Accounts (only pending resident)
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

// Sort notifications by date desc
usort($recentNotifications, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
$recentNotifications = array_slice($recentNotifications, 0, 10);

// Add role-based access check
if (!isset($_SESSION["role"]) || !in_array($_SESSION["role"], ['Admin', 'SuperAdmin'])) {
    header("Location: login.php");
    exit;
}



/* ==========================
   PRINTABLE PDF EXPORT
   ========================== */
if (isset($_GET['export']) && $_GET['export'] === 'pdf' && in_array($_SESSION['role'], ['Admin', 'SuperAdmin'])) {

    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $day_filter = isset($_GET['day']) ? $_GET['day'] : '';
    $date_from = isset($_GET['from']) ? $_GET['from'] : '';
    $date_to = isset($_GET['to']) ? $_GET['to'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    $sql = "SELECT d.id, d.message, d.proof_of_donation, d.status, d.created_at,
                   a.fullname AS resident_name, a.id AS resident_id
            FROM donations d
            JOIN accounts a ON d.user_id = a.id
            WHERE d.status IN ('New', 'Claimed')";

    $params = [];
    $types = '';

    if ($status_filter) {
        $sql .= " AND d.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }

    if ($day_filter === '7') {
        $sql .= " AND DATE(d.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($day_filter === '30') {
        $sql .= " AND DATE(d.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    } elseif ($day_filter === '365') {
        $sql .= " AND DATE(d.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
    } elseif ($day_filter === 'custom' && $date_from && $date_to) {
        $sql .= " AND DATE(d.created_at) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
        $types .= 'ss';
    } elseif ($date_from && $date_to) {
        $sql .= " AND DATE(d.created_at) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
        $types .= 'ss';
    }

    if ($search) {
        $sql .= " AND (a.fullname LIKE ? OR d.message LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'ss';
    }

    $sql .= " ORDER BY d.created_at DESC";

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    $totalDonations = 0;
    $totalNew = 0;
    $totalViewed = 0;
    $totalApproved = 0;
    $totalDeclined = 0;

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        $totalDonations++;

        if ($row['status'] === 'New') $totalNew++;
        if ($row['status'] === 'Viewed') $totalViewed++;
        if ($row['status'] === 'Approved') $totalApproved++;
        if ($row['status'] === 'Declined') $totalDeclined++;
    }

    $stmt->close();

    $qs = $_GET;
    unset($qs['export']);
    $returnUrl = 'Donate.php' . (!empty($qs) ? ('?' . http_build_query($qs)) : '');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donations Report</title>
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
        @media print { .no-print { display: none; } body { padding: 0; } .report-wrap { border: 2px solid #6a5acd; } }
    </style>
    </head>
    <body>
        <div class="report-wrap">
            <div class="report-header">
                <img src="images/logo2.png" alt="Logo">
                <h2>Logo Barangay 290</h2>
            </div>

            <div class="report-meta">
                <div><strong>Report of Donations</strong> (<?= date('M d, Y') ?>)</div>
                <div><strong>Status:</strong> <?= htmlspecialchars($status_filter ? $status_filter : 'All') ?></div>
                <div><strong>Total New:</strong> <?= $totalNew ?></div>
                <div><strong>Total Viewed:</strong> <?= $totalViewed ?></div>
                <div><strong>Total Approved:</strong> <?= $totalApproved ?></div>
                <div><strong>Total Declined:</strong> <?= $totalDeclined ?></div>
                <div><strong>Total Donations:</strong> <?= $totalDonations ?></div>
            </div>

            <div class="table-title">Table</div>

            <table>
                <tr>
                    <th>Donation ID</th>
                    <th>Resident ID</th>
                    <th>Resident Name</th>
                    <th>Message</th>
                    <th>Date</th>
                </tr>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6">No donations found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $d): ?>
                        <tr>
                            <td>DON-<?= str_pad($d['id'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td>RES-<?= str_pad($d['resident_id'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($d['resident_name']) ?></td>
                            <td><?= htmlspecialchars($d['message']) ?></td>
                            <td><?= htmlspecialchars($d['created_at']) ?></td>
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
   CSV EXPORT
   ========================== */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $day_filter = isset($_GET['day']) ? $_GET['day'] : '';
    $date_from = isset($_GET['from']) ? $_GET['from'] : '';
    $date_to = isset($_GET['to']) ? $_GET['to'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    $sql = "SELECT d.id, d.message, d.proof_of_donation, d.status, d.created_at, a.fullname AS resident_name, a.id AS resident_id 
            FROM donations d 
            JOIN accounts a ON d.user_id = a.id 
            WHERE d.status IN ('New', 'Claimed')";

    $params = [];
    $types = '';

    if ($status_filter) {
        $sql .= " AND d.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }

    if ($day_filter === '7') {
        $sql .= " AND DATE(d.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($day_filter === '30') {
        $sql .= " AND DATE(d.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    } elseif ($day_filter === '365') {
        $sql .= " AND DATE(d.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
    } elseif ($day_filter === 'custom' && $date_from && $date_to) {
        $sql .= " AND DATE(d.created_at) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
        $types .= 'ss';
    } elseif ($date_from && $date_to) {
        $sql .= " AND DATE(d.created_at) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
        $types .= 'ss';
    }

    if ($search) {
        $sql .= " AND (a.fullname LIKE ? OR d.message LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'ss';
    }

    $sql .= " ORDER BY d.created_at DESC";

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $export_donations = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="donations_report.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Request ID', 'Resident ID', 'Resident Name', 'Status', 'Message', 'Created At']);
    foreach ($export_donations as $donation) {
        fputcsv($output, [
            'DON-' . str_pad($donation['id'], 3, '0', STR_PAD_LEFT),
            'RES-' . str_pad($donation['resident_id'], 3, '0', STR_PAD_LEFT),
            $donation['resident_name'],
            $donation['status'],
            $donation['message'],
            $donation['created_at']
        ]);
    }
    fclose($output);
    exit;
}

/* ==========================
   NORMAL LIST QUERY
   ========================== */
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$day_filter = isset($_GET['day']) ? $_GET['day'] : '';
$date_from = isset($_GET['from']) ? $_GET['from'] : '';
$date_to = isset($_GET['to']) ? $_GET['to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT d.id, d.message, d.proof_of_donation, d.status, d.created_at, a.fullname AS resident_name, a.id AS resident_id 
        FROM donations d 
        JOIN accounts a ON d.user_id = a.id 
        WHERE d.status IN ('New', 'Claimed')";

$params = [];
$types = '';

if ($status_filter) {
    $sql .= " AND d.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($day_filter === '7') {
    $sql .= " AND DATE(d.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($day_filter === '30') {
    $sql .= " AND DATE(d.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
} elseif ($day_filter === '365') {
    $sql .= " AND DATE(d.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
} elseif ($day_filter === 'custom' && $date_from && $date_to) {
    $sql .= " AND DATE(d.created_at) BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
    $types .= 'ss';
} elseif ($date_from && $date_to) {
    $sql .= " AND DATE(d.created_at) BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
    $types .= 'ss';
}

if ($search) {
    $sql .= " AND (a.fullname LIKE ? OR d.message LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';
}

$sql .= " ORDER BY d.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$donations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="secretary.css">
<title>Admin Dashboard - Donations</title>

<style>
/* Inline styles for notification badge and dropdown */
.notification-container { position: relative; display: inline-block; cursor: pointer; }
.notification-badge { position: absolute; top: -5px; right: 18px; background: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px; font-weight: bold; }
.notification-dropdown { position: absolute; top: 50px; right: 0; background: white; border: 1px solid #ccc; padding: 10px; width: 350px; max-height: 400px; overflow-y: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); z-index: 1000; }
.notification-dropdown h4 { margin: 0 0 10px 0; font-size: 16px; }
.notification-dropdown ul { list-style: none; padding: 0; margin: 0; }
.notification-dropdown li { margin-bottom: 10px; padding: 8px; border-bottom: 1px solid #eee; }
.notification-dropdown li:last-child { border-bottom: none; }
.notification-dropdown .notification-item { text-decoration: none; color: #333; display: block; }
.notification-dropdown .notification-item:hover { background: #f0f0f0; }
.notification-dropdown .notification-type { font-weight: bold; color: #007bff; }
.notification-dropdown .notification-details { font-size: 14px; margin: 5px 0; }
.notification-dropdown .notification-date { font-size: 12px; color: #666; }

/* ✅ Modal styles (same as Admin Dashboard) */
.modal { display:none; position:fixed; z-index:5000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); }
.modal-content { background:#fff; margin: 6% auto; padding: 20px; width:92%; max-width: 600px; border-radius:10px; box-shadow:0 8px 25px rgba(0,0,0,0.2);   font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;}
.close { float:right; font-size:28px; cursor:pointer; line-height:1; }
.btn-row { display:flex; gap:10px; justify-content:flex-end; margin-top: 14px; flex-wrap:wrap; }
.btn-basic { padding:10px 14px; border:none; border-radius:8px; cursor:pointer; font-weight:800; }
.btn-gray { background:#eee; color:#333; }
.btn-blue { background:#1a73e8; color:#fff; }

/* Notification details modal */
.notif-info { margin-top: 10px; font-size: 14px; line-height: 1.5; }
.notif-info .row { margin: 8px 0; }
.notif-info .label { font-weight: 800; color: #333; }
.notif-info .value { color: #444; white-space: pre-wrap; word-break: break-word; }

/* Textarea styling for donation details */
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

<hr class="green-line">

<!-- Display session message if set -->
<?php if (isset($_SESSION['message'])): ?>
  <script>alert('<?= addslashes($_SESSION['message']) ?>');</script>
  <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<!--DONATE-->
<div id="carddonate" class="carddonate">

<div class="flex1" style="align-items:center; gap: 10px; margin-bottom:10px; flex-wrap:wrap;">
   <div style="display:flex; align-items:flex-start; margin-top: 8px;">
       <div class="logo-containeradmin">
    <img class="logoadmin" src="images/donation.png" alt="Logo">
</div>
  <h3 style="margin-top: 8px; margin-left:5px;  color: #443d3d;">Donate Table</h3>
</div>

      <div class="flex3">
    <form  class="resform"method="GET" action="Donate.php" id="filterForm">

    <div>
      <select class="custom6" name="status" onchange="document.getElementById('filterForm').submit()">
        <option value="">All</option>
        <option value="New" <?= $status_filter === 'New' ? 'selected' : '' ?>>New</option>
        <option value="Claimed" <?= $status_filter === 'Claimed' ? 'selected' : '' ?>>Claimed</option>
      </select>
  </div>

      <div>
        <label>Day:</label>
        <select class="custom7" id="donateDayFilter" name="day" onchange="filterByDonateDay(this.value)">
          <option value="">Select</option>
          <option value="7" <?= $day_filter === '7' ? 'selected' : '' ?>>Last 7 Days</option>
          <option value="30" <?= $day_filter === '30' ? 'selected' : '' ?>>Last 30 Days</option>
          <option value="365" <?= $day_filter === '365' ? 'selected' : '' ?>>Last Year</option>
          <option value="custom" <?= $day_filter === 'custom' ? 'selected' : '' ?>>Custom</option>
        </select>
    </div>

        <label id="dfr" style="display:none;">From:</label>
        <input type="date" id="donateFrom" name="from" value="<?= htmlspecialchars($date_from) ?>" style="display:none;" onchange="applyDonateCustomDateFilter()">

        <label id="dtoo" style="display:none;">To:</label>
        <input type="date" id="donateTo" name="to" value="<?= htmlspecialchars($date_to) ?>" style="display:none;" onchange="applyDonateCustomDateFilter()">
     </form>
     <form method="GET" action="Donate.php" id="filterForm">
      <input
        placeholder="Search"
        type="search"
        name="search"
        value="<?= htmlspecialchars($search) ?>"
        onchange="document.getElementById('filterForm').submit()"
        style="margin-left:auto; width:220px; min-width:220px;">
    </form>
  </div>
</div>

<div class="table-container">
<table>
<tr>
  <th>Request ID</th>
  <th>Resident Id</th>
  <th>Resident Name</th>
  <th>Status</th>
  <th>Actions</th>
</tr>
<?php if (empty($donations)): ?>
<tr><td colspan="5">No donations found.</td></tr>
<?php else: ?>
<?php foreach ($donations as $donation): ?>
<tr>
  <td>DON-<?= str_pad($donation['id'], 3, '0', STR_PAD_LEFT) ?></td>
  <td>RES-<?= str_pad($donation['resident_id'], 3, '0', STR_PAD_LEFT) ?></td>
  <td><?= htmlspecialchars($donation['resident_name']) ?></td>
  <td><?= htmlspecialchars($donation['status']) ?></td>
  <td style="text-align:center;">
    <button class="btn-view" onclick="viewDonation(<?= htmlspecialchars(json_encode($donation)) ?>)">View</button>
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

<!-- Donation details modal -->
<div id="donationModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
  <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:20px; border-radius:5px; width:92%; max-width:600px;">
      <span class="close" onclick="closeModal()">&times;</span>
    <h2 style="margin-top:0px; margin-bottom:10px;">Donation Details</h2>
     <hr style="width:99%;">
    <div id="donationDetails"></div>
    
  </div>
</div>



</div>

<!-- ✅ NOTIFICATION DETAILS MODAL (FOR ALL TYPES) -->
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

<script>
document.getElementById("carddonate").style.display = "block";

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

  document.getElementById('notifTitle').innerText = 'Notification Details';
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

function filterByDonateDay(day) {
  const url = new URL(window.location.href);
  const from = document.getElementById("donateFrom");
  const to = document.getElementById("donateTo");
  const fr = document.getElementById("dfr");
  const too = document.getElementById("dtoo");

  if (day === 'custom') {
    from.style.display = "inline-block";
    to.style.display = "inline-block";
    fr.style.display = "inline-block";
    too.style.display = "inline-block";
    return;
  }

  url.searchParams.delete('from');
  url.searchParams.delete('to');

  if (day === '') url.searchParams.delete('day');
  else url.searchParams.set('day', day);

  from.style.display = "none";
  to.style.display = "none";
  fr.style.display = "none";
  too.style.display = "none";
  window.location.href = url.toString();
}

function applyDonateCustomDateFilter() {
  const from = document.getElementById("donateFrom").value;
  const to = document.getElementById("donateTo").value;

  if (from && to) {
    const url = new URL(window.location.href);
    url.searchParams.set('day', 'custom');
    url.searchParams.set('from', from);
    url.searchParams.set('to', to);
    window.location.href = url.toString();
  }
}

function viewDonation(donationData) {
  try {
    document.getElementById('donationDetails').innerHTML = `
      <p><strong>Resident:</strong> ${donationData.resident_name || 'N/A'}</p>
      <p><strong>Message:</strong></p>
      <textarea class="report-textarea" disabled>${donationData.message || ''}</textarea>
      <p style="margin-top:12px;"><strong>Status:</strong> ${donationData.status || 'N/A'}</p>
      <p><strong>Proof of Donation:</strong> ${donationData.proof_of_donation ? '<a href="' + donationData.proof_of_donation + '" target="_blank">View Proof</a>' : 'N/A'}</p>
    `;
    document.getElementById('donationModal').style.display = 'block';
  } catch (e) {
    alert('Error loading donation details.');
  }
}

function closeModal() {
  document.getElementById('donationModal').style.display = 'none';
}



function generateReport() {
  const url = new URL(window.location.href);
  url.searchParams.set('export', 'pdf');
  window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function() {
  const daySelect = document.getElementById('donateDayFilter');
  const fromInput = document.getElementById('donateFrom');
  const toInput = document.getElementById('donateTo');
  const frLabel = document.getElementById('dfr');
  const tooLabel = document.getElementById('dtoo');

  if (daySelect && daySelect.value === 'custom') {
    fromInput.style.display = 'inline-block';
    toInput.style.display = 'inline-block';
    frLabel.style.display = 'inline-block';
    tooLabel.style.display = 'inline-block';
  }
});
</script>

</body>
</html>