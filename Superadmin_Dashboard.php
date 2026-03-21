<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION["role"] !== "SuperAdmin") {
    header("Location: login.php");
    exit;
}

include "db.php";

/* ==========================
   ANNOUNCEMENTS: ADD / EDIT / DELETE
   ========================== */

// ADD ANNOUNCEMENT
if (isset($_POST['submit_announcement'])) {
    $event_date = $_POST['event_date'] ?? '';
    $event_time = $_POST['event_time'] ?? '';
    $event_type = trim($_POST['event_type'] ?? '');

    if ($event_date === '' || $event_time === '' || $event_type === '') {
        echo "<script>alert('Please fill in Date, Time, and Event Type.');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO announcements (event_date, event_time, event_type) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $event_date, $event_time, $event_type);
            if ($stmt->execute()) {
                echo "<script>alert('Announcement added successfully!'); window.location.href='Admin_Dashboard.php';</script>";
                exit;
            } else {
                echo "<script>alert('Error adding announcement: " . addslashes($stmt->error) . "');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('DB prepare error: " . addslashes($conn->error) . "');</script>";
        }
    }
}

// UPDATE (EDIT) ANNOUNCEMENT
if (isset($_POST['update_announcement'])) {
    $edit_id    = (int)($_POST['announcement_id'] ?? 0);
    $event_date = $_POST['event_date'] ?? '';
    $event_time = $_POST['event_time'] ?? '';
    $event_type = trim($_POST['event_type'] ?? '');

    if ($edit_id <= 0 || $event_date === '' || $event_time === '' || $event_type === '') {
        echo "<script>alert('Please fill in Date, Time, and Event Type.');</script>";
    } else {
        $stmt = $conn->prepare("UPDATE announcements SET event_date=?, event_time=?, event_type=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("sssi", $event_date, $event_time, $event_type, $edit_id);
            if ($stmt->execute()) {
                echo "<script>alert('Announcement updated successfully!'); window.location.href='Admin_Dashboard.php';</script>";
                exit;
            } else {
                echo "<script>alert('Update failed: " . addslashes($stmt->error) . "');</script>";
            }
            $stmt->close();
        }
    }
}

// DELETE ANNOUNCEMENT
if (isset($_POST['delete_announcement'])) {
    $del_id = (int)($_POST['announcement_id'] ?? 0);
    if ($del_id > 0) {
        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $del_id);
            if ($stmt->execute()) {
                echo "<script>alert('Announcement deleted!'); window.location.href='Admin_Dashboard.php';</script>";
                exit;
            } else {
                echo "<script>alert('Delete failed: " . addslashes($stmt->error) . "');</script>";
            }
            $stmt->close();
        }
    }
}

// Fetch announcements (for Manage modal)
$allAnnouncements = [];
$resA = $conn->query("SELECT id, event_date, event_time, event_type, created_at
                      FROM announcements
                      ORDER BY event_date DESC, event_time DESC, id DESC");
if ($resA) {
    while ($row = $resA->fetch_assoc()) $allAnnouncements[] = $row;
}

/* ==========================
   GENERATE REPORT (PRINT TO PDF)
   ========================== */
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {

    $query = "SELECT 
                id,
                fullname,
                address,
                phone,
                email,
                role,
                created_at,
                pwd,
                isf,
                solo_parent
              FROM accounts
              WHERE role = 'resident' AND status = 'active'
              ORDER BY created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $res = $stmt->get_result();

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

    $qs = $_GET;
    unset($qs['export']);
    $returnUrl = basename($_SERVER['PHP_SELF']) . (!empty($qs) ? ('?' . http_build_query($qs)) : '');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printable Report</title>
    <style>
        body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
   background: white; padding: 20px; }
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

        .no-print {
            margin-top: 15px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

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
                <h2>Barangay 290</h2>
            </div>

            <div class="report-sub">
                <div><strong>Report of Registered Users</strong> (<?= date('M d, Y') ?>)</div>

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
                    <th>Date Created</th>
                </tr>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7">No data found.</td></tr>
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
?>

<?php
// TOTAL REGISTERED ACCOUNTS
$totalAccountsQuery = "SELECT COUNT(*) AS total FROM accounts WHERE status = 'active' AND role = 'resident'";
$totalAccountsResult = $conn->query($totalAccountsQuery);
$totalAccounts = $totalAccountsResult ? $totalAccountsResult->fetch_assoc()['total'] : 0;

// PENDING ACCOUNTS (only residents pending approval)
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

// ✅ PENDING FOLLOW-UP MESSAGES
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

// Recent Accounts
$recentAccountsQuery = "SELECT 'Account' AS type, a.id AS id, CONCAT('New account: ', a.fullname) AS details, a.created_at AS date, a.fullname AS user
                        FROM accounts a
                        WHERE a.status = 'pending' AND a.role = 'resident'
                        ORDER BY a.created_at DESC LIMIT 5";
$recentAccountsResult = $conn->query($recentAccountsQuery);
while ($recentAccountsResult && ($row = $recentAccountsResult->fetch_assoc())) {
    $recentNotifications[] = $row;
}

// ✅ Recent Follow-ups
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

// Sort all notifications by date desc
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
<title>Admin Dashboard</title>

<style>
/* Inline styles for notification badge and dropdown */
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

/* MODALS for announcements */
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
    margin: 2% auto;
    padding: 20px;
    width:92%;
    max-width: 600px;

    font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
   background: white;
    max-height: 80%;
    overflow-y: auto;
    overflow-x:hidden;
         border-radius:10px;
            box-shadow:0 8px 25px rgba(0,0,0,0.2);
              font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                border: 2px solid green;
}
.close {
    float:right;
    font-size:28px;
    cursor:pointer;
    line-height:1;
}
.form-row { margin: 10px 0; }
.form-row label { display:block; font-weight:700; margin-bottom:6px; }
.form-row input { width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; }
.btn-row { display:flex; gap:10px; justify-content:flex-end; margin-top: 14px; flex-wrap:wrap; }
.btn-basic { padding:10px 14px; border:none; border-radius:8px; cursor:pointer; font-weight:800; }
.btn-green { background:#2d7a3e; color:#fff; }
.btn-gray { background:#eee; color:#333; }
.btn-blue { background:#1a73e8; color:#fff; }

.manage-list {
    max-height: 320px;
    overflow-y: auto;
    border: 1px solid #eee;
    padding: 10px;
    border-radius: 8px;
}
.manage-item {
    border-bottom: 1px solid #eee;
    padding: 10px 0;
}
.manage-item:last-child { border-bottom:none; }
.small { font-size: 12px; color:#666; }
.delete-btn { background:#d9534f; color:#fff; border:none; padding:8px 12px; border-radius:8px; cursor:pointer; font-weight:800; }
.edit-btn { background:#0275d8; color:#fff; border:none; padding:8px 12px; border-radius:8px; cursor:pointer; font-weight:800; margin-right:8px; }

/* Notification details modal (uses same modal classes) */
.notif-info {
  margin-top: 10px;
  font-size: 14px;
  line-height: 1.5;
}
.notif-info .row {
  margin: 8px 0;
}
.notif-info .label {
  font-weight: 800;
  color: #333;
}
.notif-info .value {
  color: #444;
  white-space: pre-wrap;
  word-break: break-word;
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

  <div class="btncontainer" onclick="window.location.href='SuperAdmin_Dashboard.php'">
    <img class="icon" src="images/dashboard.png" alt="home" /> 
    <h4 class="text">Dashboard</h4>
  </div>

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
      <h1 class="res1" >Barangay Management & E-Services Platform</h1>
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

          // safe attribute values
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

<div id="card" class="card">
    <div style="display:flex; align-items:flex-start;">
       <div class="logo-container">
    <img class="logoa" src="images/dashboard2.png" alt="Logo">
</div>
  <h3 style="margin-top: 3px; margin-left:5px;  color: #443d3d;">Dashboard Overview</h3>
</div>
  <div class="stats">
    <div class="stat-box"><h2 style="color: #443d3d;"><?= $totalAccounts ?></h2><div style="display:flex; justify-content: center;"><p>Registered Accounts</p><img class="iconss"src="images/acc.png" alt="Logo"></div></div>
    <div class="stat-box" onclick="window.location.href='Resident_User.php'"><h2 style="color: #443d3d;"><?= $pendingAccounts ?></h2><div style="display:flex; justify-content: center;"><p>Pending Accounts</p><img class="iconss"src="images/rega.png" alt="Logo"></div></div>
    <div class="stat-box" onclick="window.location.href='Request.php'"><h2 style="color: #443d3d;"><?= $pendingRequests ?></h2><div style="display:flex; justify-content: center;"><p>Pending Requests</p><img class="iconss"src="images/prequest.png" alt="Logo"></div></div>
    <div class="stat-box" onclick="window.location.href='Report.php'"><h2 style="color: #443d3d;"><?= $pendingReports ?></h2><div style="display:flex; justify-content: center;"><p>Pending Report</p><img class="iconss"src="images/preport.png" alt="Logo"></div></div>
  </div>
</div>

<div id="card1" class="card1">
  <div class="flex1">
       <div style="display:flex; align-items:flex-start;">
       <div class="logo-container">
    <img class="logoa" src="images/register.png" alt="Logo">
</div>
  <h3 style="margin-top: 3px; margin-left:5px;  color: #443d3d;">Registered Users</h3>
</div>

    <div class="flex" style="gap:10px; align-items:center;">
         <input class="search"placeholder="Search" type="search">
      <div class="reslabel0">
        <label>Category:</label>
        <select class="categoryres" onchange="filterByCategory(this.value)">
          <option value="All">All</option>
          <option value="PWD">PWD</option>
          <option value="ISF">ISF</option>
          <option value="Solo Parent">Solo Parent</option>

        </select>
      </div>

      <div class="reslabel">
        <label>Day:</label>
        <select class="categoryres2" id="dayFilter" onchange="filterByDay(this.value)">
          <option value="">Select</option>
          <option value="7days">Last 7 Days</option>
          <option value="30days">Last 30 Days</option>
          <option value="1year">Last Year</option>
          <option value="custom">Custom</option>
        </select>

        <label id="resfr" style="display:none;">From:</label>
        <input type="date" id="residentFrom" style="display:none;" onchange="applyCustomDateFilter()">
        <label id="restoo" style="display:none;">To:</label>
        <input type="date" id="residentTo" style="display:none;" onchange="applyCustomDateFilter()">
      </div>

  
    </div>
  </div>

  <div class="table-container">
    <table>
      <tr>
        <th>Resident ID</th>
        <th>Full name</th>
        <th>Address</th>
        <th>Phone number</th>
        <th>Email</th>
        <th>PWD</th>
        <th>ISF</th>  
        <th>Solo Parent</th>
        <th>Action</th>
      </tr>
      <?php
      $category_filter = isset($_GET['category']) ? $_GET['category'] : 'All';
      $day_filter = isset($_GET['day']) ? $_GET['day'] : '';
      $from_date = isset($_GET['from']) ? $_GET['from'] : '';
      $to_date = isset($_GET['to']) ? $_GET['to'] : '';
      
      $query = "SELECT 
                  id,
                  fullname,
                  address,
                  phone,
                  email,
                  role,
                  created_at,
                  pwd,
                  isf,
                  solo_parent,
                  birthdate,
                  gender,
                  civil_status,
                  household_head,
                  status,
                  pwd_proof,
                  solo_parent_proof,
                  proof_of_residency
                FROM accounts
                WHERE role = 'resident' AND status = 'active'";
      
      if ($category_filter !== 'All') {
          if ($category_filter === 'PWD') $query .= " AND pwd = 'yes'";
          elseif ($category_filter === 'ISF') $query .= " AND isf = 'yes'";
          elseif ($category_filter === 'Solo Parent') $query .= " AND solo_parent = 'yes'";
      }
      
      if ($day_filter === '7days') $query .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
      elseif ($day_filter === '30days') $query .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
      elseif ($day_filter === '1year') $query .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
      elseif ($day_filter === 'custom' && $from_date && $to_date) $query .= " AND DATE(created_at) >= ? AND DATE(created_at) <= ?";
      
      if ($day_filter === 'custom' && $from_date && $to_date) {
          $stmt = $conn->prepare($query . " ORDER BY created_at DESC");
          $stmt->bind_param('ss', $from_date, $to_date);
          $stmt->execute();
          $result = $stmt->get_result();
      } else {
          $result = $conn->query($query . " ORDER BY created_at DESC");
      }

      if ($result && $result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              echo "<tr>";
              echo "<td>RES-" . str_pad($row['id'], 3, '0', STR_PAD_LEFT) . "</td>";
              echo "<td>" . htmlspecialchars($row['fullname']) . "</td>";
              echo "<td>" . htmlspecialchars($row['address']) . "</td>";
              echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
              echo "<td>" . htmlspecialchars($row['email']) . "</td>";
              echo "<td>" . (($row['pwd'] === 'yes' || $row['pwd'] == 1) ? '✓' : '') . "</td>";
              echo "<td>" . (($row['isf'] === 'yes' || $row['isf'] == 1) ? '✓' : '') . "</td>";
              echo "<td>" . (($row['solo_parent'] === 'yes' || $row['solo_parent'] == 1) ? '✓' : '') . "</td>";
              $userJson = htmlspecialchars(json_encode($row), ENT_QUOTES);
              echo "<td><button class=\"btn-view\" data-user=\"$userJson\" onclick=\"viewAccount(this.dataset.user)\">View</button></td>";
              echo "</tr>";
          }
      } else {
          echo "<tr><td colspan='9'>No registered users found</td></tr>";
      }
      ?>
    </table>
  </div>

  <!-- VIEW USER DETAILS MODAL -->
  <div id="viewModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal()">&times;</span>
      <div style="display:flex; align-items:flex-start;">
       <div class="logo-container">
    <img class="logoa" src="images/information.png" alt="Logo">
</div>
  <h3 style="margin-top: 3px; margin-left:5px;  color: #443d3d;">User Details</h3>
</div>
<hr style="width:100%;">
      <div id="modalInfo"></div>
     
    </div>
  </div>

  <div style="display: flex;">
    <div class="botbtn">
      <button class="btn-post" id="btnOpenAnnouncementModal">Send Annoucement</button>
      <button class="btn-post" id="btnOpenManageModal" style="margin-left:0px;">Manage Events</button>
      <button class="btn-report">Generate Report</button>
    </div>
  </div>
</div>

<!-- SEND ANNOUNCEMENT MODAL -->
<div id="announcementModal" class="modal">
  <div class="modal-content">
    <span class="close" data-close="announcementModal">&times;</span>
    <h2>Send Announcement</h2>

    <form method="POST">
      <div class="form-row">
        <label>Date of Event</label>
        <input type="date" name="event_date" required>
      </div>

      <div class="form-row">
        <label>Time of Event</label>
        <input type="time" name="event_time" required>
      </div>

      <div class="form-row">
        <label>What kind of event</label>
        <input type="text" name="event_type" placeholder="e.g. Meeting, Clean-up Drive" required>
      </div>

      <div class="btn-row">
        <button type="button" class="btn-basic btn-gray" data-close="announcementModal">Cancel</button>
        <button type="submit" class="btn-basic btn-green" name="submit_announcement">Send</button>
      </div>
    </form>
  </div>
</div>

<!-- MANAGE EVENTS MODAL -->
<div id="manageModal" class="modal">
  <div class="modal-content">
    <span class="close" data-close="manageModal">&times;</span>
    <h2>Manage Events</h2>

    <div class="manage-list">
      <?php if (empty($allAnnouncements)): ?>
        <div>No announcements yet.</div>
      <?php else: ?>
        <?php foreach ($allAnnouncements as $a): ?>
          <div class="manage-item">
            <div><b>ID:</b> <?= (int)$a['id'] ?></div>
            <div><b>Event:</b> <?= htmlspecialchars($a['event_type']) ?></div>
            <div><b>Date & Time:</b>
              <?= date('M d, Y', strtotime($a['event_date'])) ?>
              <?= date('h:i A', strtotime($a['event_time'])) ?>
            </div>
            <div class="small"><b>Created:</b> <?= date('M d, Y H:i', strtotime($a['created_at'])) ?></div>

            <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
              <button type="button"
                      class="edit-btn"
                      onclick="openEditAnnouncement(
                        '<?= (int)$a['id'] ?>',
                        '<?= htmlspecialchars($a['event_date'], ENT_QUOTES) ?>',
                        '<?= htmlspecialchars($a['event_time'], ENT_QUOTES) ?>',
                        '<?= htmlspecialchars($a['event_type'], ENT_QUOTES) ?>'
                      )">
                Edit
              </button>

              <form method="POST" style="margin:0;">
                <input type="hidden" name="announcement_id" value="<?= (int)$a['id'] ?>">
                <button type="submit" name="delete_announcement" class="delete-btn"
                  onclick="return confirm('Delete this announcement?');">
                  Delete
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="btn-row">
      <button type="button" class="btn-basic btn-gray" data-close="manageModal">Close</button>
    </div>
  </div>
</div>

<!-- EDIT ANNOUNCEMENT MODAL -->
<div id="editAnnouncementModal" class="modal">
  <div class="modal-content">
    <span class="close" data-close="editAnnouncementModal">&times;</span>
    <h2>Edit Announcement</h2>

    <form method="POST">
      <input type="hidden" name="announcement_id" id="edit_announcement_id">

      <div class="form-row">
        <label>Date of Event</label>
        <input type="date" name="event_date" id="edit_event_date" required>
      </div>

      <div class="form-row">
        <label>Time of Event</label>
        <input type="time" name="event_time" id="edit_event_time" required>
      </div>

      <div class="form-row">
        <label>What kind of event</label>
        <input type="text" name="event_type" id="edit_event_type" required>
      </div>

      <div class="btn-row">
        <button type="button" class="btn-basic btn-gray" data-close="editAnnouncementModal">Cancel</button>
        <button type="submit" class="btn-basic btn-green" name="update_announcement">Save</button>
      </div>
    </form>
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
      <div class="row"><span class="value" id="notifDetails"></span></div>
    </div>

    <div class="btn-row">
      <button type="button" class="btn-basic btn-blue" id="notifGoBtn">Go to Page</button>
    </div>
  </div>
</div>

<script>
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
  const id = el.getAttribute('data-id') || '';
  const user = el.getAttribute('data-user') || '';
  const details = el.getAttribute('data-details') || '';
  const dateRaw = el.getAttribute('data-date') || '';

  document.getElementById('notifTitle').innerText = 'Notification Details';
  document.getElementById('notifType').innerText = type;
  document.getElementById('notifFrom').innerText = user;

  // show formatted date (same as dropdown format)
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

  // full details (NOT truncated)
  document.getElementById('notifDetails').innerText = details;

  // go button uses type mapping (you can later add "?id=" if you want)
  const goBtn = document.getElementById('notifGoBtn');
  goBtn.onclick = function() {
    window.location.href = getPageByType(type);
  };

  // hide dropdown + show modal
  document.getElementById('notificationDropdown').style.display = 'none';
  document.getElementById('notificationModal').style.display = 'block';
}

function closeNotificationModal() {
  document.getElementById('notificationModal').style.display = 'none';
}

// Close modal if click outside content
window.addEventListener('click', function(e){
  const notifModal = document.getElementById('notificationModal');
  if (e.target === notifModal) notifModal.style.display = 'none';
});

/* Existing dashboard functions you already had */
function filterByCategory(category) {
  const url = new URL(window.location.href);
  if (category === 'All') url.searchParams.delete('category');
  else url.searchParams.set('category', category);
  window.location.href = url.toString();
}

function filterByDay(day) {
  const url = new URL(window.location.href);
  const from = document.getElementById("residentFrom");
  const to = document.getElementById("residentTo");
  const fr = document.getElementById("resfr");
  const too = document.getElementById("restoo");

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

function applyCustomDateFilter() {
  const from = document.getElementById("residentFrom").value;
  const to = document.getElementById("residentTo").value;

  if (from && to) {
    const url = new URL(window.location.href);
    url.searchParams.set('day', 'custom');
    url.searchParams.set('from', from);
    url.searchParams.set('to', to);
    window.location.href = url.toString();
  }
}

document.addEventListener("click", function(e){
  const btn = e.target.closest(".btn-report");
  if(!btn) return;

  const url = new URL(window.location.href);
  url.searchParams.set("export", "pdf");
  window.location.href = url.toString();
});

/* Announcement modals */
const announcementModal = document.getElementById('announcementModal');
const manageModal = document.getElementById('manageModal');
const editAnnouncementModal = document.getElementById('editAnnouncementModal');

document.getElementById('btnOpenAnnouncementModal').addEventListener('click', function(){
  announcementModal.style.display = 'block';
});

document.getElementById('btnOpenManageModal').addEventListener('click', function(){
  manageModal.style.display = 'block';
});

document.querySelectorAll('[data-close]').forEach(btn => {
  btn.addEventListener('click', function(){
    const id = this.getAttribute('data-close');
    const modal = document.getElementById(id);
    if(modal) modal.style.display = 'none';
  });
});

window.addEventListener('click', function(e){
  if(e.target === announcementModal) announcementModal.style.display = 'none';
  if(e.target === manageModal) manageModal.style.display = 'none';
  if(e.target === editAnnouncementModal) editAnnouncementModal.style.display = 'none';
  const viewModal = document.getElementById('viewModal');
  if (viewModal && e.target === viewModal) viewModal.style.display = 'none';
});

// Restore category/day filter values
document.addEventListener('DOMContentLoaded', function() {
  const url = new URL(window.location.href);
  const category = url.searchParams.get('category') || 'All';
  const day = url.searchParams.get('day') || '';
  const from = url.searchParams.get('from') || '';
  const to = url.searchParams.get('to') || '';

  const categorySelect = document.querySelector('.categoryres');
  if (categorySelect) categorySelect.value = category;

  const daySelect = document.getElementById('dayFilter');
  if (daySelect) daySelect.value = day;

  if (day === 'custom') {
    const fromInput = document.getElementById('residentFrom');
    const toInput = document.getElementById('residentTo');
    const frLabel = document.getElementById('resfr');
    const tooLabel = document.getElementById('restoo');

    if (fromInput && toInput) {
      fromInput.style.display = 'inline-block';
      toInput.style.display = 'inline-block';
      frLabel.style.display = 'inline-block';
      tooLabel.style.display = 'inline-block';

      fromInput.value = from;
      toInput.value = to;
    }
  }
});

function openEditAnnouncement(id, date, time, type){
  document.getElementById('edit_announcement_id').value = id;
  document.getElementById('edit_event_date').value = date;
  document.getElementById('edit_event_time').value = time;
  document.getElementById('edit_event_type').value = type;
  editAnnouncementModal.style.display = 'block';
}

function viewAccount(data) {
  const modal = document.getElementById('viewModal');
  const modalInfo = document.getElementById('modalInfo');

  // Accept either object or JSON string
  const user = typeof data === 'string' ? JSON.parse(data) : (data || {});

  let category = 'Regular';
  if (user.pwd == 1 || user.pwd === 'yes') category = 'PWD';
  else if (user.isf == 1 || user.isf === 'yes') category = 'ISF';
  else if (user.solo_parent == 1 || user.solo_parent === 'yes') category = 'Solo Parent';

  const info = `
    <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Resident ID:</strong> RES-${String(user.id).padStart(3, '0')}</p>
    <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Full Name:</strong> ${user.fullname || ''}</p>
    <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Birthdate:</strong> ${user.birthdate || 'N/A'}</p>
    <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Gender:</strong> ${user.gender || 'N/A'}</p>
    <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Civil Status:</strong> ${user.civil_status || 'N/A'}</p>
    <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Address:</strong> ${user.address || ''}</p>
    <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Phone:</strong> ${user.phone || ''}</p>
    <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Email:</strong> ${user.email || ''}</p>
    <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Category:</strong> ${category}</p>
    <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Household Head:</strong> ${user.household_head || 'N/A'}</p>
    <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>PWD:</strong> ${(user.pwd == 1 || user.pwd === 'yes') ? 'Yes' : 'No'}</p>
    <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>ISF:</strong> ${(user.isf == 1 || user.isf === 'yes') ? 'Yes' : 'No'}</p>
    <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Solo Parent:</strong> ${(user.solo_parent == 1 || user.solo_parent === 'yes') ? 'Yes' : 'No'}</p>
    <p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Status:</strong> ${user.status || 'N/A'}</p>
    ${user.pwd_proof ? `<p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>PWD Proof:</strong> <a href="${user.pwd_proof}" target="_blank">View Proof</a></p>` : ''}
    ${user.solo_parent_proof ? `<p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Solo Parent Proof:</strong> <a href="${user.solo_parent_proof}" target="_blank">View Proof</a></p>` : ''}
    ${user.proof_of_residency ? `<p style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 0px;"><strong>Proof of Residency:</strong> <a href="${user.proof_of_residency}" target="_blank">View Proof</a></p>` : ''}
  `;

  modalInfo.innerHTML = info;
  modal.style.display = 'block';
}

function closeModal() {
  const viewModal = document.getElementById('viewModal');
  if (viewModal) viewModal.style.display = 'none';
}
</script>

</body>
</html>