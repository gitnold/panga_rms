<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php?error=' . urlencode('Please login first'));
    exit();
}

$fullname = $_SESSION['fullname'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$role = $_SESSION['role'];

$conn = getDBConnection();

$user_id = null;
if (isset($_SESSION['username'])) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
    }
    $stmt->close();
}

// Handle "Check Payment" action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_payment'])) {
    if ($user_id && $role === 'tenant') {
        $rental_id = null;
        // Get the active rental_id for the current tenant
        $stmt_rental = $conn->prepare("SELECT id, rent_amount FROM rentals WHERE tenant_id = ? AND status = 'active'");
        $stmt_rental->bind_param("i", $user_id);
        $stmt_rental->execute();
        $result_rental = $stmt_rental->get_result();
        if ($result_rental->num_rows > 0) {
            $row_rental = $result_rental->fetch_assoc();
            $rental_id = $row_rental['id'];
            $rent_amount_from_rental = $row_rental['rent_amount'];
        }
        $stmt_rental->close();

        if ($rental_id) {
            $payment_for_month = date('Y-m-01');
            $current_time = date('Y-m-d H:i:s');

            // Check if payment already exists for this month and rental
            $stmt_check = $conn->prepare("SELECT id FROM payments WHERE rental_id = ? AND payment_for_month = ?");
            $stmt_check->bind_param("is", $rental_id, $payment_for_month);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                // Update existing payment
                $stmt_update = $conn->prepare("UPDATE payments SET status = 'paid', amount_paid = ?, payment_date = ? WHERE rental_id = ? AND payment_for_month = ?");
                $stmt_update->bind_param("dsis", $rent_amount_from_rental, $current_time, $rental_id, $payment_for_month);
                if ($stmt_update->execute()) {
                    error_log("Payment UPDATE successful for rental_id: $rental_id, month: $payment_for_month");
                } else {
                    error_log("Payment UPDATE failed: " . $stmt_update->error);
                }
                $stmt_update->close();
            } else {
                // Insert new payment
                $stmt_insert = $conn->prepare("INSERT INTO payments (rental_id, payment_for_month, amount_due, amount_paid, status, payment_date) VALUES (?, ?, ?, ?, 'paid', ?)");
                $stmt_insert->bind_param("isdds", $rental_id, $payment_for_month, $rent_amount_from_rental, $rent_amount_from_rental, $current_time);
                if ($stmt_insert->execute()) {
                    error_log("Payment INSERT successful for rental_id: $rental_id, month: $payment_for_month");
                } else {
                    error_log("Payment INSERT failed: " . $stmt_insert->error);
                }
                $stmt_insert->close();
            }
            header('Location: rent.php?success=' . urlencode('Rent marked as paid for ' . date('F Y')));
            exit();
        } else {
            // No active rental found for the tenant
            header('Location: rent.php?error=' . urlencode('No active rental found for your account.'));
            exit();
        }
    } else {
        header('Location: rent.php?error=' . urlencode('Unauthorized action.'));
        exit();
    }
}


$rent_amount = 0;
$payment_status = 'N/A';
$current_month = date('m/Y');

if ($user_id && $role === 'tenant') {
    $sql = "SELECT r.rent_amount, p.status 
            FROM rentals r
            LEFT JOIN payments p ON r.id = p.rental_id AND p.payment_for_month = ?
            WHERE r.tenant_id = ? AND r.status = 'active'";
    
    $payment_for_month = date('Y-m-01');

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $payment_for_month, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $rent_amount = $row['rent_amount'];
        $payment_status = $row['status'] ? ucfirst(str_replace('_', ' ', $row['status'])) : 'Not Paid';
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Rent - PangaRms</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/inputs.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <div class="logo-icon"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
            <div class="logo-text"><span class="panga">Panga</span><span class="rms">Rms</span></div>
        </div>
        <div class="nav-section">
            <div class="nav-title">MENU</div>
            <a href="<?php echo ($role === 'caretaker') ? 'caretaker_dashboard.php' : 'dashboard.php'; ?>" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg><span>Dashboard</span></a>
            <a href="notifications.php" class="nav-item"><svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg><span>Notifications</span></a>
            <a href="tenants.php" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <line x1="20" y1="8" x2="20" y2="14"/>
                    <line x1="17" y1="11" x2="23" y2="11"/>
                </svg>
                <span>Tenants</span>
            </a>
            <a href="issues.php" class="nav-item"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><span>Issues</span></a>
        </div>
        <div class="nav-separator"></div>
        <div class="nav-section">
            <div class="nav-title">GENERAL</div>
            <?php if ($role !== 'tenant'): ?>
            <a href="settings.php" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6m-9-9h6m6 0h6m-2.636 6.364l-4.242-4.242m0 8.484l4.242-4.242m-8.485 0l4.243 4.242m0-8.484l-4.243 4.242"/></svg>
                <span>Settings</span>
            </a>
            <?php else: ?>
            <a href="tenant_settings.php" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Tenant Settings</span>
            </a>
            <?php endif; ?>
            <a href="help.php" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-help-circle">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <span>Help</span>
            </a>
            <a href="logout.php" class="nav-item"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg><span>Logout</span></a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Pay Rent</h1>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($fullname, 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($fullname); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($email); ?></div>
                </div>
            </div>
        </div>

        <?php if ($role === 'tenant'): ?>
            <div class="rent-container">
                <div class="rent-header">
                    <div class="rent-period">Rent for <?php echo $current_month; ?></div>
                    <div class="rent-status <?php echo strtolower(str_replace(' ', '-', $payment_status)); ?>"><?php echo $payment_status; ?></div>
                </div>
                <div class="rent-amount"><span class="currency">Ksh</span> <?php echo number_format($rent_amount, 2); ?></div>
            </div>

            <div class="payment-details">
                <div class="payment-title">Payment Details</div>
                <form action="rent.php" method="POST">
                    <div class="phone-input-wrapper">
                        <input type="text" class="form-input form-input-light" placeholder="+254XXXXXXXXX" name="phone_number">
                    </div>
                    <div class="payment-info">Paybill: 234544 Account No: 2345</div>
                    <div class="payment-actions">
                        <button class="payment-btn send-prompt-btn" type="button">Send Prompt</button>
                        <button class="payment-btn check-payment-btn" type="submit" name="check_payment">Check Payment</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="rent-container">
                <p>This page is for tenants only.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
