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

// Handle marking notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_read'])) {
    $notification_id = $_POST['notification_id'];
    if ($user_id && $notification_id) {
        $stmt = $conn->prepare("UPDATE notification_recipients SET is_read = TRUE, read_at = NOW() WHERE notification_id = ? AND recipient_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();
        $stmt->close();
        header('Location: notifications.php');
        exit();
    }
}

// Fetch unread notifications
$notifications = [];
if ($user_id) {
    $sql = "SELECT n.id, n.title, n.message, n.created_at, u.role as sender_role, s.fullname as sender_name
            FROM notifications n
            JOIN notification_recipients nr ON n.id = nr.notification_id
            JOIN users u ON n.sender_id = u.id
            JOIN users s ON n.sender_id = s.id
            WHERE nr.recipient_id = ? AND nr.is_read = FALSE
            ORDER BY n.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - PangaRms</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            <div class="logo-text"><span class="panga">Panga</span><span class="rms">Rms</span></div>
        </div>

        <div class="nav-section">
            <div class="nav-title">MENU</div>
            <a href="<?php echo ($role === 'caretaker') ? 'caretaker_dashboard.php' : 'dashboard.php'; ?>" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="notifications.php" class="nav-item active">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <span>Notifications</span>
            </a>
            <?php if ($role === 'tenant'): ?>
            <a href="rent.php" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                <span>Pay Rent</span>
            </a>
            <?php elseif ($role === 'caretaker'): ?>
            <a href="register_tenant.php" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <line x1="20" y1="8" x2="20" y2="14"/>
                    <line x1="17" y1="11" x2="23" y2="11"/>
                </svg>
                <span>Register Tenant</span>
            </a>
            <?php endif; ?>
            <a href="issues.php" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>Issues</span>
            </a>
        </div>

        <div class="nav-separator"></div>

        <div class="nav-section">
            <div class="nav-title">GENERAL</div>
            <a href="settings.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php' && $role !== 'tenant') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6m-9-9h6m6 0h6m-2.636 6.364l-4.242-4.242m0 8.484l4.242-4.242m-8.485 0l4.243 4.242m0-8.484l-4.243 4.242"/></svg>
                <span>Settings</span>
            </a>
            <?php if ($role === 'tenant'): ?>
            <a href="tenant_settings.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'tenant_settings.php') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Tenant Settings</span>
            </a>
            <?php endif; ?>
            <a href="logout.php" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Notifications</h1>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($fullname, 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($fullname); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($email); ?></div>
                </div>
            </div>
        </div>

        <div class="notifications-container">
            <?php if (!empty($notifications)): ?>
                <?php foreach($notifications as $notification): ?>
                    <div class="notification-item">
                        <span class="sender-tag"><?php echo htmlspecialchars($notification['sender_role']); ?></span>
                        <div class="notification-content">
                            <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                        </div>
                        <form method="POST" action="notifications.php" style="margin: 0;">
                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                            <button type="submit" name="mark_as_read" class="mark-read-btn">Mark as Read</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-notifications">
                    <p>No unread notifications.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
