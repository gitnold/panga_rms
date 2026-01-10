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
                            echo "<td>" . htmlspecialchars($row['room_number'] ?? '') . "</td>";
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