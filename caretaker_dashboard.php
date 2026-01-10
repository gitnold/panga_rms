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

// Get user ID from username
$user_id = null;
if (isset($_SESSION['username'])) {
    $stmt_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt_user->bind_param("s", $_SESSION['username']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows > 0) {
        $user = $result_user->fetch_assoc();
        $user_id = $user['id'];
    }
    $stmt_user->close();
}

// Get pending issues count
$pending_issues_count = 0;
if ($user_id) {
    $sql_issues = "SELECT COUNT(*) AS count FROM issues WHERE status = 'pending'";
    if ($role === 'tenant') {
        $sql_issues .= " AND user_id = ?";
    }
    $stmt_issues = $conn->prepare($sql_issues);
    if ($role === 'tenant') {
        $stmt_issues->bind_param("i", $user_id);
    }
    $stmt_issues->execute();
    $result_issues = $stmt_issues->get_result();
    if ($result_issues) {
        $row_issues = $result_issues->fetch_assoc();
        $pending_issues_count = $row_issues['count'];
    }
    $stmt_issues->close();
}

// Get unread notifications count
$unread_notifications_count = 0;
if ($user_id) {
    $sql_notifications = "SELECT COUNT(*) as count FROM notification_recipients WHERE recipient_id = ? AND is_read = FALSE";
    $stmt_notifications = $conn->prepare($sql_notifications);
    $stmt_notifications->bind_param("i", $user_id);
    $stmt_notifications->execute();
    $result_notifications = $stmt_notifications->get_result();
    if ($result_notifications) {
        $row_notifications = $result_notifications->fetch_assoc();
        $unread_notifications_count = $row_notifications['count'];
    }
    $stmt_notifications->close();
}

// Get rent status for the current month
$rent_payment_status = 'N/A';
$rent_description = 'Not applicable';
if ($user_id && $role === 'tenant') {
    $payment_for_month = date('Y-m-01');
    $sql_rent = "SELECT p.status 
                 FROM rentals r
                 LEFT JOIN payments p ON r.id = p.rental_id AND p.payment_for_month = ?
                 WHERE r.tenant_id = ? AND r.status = 'active'";
    
    $stmt_rent = $conn->prepare($sql_rent);
    $stmt_rent->bind_param("si", $payment_for_month, $user_id);
    $stmt_rent->execute();
    $result_rent = $stmt_rent->get_result();

    if ($result_rent && $result_rent->num_rows > 0) {
        $row_rent = $result_rent->fetch_assoc();
        $rent_payment_status = $row_rent['status'] ? ucfirst(str_replace('_', ' ', $row_rent['status'])) : 'Not Paid';
    } else {
        $rent_payment_status = 'Not Paid';
    }

    if ($rent_payment_status === 'Paid') {
        $next_month = date('M jS', strtotime('first day of next month'));
        $rent_description = 'Next payment due ' . $next_month;
    } else {
        $rent_description = 'Payment for this month is due';
    }
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caretaker Dashboard - PangaRms</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 240px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 25px 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: #2d4d52;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon svg {
            width: 24px;
            height: 24px;
            fill: #5a7d82;
        }

        .logo-text {
            font-size: 24px;
            font-weight: 300;
        }

        .logo-text .panga {
            color: #4ade80;
        }

        .logo-text .rms {
            color: #1f2937;
            font-weight: 600;
        }

        .nav-section {
            padding: 20px 0;
        }

        .nav-title {
            padding: 0 20px;
            font-size: 11px;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border-left: 3px solid transparent;
        }

        .nav-item:hover {
            background: #f9fafb;
            color: #1f2937;
        }

        .nav-item.active {
            background: #f0fdf4;
            color: #16a34a;
            border-left-color: #16a34a;
            font-weight: 500;
        }

        .nav-item svg {
            width: 20px;
            height: 20px;
            fill: currentColor;
        }

        .nav-separator {
            height: 1px;
            background: #e5e7eb;
            margin: 10px 0;
        }

        /* Main Content */
        .main-content {
            margin-left: 240px;
            flex: 1;
            padding: 30px;
            background: #f5f5f5;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 32px;
            color: #1f2937;
            font-weight: 600;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            padding: 10px 15px;
            border-radius: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4ade80, #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
        }

        .user-email {
            font-size: 12px;
            color: #6b7280;
        }

        /* Announcement Card */
        .announcement-card {
            background: linear-gradient(135deg, #5a7d82 0%, #4a6b70 100%); /* Muted teal/grey gradient */
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(90, 125, 130, 0.2); /* Muted shadow */
            position: relative;
            overflow: hidden;
        }

        .announcement-content {
            flex: 1;
            z-index: 1;
        }

        .announcement-title {
            font-size: 28px;
            color: white;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .announcement-text {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            margin-bottom: 20px;
        }

        .see-more-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(0, 0, 0, 0.3);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .see-more-btn:hover {
            background: rgba(0, 0, 0, 0.5);
        }

        .announcement-image {
            width: 200px;
            height: 120px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }

        .announcement-image svg {
            width: 80px;
            height: 80px;
            fill: rgba(255, 255, 255, 0.3);
        }

        /* Dashboard Cards */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .stat-card.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .stat-card.orange {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
        }

        .stat-card.teal {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            color: white;
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 500;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon svg {
            width: 24px;
            height: 24px;
            fill: currentColor;
        }

        .stat-value {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-description {
            font-size: 13px;
            opacity: 0.8;
        }

        .stat-card.issues-card {
            position: relative;
        }

        .issues-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: white;
            color: #0d9488;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .trend-icon {
            position: absolute;
            bottom: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .trend-icon svg {
            width: 20px;
            height: 20px;
            fill: white;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }

            .main-content {
                margin-left: 70px;
            }

            .logo-text, .nav-title {
                display: none;
            }

            .nav-item span {
                display: none;
            }

            .announcement-card {
                flex-direction: column;
                text-align: center;
            }

            .announcement-image {
                margin-top: 20px;
            }
        }
    </style>
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
            <a href="dashboard.php" class="nav-item active">
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
            <a href="register_tenant.php" class="nav-item">
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
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-log-.out">
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
            <h1 class="page-title">Caretaker Dashboard</h1>
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

        <!-- Tenant Complaints -->
        <div class="issues-list">
            <h2>Tenant Complaints</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Issue</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn = getDBConnection();
                    $sql = "SELECT i.issue_type, i.description, i.status, i.created_at, u.fullname 
                            FROM issues i 
                            JOIN users u ON i.user_id = u.id 
                            ORDER BY i.created_at DESC";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['fullname']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['issue_type']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                            echo "<td>" . date('M j, Y', strtotime($row['created_at'])) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No issues found</td></tr>";
                    }
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Rent Status -->
        <div class="rent-status">
            <h2>Rent Status (Current Month)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Room Number</th>
                        <th>Rent Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn = getDBConnection();
                    $payment_for_month = date('Y-m-01');
                    $sql = "SELECT u.fullname, r.room_number, p.status 
                            FROM users u
                            JOIN rentals r ON u.id = r.tenant_id
                            LEFT JOIN payments p ON r.id = p.rental_id AND p.payment_for_month = ?
                            WHERE u.role = 'tenant' AND r.status = 'active'";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $payment_for_month);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $rent_status = $row['status'] ? ucfirst(str_replace('_', ' ', $row['status'])) : 'Not Paid';
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['fullname']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['room_number']) . "</td>";
                            echo "<td>" . htmlspecialchars($rent_status) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No tenants found</td></tr>";
                    }
                    $stmt->close();
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // The navigation is handled by server-side rendering, so this script is not needed.
    </script>
</body>
</html>