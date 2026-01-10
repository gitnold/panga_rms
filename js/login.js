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

        if (selectedRole === 'tenant') {
            registerForm.classList.add('hidden');
            toggleFormLink.classList.add('hidden'); // Hide the toggle link itself
            forgotPassword.classList.remove('hidden'); // Ensure forgot password is visible for login
            loginForm.classList.remove('hidden'); // Ensure login form is visible
            isLoginForm = true; // Force to login view

            // Optionally, update formTitle to 'Welcome to RMS'
            formTitle.textContent = 'Welcome to RMS';
        } else {
            toggleFormLink.classList.remove('hidden'); // Show toggle link for other roles
            // Re-evaluate current form state for non-tenant roles
            if (isLoginForm) {
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
                forgotPassword.classList.remove('hidden');
                toggleText.textContent = "Don't have an account?";
                toggleFormLink.textContent = 'Register';
                formTitle.textContent = 'Welcome to RMS';
            } else {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
                forgotPassword.classList.add('hidden');
                toggleText.textContent = 'Already have an account?';
                toggleFormLink.textContent = 'Login';
                formTitle.textContent = 'Create Account';
            }
        }
    });
});

// Initial state setup (or if selected role is tenant initially)
if (selectedRole === 'tenant') {
    registerForm.classList.add('hidden');
    toggleFormLink.classList.add('hidden');
    forgotPassword.classList.remove('hidden');
    loginForm.classList.remove('hidden');
    isLoginForm = true;
    formTitle.textContent = 'Welcome to RMS';
} else {
    toggleFormLink.classList.remove('hidden');
}

// Toggle between login and register forms
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
