    $current_fullname = '';
    $current_phone_number = '';

    $stmt_fetch = $conn->prepare("SELECT fullname, phone_number FROM users WHERE id = ?");
    $stmt_fetch->bind_param("i", $user_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    if ($result_fetch->num_rows > 0) {
        $user_data = $result_fetch->fetch_assoc();
        $current_fullname = $user_data['fullname'];
        $current_phone_number = $user_data['phone_number'];
    }
    $stmt_fetch->close();


    // Handle form submission for updating details
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_details'])) {
        $new_fullname = trim($_POST['fullname']);
        $new_phone_number = trim($_POST['phone_number']);

        if (empty($new_fullname) || empty($new_phone_number)) {
            $error_message = "All fields are required.";
        } else {
            $stmt_update = $conn->prepare("UPDATE users SET fullname = ?, phone_number = ? WHERE id = ?");
            $stmt_update->bind_param("ssi", $new_fullname, $new_phone_number, $user_id);
            
            if ($stmt_update->execute()) {
                $_SESSION['fullname'] = $new_fullname; // Update session with new fullname
                $success_message = "Your details have been updated successfully.";
                // Refresh current data
                $current_fullname = $new_fullname;
                $current_phone_number = $new_phone_number;
            } else {
                $error_message = "Error updating details: " . $stmt_update->error;
            }
            $stmt_update->close();
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
                <h1 class="page-title">Tenant Settings</h1>
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

            <div class="settings-container">
                <?php if (isset($success_message)): ?>
                    <p class="alert alert-success"><?php echo $success_message; ?></p>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <p class="alert alert-error"><?php echo $error_message; ?></p>
                <?php endif; ?>

                <form action="tenant_settings.php" method="post" class="settings-form">
                    <div class="form-group">
                        <label for="fullname">Full Name:</label>
                        <input type="text" id="fullname" name="fullname" class="form-input" value="<?php echo htmlspecialchars($current_fullname); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone_number">Phone Number:</label>
                        <input type="text" id="phone_number" name="phone_number" class="form-input" value="<?php echo htmlspecialchars($current_phone_number); ?>" required>
                    </div>
                    

                    <div class="form-actions">
                        <button type="submit" name="update_details" class="submit-btn">Update Details</button>
                    </div>
                </form>
            </div>
        </div>
    </body>
    </html>
