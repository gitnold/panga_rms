<?php
require_once 'config.php';

// Check if user is logged in and is a caretaker
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'caretaker') {
    header('Location: index.php?error=' . urlencode('You do not have permission to view this page'));
    exit();
}

$fullname = $_SESSION['fullname'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$role = $_SESSION['role'];

$conn = getDBConnection();

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname_tenant = $_POST['fullname'];
    $room_number = $_POST['room_number'];
    $id_number = $_POST['id_number'];
    $phone_number = $_POST['phone_number'];

    // For simplicity, we'll generate a random username and password
    $new_username = strtolower(str_replace(' ', '', $fullname_tenant)) . rand(100, 999);
    $new_password = password_hash('password123', PASSWORD_DEFAULT);
    $new_email = $new_username . '@pangarms.com';

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Insert new user
        $stmt_user = $conn->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, 'tenant')");
        $stmt_user->bind_param("ssss", $fullname_tenant, $new_email, $new_username, $new_password);
        if (!$stmt_user->execute()) {
            throw new Exception("Error registering user: " . $stmt_user->error);
        }
        $new_tenant_id = $stmt_user->insert_id;
        $stmt_user->close();

        // Assuming a default property_id for now, replace with actual logic to select property
        $property_id = 1; 
        $rent_amount = 10000.00; // Default rent amount, replace with actual logic
        $start_date = date('Y-m-d'); // Current date as start date

        // Insert new rental
        $stmt_rental = $conn->prepare("INSERT INTO rentals (property_id, tenant_id, caretaker_id, room_number, rent_amount, start_date, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt_rental->bind_param("iiisds", $property_id, $new_tenant_id, $_SESSION['user_id'], $room_number, $rent_amount, $start_date);
        if (!$stmt_rental->execute()) {
            throw new Exception("Error registering rental: " . $stmt_rental->error);
        }
        $stmt_rental->close();

        $conn->commit();
        $success_message = "Tenant registered successfully. Username: $new_username, Password: password123";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Registration failed: " . $e->getMessage();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Tenant - PangaRms</title>
    <link rel="stylesheet" href="css/main.css">
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
            <a href="caretaker_dashboard.php" class="nav-item">
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
            <a href="issues.php" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span>Issues</span>
            </a>
            <a href="register_tenant.php" class="nav-item active">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <line x1="20" y1="8" x2="20" y2="14"/>
                    <line x1="17" y1="11" x2="23" y2="11"/>
                </svg>
                <span>Register Tenant</span>
            </a>
        </div>

        <div class="nav-separator"></div>

        <div class="nav-section">
            <div class="nav-title">GENERAL</div>
            <a href="settings.php" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-settings">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                <span>Settings</span>
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
            <h1 class="page-title">Register Tenant</h1>
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($fullname, 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($fullname); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($email); ?></div>
                </div>
            </div>
        </div>

        <div class="register-tenant-container">
            <?php if (isset($success_message)): ?>
                <p class="alert alert-success"><?php echo $success_message; ?></p>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <p class="alert alert-error"><?php echo $error_message; ?></p>
            <?php endif; ?>

            <form action="register_tenant.php" method="post" class="register-form">
                <div class="form-group">
                    <label for="fullname">Full Name:</label>
                    <input type="text" id="fullname" name="fullname" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="room_number">Room Number:</label>
                    <input type="text" id="room_number" name="room_number" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="id_number">ID Number:</label>
                    <input type="text" id="id_number" name="id_number" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number:</label>
                    <input type="text" id="phone_number" name="phone_number" class="form-input" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">Register Tenant</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>