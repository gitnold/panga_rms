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
$role = $_SESSION['role']; // Directly use role from session

$conn = getDBConnection();

// Get user ID from username
// NOTE: It would be more efficient to store the user_id in the session upon login.
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

$error_message = '';
$success_message = '';

// Handle issue submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_issue'])) {
    if ($user_id) {
        $issue_type = $_POST['issue_type'];
        $description = trim($_POST['description']);

        if (!empty($issue_type) && !empty($description)) {
            $stmt = $conn->prepare("INSERT INTO issues (user_id, issue_type, description) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $issue_type, $description);
            if ($stmt->execute()) {
                header('Location: issues.php?success=' . urlencode('Issue submitted successfully.'));
                exit();
            } else {
                $error_message = "Error: Could not submit the issue.";
            }
            $stmt->close();
        } else {
            $error_message = "Please fill out all fields.";
        }
    } else {
        $error_message = "Could not identify user. Please login again.";
    }
}

// Handle issue removal (soft delete by changing status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_issue'])) {
    $issue_id = $_POST['issue_id'];
    if ($user_id && $issue_id) {
        $sql = "UPDATE issues SET status = 'closed' WHERE id = ?";
        // Allow landlord/caretaker to close any issue, tenant only their own.
        if ($role === 'tenant') {
            $sql .= " AND user_id = ?";
        }
        $stmt = $conn->prepare($sql);

        if ($role === 'tenant') {
            $stmt->bind_param("ii", $issue_id, $user_id);
        } else {
            $stmt->bind_param("i", $issue_id);
        }

        if ($stmt->execute()) {
            header('Location: issues.php?success=' . urlencode('Issue has been closed.'));
            exit();
        } else {
            $error_message = "Error: Could not remove the issue.";
        }
        $stmt->close();
    }
}


// Fetch issues based on role
$issues = [];
$sql = "SELECT i.id, i.issue_type, i.description, i.status, i.created_at, u.fullname 
        FROM issues i 
        JOIN users u ON i.user_id = u.id 
        WHERE i.status = 'pending'";

if ($role === 'tenant') {
    $sql .= " AND i.user_id = ?";
}

$sql .= " ORDER BY i.created_at DESC";
$stmt = $conn->prepare($sql);

if ($role === 'tenant' && $user_id) {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();


if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $issues[] = $row;
    }
}
$stmt->close();

if(isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}
if(isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issues - PangaRms</title>
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
            <a href="<?php echo ($role === 'caretaker') ? 'caretaker_dashboard.php' : 'dashboard.php'; ?>" class="nav-item">
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
            <?php if ($role === 'tenant'): ?>
            <a href="rent.php" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
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
            <a href="issues.php" class="nav-item active">
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
            <a href="settings.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php' && $role !== 'tenant') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-settings">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
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
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <div></div>
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

        <div class="issues-container">
            <div class="issues-header">
                <h1 class="page-title">Issues</h1>
                <div class="action-buttons">
                    <button class="action-btn repair" data-type="repair">Repair +</button>
                    <button class="action-btn complain" data-type="complaint">Complain +</button>
                </div>
            </div>

            <div class="issues-list">
                <div class="list-subtitle">Pending</div>
                <?php if (!empty($issues)): ?>
                    <?php foreach($issues as $issue): ?>
                        <div class="issue-item">
                            <span class="issue-tag"><?php echo htmlspecialchars($issue['issue_type']); ?></span>
                            <div class="issue-description"><?php echo htmlspecialchars($issue['description']); ?></div>
                            <?php if ($role !== 'tenant'): ?>
                                <div class="issue-reporter">By: <?php echo htmlspecialchars($issue['fullname']); ?></div>
                            <?php endif; ?>
                            <form method="POST" action="issues.php" style="margin: 0;">
                                <input type="hidden" name="issue_id" value="<?php echo $issue['id']; ?>">
                                <button type="submit" name="remove_issue" class="remove-btn">Remove</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-issues">
                        <p>No pending issues found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Issue Submission Modal -->
    <div class="modal-overlay" id="issue-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-title">New Issue</h2>
                <button class="close-modal-btn" id="close-modal">&times;</button>
            </div>
            <form action="issues.php" method="POST">
                <div class="form-group">
                    <label for="issue_type">Issue Type</label>
                    <select name="issue_type" id="issue_type">
                        <option value="repair">Repair</option>
                        <option value="complaint">Complaint</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" placeholder="Please describe the issue in detail."></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" name="submit_issue" class="submit-btn">Submit Issue</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/issues.js"></script>
</body>
</html>
