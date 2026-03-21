<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "SuperAdmin") {
    header("Location: login.php");
    exit;
}

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
        $updateStmt->execute();
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

    if ($account_id !== (int) $_SESSION["user_id"] && in_array($new_role, ["Admin", "resident"])) {
        $roleStmt = $conn->prepare("UPDATE accounts SET role = ? WHERE id = ?");
        $roleStmt->bind_param("si", $new_role, $account_id);
        $roleStmt->execute();
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

        .btn-activate, .btn-deactivate, .btn-view, .btn-role {
            border: none;
            padding: 8px 12px;
            margin: 2px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-activate { background: #2e7d32; color: white; }
        .btn-deactivate { background: #c62828; color: white; }
        .btn-view { background: #1565c0; color: white; }
        .btn-role { background: #6a1b9a; color: white; }

        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .filter-bar input,
        .filter-bar select {
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

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

        .role-option-wrap button {
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

        .btn-cancel {
            background: #6c757d;
            color: #fff;
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

    <div class="btncontainer" onclick="window.location.href='Manage_Accounts.php'">
        <img class="icon" src="images/add-user.png" alt="manage accounts" />
        <h4 class="text">Manage Accounts</h4>
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
            <img src="images/logo2.png" class="logo" alt="Logo">
            <h1>Manage Accounts</h1>
        </div>
    </div>

    <div id="cardaccounts" class="cardaccounts" style="display:block;">
        <div class="filter-bar">
            <form method="GET" action="Manage_Accounts.php" style="display:flex; gap:10px; flex-wrap:wrap;">
                <input type="text" name="search" placeholder="Search name, email, phone" value="<?= htmlspecialchars($search) ?>">

                <select name="role">
                    <option value="all" <?= $role_filter === 'all' ? 'selected' : '' ?>>All Roles</option>
                    <option value="resident" <?= $role_filter === 'resident' ? 'selected' : '' ?>>Resident</option>
                    <option value="Admin" <?= $role_filter === 'Admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="SuperAdmin" <?= $role_filter === 'SuperAdmin' ? 'selected' : '' ?>>SuperAdmin</option>
                </select>

                <select name="status">
                    <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="declined" <?= $status_filter === 'declined' ? 'selected' : '' ?>>Declined</option>
                </select>

                <button type="submit" class="btn-view">Filter</button>
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
                            <td>
                                <?= !empty($row["last_login"]) ? date("M d, Y h:i A", strtotime($row["last_login"])) : "Never" ?>
                            </td>
                            <td>
                                <?php if ((int)$row["id"] === (int)$_SESSION["user_id"]): ?>
                                    <span class="current-user-label">Current User</span>
                                <?php else: ?>
                                    <div class="action-wrap">
                                        <?php if ($row["status"] === "active"): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="account_id" value="<?= (int)$row["id"] ?>">
                                                <input type="hidden" name="toggle_status" value="deactivate">
                                                <button type="submit" class="btn-deactivate">Deactivate</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="account_id" value="<?= (int)$row["id"] ?>">
                                                <input type="hidden" name="toggle_status" value="activate">
                                                <button type="submit" class="btn-activate">Activate</button>
                                            </form>
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
                <button type="button" class="btn-cancel" onclick="closeRoleModal()">Cancel</button>
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

window.onclick = function(event) {
    const roleModal = document.getElementById("roleModal");
    if (event.target === roleModal) {
        roleModal.style.display = "none";
    }
}
</script>

</body>
</html>
<?php
$stmt->close();
?>