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

// Handle tenant deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate_tenant'])) {
    $tenant_id_to_deactivate = $_POST['tenant_id'];
    $conn_deactivate = getDBConnection();
    $stmt_deactivate = $conn_deactivate->prepare("UPDATE users SET status = 'inactive' WHERE id = ? AND role = 'tenant'");
    $stmt_deactivate->bind_param("i", $tenant_id_to_deactivate);
    if ($stmt_deactivate->execute()) {
        header('Location: caretaker_dashboard.php?success=' . urlencode('Tenant deactivated successfully.'));
    } else {
        $error_message = "Error deactivating tenant: " . $stmt_deactivate->error;
    }
    $stmt_deactivate->close();
    $conn_deactivate->close();
    exit();
}

// Handle tenant deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_tenant'])) {
    $tenant_id_to_delete = $_POST['tenant_id'];
    $conn_delete = getDBConnection();
    $stmt_delete = $conn_delete->prepare("DELETE FROM users WHERE id = ? AND role = 'tenant'");
    $stmt_delete->bind_param("i", $tenant_id_to_delete);
    if ($stmt_delete->execute()) {
        header('Location: caretaker_dashboard.php?success=' . urlencode('Tenant deleted successfully.'));
    } else {
        $error_message = "Error deleting tenant: " . $stmt_delete->error;
    }
    $stmt_delete->close();
    $conn_delete->close();
    exit();
}

// Get pending issues count
$pending_issues_count = 0;
if ($user_id) {
    $sql_issues = "SELECT COUNT(*) AS count FROM issues WHERE status = 'pending'";
    $stmt_issues = $conn->prepare($sql_issues);
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

// Get number of tenants
$tenants_count = 0;
$sql_tenants = "SELECT COUNT(*) AS count FROM users WHERE role = 'tenant'";
$result_tenants = $conn->query($sql_tenants);
if ($result_tenants) {
    $row_tenants = $result_tenants->fetch_assoc();
    $tenants_count = $row_tenants['count'];
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
    <?php include 'sidebar.php'; ?>

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

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <a href="notifications.php" class="stat-card orange" style="text-decoration: none; color: white;">
                <div class="stat-header">
                    <span class="stat-label">NOTIFICATIONS</span>
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo $unread_notifications_count; ?></div>
                <div class="stat-description">New messages waiting</div>
            </a>

            <a href="tenants.php" class="stat-card green" style="text-decoration: none; color: white;">
                <div class="stat-header">
                    <span class="stat-label">TENANTS</span>
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <line x1="20" y1="8" x2="20" y2="14"/>
                            <line x1="17" y1="11" x2="23" y2="11"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo $tenants_count; ?></div>
                <div class="stat-description">Total registered tenants</div>
            </a>

            <a href="issues.php" class="stat-card teal issues-card" style="text-decoration: none; color: white;">
                <span class="issues-badge">ISSUES</span>
                <div class="stat-value"><?php echo $pending_issues_count; ?></div>
                <div class="stat-description">Increased from last month</div>
                <div class="trend-icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <polyline points="18 15 12 9 6 15"/>
                    </svg>
                </div>
            </a>
        </div>

        <!-- Tenant Complaints -->
        <div class="issues-list">
            <h2>Tenant Complaints</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Room Number</th>
                        <th>Issue</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn_issues = getDBConnection(); // Use a new connection for this section
                    $sql_complaints = "SELECT i.issue_type, i.description, i.status, i.created_at, u.fullname, i.room_number 
                            FROM issues i 
                            JOIN users u ON i.user_id = u.id 
                            ORDER BY i.created_at DESC";
                    $result_complaints = $conn_issues->query($sql_complaints);

                    if ($result_complaints->num_rows > 0) {
                        while($row = $result_complaints->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['fullname']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['room_number'] ?? 'N/A') . "</td>";
                            echo "<td>" . htmlspecialchars($row['issue_type']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                            echo "<td>" . date('M j, Y', strtotime($row['created_at'])) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No issues found</td></tr>";
                    }
                    $conn_issues->close(); // Close the connection for this section
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Tenant Management -->
        <div class="tenant-management">
            <div class="issues-header">
                <h2>Tenant Management - All Tenants</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn_tenants_manage = getDBConnection(); // Use a new connection
                    $sql_manage_tenants = "SELECT id, fullname, email, phone_number, status FROM users WHERE role = 'tenant'";
                    $result_manage_tenants = $conn_tenants_manage->query($sql_manage_tenants);

                    if ($result_manage_tenants->num_rows > 0) {
                        while ($row_manage = $result_manage_tenants->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row_manage['fullname']) . "</td>";
                            echo "<td>" . htmlspecialchars($row_manage['email']) . "</td>";
                            echo "<td>" . htmlspecialchars($row_manage['phone_number'] ?? 'N/A') . "</td>";
                            echo "<td>" . htmlspecialchars($row_manage['status']) . "</td>";
                            echo "<td>";
                            echo '<form method="POST" action="caretaker_dashboard.php" style="display: inline-block;">';
                            echo '<input type="hidden" name="tenant_id" value="' . $row_manage['id'] . '">';
                            echo '<button type="submit" name="deactivate_tenant" class="action-btn repair">Deactivate</button>';
                            echo '</form>';
                            echo '<form method="POST" action="caretaker_dashboard.php" style="display: inline-block;">';
                            echo '<input type="hidden" name="tenant_id" value="' . $row_manage['id'] . '">';
                            echo '<button type="submit" name="delete_tenant" class="action-btn complain">Delete</button>';
                            echo '</form>';
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No tenants found.</td></tr>";
                    }
                    $conn_tenants_manage->close(); // Close the connection
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
                    $conn_rent_status = getDBConnection(); // Use a new connection
                    $payment_for_month = date('Y-m-01');
                    $sql_rent = "SELECT u.fullname, r.room_number, p.status 
                            FROM users u
                            JOIN rentals r ON u.id = r.tenant_id
                            LEFT JOIN payments p ON r.id = p.rental_id AND p.payment_for_month = ?
                            WHERE u.role = 'tenant' AND r.status = 'active'";
                    
                    $stmt_rent = $conn_rent_status->prepare($sql_rent);
                    $stmt_rent->bind_param("s", $payment_for_month);
                    $stmt_rent->execute();
                    $result_rent = $stmt_rent->get_result();

                    if ($result_rent->num_rows > 0) {
                        while($row = $result_rent->fetch_assoc()) {
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
                    $stmt_rent->close();
                    $conn_rent_status->close();
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