<?php
session_start();
include "db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

/* ==========================
   FETCH CURRENT USER INFO
   ========================== */
$stmt = $conn->prepare("SELECT * FROM accounts WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$currentUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$currentUser) {
    die("User not found.");
}

/* =========================================================
   USER NOTIFICATIONS: SHOW APPROVED / DECLINED UPDATES
   (Request / Report / Donation / Feedback)
   ========================================================= */
$userStatusNotifications = [];

function fetchNotifs($conn, $sql, $user_id) {
    $out = [];
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $out[] = $row;
        $stmt->close();
    }
    return $out;
}

$reqNotifSql = "SELECT 'Request' AS type, r.request_id AS id, r.document_type AS details, r.status AS status, r.decline_reason AS reason, r.created_at AS date
                FROM requests r
                WHERE r.user_id = ? AND LOWER(r.status) IN ('approved','declined')
                ORDER BY date DESC LIMIT 10";

$repNotifSql = "SELECT 'Report' AS type, r.id AS id, r.reason AS details, r.status AS status, r.created_at AS date
                FROM reports r
                WHERE r.user_id = ? AND LOWER(r.status) IN ('resolved','declined')
                ORDER BY date DESC LIMIT 10";

$donNotifSql = "SELECT 'Donation' AS type, d.id AS id, d.message AS details, d.status AS status, d.created_at AS date
                FROM donations d
                WHERE d.user_id = ? AND LOWER(d.status) IN ('approved','declined')
                ORDER BY date DESC LIMIT 10";

$fbNotifSql  = "SELECT 'Feedback' AS type, f.id AS id, f.feedback_text AS details, f.status AS status, f.created_at AS date
                FROM feedbacks f
                WHERE f.user_id = ? AND LOWER(f.status) IN ('reviewed','declined')
                ORDER BY date DESC LIMIT 10";

$userStatusNotifications = array_merge(
    fetchNotifs($conn, $reqNotifSql, $user_id),
    fetchNotifs($conn, $repNotifSql, $user_id),
    fetchNotifs($conn, $donNotifSql, $user_id),
    fetchNotifs($conn, $fbNotifSql,  $user_id)
);

usort($userStatusNotifications, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
$userStatusNotifications = array_slice($userStatusNotifications, 0, 10);
$userStatusNotifCount = count($userStatusNotifications);

/* =========================================================
   ANNOUNCEMENT (LATEST ONLY) + MODAL (ALL ANNOUNCEMENTS)
   ========================================================= */
$announcementText = "No announcements yet.";
$allAnnouncementsText = "No announcements yet.";

/* Latest announcement (try with event_time first, fallback if column doesn't exist) */
$annRes = $conn->query("SELECT id, event_date, event_time, event_type, created_at 
                        FROM announcements 
                        ORDER BY created_at DESC 
                        LIMIT 1");
if (!$annRes) {
    $annRes = $conn->query("SELECT id, event_date, event_type, created_at 
                            FROM announcements 
                            ORDER BY created_at DESC 
                            LIMIT 1");
}
if ($annRes && $annRes->num_rows > 0) {
    $ann = $annRes->fetch_assoc();

    $timeLine = "";
    if (isset($ann['event_time']) && $ann['event_time'] !== null && $ann['event_time'] !== '') {
        $timeLine = "Time of Event: " . date('h:i A', strtotime($ann['event_time'])) . "\n";
    }

    $announcementText =
        "Date of Event: " . date('M d, Y', strtotime($ann['event_date'])) . "\n" .
        $timeLine .
        "Event Type: " . $ann['event_type'] . "\n" .
        "Posted: " . date('M d, Y h:i A', strtotime($ann['created_at']));
}

/* All announcements for modal (try with event_time first, fallback) */
$annAllRes = $conn->query("SELECT id, event_date, event_time, event_type, created_at
                           FROM announcements
                           ORDER BY created_at DESC");
if (!$annAllRes) {
    $annAllRes = $conn->query("SELECT id, event_date, event_type, created_at
                               FROM announcements
                               ORDER BY created_at DESC");
}

if ($annAllRes && $annAllRes->num_rows > 0) {
    $lines = [];
    while ($row = $annAllRes->fetch_assoc()) {

        $timeLine = "";
        if (isset($row['event_time']) && $row['event_time'] !== null && $row['event_time'] !== '') {
            $timeLine = "Time of Event: " . date('h:i A', strtotime($row['event_time'])) . "\n";
        }

       $lines[] =
    "<div style='margin-bottom:10px; background-color:#f7f7f7;padding:10px;    font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; border-radius:8px; '>
        <div style='font-weight:bold; color:green;'>{$row['event_type']}</div>
        <div style='font-size:13px; color:gray;'>Date of Event: " . date('M d, Y', strtotime($row['event_date'])) . "</div>
        <div style='font-size:13px; color:gray;'>$timeLine</div>
        <div style='font-size:13px; color:gray; font-style:italic;'>Posted: " . date('M d, Y h:i A', strtotime($row['created_at'])) . "</div>
    </div>";
    }
    $allAnnouncementsText = implode("\n\n", $lines);
}

/* =========================================================
   UPDATE INFO (inside View Information modal)
   ONLY editable:
   fullname, birthdate, gender, address, phone, username, profile_image
   ========================================================= */
if (isset($_POST['update_info'])) {

    $new_fullname  = trim($_POST['fullname'] ?? '');
    $new_birthdate = $_POST['birthdate'] ?? '';
    $new_gender    = trim($_POST['gender'] ?? '');
    $new_address   = trim($_POST['address'] ?? '');
    $new_phone     = trim($_POST['phone'] ?? '');
    $new_username  = trim($_POST['username'] ?? '');

    if ($new_fullname === '' || $new_birthdate === '' || $new_gender === '' || $new_address === '' || $new_phone === '' || $new_username === '') {
        echo "<script>alert('Please fill in all editable fields.');</script>";
    } else {

        // Check username uniqueness (because username is UNIQUE in DB)
        $stmt = $conn->prepare("SELECT id FROM accounts WHERE username = ? AND id <> ?");
        $stmt->bind_param("si", $new_username, $user_id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            echo "<script>alert('Username is already taken. Please choose another.');</script>";
        } else {

            $profile_image_path = $currentUser['profile_image'] ?? NULL;

            // optional image upload
            if (!empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['error'] === 0) {
                $allowed = ['jpg','jpeg','png','webp'];
                $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed)) {
                    echo "<script>alert('Profile image must be JPG, JPEG, PNG, or WEBP.');</script>";
                } else {
                    $upload_dir = "uploads/profiles/";
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                    $newName = "profile_" . $user_id . "_" . time() . "." . $ext;
                    $targetPath = $upload_dir . $newName;

                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
                        $profile_image_path = $targetPath;
                    } else {
                        echo "<script>alert('Failed to upload image.');</script>";
                    }
                }
            }

            // update ONLY allowed fields
            $stmt = $conn->prepare("UPDATE accounts 
                                    SET fullname=?, birthdate=?, gender=?, address=?, phone=?, username=?, profile_image=?
                                    WHERE id=?");
            $stmt->bind_param(
                "sssssssi",
                $new_fullname,
                $new_birthdate,
                $new_gender,
                $new_address,
                $new_phone,
                $new_username,
                $profile_image_path,
                $user_id
            );

            if ($stmt->execute()) {
                $_SESSION['fullname'] = $new_fullname;

                echo "<script>alert('Information updated successfully!'); window.location.href='homepage.php';</script>";
                exit();
            } else {
                echo "<script>alert('Error updating information: " . addslashes($stmt->error) . "');</script>";
            }
            $stmt->close();
        }
    }
}

/* =========================================================
   CHANGE PASSWORD (PLAINTEXT - as requested)
   ========================================================= */
if (isset($_POST['change_password'])) {

    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($current_password === '' || $new_password === '' || $confirm_password === '') {
        echo "<script>alert('Please fill in all password fields.');</script>";
    } elseif ($new_password !== $confirm_password) {
        echo "<script>alert('New password and confirmation do not match.');</script>";
    } else {
        $storedPassword = (string)$currentUser['password'];
        $isValid = false;

        if (password_verify($current_password, $storedPassword)) {
            $isValid = true;
        } elseif ($current_password === $storedPassword) {
            // Legacy plaintext password
            $isValid = true;
        }

        if (! $isValid) {
            echo "<script>alert('Current password is incorrect.');</script>";
        } else {
            $newHash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE accounts SET password=? WHERE id=?");
            $stmt->bind_param("si", $newHash, $user_id);

            if ($stmt->execute()) {
                echo "<script>alert('Password changed successfully!'); window.location.href='homepage.php';</script>";
                exit();
            } else {
                echo "<script>alert('Failed to change password: " . addslashes($stmt->error) . "');</script>";
            }
            $stmt->close();
        }
    }
}

/* =========================================================
   FORMS (YOUR EXISTING CODE)
   ========================================================= */

/* --------------------------
   REQUEST: FETCH USER'S ALREADY-REQUESTED DOCS (for JS pre-check)
   -------------------------- */
$requestedDocs = [];
$rd = $conn->prepare("SELECT DISTINCT document_type FROM requests WHERE user_id = ?");
if ($rd) {
    $rd->bind_param("i", $user_id);
    $rd->execute();
    $res = $rd->get_result();
    while ($row = $res->fetch_assoc()) {
        $requestedDocs[] = (string)$row['document_type'];
    }
    $rd->close();
}

/* --------------------------
   Handle Request Form Submission (per-document only once) - return JSON
   -------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request']) && isset($_POST['docs'])) {
    header('Content-Type: application/json; charset=utf-8');

    $document = trim($_POST['docs'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $purpose  = trim($_POST['purpose'] ?? '');
    $guardian_name    = trim($_POST['guardian_name'] ?? '');
    $guardian_address = trim($_POST['guardian_address'] ?? '');
    $guardian_contact = trim($_POST['guardian_contact'] ?? '');

    // basic validation
    if ($document === '' || $fullname === '' || $address === '' || $purpose === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit;
    }

    // Only once per document type
    $chk = $conn->prepare("SELECT 1 FROM requests WHERE user_id = ? AND document_type = ? LIMIT 1");
    if ($chk) {
        $chk->bind_param("is", $user_id, $document);
        $chk->execute();
        $exists = $chk->get_result()->fetch_assoc();
        $chk->close();

        if ($exists) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'You already requested this document. You can only request each document once.']);
            exit();
        }
    }

    $sql = "INSERT INTO requests 
            (user_id, document_type, fullname, address, purpose, guardian_name, guardian_address, guardian_contact)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("isssssss", $user_id, $document, $fullname, $address, $purpose, $guardian_name, $guardian_address, $guardian_contact);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Request submitted successfully!']);
        exit();
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error submitting request: ' . $stmt->error]);
        exit();
    }

    $stmt->close();
}

// Handle Report Form Submission (AJAX / no reload) - return JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['docsReport'])) {
    header('Content-Type: application/json; charset=utf-8');

    $reason = trim($_POST['docsReport']);
    $person_reported = trim($_POST['person'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $specify = trim($_POST['specify'] ?? '');

    // basic validation
    if ($reason === '' || $address === '' || $specify === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit;
    }

    if (!isset($_FILES['proof']) || $_FILES['proof']['error'] != 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Please upload a proof file.']);
        exit;
    }

    $target_dir = "uploads/reports/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

    $file_name = time() . '_' . basename($_FILES['proof']['name']);
    $proof_path = $target_dir . $file_name;

    if (!move_uploaded_file($_FILES['proof']['tmp_name'], $proof_path)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error uploading file. Please try again.']);
        exit;
    }

    $sql = "INSERT INTO reports (user_id, reason, person_reported, address, proof, specify, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("isssss", $user_id, $reason, $person_reported, $address, $proof_path, $specify);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Report submitted successfully!']);
        exit();
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error submitting report: ' . $stmt->error]);
        exit();
    }

    $stmt->close();
}

// Handle Donate Form Submission (AJAX / no reload) - return JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['donatetextarea'])) {
    header('Content-Type: application/json; charset=utf-8');

    $message = trim($_POST['donatetextarea'] ?? '');

    if ($message === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Please enter a message for your donation.']);
        exit;
    }

    if (!isset($_FILES['pod']) || $_FILES['pod']['error'] != 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Please upload proof of donation.']);
        exit;
    }

    $target_dir = "uploads/donations/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

    $file_name = time() . '_' . basename($_FILES['pod']['name']);
    $pod_path = $target_dir . $file_name;

    if (!move_uploaded_file($_FILES['pod']['tmp_name'], $pod_path)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error uploading proof of donation.']);
        exit;
    }

    $sql = "INSERT INTO donations (user_id, message, proof_of_donation, status) VALUES (?, ?, ?, 'New')";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("iss", $user_id, $message, $pod_path);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Donation submitted successfully!. Thank you for your generosity!']);
        exit();
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error submitting donation: ' . $stmt->error]);
        exit();
    }

    $stmt->close();
}

// Handle Feedback Form Submission (AJAX / no reload) - return JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedbackta'])) {
    header('Content-Type: application/json; charset=utf-8');

    $feedback_text = trim($_POST['feedbackta'] ?? '');

    if ($feedback_text === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Please enter your feedback.']);
        exit;
    }

    $sql = "INSERT INTO feedbacks (user_id, feedback_text) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("is", $user_id, $feedback_text);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Feedback submitted successfully!. Thank you for helping us improve our services!']);
        exit();
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error submitting feedback: ' . $stmt->error]);
        exit();
    }

    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay 292 - Homepage</title>
    <link rel="stylesheet" href="homepage.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* Center the user icon again */
         .user { cursor: pointer; transition: transform 0.2s ease, opacity 0.2s ease; }
        .user:hover { transform: scale(1.08); opacity: 0.85; }
        .user:focus { outline: 2px solid #2d7a3e; outline-offset: 2px; border-radius: 50%; }
        .user-avatar { margin-left:5px; width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }

        /* Profile Dropdown Styles */
      .profile-dropdown {
    display: none;
    position: absolute;
    top: 50px;      /* distance below icon */
    left: 20px;       /* align to the icon */
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 1000;
    min-width: 190px;
    overflow: hidden;
}
        .profile-dropdown.active { display: block; }
        .dropdown-item {
            display: block;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.2s ease, color 0.2s ease;
            border-bottom: 1px solid #f0f0f0;
        }
        .dropdown-item:last-child { border-bottom: none; }
        .dropdown-item:hover, .dropdown-item:focus {
            background-color: #f5f5f5;
            color: #2d7a3e;
            text-decoration: none;
            outline: none;
        }

        /* Notification bell + badge in upper-right */
        .notification-container{
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: auto;
            width: 44px;
            height: 44px;
            cursor: pointer;
        }
        .notification-badge{
            position: absolute;
            top: 4px;
            right: 4px;
            background: red;
            color: white;
            border-radius: 999px;
            padding: 2px 6px;
            font-size: 12px;
            font-weight: bold;
            line-height: 1;
            border: 2px solid #fff;
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
            overflow-y: auto;a
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            z-index: 2000;
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

        /* MODALS */
        .modal { overflow: hidden; }
        .modal-content { max-height: 70vh; overflow-y: auto; overflow-x:hidden; }
              .modal-contentannunce { max-height: 80vh; overflow-y: auto; overflow-x:hidden; }

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
            width:80%;
            max-width: 720px;
            border-radius:10px;
            box-shadow:0 8px 25px rgba(0,0,0,0.2);
              font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                border: 2px solid green;
        }

          .modal-contentpass {
            background:#fff;
            margin: 6% auto;
            padding: 20px;
            width:40%;
            max-width: 720px;
            border-radius:10px;
            box-shadow:0 8px 25px rgba(0,0,0,0.2);
              font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                border: 2px solid green;
        }
         .modal-contentannounce {
            background:#fff;
            margin: 6% auto;
            padding: 20px;
            width:30%;
            height: 320px;
            max-width: 720px;
            border-radius:10px;
            box-shadow:0 8px 25px rgba(0,0,0,0.2);
              font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                border: 2px solid green;
                  overflow-x: auto;
        }

        .close { float:right; font-size:28px; cursor:pointer; line-height:1; }

        .info-table { width:100%; border-collapse:collapse; margin-top: 10px; font-size:14px; }
        .info-table td { padding:8px 10px; border-bottom:1px solid #eee; vertical-align:top; }
        .info-key { font-weight:bold; width: 36%;    font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #443d3d; }
        .info-val { word-break: break-word; }

        .edit-box { display:none; margin-top: 14px; padding-top: 12px;  }
        .edit-box label { display:block; margin: 10px 0 6px; font-weight: 700; font-size: 13px; }
        .edit-box input, .edit-box select {
            width:100%;
            padding:10px;
            border:1px solid #ccc;
            border-radius:6px;
            outline:none;
        }

        .btn {
         
            border:none;
             font-family: Inter, sans-serif;
            cursor:pointer;
            font-weight:bold;
        }
        .btn-primary { background:#2d7a3e; color:#fff; }
        .btn-secondary { background:#eee; color:#333; }
        .btn-row { display:flex; gap:10px; margin-top: 12px; flex-wrap: wrap; }

        .profile-preview { display:flex; gap:12px; align-items:center; margin: 8px 0 6px; }
        .profile-preview img { width:64px; height:64px; border-radius:50%; object-fit:cover; border:1px solid #eee; }

        .header-icon{ font-size: 26px; color: #222; display: block; line-height: 1; }
        .notification-container:hover .header-icon{ opacity: 0.85; }
        .child1{ display: flex; align-items: center; }
        .header-icon{ font-size: 26px; line-height: 1; }

        /* announcement modal textarea only */
        .textareaannouncement-modal{
            width: 95%;
           margin-right: 10px;
            height: 300px;
            resize: none;
            overflow-y: auto;
            padding: 10px;
            line-height: 1.3;
            font-family: inherit;
          border-color: green;
          overflow-x: auto;
        }

        /* small confirm modal text */
        .confirm-text {
            font-size: 14px;
            line-height: 1.4;
            color: #333;
            white-space: pre-line;
            margin-top: 8px;
        }
 
        @media(max-width:885px){
            .user-avatar{
               align-self: center;
               margin-left: 0;
            }
            .usercontainer{
                width: 100%;
            }
        }
               @media(max-width:885px){
  .modal-contentpass{
  width: 70%;
}
.modal-contentannounce{
     width: 70%;
}
}
    </style>
</head>

<body>
<?php
    $img = $currentUser['profile_image'] ?? '';
    $hasImg = (!empty($img) && file_exists($img));
?>
<div class="main">
    <!-- Sidebar -->
    <div class="sidebar">
               <div class="sideflex">
        <div class="usercontainer">

            <?php if ($hasImg): ?>
                <img class="user user-avatar" src="<?= htmlspecialchars($img) ?>" alt="Profile" id="profileIcon" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false">
            <?php else: ?>
                <img class="user user-avatar" src="images/usericon.png" alt="User Icon" id="profileIcon" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false">
            <?php endif; ?>

            <div class="profile-dropdown" id="profileDropdown" role="menu">
                <a href="#" class="dropdown-item" role="menuitem" data-modal="viewInfoModal">View Information</a>
                <a href="#" class="dropdown-item" role="menuitem" data-modal="changePassModal">Change Password</a>
            </div>
        </div>
              <div class="info">
        <h5 class="username"><?= htmlspecialchars($_SESSION["fullname"]) ?></h5>
            <h4 class="usertype"><?= htmlspecialchars($_SESSION["role"]) ?></h4>
        </div>
    </div>

        <hr class="hrside">

        <div class="btncontainer active" onclick="showSection('home', this)">
            <img class="icon" src="images/homeicon.png" alt="Home">
            <h4 class="text">Home</h4>
        </div>

        <div class="btncontainer" onclick="showSection('request', this)">
            <img class="icon" src="images/reqicon.png" alt="Request">
            <h4 class="text">Request</h4>
        </div>

        <div class="btncontainer" onclick="showSection('report', this)">
            <img class="icon" src="images/repicon.png" alt="Report">
            <h4 class="text">Report</h4>
        </div>

        <div class="btncontainer" onclick="showSection('donate', this)">
            <img class="icon" src="images/dicon.png" alt="Donate">
            <h4 class="text">Donate</h4>
        </div>

        <div class="btncontainer" onclick="showSection('feedback', this)">
            <img class="icon" src="images/fbicon.png" alt="Feedback">
            <h4 class="text">Feedback</h4>
        </div>
         <hr style="width: 100%; border: 0.5px solid rgba(255, 255, 255, 0.4); margin-top: 10px;">

         
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

    <!-- Header -->
    <div class="header">
        <div class="child1">
            <img class="logo" src="images/logo2.png" alt="Logo">
            <h3 class="h3top">Barangay management & E-Services Platform</h3>

            <!-- NOTIFICATION -->
            <div class="notification-container" onclick="toggleNotifications()" style="margin-left:auto;">
                <i class="fa-solid fa-bell header-icon" aria-label="Notifications"></i>
                <?php if ($userStatusNotifCount > 0): ?>
                    <span class="notification-badge"><?= $userStatusNotifCount ?></span>
                <?php endif; ?>
                <div id="notificationDropdown" class="notification-dropdown" style="display:none;">
                    <h4>Recent Notifications</h4>
                    <ul>
                        <?php if (empty($userStatusNotifications)): ?>
                            <li>No new notifications.</li>
                        <?php else: ?>
                            <?php foreach ($userStatusNotifications as $notif): ?>
                                <li>
                                    <a href="#" class="notification-item" onclick="event.preventDefault(); openNotificationModal(<?= htmlspecialchars(json_encode($notif)) ?>);">
                                        <div class="notification-type">
                                            <?= htmlspecialchars($notif['type']) ?> - <?= htmlspecialchars(strtoupper($notif['status'])) ?>
                                        </div>
                                        <div class="notification-details">
                                            <?= htmlspecialchars(substr((string)$notif['details'], 0, 50)) ?>...
                                        </div>
                                        <div class="notification-date">
                                            <?= date('M d, Y H:i', strtotime($notif['date'])) ?>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <hr class="hrtop1">

        <div class="main2">
            <!-- Home Section -->
            <div class="home" id="home">
                   <div class="homeparent">
                
                <div class="content3">
                    <div class="descrip">
                        <br>
                        <p class="par1">Barangay 292</p><p class="w">Welcome to Your</p>
<p class="e" >E-Services Portal</p>
 <p class="f">Fast, transparent, and accessible barangay services for every resident. </p>
                    </div>
                    <div class="image-container">
                        <img class="barangay" src="images/barangay.jpg" alt="Barangay">
                    </div>
                </div>
            </div>
                <div class="content4">
                    <div class="announcement">
                             <div class="nameflex2">
                                <div class="logo-container">
    <img class="logoa" src="images/marketing.png" alt="Logo">
</div>
                        <h4 class="h41">Announcement</h4>
                         
                       </div>
                        <hr class="hr2">

                        <!-- latest announcement only -->
                       <div class="textareaannouncement">
  <div class="announcement-row">
      
        <span  class="fprog">
            <?= htmlspecialchars($ann['event_type']) ?>
        </span>
    </div>
    <div class="announcement-row">
        <span class="date">Date:</span>
        <span class="date">
            <?= date('M d, Y', strtotime($ann['event_date'])) ?>
        </span>
    </div>

    <?php if(!empty($ann['event_time'])): ?>
    <div class="timecon">
        <span class="time">Time:</span>
        <span class="time">
            <?= date('h:i A', strtotime($ann['event_time'])) ?>
        </span>
    </div>
    <?php endif; ?>

  

    <div class="announcement-row posted">
        <span class="posted">Posted:</span>
        <span class="posted">
            <?= date('M d, Y h:i A', strtotime($ann['created_at'])) ?>
        </span>
    </div>

    <div class="submitannounce">
         <input class="announcebtn"type="button"  value="View Previous" >
    </div>

</div>
                    </div>

                     <div class="office">
                        <div class="nameflex3">
                            <div class="logo-container2">
    <img class="logoc" src="images/emergency.png" alt="Logo">
</div>
                        <h4 class="h42">Emergency Hotlines</h4>
                          
                    </div>
                        <hr class="hr3">

                        <div class="officeflex">
                        <div class="upcon"><p class="upper">Barangay Hall</p><p class="lower"style="color:green;">0000-0000-0000</p></div>
                            <div class="upcon"><p class="upper">PNP</p><p class="lower" style="color:#1f3a93;">0000-0000-0000</p></div>
                             <div class="upcon"><p class="upper">Fire Department</p><p class="lower"style="color:red;">0000-0000-0000</p></div>
                              <div class="upcon"><p class="upper">Hospital</p><p class="lower"style="color: #3498db;">0000-0000-0000</p></div>
                          </div>
                    </div>
                </div>
               <div class="content5">
               <div class="emergency">
                        <div class="nameflex3">
                             <div class="logo-container3">
    <img class="logod" src="images/stetoscope.png" alt="Logo">
</div>
                        <h4 class="h42">Community Consultation</h4>
                        
                    </div>
                        <hr class="hr4">
                        <div class="par2">
                            <div class="concon">
                            <p class="upper2">Monday</p><p class="mid"> Traditional Chinese Medicine</p>
                            <p class="lower2">9:00 am - 12:00 pm (By Appointment)</p>
                        </div>
                         <div class="concon">
                            <p class="upper2">Wednesday</p><p class="mid">Surgery</p>
                            <p class="lower2">1:30 pm - 3:30 pm</p>
                        </div>
                         <div class="concon">
                            <p class="upper2">Thursday</p><p class="mid">Pediatrics</p>
                            <p class="lower2">1:00 pm - 4:00 pm</p>
                        </div>
                         <div class="concon">
                            <p class="upper2">Friday</p><p class="mid">Internal Medicine</p>
                            <p class="lower2">1:30 pm - 3:30 pm</p>
                        </div>
                         <div class="concon">
                            <p class="upper2">Saturday</p><p class="mid">Obstetrics, Gynecology & Opthalmology</p>
                            <p class="lower2">9:00 am - 12:00 pm</p>
                        </div>
                            </div>
                    </div>
                       <div class="aboutus">
                        <div class="nameflex3">
                            <div class="logo-containerd">
    <img class="logoe" src="images/drugs.png" alt="Logo">
</div>
                        <h4 class="h42">Free medication</h4>
                       
                    </div>
                        <hr class="hrd">
                            <div class="par3">
                            <div class="concon">
                            <p class="upper3">Metformin</p><p class="lower2">Blood sugar control</p>
                          
                        </div>
                         <div class="concon">
                            <p class="upper3">Losartan</p><p class="lower2">Blood pressure & Kidney Protection</p>
                           
                        </div>
                         <div class="concon">
                            <p class="upper3">Vitamin B12</p><p class="lower2">Nerve support</p>
                           
                        </div>
                         <div class="concon">
                            <p class="upper3">Amoxicillin</p><p class="lower2">Bacterial infection</p>
                         
                        </div>
                         <div class="concon">
                            <p class="upper3">Amlodipine</p><p class="lower2">Blood Pressure and Heart Support</p>
                          
                        </div>
                            </div>
                    </div>

                </div>
            </div>

            <!-- Request Section -->
            <div class="request" id="request" style="display:none;">
                <div style="display: flex; align-items:flex-start; margin-bottom: 8px;">
                <div class="logo-containerreq">
    <img class="logoreq" src="images/report.png" alt="Logo">
   
</div>
   <h4 class="requestname">Service Request</h4>
</div>
            
                <hr class="hr1">
                <!-- ✅ ADDED id="requestForm" -->
                <form method="POST" action="" id="requestForm">
                    <div class="formcontainer">
                        <div class="minform">
                            <label class="doclist" for="docs">Document:</label>
                            <select class="docselect" name="docs" id="docs" onchange="toggleGuardian(this)">
                                <option value="Barangay Clearance">Barangay Clearance</option>
                                <option value="Barangay Indigency">Barangay Indigency</option>
                                <option value="Barangay ID">Barangay ID</option>
                                <option value="First Time Job Seeker">First Time Job-Seeker</option>
                            </select>

                            <div class="notecontainer">
                                <p class="note"><b style="color: #443d3d;">Please be adviced</b> that Business Permits are not issued at the Barangay level. All applications for Business Permits must be processed at the City Hall. Thank you for your understanding.</p>
                            </div>

                            <div class="miniform">
                                <div class="miniform1">
                                    <label for="fullname" class="nametext">Full Name</label><br>
                                    <input class="nametextbox" type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($_SESSION["fullname"]) ?>"required><br>

                                    <label for="address" class="addresstext">Address</label><br>
                                    <input class="nametextbox" type="text" id="address" name="address" value="<?= htmlspecialchars($currentUser['address']) ?>" required><br>

                                    <label for="purpose" class="purposetext">Purpose</label><br>
                                    <input class="nametextbox" type="text" id="purpose" name="purpose" required>
                                </div>

                                <div class="miniform2" id="miniform2" style="display:none;">
                                    <label for="guardian_name" class="purposetext">Name of Guardian</label><br>
                                    <input class="nametextbox" type="text" id="guardian_name" name="guardian_name"><br>

                                    <label for="guardian_address" class="purposetext">Address of Guardian</label><br>
                                    <input class="nametextbox" type="text" id="guardian_address" name="guardian_address"><br>

                                    <label for="guardian_contact" class="purposetext">Contact No. of Guardian</label><br>
                                    <input class="nametextbox" type="text" id="guardian_contact" name="guardian_contact">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="submitcontainer">
                        <!-- ✅ CHANGED to type="button" so modal shows first -->
                        <input type="button" class="btnsubmit" value="Submit" id="idsubmit">
                        <!-- ✅ Hidden real submit flag for PHP -->
                        <input type="hidden" name="submit_request" value="1">
                    </div>
                </form>
            </div>

            <!-- Report Section -->
            <div class="report" id="report" style="display:none;">
                    <div style="display: flex; align-items:flex-start; margin-bottom: 8px;">
                <div class="logo-containerreq">
    <img class="logoreq" src="images/application.png" alt="Logo">
   
</div>
   <h4 class="reportname">Incident Report Form</h4>
</div>
                <hr class="hr1">
               <form method="POST" enctype="multipart/form-data" id="reportForm">
                    <div class="formcontainerReport">
                        <div class="minformReport">
                            <label class="doclistReport" for="docsReport">Reason:</label>
                            <select class="docselectReport" name="docsReport" id="docsReport">
                                <option value="Noise Disturbance">Noise Disturbance</option>
                                <option value="Illegal Parking">Illegal Parking</option>
                                <option value="Loitering">Loitering</option>
                                <option value="Vandalism">Vandalism</option>
                                <option value="Domestic Dispute">Domestic Dispute</option>
                                <option value="Suspicious Activity">Suspicious Activity</option>
                                <option value="Others">Others</option>
                            </select>

                            <div class="notecontainerReport">
                                <p class="noteReport"><b style="color: #443d3d;">Important Notice:</b> Some report reasons may require proof and verification before any action is taken by the Barangay. Please ensure that all information provided is accurate and truthful, as any false, misleading, or troll reports may result in proper compensation or penalties in accordance with Barangay rules and regulations. If you do not know the name of the person being reported, you may leave it blank and instead provide the complete address and clear details to help with verification.</p>
                            </div>

                            <div class="miniformReport">
                                <div class="miniform1">
                                    <label for="reason" class="Reasontext">Reason</label><br>
                                    <input class="Reasontextbox" type="text" id="reason" name="reason" required>
                                    <br>
                                    <label for="person" class="persontext">Person Being Reported</label><br>
                                    <input class="persontextbox" type="text" id="person" name="person" required>
                                    <br>
                                    <label for="report_address" class="addressReporttext">Address</label><br>
                                    <input class="nametextbox" type="text" id="report_address" name="address" required>
                                </div>

                                <div class="miniform3">
                                    <label for="proof" class="prooftext">Proof</label><br>
                                    <input class="podfileproof" type="file" id="proof" name="proof" required>
                                    <br>
                                    <label for="specify" class="specifytext">Specify</label><br>
                                    <div class="specifyReportdiv">
                                        <textarea class="specifyReport" id="specify" name="specify" required></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                      <div class="submitcontainerReport">
                        <input class="btnsubmitReport" type="submit" name="submit_report" value="Submit">
                    </div>
                </form>
            </div>

            <!-- Donate Section -->
            <div class="donate" id="donate" style="display:none;">
               <div style="display: flex; align-items:flex-start; margin-bottom: 8px;">
                <div class="logo-containerreq">
    <img class="logoreq" src="images/donation.png" alt="Logo">
   
</div>
   <h4 class="donatename">Support The Community</h4>
</div>
                <hr class="hr1">
                <form id="donateForm" method="POST" action="" enctype="multipart/form-data">
                    <div class="donatecontainer">
                        <div class="donatetext">
                            <p class="dtext"><b class="bold"style="color: #443d3d;">Help Support Our Barangay</b><br>
                            Your generosity helps us improve community programs, maintain public spaces, and support initiatives that benefit everyone. Every contribution big or small creates a meaningful impact in strengthening our Barangay.<br><br>
                            You may also include a short message or specify the purpose of your donation in the space provided below whether it is for community projects, emergency assistance, youth programs, or other causes close to your heart. This helps ensure your donation is directed where it is needed most.
                            </p>
                            <textarea name="donatetextarea" class="donatetextarea"></textarea>
                        </div>
                        <div class="qrdonatecontainer">
                            <img class="qrlogo" src="images/qr.png" alt="QR Code">
                            <div class="labeldonate">
                                <label for="pod" class="podtext">Upload Proof of Donation</label>
                            </div><br>
                            <input class="podfile" type="file" id="pod" name="pod" required>
                        </div>
                    </div>
                    <div class="dsubmitcontainer">
                        <input class="btnsubmitdonate" type="submit" name="submit" value="Submit">
                    </div>
                </form>
            </div>

            <!-- Feedback Section -->
            <div class="feedback" id="feedback" style="display:none;">
                   <div style="display: flex; align-items:flex-start; margin-bottom: 8px;">
                <div class="logo-containerreq">
    <img class="logoreq" src="images/feedback-icon.png" alt="Logo">
   
</div>
   <h4 class="donatename">Comments & Suggestions</h4>
</div>
                <hr class="hr1">
                <form id="feedbackForm" method="POST" action="">
                    <div class="feedbackcontainer">
                        <div class="feedbacktext">
                            <p class="ptext"><b class="bold"style="color: #443d3d;">Help us shape the future of our Barangay</b><br>
                            Share your ideas, suggestions, and feedback on community life or ways we can make our online platform better. Together, let's build a more vibrant community both offline and online.<br><br>
                            By working collaboratively, we can foster a safe, inclusive, and supportive space where everyone feels heard, connected, and empowered to participate and contribute.
                            </p>
                        </div>
                    </div>
                    <textarea name="feedbackta" class="textarea"></textarea>
                    <div class="fbsubmitcontainer">
                        <input class="btnfb"type="submit" name="submit" value="Submit">
                    </div>
                </form>
            </div>

            <!-- Officials Section (unchanged) -->
            <div class="content2">
                <div class="officials" id="brgyofficials">
                    <div class="officialtextcontainer">
                         <div class="logo-containerbr">
    <img class="logobrgy" src="images/group.png" alt="Logo">
</div>
                        <h4 class="brgyofficialtext">Barangay Officials</h4>
                         
                    </div>
                    <hr class="hr5">
                    <div class="officials-list">
                        <div class="pics">
                            <img class="pic" src="images/Chairman.png" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Hance Kerwin Santos GAW<br><i>Chairman</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/Secretary.png" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Claire Agotero Valbuena<br><i>Secretary</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/Treasurer.png" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Alexander Tio Chua<br><i>Treasurer</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/KagawadHelen.png" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Helen Uy Tio<br><i>Kagawad</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/KagawadAlvin.png" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Alvin Glenn Lau Pua<br><i>Kagawad</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/KagawadAntonio.png" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Antonio Co Cua Lee<br><i>Kagawad</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/KagawadCorazon.png" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Corazon Pua Sia<br><i>Kagawad</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/KagawadAarone.png" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Aarone Kedrick Calijan Teng<br><i>Kagawad</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/KagawadAlexander.png" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Alexander Ho Sia<br><i>Kagawad</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/KagawadGilbert.png" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Gilbert Tio Laddaran<br><i>Kagawad</i></h5>
                            </div>
                        </div>
                    </div>
                    <hr class="hr6">
                    <div class="skcontainer" onclick="showSK()">
                        <button><b class="b3">View SK Officials</b></button>
                    </div>
                </div>

                <div class="officials2" id="brgyskofficials" style="display:none;">
                    <div class="skofficialtextcontainer">
                                    <div class="logo-containerbr">
    <img class="logobrgy" src="images/group.png" alt="Logo">
</div>
                        <h4 class="sktext">S.K Officials</h4>
                      
                    </div>
                    <hr class="hr5">
                    <div class="officials-list">
                        <div class="pics">
                            <img class="pic" src="images/skchairman.jpg" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Emily Charr Villaram<br><i>SK Chairman</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/skmaxine.jpg" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Maxine Sabile<br><i>SK Secretary</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/skrea.jpg" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Rea Jean Gabo<br><i>SK Treasurer</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/divine.jpg" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Divine Abanador<br><i>SK Kagawad</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/skteng.jpg" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Darrly Teng<br><i>SK Kagawad</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/skbaby.jpg" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Baby Joy Atibagos<br><i>SK Kagawad</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/skqueenie.jpg" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Quinnie Perante<br><i>SK Kagawad</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/divine.jpg" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Alvin Abrique<br><i>SK Kagawad</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/skshela.jpg" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Sheila Salagantin<br><i>SK Kagawad</i></h5>
                            </div>
                        </div>
                        <div class="pics">
                            <img class="pic" src="images/skchristian.jpg" alt="Official">
                            <div class="h5container">
                                <h5 class="text2">Christian Magtubo<br><i>SK Kagawad</i></h5>
                            </div>
                        </div>
                    </div>
                    <hr class="hr6">
                    <div class="skcontainer" onclick="showOfficials()">
                        <button><b class="b3">View Barangay Officials</b></button>
                    </div>
                </div>

                <div class="botright">
                    <div style="display: flex;    justify-content: center;"> <p class="pbr">Contacts Us</p></div>
                    <hr style="margin-top: 10px; border: 0.5px solid rgba(255, 255, 255, 0.4); width:100%;">
                    <div>
                        <div class="phone">
                            <img class="logop" src="images/telephone.png" alt="Official">
                            <div><p class="ttext">Telephone</p><p class="tnumber">734 - 61 - 57</p></div>
                            
                        </div>
                        <div class="phone">
                            <img class="logop" src="images/cellphone.png" alt="Official">
                            <div><p class="mtext">Mobile</p><p class="mnumber">0935 - 219 - 8168</p></div>
                            
                        </div>
                         <div class="phone">
                            <img class="logofb" src="images/gmail.png" alt="Official">
                            <img class="logogm" src="images/facebook.png" alt="Official">
                            
                            
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ REQUEST CONFIRM MODAL -->
<div id="requestConfirmModal" class="modal">
    <div class="modal-content" style="max-width:520px;">
        <span class="close" data-close="requestConfirmModal">&times;</span>
          <h3 style="color:green">Important Notice</h3>
            <hr style="width:100%">
         <div class="confirm-text">
<i>After your document request has been approved, you are required to personally claim the document at the barangay office. Failure to claim the approved document may result in possible penalties.

Please ensure that you claim your document promptly to avoid any inconvenience.</i>

        </div>

        <div class="btn-row" style="justify-content:flex-end;">
            <button type="button" class="btn btn-secondary" id="btnReqNo">Cancel</button>
            <button type="button" class="btn btn-primary" id="btnReqYes">Submit</button>
        </div>
    </div>
</div>

<!-- NOTIFICATION DETAIL MODAL -->
<div id="notificationDetailModal" class="modal">
    <div class="modal-content" style="max-width:520px;">
        <span class="close" data-close="notificationDetailModal">&times;</span>
        <div style="display: flex; align-items:flex-start; margin-left:0px;">
         <div class="logo-containerinfo">
    <img class="logoa" src="images/info1.png" alt="Logo">
</div>
        <h3 style="margin-top: 3px; margin-left:5px; ">Notification Details</h3>
    </div>
        <hr style="width:99%;">
        <table class="info-table">
            <tr><td class="info-key">Type</td><td class="info-val" id="notifModalTypeText">-</td></tr>
            <tr><td class="info-key">Status</td><td class="info-val" id="notifModalStatus">-</td></tr>
            <tr><td class="info-key">Message</td><td class="info-val" id="notifModalMessage" style="white-space:pre-line;">-</td></tr>
            <tr><td class="info-key">Reason</td><td class="info-val" id="notifModalReason" style="white-space:pre-line;">-</td></tr>
            <tr><td class="info-key">Date</td><td class="info-val" id="notifModalDate">-</td></tr>
        </table>
     
    </div>
</div>

<!-- ANNOUNCEMENTS MODAL (ALL ANNOUNCEMENTS) -->
<div id="announcementsModal" class="modal">
    <div class="modal-contentannounce">
        <span class="close" data-close="announcementsModal">&times;</span>
        <div style="display: flex; align-items:flex-start; margin-left5:5px;">
                    <div class="logo-container" style="margin-left: 0px;">
    <img class="logoa" src="images/marketing.png" alt="Logo">
</div>
        <h3 style="margin-top: 3px; margin-left:5px;">Previous Announcements</h2>
    </div>

      <div class="announcement-container">
    <?= $allAnnouncementsText ?>
</div>
        <div class="btn-row">
            <button class="btn btn-secondary" type="button" data-close="announcementsModal" style="width:100%; color:white; background-color:gray;">Close</button>
        </div>
    </div>
</div>

<!-- VIEW INFORMATION MODAL -->
<div id="viewInfoModal" class="modal">
    <div class="modal-content">
        <span class="close" data-close="viewInfoModal">&times;</span>
        <div style="display: flex; align-items:flex-start;">
         <div class="logo-container" style="margin-left: 0px;">
    <img class="logoa" src="images/information.png" alt="Logo">
</div>
        <h3 style="  color: #443d3d; margin-top:3px; margin-left:5px;">Account Information</h3>
    </div>
        <hr style="width:100%; margin-bottom: 10px;   border: 1px solid rgba(150, 202, 149, 0.5);">

        <div class="profile-preview">
            <?php if ($hasImg): ?>
                <img src="<?= htmlspecialchars($img) ?>" alt="Profile">
            <?php else: ?>
                <img src="images/usericon.png" alt="Profile">
            <?php endif; ?>
            <div>
                <div style="font-weight:bold; color: #443d3d;"><?= htmlspecialchars($currentUser['fullname']) ?></div>
                <div style="font-size:13px;color: #443d3d;"><?= htmlspecialchars($currentUser['role']) ?></div>
            </div>
        </div>

        <table class="info-table">
            <tr><td class="info-key">Fullname</td><td class="info-val"><?= htmlspecialchars($currentUser['fullname']) ?></td></tr>
            <tr><td class="info-key">Birthdate</td><td class="info-val"><?= htmlspecialchars($currentUser['birthdate']) ?></td></tr>
            <tr><td class="info-key">Gender</td><td class="info-val"><?= htmlspecialchars($currentUser['gender']) ?></td></tr>
            <tr><td class="info-key">Civil Status</td><td class="info-val"><?= htmlspecialchars($currentUser['civil_status']) ?></td></tr>
            <tr><td class="info-key">Address</td><td class="info-val"><?= htmlspecialchars($currentUser['address']) ?></td></tr>
            <tr><td class="info-key">Phone</td><td class="info-val"><?= htmlspecialchars($currentUser['phone']) ?></td></tr>
            <tr><td class="info-key">Email</td><td class="info-val"><?= htmlspecialchars($currentUser['email']) ?></td></tr>
            <tr><td class="info-key">Username</td><td class="info-val"><?= htmlspecialchars($currentUser['username']) ?></td></tr>
            <tr><td class="info-key">Password</td><td class="info-val">********</td></tr>
            <tr><td class="info-key">ISF</td><td class="info-val"><?= htmlspecialchars($currentUser['isf']) ?></td></tr>
            <tr><td class="info-key">Household Head</td><td class="info-val"><?= htmlspecialchars($currentUser['household_head']) ?></td></tr>
            <tr><td class="info-key">PWD</td><td class="info-val"><?= htmlspecialchars($currentUser['pwd']) ?></td></tr>
            <tr><td class="info-key">Solo Parent</td><td class="info-val"><?= htmlspecialchars($currentUser['solo_parent']) ?></td></tr>
            <tr><td class="info-key">PWD Proof</td><td class="info-val"><?= htmlspecialchars($currentUser['pwd_proof'] ?? '—') ?></td></tr>
            <tr><td class="info-key">Solo Parent Proof</td><td class="info-val"><?= htmlspecialchars($currentUser['solo_parent_proof'] ?? '—') ?></td></tr>
            <tr><td class="info-key">Role</td><td class="info-val"><?= htmlspecialchars($currentUser['role']) ?></td></tr>
            <tr><td class="info-key">Status</td><td class="info-val"><?= htmlspecialchars($currentUser['status']) ?></td></tr>
            <tr><td class="info-key">Profile Image</td><td class="info-val"><?= htmlspecialchars($currentUser['profile_image'] ?? '—') ?></td></tr>
        </table>

        <div class="btn-row">
            <button class="btn btn-primary" type="button" id="btnShowEdit">Edit</button>
            <button 
  class="btn btn-secondary" 
  type="button" 
  data-close="viewInfoModal"
  style="background-color:#6c757d; color:white;"
  onmouseover="this.style.backgroundColor='#5a6268'"
  onmouseout="this.style.backgroundColor='#6c757d'">
  Close
</button>
        </div>

        <div class="edit-box" id="editBox">
                <div style="display: flex; align-items:flex-start;">
            <div class="logo-container" style="margin-left: 0px;">
    <img class="logoa" src="images/edit-info.png" alt="Logo">
</div>
            <h3 style="margin-top:4px; margin-left:5px; color: #443d3d;">Update Information</h3>
        </div>
            <hr style="width:100%; margin-top: 0px;   border: 1px solid rgba(150, 202, 149, 0.5);">

            <form method="POST" enctype="multipart/form-data">
                <label style="color:gray;">fullname</label>
                <input type="text" name="fullname" value="<?= htmlspecialchars($currentUser['fullname']) ?>" required>

                <label style="color:gray;">birthdate</label>
                <input type="date" name="birthdate" value="<?= htmlspecialchars($currentUser['birthdate']) ?>" required>

                <label style="color:gray;">gender</label>
                <select name="gender" required>
                    <option value="male"   <?= ($currentUser['gender'] === 'male') ? 'selected' : '' ?>>male</option>
                    <option value="female" <?= ($currentUser['gender'] === 'female') ? 'selected' : '' ?>>female</option>
                    <option value="other"  <?= ($currentUser['gender'] === 'other') ? 'selected' : '' ?>>other</option>
                </select>

                <label style="color:gray;">address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($currentUser['address']) ?>" required>

                <label style="color:gray;">phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($currentUser['phone']) ?>" required>

                <label style="color:gray;">username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($currentUser['username']) ?>" required>

                <label style="color:gray;">profile_image (optional)</label>
                <input type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp">

                <button class="btn btn-primary" type="submit" name="update_info" style="margin-top:12px;">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<!-- CHANGE PASSWORD MODAL -->
<div id="changePassModal" class="modal">
    <div class="modal-contentpass">
        <span class="close" data-close="changePassModal">&times;</span>
        <div style="display: flex; align-items:flex-start;">
         <div class="logo-container" style="margin-left: 0px;">
    <img class="logoa" src="images/reset-password.png" alt="Logo">
</div>
  <h3 style="margin-top:4px; margin-left:5px; color: #443d3d;">Change Password</h3>
</div>
        
        <hr class="hrinfo">

         <form method="POST">
            <label style="display:block;font-weight:normal;margin:10px 0 6px; color:gray;">Current Password</label>
            <input type="password" name="current_password" required style="width:95%;padding:10px;border:1px solid #ccc;border-radius:6px;">

            <label style="display:block;font-weight:normal;margin:10px 0 6px; color:gray;">New Password</label>
            <input type="password" name="new_password" required style="width:95%;padding:10px;border:1px solid #ccc;border-radius:6px;">

            <label style="display:block;font-weight:normal;margin:10px 0 6px;color:gray;">Confirm New Password</label>
            <input type="password" name="confirm_password" required style="width:95%;padding:10px;border:1px solid #ccc;border-radius:6px;">

            <button type="submit" name="change_password" class="btn btn-primary" style="margin-top:12px;width:100%;">Update Password</button>
        </form>
    </div>
</div>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const reportForm = document.getElementById("reportForm");

    if (reportForm) {
        reportForm.addEventListener("submit", function (e) {
            e.preventDefault();

            const ok = confirm("Are you sure you want to submit this report?");
            if (!ok) return;

            const submitBtn = reportForm.querySelector('input[type="submit"]');
            const originalBtnText = submitBtn ? submitBtn.value : null;

            const formData = new FormData(reportForm);
            formData.append("submit_report", "1");

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.value = 'Submitting...';
            }

            fetch("homepage.php?section=report", {
                method: "POST",
                body: formData
            })
            .then(response => response.json().then(data => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                if (ok && data && data.status === 'success') {
                    alert(data.message || 'Report submitted successfully!');
                    reportForm.reset();
                } else {
                    alert((data && data.message) ? data.message : 'Error submitting report.');
                }
            })
            .catch(() => {
                alert("Something went wrong while submitting the report.");
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    if (originalBtnText !== null) submitBtn.value = originalBtnText;
                }
            });
        });
    }
});

    // Additional AJAX handlers for Donate and Feedback (confirm + no-reload)
    document.addEventListener("DOMContentLoaded", function () {
        // Donate form
        const donateForm = document.getElementById('donateForm');
        if (donateForm) {
            donateForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const ok = confirm('Are you sure you want to submit this donation?');
                if (!ok) return;

                const submitBtn = donateForm.querySelector('input[type="submit"]');
                const originalBtnText = submitBtn ? submitBtn.value : null;

                const formData = new FormData(donateForm);

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.value = 'Submitting...';
                }

                fetch('homepage.php?section=donate', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    if (ok && data && data.status === 'success') {
                        alert(data.message || 'Donation submitted successfully!');
                        donateForm.reset();
                    } else {
                        alert((data && data.message) ? data.message : 'Error submitting donation.');
                    }
                })
                .catch(() => {
                    alert('Something went wrong while submitting the donation.');
                })
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        if (originalBtnText !== null) submitBtn.value = originalBtnText;
                    }
                });
            });
        }

        // Feedback form
        const feedbackForm = document.getElementById('feedbackForm');
        if (feedbackForm) {
            feedbackForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const ok = confirm('Are you sure you want to submit your feedback?');
                if (!ok) return;

                const submitBtn = feedbackForm.querySelector('input[type="submit"]');
                const originalBtnText = submitBtn ? submitBtn.value : null;

                const formData = new FormData(feedbackForm);

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.value = 'Submitting...';
                }

                fetch('homepage.php?section=feedback', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    if (ok && data && data.status === 'success') {
                        alert(data.message || 'Feedback submitted successfully!');
                        feedbackForm.reset();
                    } else {
                        alert((data && data.message) ? data.message : 'Error submitting feedback.');
                    }
                })
                .catch(() => {
                    alert('Something went wrong while submitting the feedback.');
                })
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        if (originalBtnText !== null) submitBtn.value = originalBtnText;
                    }
                });
            });
        }
    });

    function showSection(sectionId, el) {
        const sections = ['home', 'request', 'report', 'donate', 'feedback'];
        sections.forEach(id => {
            const node = document.getElementById(id);
            if (node) node.style.display = (id === sectionId) ? 'block' : 'none';
        });

        const buttons = document.querySelectorAll('.btncontainer');
        buttons.forEach(btn => btn.classList.remove('active'));
        if (el) el.classList.add('active');
    }

    function toggleGuardian(select) {
        const miniform2 = document.getElementById('miniform2');
        if (miniform2) miniform2.style.display = (select.value === 'Barangay ID') ? 'block' : 'none';
    }

    function showSK() {
        document.getElementById('brgyofficials').style.display = 'none';
        document.getElementById('brgyskofficials').style.display = 'block';
    }

    function showOfficials() {
        document.getElementById('brgyofficials').style.display = 'block';
        document.getElementById('brgyskofficials').style.display = 'none';
    }

    function toggleNotifications() {
        const dropdown = document.getElementById('notificationDropdown');
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }

    function openNotificationModal(notif) {
        document.getElementById('notifModalTypeText').textContent = notif.type || '-';
        document.getElementById('notifModalStatus').textContent = notif.status || '-';
        document.getElementById('notifModalMessage').textContent = notif.details || '-';
        document.getElementById('notifModalReason').textContent = notif.reason || '-';
        document.getElementById('notifModalDate').textContent = new Date(notif.date).toLocaleString() || '-';
        const modal = document.getElementById('notificationDetailModal');
        if (modal) modal.style.display = 'block';
    }

    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('notificationDropdown');
        const container = e.target.closest('.notification-container');
        if (!container && dropdown) dropdown.style.display = 'none';
    });

    // open announcements modal
    function openAnnouncementsModal(){
        const modal = document.getElementById('announcementsModal');
        if (modal) modal.style.display = 'block';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const profileIcon = document.getElementById('profileIcon');
        const profileDropdown = document.getElementById('profileDropdown');

        profileIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
            profileIcon.setAttribute('aria-expanded', profileDropdown.classList.contains('active'));
        });

        profileIcon.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                profileIcon.click();
            }
        });

        document.querySelectorAll('.dropdown-item[data-modal]').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const modalId = this.getAttribute('data-modal');
                const modal = document.getElementById(modalId);
                if (modal) modal.style.display = 'block';
                profileDropdown.classList.remove('active');
                profileIcon.setAttribute('aria-expanded', 'false');
            });
        });

        document.addEventListener('click', function(e) {
            if (!profileIcon.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('active');
                profileIcon.setAttribute('aria-expanded', 'false');
            }
        });

        document.querySelectorAll('[data-close]').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-close');
                const modal = document.getElementById(id);
                if (modal) modal.style.display = 'none';
            });
        });

        window.addEventListener('click', function(e) {
            document.querySelectorAll('.modal').forEach(m => {
                if (e.target === m) m.style.display = 'none';
            });
        });

        const btnShowEdit = document.getElementById('btnShowEdit');
        const editBox = document.getElementById('editBox');
        if (btnShowEdit && editBox) {
            btnShowEdit.addEventListener('click', function(){
                editBox.style.display = (editBox.style.display === 'block') ? 'none' : 'block';
            });
        }

        // clicking the latest announcement textarea opens modal too
        const annTa = document.querySelector('.submitannounce');
        if (annTa) {
            annTa.style.cursor = 'pointer';
            annTa.addEventListener('click', function(){
                openAnnouncementsModal();
            });
        }

        /* ✅ REQUEST CONFIRM MODAL LOGIC + PER-DOCUMENT LIMIT (CLIENT SIDE PRE-CHECK) */
        const requestedDocs = <?php echo json_encode($requestedDocs); ?>;

        const requestForm = document.getElementById('requestForm');
        const submitBtn   = document.getElementById('idsubmit');
        const docsSelect = document.getElementById('docs');

        if (submitBtn && requestForm) {
            submitBtn.addEventListener('click', function(e){
                const selectedDoc = docsSelect ? docsSelect.value : '';

                // block immediately if already requested this specific document
                if (selectedDoc && requestedDocs.includes(selectedDoc)) {
                    alert('You already requested this document. You can only request each document once.');
                    return;
                }

                const ok = confirm('Are you sure you want to submit this request?');
                if (!ok) return;

                const submitBtnInner = submitBtn;
                const originalBtnText = submitBtnInner ? submitBtnInner.value : null;

                const formData = new FormData(requestForm);

                if (submitBtnInner) {
                    submitBtnInner.disabled = true;
                    submitBtnInner.value = 'Submitting...';
                }

                fetch('homepage.php?section=request', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    if (ok && data && data.status === 'success') {
                        alert(data.message || 'Request submitted successfully!');
                        requestForm.reset();
                    } else {
                        alert((data && data.message) ? data.message : 'Error submitting request.');
                    }
                })
                .catch(() => {
                    alert('Something went wrong while submitting the request.');
                })
                .finally(() => {
                    if (submitBtnInner) {
                        submitBtnInner.disabled = false;
                        if (originalBtnText !== null) submitBtnInner.value = originalBtnText;
                    }
                });
            });
        }

        if (btnYes && requestForm) {
            btnYes.addEventListener('click', function(){
                // ✅ THIS IS WHERE IT GOES
                requestForm.submit();
            });
        }
    });
window.onload = function() {

    const params = new URLSearchParams(window.location.search);
    const section = params.get("section");

    if(section){
        const button = document.querySelector(`.btncontainer[onclick*="${section}"]`);
        showSection(section, button);
    }

};
</script>
</body>
</html>
