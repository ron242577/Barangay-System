<?php
session_start();
include "db.php";

$message = "";
$showModal = false; // flag to trigger success modal display

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Personal info
    $firstName  = trim($_POST["firstName"] ?? "");
    $middleName = trim($_POST["middleName"] ?? "");
    $lastName   = trim($_POST["lastName"] ?? "");
    $fullname   = trim($firstName . " " . $middleName . " " . $lastName);

    $birthdate   = $_POST["birthdate"] ?? null;
    $gender      = $_POST["gender"] ?? null;
    $civilStatus = $_POST["civilStatus"] ?? null;

    // Contact info
    $address = trim($_POST["address"] ?? "");
    $phone   = trim($_POST["contactNumber"] ?? "");
    $email   = trim($_POST["email"] ?? "");

    // Account info
    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $confirmPassword = trim($_POST["confirmPassword"] ?? "");
    // 🔐 HASH THE PASSWORD
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Household / flags
    $isf           = $_POST["isf"] ?? null;
    $householdHead = trim($_POST["householdHead"] ?? "");
    $pwd           = $_POST["pwd"] ?? null;
    $soloParent    = $_POST["soloParent"] ?? null;

    $role   = "resident";
    $status = "pending"; // account pending approval

    // ===== SERVER-SIDE VALIDATION =====
    $validationErrors = [];

    if ($firstName === "") $validationErrors[] = "first name";
    if ($lastName === "") $validationErrors[] = "last name";
    if (empty($birthdate)) $validationErrors[] = "birthdate";
    if (empty($gender)) $validationErrors[] = "gender";
    if (empty($civilStatus)) $validationErrors[] = "civil status";
    if ($address === "") $validationErrors[] = "address";
    if ($username === "") $validationErrors[] = "username";
    if (strlen($password) < 8) $validationErrors[] = "password must be at least 8 characters";
    if ($password !== $confirmPassword) $validationErrors[] = "passwords do not match";
    if ($householdHead === "") $validationErrors[] = "household head name";
    if (empty($isf)) $validationErrors[] = "ISF selection";
    if (empty($pwd)) $validationErrors[] = "PWD selection";
    if (empty($soloParent)) $validationErrors[] = "solo parent selection";

    $cleanPhone = preg_replace('/[-\s]/', '', $phone);
    if (!preg_match('/^09\d{9}$/', $cleanPhone)) {
        $validationErrors[] = "valid contact number";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $validationErrors[] = "valid email address";
    }

    if (!isset($_FILES["proofOfResidency"]) || empty($_FILES["proofOfResidency"]["name"])) {
        $validationErrors[] = "proof of residency";
    }

    if (!empty($validationErrors)) {
        $message = "⚠ Please check the following: " . implode(", ", $validationErrors) . ".";
    } else {

        // ===== DUPLICATE CHECK =====
        $duplicateFields = [];

        // Check email
        if (!empty($email)) {
            $checkEmail = $conn->prepare("SELECT id FROM accounts WHERE email = ?");
            $checkEmail->bind_param("s", $email);
            $checkEmail->execute();
            $checkEmail->store_result();
            if ($checkEmail->num_rows > 0) {
                $duplicateFields[] = "email";
            }
            $checkEmail->close();
        }

        // Check username
        if (!empty($username)) {
            $checkUsername = $conn->prepare("SELECT id FROM accounts WHERE username = ?");
            $checkUsername->bind_param("s", $username);
            $checkUsername->execute();
            $checkUsername->store_result();
            if ($checkUsername->num_rows > 0) {
                $duplicateFields[] = "username";
            }
            $checkUsername->close();
        }

        // Check phone
        if (!empty($phone)) {
            $checkPhone = $conn->prepare("SELECT id FROM accounts WHERE phone = ?");
            $checkPhone->bind_param("s", $phone);
            $checkPhone->execute();
            $checkPhone->store_result();
            if ($checkPhone->num_rows > 0) {
                $duplicateFields[] = "contact number";
            }
            $checkPhone->close();
        }

        if (!empty($duplicateFields)) {
            $message = "⚠ The following field(s) already exist: " . implode(", ", $duplicateFields) . ". Please use different values.";
        } else {

            // ===== FILE UPLOADS =====
            $uploadDir = "uploads/ids/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $pwdProofPath = null;
            if (!empty($_FILES["pwdProof"]["name"])) {
                $ext = strtolower(pathinfo($_FILES["pwdProof"]["name"], PATHINFO_EXTENSION));
                $pwdProofPath = $uploadDir . "pwd_" . time() . "_" . uniqid() . "." . $ext;
                move_uploaded_file($_FILES["pwdProof"]["tmp_name"], $pwdProofPath);
            }

            $soloParentProofPath = null;
            if (!empty($_FILES["soloParentProof"]["name"])) {
                $ext = strtolower(pathinfo($_FILES["soloParentProof"]["name"], PATHINFO_EXTENSION));
                $soloParentProofPath = $uploadDir . "solo_" . time() . "_" . uniqid() . "." . $ext;
                move_uploaded_file($_FILES["soloParentProof"]["tmp_name"], $soloParentProofPath);
            }

            $proofOfResidencyPath = null;
            if (!empty($_FILES["proofOfResidency"]["name"])) {
                $ext = strtolower(pathinfo($_FILES["proofOfResidency"]["name"], PATHINFO_EXTENSION));
                $proofOfResidencyPath = $uploadDir . "residency_" . time() . "_" . uniqid() . "." . $ext;
                move_uploaded_file($_FILES["proofOfResidency"]["tmp_name"], $proofOfResidencyPath);
            }

            // ===== INSERT ACCOUNT =====
            $stmt = $conn->prepare(
                "INSERT INTO accounts 
                (fullname, birthdate, gender, civil_status, address, phone, email, username, password,
                 isf, household_head, pwd, solo_parent, pwd_proof, solo_parent_proof, proof_of_residency, role, status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            );

            $stmt->bind_param(
                "ssssssssssssssssss",
                $fullname,
                $birthdate,
                $gender,
                $civilStatus,
                $address,
                $phone,
                $email,
                $username,
                $hashed_password,
                $isf,
                $householdHead,
                $pwd,
                $soloParent,
                $pwdProofPath,
                $soloParentProofPath,
                $proofOfResidencyPath,
                $role,
                $status
            );

            if ($stmt->execute()) {
                $message = "✅ Registration successful! Your account is pending approval.";
                $showModal = true;

                // Optional: clear POST values after successful registration
                $_POST = [];
            } else {
                $message = "❌ Registration failed: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Barangay 292 E-Services</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #2d7a3e 0%, #1a4d2e 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 900px;
            margin: 0 auto;
            overflow: hidden;
        }

        .register-header {
            background: linear-gradient(135deg, #2d7a3e 0%, #1a4d2e 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            padding: 10px;
            margin: 0 auto 20px;
        }

        .register-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .register-header p {
            font-size: 16px;
            opacity: 0.95;
        }

        .register-form {
            padding: 50px;
        }

        .form-section {
            margin-bottom: 35px;
        }

        .section-title {
            font-size: 20px;
            color: #1a4d2e;
            margin-bottom: 20px;
            font-weight: 700;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group label .required {
            color: #dc3545;
            margin-left: 3px;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="tel"],
        .form-group input[type="date"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: 'DM Sans', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2d7a3e;
            box-shadow: 0 0 0 3px rgba(45, 122, 62, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .radio-group {
            display: flex;
            gap: 30px;
            margin-top: 10px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .radio-option input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #2d7a3e;
        }

        .radio-option label {
            margin: 0;
            cursor: pointer;
            font-weight: 400;
        }

        .file-input-wrapper {
            position: relative;
            margin-top: 10px;
        }

        .file-input-wrapper input[type="file"] {
            padding: 10px;
            border: 2px dashed #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            width: 100%;
        }

        .file-input-wrapper input[type="file"]::-webkit-file-upload-button {
            background: #2d7a3e;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            margin-right: 10px;
        }

        .helper-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .register-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #2d7a3e 0%, #1a4d2e 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            font-family: 'DM Sans', sans-serif;
            margin-top: 20px;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(45, 122, 62, 0.3);
        }

        .register-btn:active {
            transform: translateY(0);
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .modal-content h2 {
            margin-bottom: 15px;
            color: #1a4d2e;
        }

        .modal-content p {
            margin-bottom: 20px;
            font-size: 15px;
            color: #333;
        }

        .modal-actions {
            display: flex;
            justify-content: space-around;
        }

        .modal-actions button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .modal-actions #modalYes {
            background: #2d7a3e;
            color: white;
        }

        .modal-actions #modalNo {
            background: #ccc;
            color: #333;
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #666;
        }

        .login-link a {
            color: #2d7a3e;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #1a4d2e;
        }

        @media (max-width: 768px) {
            .register-form {
                padding: 30px 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .register-header h1 {
                font-size: 26px;
            }

            .section-title {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <img class="logo" src="images/logo2.png" alt="Barangay 292 Logo" onerror="this.style.display='none'">
            <h1>Barangay 292 Registration</h1>
            <p>Create your account to access barangay services</p>
        </div>

        <div class="register-form">
            <form id="registerForm" method="POST" action="" enctype="multipart/form-data">
                
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h2 class="section-title">Personal Information</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name <span class="required">*</span></label>
                            <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($_POST['firstName'] ?? ''); ?>" required>
                            <span class="error-message" id="firstNameError">Please enter your first name</span>
                        </div>

                        <div class="form-group">
                            <label for="middleName">Middle Name</label>
                            <input type="text" id="middleName" name="middleName" value="<?php echo htmlspecialchars($_POST['middleName'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="lastName">Last Name <span class="required">*</span></label>
                            <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($_POST['lastName'] ?? ''); ?>" required>
                            <span class="error-message" id="lastNameError">Please enter your last name</span>
                        </div>

                        <div class="form-group">
                            <label for="birthdate">Date of Birth <span class="required">*</span></label>
                            <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($_POST['birthdate'] ?? ''); ?>" required>
                            <span class="error-message" id="birthdateError">Please select your birthdate</span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="gender">Gender <span class="required">*</span></label>
                            <select id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo (($_POST['gender'] ?? '') === 'male') ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo (($_POST['gender'] ?? '') === 'female') ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo (($_POST['gender'] ?? '') === 'other') ? 'selected' : ''; ?>>Prefer not to say</option>
                            </select>
                            <span class="error-message" id="genderError">Please select your gender</span>
                        </div>

                        <div class="form-group">
                            <label for="civilStatus">Civil Status <span class="required">*</span></label>
                            <select id="civilStatus" name="civilStatus" required>
                                <option value="">Select Civil Status</option>
                                <option value="single" <?php echo (($_POST['civilStatus'] ?? '') === 'single') ? 'selected' : ''; ?>>Single</option>
                                <option value="married" <?php echo (($_POST['civilStatus'] ?? '') === 'married') ? 'selected' : ''; ?>>Married</option>
                                <option value="widowed" <?php echo (($_POST['civilStatus'] ?? '') === 'widowed') ? 'selected' : ''; ?>>Widowed</option>
                            </select>
                            <span class="error-message" id="civilStatusError">Please select your civil status</span>
                        </div>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="form-section">
                    <h2 class="section-title">Contact Information</h2>
                    
                    <div class="form-group full-width">
                        <label for="address">Complete Address <span class="required">*</span></label>
                        <textarea id="address" name="address" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                        <span class="error-message" id="addressError">Please enter your complete address</span>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="contactNumber">Contact Number <span class="required">*</span></label>
                            <input type="tel" id="contactNumber" name="contactNumber" placeholder="09XX-XXX-XXXX" value="<?php echo htmlspecialchars($_POST['contactNumber'] ?? ''); ?>" required>
                            <span class="error-message" id="contactError">Please enter a valid contact number</span>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            <span class="error-message" id="emailError">Please enter a valid email address</span>
                        </div>
                    </div>
                </div>

                <!-- Account Information Section -->
                <div class="form-section">
                    <h2 class="section-title">Account Information</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username <span class="required">*</span></label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            <span class="error-message" id="usernameError">Please enter a username</span>
                        </div>

                        <div class="form-group">
                            <label for="password">Password <span class="required">*</span></label>
                            <input type="password" id="password" name="password" value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>" required>
                            <span class="error-message" id="passwordError">Password must be at least 8 characters</span>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="confirmPassword">Confirm Password <span class="required">*</span></label>
                        <input type="password" id="confirmPassword" name="confirmPassword" value="<?php echo htmlspecialchars($_POST['confirmPassword'] ?? ''); ?>" required>
                        <span class="error-message" id="confirmPasswordError">Passwords do not match</span>
                    </div>
                </div>

                <!-- Household Information Section -->
                <div class="form-section">
                    <h2 class="section-title">Household Information</h2>
                    
                    <div class="form-group full-width">
                        <label>Are you an Informal Settler Family (ISF) member? <span class="required">*</span></label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="isfYes" name="isf" value="yes" <?php echo (($_POST['isf'] ?? '') === 'yes') ? 'checked' : ''; ?> required>
                                <label for="isfYes">Yes</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="isfNo" name="isf" value="no" <?php echo (($_POST['isf'] ?? '') === 'no') ? 'checked' : ''; ?> required>
                                <label for="isfNo">No</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="householdHead">Parent / Household Head Name <span class="required">*</span></label>
                        <input type="text" id="householdHead" name="householdHead" value="<?php echo htmlspecialchars($_POST['householdHead'] ?? ''); ?>" required>
                        <span class="helper-text">If you are the household head, enter your own name</span>
                        <span class="error-message" id="householdHeadError">Please enter household head name</span>
                    </div>

                    <div class="form-group full-width">
                        <label for="proofOfResidency">Proof of Residency <span class="required">*</span></label>
                        <div class="file-input-wrapper">
                            <input type="file" id="proofOfResidency" name="proofOfResidency" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <span class="helper-text">Upload utility bill, lease agreement, or barangay certification (PDF, JPG, PNG - Max 5MB)</span>
                        <span class="error-message" id="proofOfResidencyError">Please upload proof of residency</span>
                    </div>
                </div>

                <!-- PWD Section -->
                <div class="form-section">
                    <h2 class="section-title">Person with Disability (PWD)</h2>
                    
                    <div class="form-group full-width">
                        <label>Are you a Person with Disability (PWD)? <span class="required">*</span></label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="pwdYes" name="pwd" value="yes" <?php echo (($_POST['pwd'] ?? '') === 'yes') ? 'checked' : ''; ?> required onchange="togglePwdProof()">
                                <label for="pwdYes">Yes</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="pwdNo" name="pwd" value="no" <?php echo (($_POST['pwd'] ?? '') === 'no') ? 'checked' : ''; ?> required onchange="togglePwdProof()">
                                <label for="pwdNo">No</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width" id="pwdProofSection" style="display: none;">
                        <label for="pwdProof">PWD ID or Medical Certificate</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="pwdProof" name="pwdProof" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <span class="helper-text">Upload PWD ID or medical certificate (PDF, JPG, PNG - Max 5MB)</span>
                    </div>
                </div>

                <!-- Solo Parent Section -->
                <div class="form-section">
                    <h2 class="section-title">Solo Parent</h2>
                    
                    <div class="form-group full-width">
                        <label>Are you a Solo Parent? <span class="required">*</span></label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="soloParentYes" name="soloParent" value="yes" <?php echo (($_POST['soloParent'] ?? '') === 'yes') ? 'checked' : ''; ?> required onchange="toggleSoloParentProof()">
                                <label for="soloParentYes">Yes</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="soloParentNo" name="soloParent" value="no" <?php echo (($_POST['soloParent'] ?? '') === 'no') ? 'checked' : ''; ?> required onchange="toggleSoloParentProof()">
                                <label for="soloParentNo">No</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width" id="soloParentProofSection" style="display: none;">
                        <label for="soloParentProof">Solo Parent ID or Certificate</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="soloParentProof" name="soloParentProof" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <span class="helper-text">Upload Solo Parent ID or certificate (PDF, JPG, PNG - Max 5MB)</span>
                    </div>
                </div>

                <!-- confirmation modal -->
                <div id="confirmationModal" class="modal" style="display:none;">
                    <div class="modal-content">
                        <h2>Registration Successful</h2>
                        <p>Please wait for a message on your Gmail indicating whether your account has been approved or declined.</p>
                        <p>Do you want to continue to the login page?</p>
                        <div class="modal-actions">
                            <button type="button" id="modalYes">Yes</button>
                            <button type="button" id="modalNo">No</button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="register-btn">Register Account</button>

                <div class="login-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const registerForm = document.getElementById('registerForm');
        const showSuccessModal = <?php echo $showModal ? 'true' : 'false'; ?>;
        const alertMessage = <?php echo json_encode((!empty($message) && !str_starts_with($message, "✅")) ? $message : ""); ?>;

        function showModal() {
            const modal = document.getElementById('confirmationModal');
            modal.style.display = 'flex';
        }

      function togglePwdProof() {
    const pwdYes = document.getElementById('pwdYes');
    const pwdProofSection = document.getElementById('pwdProofSection');
    const pwdProof = document.getElementById('pwdProof');

    if (pwdYes.checked) {
        pwdProofSection.style.display = 'block';
        pwdProof.required = true;
    } else {
        pwdProofSection.style.display = 'none';
        pwdProof.required = false;
        pwdProof.value = '';
    }
}

      function toggleSoloParentProof() {
    const soloParentYes = document.getElementById('soloParentYes');
    const soloParentProofSection = document.getElementById('soloParentProofSection');
    const soloParentProof = document.getElementById('soloParentProof');

    if (soloParentYes.checked) {
        soloParentProofSection.style.display = 'block';
        soloParentProof.required = true;
    } else {
        soloParentProofSection.style.display = 'none';
        soloParentProof.required = false;
        soloParentProof.value = '';
    }
}

        function showError(errorId, inputElement) {
            document.getElementById(errorId).style.display = 'block';
            inputElement.style.borderColor = '#dc3545';
        }

        document.addEventListener('DOMContentLoaded', function() {
            togglePwdProof();
            toggleSoloParentProof();

            if (alertMessage) {
                alert(alertMessage);
            }

            if (showSuccessModal) {
                showModal();
            }

            const yesBtn = document.getElementById('modalYes');
            const noBtn = document.getElementById('modalNo');

            if (yesBtn) {
                yesBtn.addEventListener('click', function() {
                    window.location.href = 'login.php';
                });
            }

            if (noBtn) {
                noBtn.addEventListener('click', function() {
                    document.getElementById('confirmationModal').style.display = 'none';
                });
            }
        });

        registerForm.addEventListener('submit', function(e) {
            let isValid = true;

            document.querySelectorAll('.error-message').forEach(msg => msg.style.display = 'none');
            document.querySelectorAll('input, select, textarea').forEach(input => {
                input.style.borderColor = '#e0e0e0';
            });

            const firstName = document.getElementById('firstName');
            if (firstName.value.trim() === '') {
                showError('firstNameError', firstName);
                isValid = false;
            }

            const lastName = document.getElementById('lastName');
            if (lastName.value.trim() === '') {
                showError('lastNameError', lastName);
                isValid = false;
            }

            const birthdate = document.getElementById('birthdate');
            if (birthdate.value === '') {
                showError('birthdateError', birthdate);
                isValid = false;
            }

            const gender = document.getElementById('gender');
            if (gender.value === '') {
                showError('genderError', gender);
                isValid = false;
            }

            const civilStatus = document.getElementById('civilStatus');
            if (civilStatus.value === '') {
                showError('civilStatusError', civilStatus);
                isValid = false;
            }

            const address = document.getElementById('address');
            if (address.value.trim() === '') {
                showError('addressError', address);
                isValid = false;
            }

            const contactNumber = document.getElementById('contactNumber');
            const phoneRegex = /^09\d{9}$/;
            if (!phoneRegex.test(contactNumber.value.replace(/[-\s]/g, ''))) {
                showError('contactError', contactNumber);
                isValid = false;
            }

            const email = document.getElementById('email');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value)) {
                showError('emailError', email);
                isValid = false;
            }

            const username = document.getElementById('username');
            if (username.value.trim() === '') {
                showError('usernameError', username);
                isValid = false;
            }

            const password = document.getElementById('password');
            if (password.value.length < 8) {
                showError('passwordError', password);
                isValid = false;
            }

            const confirmPassword = document.getElementById('confirmPassword');
            if (password.value !== confirmPassword.value) {
                showError('confirmPasswordError', confirmPassword);
                isValid = false;
            }

            const householdHead = document.getElementById('householdHead');
            if (householdHead.value.trim() === '') {
                showError('householdHeadError', householdHead);
                isValid = false;
            }

            const proofOfResidency = document.getElementById('proofOfResidency');
            if (!proofOfResidency.files || proofOfResidency.files.length === 0) {
                showError('proofOfResidencyError', proofOfResidency);
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                alert('⚠ Please fix the highlighted fields before submitting.');
            }
        });

        document.querySelectorAll('input, select, textarea').forEach(input => {
            input.addEventListener('input', function() {
                this.style.borderColor = '#e0e0e0';
                const errorElement = this.parentElement.querySelector('.error-message');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            });

            input.addEventListener('change', function() {
                this.style.borderColor = '#e0e0e0';
                const errorElement = this.parentElement.querySelector('.error-message');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>