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
                $stmt_update->execute();
                $stmt_update->close();
            } else {
                // Insert new payment
                $stmt_insert = $conn->prepare("INSERT INTO payments (rental_id, payment_for_month, amount_due, amount_paid, status, payment_date) VALUES (?, ?, ?, ?, 'paid', ?)");
                $stmt_insert->bind_param("isdds", $rental_id, $payment_for_month, $rent_amount_from_rental, $rent_amount_from_rental, $current_time);
                $stmt_insert->execute();
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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; display: flex; min-height: 100vh; }
        .sidebar { width: 240px; background: white; box-shadow: 2px 0 10px rgba(0,0,0,0.05); display: flex; flex-direction: column; position: fixed; height: 100vh; overflow-y: auto; z-index: 100; }
        .logo { display: flex; align-items: center; gap: 10px; padding: 25px 20px; border-bottom: 1px solid #e5e7eb; }
        .logo-icon { width: 40px; height: 40px; background: #2d4d52; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .logo-icon svg { width: 24px; height: 24px; fill: #5a7d82; }
        .logo-text { font-size: 24px; font-weight: 300; }
        .logo-text .panga { color: #4ade80; }
        .logo-text .rms { color: #1f2937; font-weight: 600; }
        .nav-section { padding: 20px 0; }
        .nav-title { padding: 0 20px; font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #6b7280; text-decoration: none; transition: all 0.2s; cursor: pointer; border-left: 3px solid transparent; }
        .nav-item:hover { background: #f9fafb; color: #1f2937; }
        .nav-item.active { background: #f0fdf4; color: #16a34a; border-left-color: #16a34a; font-weight: 500; }
        .nav-item svg { width: 20px; height: 20px; fill: currentColor; }
        .nav-separator { height: 1px; background: #e5e7eb; margin: 10px 0; }
        .main-content { margin-left: 240px; flex: 1; padding: 30px; background: #f5f5f5; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 32px; color: #1f2937; font-weight: 600; }
        .user-profile { display: flex; align-items: center; gap: 12px; background: white; padding: 10px 15px; border-radius: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #4ade80, #3b82f6); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 16px; }
        .user-info { display: flex; flex-direction: column; }
        .user-name { font-weight: 600; color: #1f2937; font-size: 14px; }
        .user-email { font-size: 12px; color: #6b7280; }

        /* Rent Page Styles */
        .rent-container { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .rent-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .rent-period { font-size: 16px; color: #6b7280; font-weight: 500; }
        .rent-status { padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .rent-status.not-paid { background: #fee2e2; color: #ef4444; }
        .rent-status.paid { background: #dcfce7; color: #22c55e; }
        .rent-amount { font-size: 36px; font-weight: 700; color: #1f2937; }
        .rent-amount .currency { color: #f97316; }
        
        .payment-details { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .payment-title { font-size: 16px; color: #6b7280; font-weight: 500; margin-bottom: 20px; }
        .phone-input-wrapper { display: flex; justify-content: center; margin-bottom: 15px; }
        .phone-input { font-size: 28px; font-weight: 500; padding: 15px 25px; border: 1px solid #e5e7eb; border-radius: 15px; background: #f9fafb; text-align: center; max-width: 400px; width: 100%; }
        .payment-info { text-align: center; color: #6b7280; font-size: 14px; margin-bottom: 30px; }
        .payment-actions { display: flex; justify-content: center; gap: 15px; }
        .payment-btn { padding: 12px 30px; border-radius: 25px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; }
        .send-prompt-btn { background: #16a34a; color: white; }
        .send-prompt-btn:hover { background: #15803d; }
        .check-payment-btn { background: #1f2937; color: white; }
        .check-payment-btn:hover { background: #374151; }
    </style>
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
            <a href="dashboard.php" class="nav-item"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg><span>Dashboard</span></a>
            <a href="notifications.php" class="nav-item"><svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg><span>Notifications</span></a>
            <a href="rent.php" class="nav-item active"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg><span>Pay Rent</span></a>
            <a href="issues.php" class="nav-item"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><span>Issues</span></a>
        </div>
        <div class="nav-separator"></div>
        <div class="nav-section">
            <div class="nav-title">GENERAL</div>
            <a href="#" class="nav-item"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6m-9-9h6m6 0h6m-2.636 6.364l-4.242-4.242m0 8.484l4.242-4.242m-8.485 0l4.243 4.242m0-8.484l-4.243 4.242"/></svg><span>Settings</span></a>
            <a href="#" class="nav-item"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><span>Help</span></a>
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
                        <input type="text" class="phone-input" placeholder="+254XXXXXXXXX" name="phone_number">
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
