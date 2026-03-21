<?php
session_start();
include "db.php";

$successMessage = $_SESSION["success_message"] ?? "";
unset($_SESSION["success_message"]);

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "SuperAdmin") {
    header("Location: login.php");
    exit;
}

/* =========================================
   HELPER: LAST LOGIN TEXT
========================================= */
function formatLastLogin($datetime) {
    if (empty($datetime)) {
        return "Never";
    }

    $timestamp = strtotime($datetime);
    if (!$timestamp) {
        return "Never";
    }

    $formatted = date("m/d/Y h:i A", $timestamp);

    $diff = time() - $timestamp;
    if ($diff < 60) {
        $ago = "just now";
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        $ago = $minutes . " minute" . ($minutes > 1 ? "s" : "") . " ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        $ago = $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } else {
        $days = floor($diff / 86400);
        $ago = $days . " day" . ($days > 1 ? "s" : "") . " ago";
    }

    return $formatted . "<br><small>(" . $ago . ")</small>";
}

/* =========================================
   NOTIFICATIONS
========================================= */
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

/* =========================================
   ACTIVATE / DEACTIVATE ACCOUNT
========================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["account_id"], $_POST["toggle_status"])) {
    $account_id = (int) $_POST["account_id"];
    $action = trim($_POST["toggle_status"]);

    if ($account_id !== (int) $_SESSION["user_id"]) {
        $new_status = ($action === "activate") ? "active" : "inactive";

        $updateStmt = $conn->prepare("UPDATE accounts SET status = ? WHERE id = ?");
        $updateStmt->bind_param("si", $new_status, $account_id);

        if ($updateStmt->execute()) {
            $_SESSION["success_message"] = ($action === "activate")
                ? "Account activated successfully."
                : "Account deactivated successfully.";
        } else {
            $_SESSION["success_message"] = "Failed to update account status.";
        }

        $updateStmt->close();
    }

    header("Location: Manage_Accounts.php");
    exit;
}

/* =========================================
   CHANGE ROLE
========================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_role"], $_POST["account_id"], $_POST["new_role"])) {
    $account_id = (int) $_POST["account_id"];
    $new_role = trim($_POST["new_role"]);

    if ($account_id !== (int) $_SESSION["user_id"] && in_array($new_role, ["Admin", "resident", "SuperAdmin"])) {
        $roleStmt = $conn->prepare("UPDATE accounts SET role = ? WHERE id = ?");
        $roleStmt->bind_param("si", $new_role, $account_id);

        if ($roleStmt->execute()) {
            $_SESSION["success_message"] = "Role changed successfully.";
        } else {
            $_SESSION["success_message"] = "Failed to change role.";
        }

        $roleStmt->close();
    }

    header("Location: Manage_Accounts.php");
    exit;
}

/* =========================================
   FETCH ACCOUNTS + LAST LOGIN
========================================= */
$search = $_GET["search"] ?? "";
$role_filter = $_GET["role"] ?? "all";
$status_filter = $_GET["status"] ?? "all";

$query = "
    SELECT
        a.id,
        a.fullname,
        a.email,
        a.phone,
        a.role,
        a.status,
        a.created_at,
        MAX(ll.login_time) AS last_login
    FROM accounts a
    LEFT JOIN login_logs ll ON ll.user_id = a.id
    WHERE 1=1
";

$params = [];
$types = "";

if ($role_filter !== "all") {
    $query .= " AND a.role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if ($status_filter !== "all") {
    $query .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($search !== "") {
    $query .= " AND (a.fullname LIKE ? OR a.email LIKE ? OR a.phone LIKE ?)";
    $search_like = "%{$search}%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= "sss";
}

$query .= "
    GROUP BY a.id, a.fullname, a.email, a.phone, a.role, a.status, a.created_at
    ORDER BY a.id DESC
";

$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Database query failed: " . $conn->error);
}

$accounts = [];
while ($row = $result->fetch_assoc()) {
    $accounts[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts</title>
    <link rel="stylesheet" href="secretary.css">
    <style>
        .table-container {
            overflow-x: auto;
            margin-top: 15px;
        }

        .cardaccounts {
            display: block !important;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        table th, table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }

        table th {
            background: #f5f5f5;
        }

        .btn-activate, .btn-deactivate, .btn-role {
            border: none;
            padding: 8px 12px;
            margin: 2px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-activate { background: #2e7d32; color: white; }
        .btn-deactivate { background: #c62828; color: white; }
        .btn-role { background: #6a1b9a; color: white; }

        .badge-active {
            background: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-declined {
            background: #f5c6cb;
            color: #721c24;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .current-user-label {
            color: #777;
            font-style: italic;
            font-weight: 600;
        }

        .action-wrap {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.45);
        }

        .modal-content {
            background: #fff;
            width: 92%;
            max-width: 430px;
            margin: 8% auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        .close {
            float: right;
            font-size: 28px;
            cursor: pointer;
            line-height: 1;
        }

        .modal-title {
            margin-top: 0;
            margin-bottom: 10px;
        }

        .role-option-wrap {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .role-option-wrap button,
        .confirm-btn-wrap button {
            border: none;
            padding: 10px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
        }

        .btn-admin {
            background: #0d6efd;
            color: #fff;
        }

        .btn-resident {
            background: #198754;
            color: #fff;
        }

        .btn-superadmin {
            background: #6f42c1;
            color: #fff;
        }

        .btn-cancel {
            background: #6c757d;
            color: #fff;
        }

        .btn-confirm-activate {
            background: #2e7d32;
            color: #fff;
        }

        .btn-confirm-deactivate {
            background: #c62828;
            color: #fff;
        }

        .last-login-cell small {
            color: #666;
            font-size: 12px;
        }

        .confirm-btn-wrap {
            display: flex;
            gap: 10px;
            margin-top: 18px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

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

        .notif-info { margin-top: 10px; font-size: 14px; line-height: 1.5; }
        .notif-info .row { margin: 8px 0; }
        .notif-info .label { font-weight: 800; color: #333; }
        .notif-info .value { color: #444; white-space: pre-wrap; word-break: break-word; }

        .btn-row {
            display:flex;
            gap:10px;
            justify-content:flex-end;
            margin-top: 14px;
            flex-wrap:wrap;
        }

        .btn-basic {
            padding:10px 14px;
            border:none;
            border-radius:8px;
            cursor:pointer;
            font-weight:800;
        }

        .btn-blue {
            background:#1a73e8;
            color:#fff;
        }
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

    <div class="btncontainer" onclick="window.location.href='SuperAdmin_Dashboard.php'">
        <img class="icon" src="images/dashboard.png" alt="home" />
        <h4 class="text">Dashboard</h4>
    </div>
<div class="btncontainer" onclick="window.location.href='Manage_Accounts.php'">
        <img class="icon" src="images/add-user.png" alt="manage accounts" />
        <h4 class="text">Manage Accounts</h4>
    </div>
    <div class="btncontainer" onclick="window.location.href='Resident_User.php'">
        <img class="icon" src="images/add-user.png" alt="home" />
        <h4 class="text">Pending Accounts</h4>
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

    <div class="address1">
        <img class="logoadd" src="images/pin.png" alt="Address">
        <div class="addtext">
            <p>Zone 28 District 3 <br>808 Reina Regente St. <br>Binondo, Manila</p>
        </div>
    </div>

    <div class="logoutcontainer" onclick="window.location.href='logout.php'">
        <img class="logolog" src="images/logout.png" alt="Logout">
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

<hr class="green-line">

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
                    ?>
                    <li>
                        <a href="#"
                           class="notification-item"
                           data-type="<?= htmlspecialchars($type, ENT_QUOTES) ?>"
                           data-id="<?= $id ?>"
                           data-user="<?= htmlspecialchars($user, ENT_QUOTES) ?>"
                           data-details="<?= htmlspecialchars($details, ENT_QUOTES) ?>"
                           data-date="<?= htmlspecialchars($date, ENT_QUOTES) ?>"
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

    <div id="cardaccounts" class="cardaccounts" style="display:block;">
        <div class="flex1" style="align-items:center; gap: 10px; margin-bottom:10px; flex-wrap:wrap;">
            <div style="display:flex; align-items:flex-start; margin-top: 8px;">
                <div class="logo-containeradmin">
                    <img class="logoadmin" src="images/report.png" alt="Logo">
                </div>
                <h3 style="margin-top: 8px; margin-left:5px; color: #443d3d;">Manage Accounts</h3>
            </div>

            <div class="flex3">
                <form class="resform" method="GET" action="Manage_Accounts.php" id="filterForm">
                    <div>
                        <label>Role:</label>
                        <select class="custom5" name="role" onchange="document.getElementById('filterForm').submit()">
                            <option value="all" <?= $role_filter === 'all' ? 'selected' : '' ?>>All</option>
                            <option value="resident" <?= $role_filter === 'resident' ? 'selected' : '' ?>>Resident</option>
                            <option value="Admin" <?= $role_filter === 'Admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="SuperAdmin" <?= $role_filter === 'SuperAdmin' ? 'selected' : '' ?>>SuperAdmin</option>
                        </select>
                    </div>

                    <div>
                        <label>Status:</label>
                        <select class="custom3" name="status" onchange="document.getElementById('filterForm').submit()">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All</option>
                            <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="declined" <?= $status_filter === 'declined' ? 'selected' : '' ?>>Declined</option>
                        </select>
                    </div>
                </form>
            </div>

            <form method="GET" action="Manage_Accounts.php" id="searchForm">
                <input
                    class="searches"
                    placeholder="Search"
                    type="search"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    onchange="document.getElementById('searchForm').submit()"
                >
                <input type="hidden" name="role" value="<?= htmlspecialchars($role_filter) ?>">
                <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
            </form>
        </div>

        <div class="table-container">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Action</th>
                </tr>

                <?php if (!empty($accounts)): ?>
                    <?php foreach ($accounts as $row): ?>
                        <tr>
                            <td>ACC-<?= str_pad($row["id"], 3, "0", STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($row["fullname"]) ?></td>
                            <td><?= htmlspecialchars($row["email"]) ?></td>
                            <td><?= htmlspecialchars($row["phone"]) ?></td>
                            <td><?= htmlspecialchars($row["role"]) ?></td>
                            <td>
                                <?php if ($row["status"] === "active"): ?>
                                    <span class="badge-active">Active</span>
                                <?php elseif ($row["status"] === "inactive"): ?>
                                    <span class="badge-inactive">Inactive</span>
                                <?php elseif ($row["status"] === "declined"): ?>
                                    <span class="badge-declined">Declined</span>
                                <?php else: ?>
                                    <span class="badge-pending"><?= htmlspecialchars(ucfirst($row["status"])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="last-login-cell">
                                <?= formatLastLogin($row["last_login"]) ?>
                            </td>
                            <td>
                                <?php if ((int)$row["id"] === (int)$_SESSION["user_id"]): ?>
                                    <span class="current-user-label">Current User</span>
                                <?php else: ?>
                                    <div class="action-wrap">
                                        <?php if ($row["status"] === "active"): ?>
                                            <button
                                                type="button"
                                                class="btn-deactivate"
                                                onclick="openStatusModal(<?= (int)$row['id'] ?>, 'deactivate', '<?= htmlspecialchars($row['fullname'], ENT_QUOTES) ?>')"
                                            >
                                                Deactivate
                                            </button>
                                        <?php else: ?>
                                            <button
                                                type="button"
                                                class="btn-activate"
                                                onclick="openStatusModal(<?= (int)$row['id'] ?>, 'activate', '<?= htmlspecialchars($row['fullname'], ENT_QUOTES) ?>')"
                                            >
                                                Activate
                                            </button>
                                        <?php endif; ?>

                                        <button
                                            type="button"
                                            class="btn-role"
                                            onclick="openRoleModal(<?= (int)$row['id'] ?>, '<?= htmlspecialchars($row['fullname'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['role'], ENT_QUOTES) ?>')"
                                        >
                                            Change Role
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No accounts found.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<div id="roleModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeRoleModal()">&times;</span>
        <h2 class="modal-title">Change Role</h2>
        <p id="roleModalText">Select a new role for this account.</p>

        <form method="POST" id="changeRoleForm">
            <input type="hidden" name="change_role" value="1">
            <input type="hidden" name="account_id" id="roleAccountId" value="">
            <input type="hidden" name="new_role" id="roleNewValue" value="">

            <div class="role-option-wrap">
                <button type="button" class="btn-admin" onclick="submitRoleChange('Admin')">Set as Admin</button>
                <button type="button" class="btn-resident" onclick="submitRoleChange('resident')">Set as Resident</button>
                <button type="button" class="btn-superadmin" onclick="submitRoleChange('SuperAdmin')">Set as SuperAdmin</button>
                <button type="button" class="btn-cancel" onclick="closeRoleModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="statusConfirmModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeStatusModal()">&times;</span>
        <h2 class="modal-title" id="statusModalTitle">Confirm Action</h2>
        <p id="statusModalText">Are you sure?</p>

        <form method="POST" id="statusConfirmForm">
            <input type="hidden" name="account_id" id="statusAccountId" value="">
            <input type="hidden" name="toggle_status" id="statusActionValue" value="">

            <div class="confirm-btn-wrap">
                <button type="button" class="btn-cancel" onclick="closeStatusModal()">Cancel</button>
                <button type="submit" id="statusConfirmButton" class="btn-confirm-deactivate">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById("cardaccounts").style.display = "block";

function openRoleModal(accountId, fullName, currentRole) {
    document.getElementById("roleAccountId").value = accountId;
    document.getElementById("roleModalText").innerText =
        "Choose a new role for " + fullName + ". Current role: " + currentRole;
    document.getElementById("roleModal").style.display = "block";
}

function closeRoleModal() {
    document.getElementById("roleModal").style.display = "none";
}

function submitRoleChange(role) {
    document.getElementById("roleNewValue").value = role;
    document.getElementById("changeRoleForm").submit();
}

function openStatusModal(accountId, action, fullName) {
    document.getElementById("statusAccountId").value = accountId;
    document.getElementById("statusActionValue").value = action;

    const title = document.getElementById("statusModalTitle");
    const text = document.getElementById("statusModalText");
    const button = document.getElementById("statusConfirmButton");

    if (action === "activate") {
        title.innerText = "Activate Account";
        text.innerText = "Are you sure you want to activate this account for " + fullName + "?";
        button.innerText = "Activate";
        button.className = "btn-confirm-activate";
    } else {
        title.innerText = "Deactivate Account";
        text.innerText = "Are you sure you want to deactivate this account for " + fullName + "?";
        button.innerText = "Deactivate";
        button.className = "btn-confirm-deactivate";
    }

    document.getElementById("statusConfirmModal").style.display = "block";
}

function closeStatusModal() {
    document.getElementById("statusConfirmModal").style.display = "none";
}

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
    return 'SuperAdmin_Dashboard.php';
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

window.onclick = function(event) {
    const roleModal = document.getElementById("roleModal");
    const statusModal = document.getElementById("statusConfirmModal");
    const notifModal = document.getElementById("notificationModal");

    if (event.target === roleModal) {
        roleModal.style.display = "none";
    }

    if (event.target === statusModal) {
        statusModal.style.display = "none";
    }

    if (event.target === notifModal) {
        notifModal.style.display = "none";
    }
}
</script>

<?php if (!empty($successMessage)): ?>
<script>
alert(<?= json_encode($successMessage) ?>);
</script>
<?php endif; ?>

</body>
</html>
<?php
$stmt->close();
?>