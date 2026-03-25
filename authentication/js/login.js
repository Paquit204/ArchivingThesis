// Login Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const loginToggle = document.getElementById('login-toggle');
    const loginPass = document.getElementById('password');

    if (loginToggle && loginPass) {
        loginToggle.addEventListener('click', function() {
            if (loginPass.type === 'password') {
                loginPass.type = 'text';
                loginToggle.textContent = 'visibility';
            } else {
                loginPass.type = 'password';
                loginToggle.textContent = 'visibility_off';
            }
        });
    }

    // Dark mode toggle
    const toggle = document.getElementById('themeToggle');
    if (toggle) {
        toggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const icon = toggle.querySelector('i');
            if (document.body.classList.contains('dark-mode')) {
                icon.textContent = 'light_mode';
                localStorage.setItem('darkMode', 'true');
            } else {
                icon.textContent = 'dark_mode';
                localStorage.setItem('darkMode', 'false');
            }
        });
        
        // Load saved dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
            toggle.querySelector('i').textContent = 'light_mode';
        }
    }

    // Form validation before submit
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (username === '') {
                e.preventDefault();
                showMessage('Please enter your username or email', 'error');
                return false;
            }
            
            if (password === '') {
                e.preventDefault();
                showMessage('Please enter your password', 'error');
                return false;
            }
        });
    }

    // Show message function
    function showMessage(message, type) {
        const existingMessage = document.querySelector('.message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.innerHTML = `
            <span class="message-icon">${type === 'success' ? '✓' : '✕'}</span>
            <span class="message-text">${message}</span>
        `;
        
        const loginContainer = document.querySelector('.login-container');
        const form = document.querySelector('.login-form');
        loginContainer.insertBefore(messageDiv, form);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 300);
        }, 5000);
    }
});

// Quick login function (global for onclick)
function quickLogin(role) {
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    
    if (!usernameInput || !passwordInput) return;
    
    switch(role) {
        case 'student':
            usernameInput.value = 'student';
            passwordInput.value = 'student123';
            break;
        case 'faculty':
            usernameInput.value = 'faculty';
            passwordInput.value = 'faculty123';
            break;
        case 'dean':
            usernameInput.value = 'dean';
            passwordInput.value = 'dean123';
            break;
        case 'admin':
            usernameInput.value = 'admin';
            passwordInput.value = 'admin123';
            break;
        default:
            break;
    }
    
    // Optional: Auto-submit after filling
    // document.getElementById('loginForm').submit();
}