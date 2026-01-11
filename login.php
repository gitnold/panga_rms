<?php
require_once 'config.php';

$conn = getDBConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'register') {
        handleRegistration($conn);
    } elseif ($action === 'login') {
        handleLogin($conn);
    }
}

function handleRegistration($conn) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'tenant';
    
    // Validation
    if (empty($fullname) || empty($email) || empty($phone_number) || empty($password)) {
        redirect('index.php', 'All fields are required', 'error');
        return;
    }
    
    if ($password !== $confirm_password) {
        redirect('index.php', 'Passwords do not match', 'error');
        return;
    }
    
    if (strlen($password) < 6) {
        redirect('index.php', 'Password must be at least 6 characters', 'error');
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect('index.php', 'Invalid email format', 'error');
        return;
    }
    
    // Check if email or phone number already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone_number = ?");
    $stmt->bind_param("ss", $email, $phone_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        redirect('index.php', 'Email or phone number already exists', 'error');
        return;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone_number, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $fullname, $email, $phone_number, $hashed_password, $role);
    
    if ($stmt->execute()) {
        redirect('index.php', 'Registration successful! Please login.', 'success');
    } else {
        redirect('index.php', 'Registration failed. Please try again.', 'error');
    }
    
    $stmt->close();
}

function handleLogin($conn) {
    $login_identifier = trim($_POST['login_identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'tenant';
    
    if (empty($login_identifier) || empty($password)) {
        redirect('index.php', 'Please enter email/phone number and password', 'error');
        return;
    }
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, fullname, email, phone_number, password, role FROM users WHERE (email = ? OR phone_number = ?) AND role = ?");
    $stmt->bind_param("sss", $login_identifier, $login_identifier, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        redirect('index.php', 'Invalid credentials or role', 'error');
        return;
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['phone_number'] = $user['phone_number'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        // Redirect to the appropriate dashboard
        if ($user['role'] === 'tenant') {
            header('Location: dashboard.php');
        } elseif ($user['role'] === 'caretaker') {
            header('Location: caretaker_dashboard.php');
        } else {
            header('Location: dashboard.php');
        }
        exit();
    } else {
        redirect('index.php', 'Invalid credentials', 'error');
    }
    
    $stmt->close();
}

function redirect($page, $message, $type) {
    $encoded_message = urlencode($message);
    header("Location: $page?$type=$encoded_message");
    exit();
}

$conn->close();
?>