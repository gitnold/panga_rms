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
            z-index: 100;
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
        
        /* Issues Page Specific Styles */
        .issues-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .issues-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .issues-header .page-title {
             margin-bottom: 0;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 10px 20px;
            border-radius: 20px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .action-btn.repair {
            background-color: #f97316;
            color: white;
        }
        .action-btn.repair:hover {
            background-color: #ea580c;
        }

        .action-btn.complain {
            background-color: #1f2937;
            color: white;
        }
        .action-btn.complain:hover {
            background-color: #374151;
        }
        
        .issues-list .list-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .issue-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f9fafb;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid #e5e7eb;
        }

        .issue-tag {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            margin-right: 15px;
            background-color: #e5e7eb;
            color: #4b5563;
            text-transform: capitalize;
        }

        .issue-description {
            flex: 1;
            color: #1f2937;
            font-weight: 500;
        }
        
        .issue-reporter {
            font-size: 12px;
            color: #6b7280;
            margin-right: 20px;
        }

        .remove-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-left: 15px;
        }
        
        .remove-btn:hover {
            background: #dc2626;
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 24px;
            font-weight: 600;
        }

        .close-modal-btn {
            background: transparent;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #9ca3af;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
        }
        
        .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-family: inherit;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-actions {
            text-align: right;
        }

        .submit-btn {
            background: #10b981;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background: #059669;
        }

        .no-issues {
            text-align: center;
            padding: 40px;
            color: #6b7280;
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
            <a href="#" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12 1v6m0 6v6m-9-9h6m6 0h6m-2.636 6.364l-4.242-4.242m0 8.484l4.242-4.242m-8.485 0l4.243 4.242m0-8.484l-4.243 4.242"/>
                </svg>
                <span>Settings</span>
            </a>
            <a href="#" class="nav-item">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span>Help</span>
            </a>
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

    <script>
        const issueModal = document.getElementById('issue-modal');
        const modalTitle = document.getElementById('modal-title');
        const issueTypeSelect = document.getElementById('issue_type');
        const openModalButtons = document.querySelectorAll('.action-btn');
        const closeModalButton = document.getElementById('close-modal');

        openModalButtons.forEach(button => {
            button.addEventListener('click', () => {
                const issueType = button.getAttribute('data-type');
                if (issueType) {
                    issueTypeSelect.value = issueType;
                    modalTitle.textContent = 'New ' + issueType.charAt(0).toUpperCase() + issueType.slice(1);
                }
                issueModal.style.display = 'flex';
            });
        });

        function closeModal() {
            issueModal.style.display = 'none';
        }

        closeModalButton.addEventListener('click', closeModal);

        issueModal.addEventListener('click', (event) => {
            if (event.target === issueModal) {
                closeModal();
            }
        });
    </script>
</body>
</html>
