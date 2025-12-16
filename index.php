<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PangaRms - Login/Register</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #4a6b70 0%, #5a7d82 50%, #6b8e94 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 40px;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background: #2d4d52;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #3d5d62;
        }

        .logo-icon svg {
            width: 35px;
            height: 35px;
            fill: #5a7d82;
        }

        .logo-text {
            font-size: 32px;
            font-weight: 300;
        }

        .logo-text .panga {
            color: #4ade80;
        }

        .logo-text .rms {
            color: #1f2937;
            font-weight: 600;
        }

        .container {
            display: flex;
            gap: 40px;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
            flex: 1;
        }

        .login-card {
            background: rgba(45, 77, 82, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 50px 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .welcome {
            text-align: center;
            margin-bottom: 40px;
        }

        .welcome h1 {
            color: #e5e7eb;
            font-size: 36px;
            font-weight: 300;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .wave {
            font-size: 40px;
        }

        .role-selector {
            text-align: center;
            margin-bottom: 30px;
        }

        .role-selector p {
            color: #d1d5db;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .role-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .role-btn {
            background: transparent;
            border: 1px solid #9ca3af;
            color: #d1d5db;
            padding: 8px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .role-btn:hover {
            border-color: #4ade80;
            color: #4ade80;
        }

        .role-btn.active {
            background: #4ade80;
            border-color: #4ade80;
            color: #1f2937;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-input {
            width: 100%;
            padding: 18px 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: #e5e7eb;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-input::placeholder {
            color: #9ca3af;
        }

        .form-input:focus {
            outline: none;
            border-color: #4ade80;
            background: rgba(255, 255, 255, 0.15);
        }

        .login-btn {
            width: 100%;
            padding: 18px;
            background: #3d2020;
            border: none;
            border-radius: 12px;
            color: #e5e7eb;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        .login-btn:hover {
            background: #4d2525;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .forgot-password {
            text-align: center;
            margin-bottom: 30px;
        }

        .forgot-password a {
            color: #fb923c;
            text-decoration: none;
            font-size: 16px;
            transition: color 0.3s;
        }

        .forgot-password a:hover {
            color: #fdba74;
        }

        .register-section {
            text-align: center;
            color: #d1d5db;
            font-size: 16px;
        }

        .register-section a {
            color: #60a5fa;
            text-decoration: none;
            transition: color 0.3s;
            cursor: pointer;
        }

        .register-section a:hover {
            color: #93c5fd;
        }

        .image-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 400px;
        }

        .image-frame {
            width: 100%;
            max-width: 600px;
            aspect-ratio: 1;
            border-radius: 50%;
            background: linear-gradient(135deg, #818cf8 0%, #4ade80 100%);
            padding: 4px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
        }

        .image-inner {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            overflow: hidden;
            background: #1f2937;
        }

        .image-inner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: rgba(74, 222, 128, 0.2);
            border: 1px solid #4ade80;
            color: #4ade80;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #ef4444;
        }

        .hidden {
            display: none;
        }

        @media (max-width: 1024px) {
            .container {
                flex-direction: column;
                align-items: center;
            }

            .image-container {
                min-width: auto;
                max-width: 400px;
            }

            .login-card {
                max-width: 600px;
            }
        }

        @media (max-width: 640px) {
            .login-card {
                padding: 30px 25px;
            }

            .welcome h1 {
                font-size: 28px;
            }

            .role-buttons {
                flex-direction: column;
            }

            .role-btn {
                width: 100%;
            }

            .image-container {
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
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

    <div class="container">
        <div class="login-card">
            <div class="welcome">
                <h1><span class="wave">👋</span> <span id="formTitle">Welcome to RMS</span></h1>
            </div>

            <div id="alertBox"></div>

            <div class="role-selector">
                <p>Please select a role</p>
                <div class="role-buttons">
                    <button class="role-btn active" data-role="tenant">tenant</button>
                    <button class="role-btn" data-role="caretaker">caretaker</button>
                    <button class="role-btn" data-role="landlord">landlord</button>
                </div>
            </div>

            <!-- Login Form -->
            <form id="loginForm" method="POST" action="login.php">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="role" id="loginRole" value="tenant">
                
                <div class="form-group">
                    <input type="text" name="username" class="form-input" placeholder="Email or username" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-input" placeholder="Password" required>
                </div>
                <button type="submit" class="login-btn">Login</button>
            </form>

            <!-- Register Form (Hidden by default) -->
            <form id="registerForm" method="POST" action="login.php" class="hidden">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="role" id="registerRole" value="tenant">
                
                <div class="form-group">
                    <input type="text" name="fullname" class="form-input" placeholder="Full Name" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" class="form-input" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="text" name="username" class="form-input" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-input" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" class="form-input" placeholder="Confirm Password" required>
                </div>
                <button type="submit" class="login-btn">Register</button>
            </form>

            <div class="forgot-password" id="forgotPassword">
                <a href="#">Forgot Password?</a>
            </div>

            <div class="register-section">
                <span id="toggleText">Don't have an account?</span> <a href="#" id="toggleForm">Register</a>
            </div>
        </div>

        <div class="image-container">
            <div class="image-frame">
                <div class="image-inner">
                    <svg viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="skyGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#1e3a3f;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#2d4d52;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        <rect width="600" height="600" fill="url(#skyGrad)"/>
                        <ellipse cx="300" cy="520" rx="250" ry="60" fill="#1a2d32" opacity="0.6"/>
                        <polygon points="200,320 200,480 320,480 400,400 400,240" fill="#0f1a1d"/>
                        <polygon points="400,240 400,400 480,320 480,160" fill="#1a2d32"/>
                        <rect x="220" y="340" width="80" height="3" fill="#fb923c"/>
                        <rect x="220" y="360" width="80" height="3" fill="#fb923c"/>
                        <rect x="220" y="380" width="80" height="3" fill="#fb923c"/>
                        <rect x="220" y="400" width="80" height="3" fill="#fb923c"/>
                        <rect x="220" y="420" width="80" height="3" fill="#fb923c"/>
                        <rect x="220" y="440" width="80" height="3" fill="#fb923c"/>
                        <rect x="410" y="260" width="60" height="3" fill="#fb923c"/>
                        <rect x="410" y="280" width="60" height="3" fill="#fb923c"/>
                        <rect x="410" y="300" width="60" height="3" fill="#fb923c"/>
                        <rect x="410" y="320" width="60" height="3" fill="#fb923c"/>
                        <rect x="410" y="340" width="60" height="3" fill="#fb923c"/>
                        <rect x="410" y="360" width="60" height="3" fill="#fb923c"/>
                        <rect x="0" y="480" width="600" height="120" fill="#0a1214"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Role selection
        const roleButtons = document.querySelectorAll('.role-btn');
        let selectedRole = 'tenant';

        roleButtons.forEach(button => {
            button.addEventListener('click', function() {
                roleButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                selectedRole = this.dataset.role;
                document.getElementById('loginRole').value = selectedRole;
                document.getElementById('registerRole').value = selectedRole;
            });
        });

        // Toggle between login and register forms
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const toggleFormLink = document.getElementById('toggleForm');
        const toggleText = document.getElementById('toggleText');
        const formTitle = document.getElementById('formTitle');
        const forgotPassword = document.getElementById('forgotPassword');
        let isLoginForm = true;

        toggleFormLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (isLoginForm) {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
                forgotPassword.classList.add('hidden');
                toggleText.textContent = 'Already have an account?';
                toggleFormLink.textContent = 'Login';
                formTitle.textContent = 'Create Account';
            } else {
                registerForm.classList.add('hidden');
                loginForm.classList.remove('hidden');
                forgotPassword.classList.remove('hidden');
                toggleText.textContent = "Don't have an account?";
                toggleFormLink.textContent = 'Register';
                formTitle.textContent = 'Welcome to RMS';
            }
            
            isLoginForm = !isLoginForm;
        });

        // Handle URL parameters for messages
        const urlParams = new URLSearchParams(window.location.search);
        const alertBox = document.getElementById('alertBox');
        
        if (urlParams.has('success')) {
            showAlert(urlParams.get('success'), 'success');
        }
        if (urlParams.has('error')) {
            showAlert(urlParams.get('error'), 'error');
        }

        function showAlert(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            alertBox.innerHTML = `<div class="alert ${alertClass}">${decodeURIComponent(message)}</div>`;
            setTimeout(() => {
                alertBox.innerHTML = '';
            }, 5000);
        }
    </script>
</body>
</html>
