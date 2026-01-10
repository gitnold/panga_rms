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
