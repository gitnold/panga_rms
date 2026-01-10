<?php
require_once 'config.php';

// Check if user is logged in and is a caretaker
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'caretaker') {
    header('Location: index.php?error=' . urlencode('You do not have permission to view this page'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'];
    $room_number = $_POST['room_number'];
    $id_number = $_POST['id_number'];
    $phone_number = $_POST['phone_number'];

    // For simplicity, we'll generate a random username and password
    $username = strtolower(str_replace(' ', '', $fullname)) . rand(100, 999);
    $password = password_hash('password123', PASSWORD_DEFAULT);
    $email = $username . '@pangarms.com';

    $conn = getDBConnection();

    $stmt = $conn->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, 'tenant')");
    $stmt->bind_param("ssss", $fullname, $email, $username, $password);

    if ($stmt->execute()) {
        $success_message = "Tenant registered successfully. Username: $username, Password: password123";
    } else {
        $error_message = "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Tenant - PangaRms</title>
</head>
<body>
    <h1>Register New Tenant</h1>

    <?php if (isset($success_message)): ?>
        <p style="color: green;"><?php echo $success_message; ?></p>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <form action="register_tenant.php" method="post">
        <label for="fullname">Full Name:</label><br>
        <input type="text" id="fullname" name="fullname" required><br><br>
        
        <label for="room_number">Room Number:</label><br>
        <input type="text" id="room_number" name="room_number" required><br><br>

        <label for="id_number">ID Number:</label><br>
        <input type="text" id="id_number" name="id_number" required><br><br>

        <label for="phone_number">Phone Number:</label><br>
        <input type="text" id="phone_number" name="phone_number" required><br><br>

        <input type="submit" value="Register Tenant">
    </form>
</body>
</html>