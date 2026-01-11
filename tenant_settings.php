<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$conn = getDBConnection();

// Initialize variables
$current_fullname = '';
$current_phone_number = '';
$current_email = '';
$hashed_password = '';

// Fetch current user data
$stmt_fetch = $conn->prepare("SELECT fullname, phone_number, password, email FROM users WHERE id = ?");
$stmt_fetch->bind_param("i", $user_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();
if ($result_fetch->num_rows > 0) {
    $user_data = $result_fetch->fetch_assoc();
    $current_fullname = $user_data['fullname'];
    $current_phone_number = $user_data['phone_number'];
    $current_email = $user_data['email'];
    $hashed_password = $user_data['password'];
}
$stmt_fetch->close();

// Handle form submission for updating details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_details'])) {
    $new_fullname = trim($_POST['fullname']);
    $new_phone_number = trim($_POST['phone_number']);
    $new_email = trim($_POST['email']);

    if (empty($new_fullname) || empty($new_phone_number) || empty($new_email)) {
        $error_message = "All fields are required.";
    } else {
        $stmt_update = $conn->prepare("UPDATE users SET fullname = ?, phone_number = ?, email = ? WHERE id = ?");
        $stmt_update->bind_param("sssi", $new_fullname, $new_phone_number, $new_email, $user_id);
        
        if ($stmt_update->execute()) {
            $_SESSION['fullname'] = $new_fullname;
            $_SESSION['email'] = $new_email;
            $success_message = "Your details have been updated successfully.";
            // Refresh current data
            $current_fullname = $new_fullname;
            $current_phone_number = $new_phone_number;
            $current_email = $new_email;
        } else {
            $error_message = "Error updating details: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
}

// Handle form submission for changing password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Verify current password
    if (!password_verify($current_password, $hashed_password)) {
        $error_message = "Incorrect current password.";
    } elseif ($new_password !== $confirm_new_password) {
        $error_message = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters long.";
    } else {
        // Hash new password
        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_update_password = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt_update_password->bind_param("si", $hashed_new_password, $user_id);

        if ($stmt_update_password->execute()) {
            $success_message = "Password updated successfully.";
            // Update the hashed_password variable for the current session
            $hashed_password = $hashed_new_password;
        } else {
            $error_message = "Error updating password: " . $stmt_update_password->error;
        }
        $stmt_update_password->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - PangaRms</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/inputs.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            <div class="logo-text">
                <span class="panga">Panga</span><span class="rms">Rms</span>
            </div>
        </div>

        <div class="nav-section">
            <div class="nav-title">MENU</div>
            <a href="dashboard.php" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
                <span>Dashboard</span>
            </a>
            <a href="notifications.php" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span>Notifications</span>
            </a>
            <a href="rent.php" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
                <span>Pay Rent</span>
            </a>
            <a href="issues.php" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span>Issues</span>
            </a>
        </div>

        <div class="nav-separator"></div>

        <div class="nav-section">
            <div class="nav-title">GENERAL</div>
            <a href="tenant_settings.php" class="nav-item active">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Tenant Settings</span>
            </a>

            <a href="logout.php" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-log-out">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Tenant Settings</h1>
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($current_fullname, 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($current_fullname); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($current_email); ?></div>
                </div>
            </div>
        </div>

        <div class="settings-container">
            <?php if (isset($success_message)): ?>
                <p class="alert alert-success"><?php echo $success_message; ?></p>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <p class="alert alert-error"><?php echo $error_message; ?></p>
            <?php endif; ?>

            <form action="tenant_settings.php" method="post" class="settings-form">
                <div class="form-group">
                    <label for="fullname" class="form-label">Full Name:</label>
                    <input type="text" id="fullname" name="fullname" class="form-input form-input-light" value="<?php echo htmlspecialchars($current_fullname); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone_number" class="form-label">Phone Number:</label>
                    <input type="text" id="phone_number" name="phone_number" class="form-input form-input-light" value="<?php echo htmlspecialchars($current_phone_number); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="form-input form-input-light" value="<?php echo htmlspecialchars($current_email); ?>" required>
                </div>

                <div class="form-actions">
                    <button type="submit" name="update_details" class="submit-btn">Update Details</button>
                </div>
            </form>
        </div>

        <div class="settings-container" style="margin-top: 20px;">
            <div class="form-group">
                <h3>Change Password</h3>
            </div>
            <form action="tenant_settings.php" method="post" class="settings-form">
                <div class="form-group">
                    <label for="current_password" class="form-label">Current Password:</label>
                    <input type="password" id="current_password" name="current_password" class="form-input form-input-light" required>
                </div>
                <div class="form-group">
                    <label for="new_password" class="form-label">New Password:</label>
                    <input type="password" id="new_password" name="new_password" class="form-input form-input-light" required>
                </div>
                <div class="form-group">
                    <label for="confirm_new_password" class="form-label">Confirm New Password:</label>
                    <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-input form-input-light" required>
                </div>
                <div class="form-actions">
                    <button type="submit" name="change_password" class="submit-btn">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
