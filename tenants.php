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

// Fetch all tenants with their rent status for the current month
$tenants = [];
$payment_for_month = date('Y-m-01');
$sql = "SELECT u.id, u.fullname, u.email, u.phone_number, u.status, p.status as rent_status
        FROM users u
        LEFT JOIN rentals r ON u.id = r.tenant_id AND r.status = 'active'
        LEFT JOIN payments p ON r.id = p.rental_id AND p.payment_for_month = ?
        WHERE u.role = 'tenant'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $payment_for_month);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $tenants[] = $row;
    }
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenants - PangaRms</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Tenants</h1>
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

        <div class="tenant-list-container">
            <table>
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Status</th>
                        <th>Rent Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tenants)): ?>
                        <?php foreach($tenants as $tenant): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tenant['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($tenant['email']); ?></td>
                                <td><?php echo htmlspecialchars($tenant['phone_number'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($tenant['status']); ?></td>
                                <td><?php echo htmlspecialchars($tenant['rent_status'] ?? 'Not Paid'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No tenants found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
