// Government Services Management System - Login Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the page
    initializePage();
    
    // Set up event listeners
    setupEventListeners();
    
    // Update date and time
    updateDateTime();
    setInterval(updateDateTime, 1000);
});

function initializePage() {
    // Add loading animation to buttons
    addLoadingStates();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Add smooth scrolling
    addSmoothScrolling();
}

function setupEventListeners() {
    // Login form submission
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLoginSubmit);
    }
    
    // Google Sign-In is handled automatically by the Google library
    
    // Email input validation
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('blur', validateEmail);
        emailInput.addEventListener('input', clearEmailError);
    }
    
    // Password input validation
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('blur', validatePassword);
        passwordInput.addEventListener('input', clearPasswordError);
    }
    
    // Register toggle
    const showRegister = document.getElementById('showRegister');
    if (showRegister) {
        showRegister.addEventListener('click', showRegisterForm);
    }
    const cancelRegister = document.getElementById('cancelRegister');
    if (cancelRegister) {
        cancelRegister.addEventListener('click', hideRegisterForm);
    }
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegisterSubmit);
    }
    const regPassword = document.getElementById('regPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    if (regPassword) {
        regPassword.addEventListener('input', function(){
            validateRegPassword(this);
            updatePasswordChecklist(this.value);
            const cp = document.getElementById('confirmPassword');
            if (cp && cp.value) { validateConfirmPassword(true); }
        });
        regPassword.addEventListener('blur', function(){
            validateRegPassword(this, true);
            updatePasswordChecklist(this.value);
            const cp = document.getElementById('confirmPassword');
            if (cp && cp.value) { validateConfirmPassword(true); }
        });
    }
    if (confirmPassword) {
        confirmPassword.addEventListener('input', function(){ validateConfirmPassword(true); });
        confirmPassword.addEventListener('blur', function(){ validateConfirmPassword(true); });
    }
    const toggles = document.querySelectorAll('.toggle-password');
    toggles.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            const icon = btn.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            }
        });
    });
    const noMiddleName = document.getElementById('noMiddleName');
    if (noMiddleName) {
        noMiddleName.addEventListener('change', function() {
            const middle = document.getElementById('middleName');
            const asterisk = document.getElementById('middleAsterisk');
            if (!middle) return;
            middle.disabled = this.checked;
            middle.required = !this.checked;
            if (asterisk) {
                asterisk.style.display = this.checked ? 'none' : 'inline';
            }
            if (this.checked) middle.value = '';
        });
    }

    // Terms modal wiring
    const openTerms = document.getElementById('openTerms');
    const footerTerms = document.getElementById('footerTerms');
    const termsModal = document.getElementById('termsModal');
    const closeTerms = document.getElementById('closeTerms');
    const closeTermsBottom = document.getElementById('closeTermsBottom');
    const openPrivacy = document.getElementById('openPrivacy');
    const footerPrivacy = document.getElementById('footerPrivacy');
    const privacyModal = document.getElementById('privacyModal');
    const closePrivacy = document.getElementById('closePrivacy');
    const closePrivacyBottom = document.getElementById('closePrivacyBottom');
    function showTerms() {
        if (!termsModal) return;
        termsModal.classList.remove('hidden');
        termsModal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }
    function hideTerms() {
        if (!termsModal) return;
        termsModal.classList.add('hidden');
        termsModal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }
    if (openTerms) openTerms.addEventListener('click', showTerms);
    if (footerTerms) footerTerms.addEventListener('click', showTerms);
    if (closeTerms) closeTerms.addEventListener('click', hideTerms);
    if (closeTermsBottom) closeTermsBottom.addEventListener('click', hideTerms);
    if (termsModal) {
        termsModal.addEventListener('click', (e) => {
            if (e.target === termsModal) hideTerms();
        });
    }

    function showPrivacy() {
        if (!privacyModal) return;
        privacyModal.classList.remove('hidden');
        privacyModal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }
    function hidePrivacy() {
        if (!privacyModal) return;
        privacyModal.classList.add('hidden');
        privacyModal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }
    if (openPrivacy) openPrivacy.addEventListener('click', showPrivacy);
    if (footerPrivacy) footerPrivacy.addEventListener('click', showPrivacy);
    if (closePrivacy) closePrivacy.addEventListener('click', hidePrivacy);
    if (closePrivacyBottom) closePrivacyBottom.addEventListener('click', hidePrivacy);
    if (privacyModal) {
        privacyModal.addEventListener('click', (e) => {
            if (e.target === privacyModal) hidePrivacy();
        });
    }
}

function updateDateTime() {
    const now = new Date();
    const options = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    };
    
    const dateTimeString = now.toLocaleDateString('en-US', options).toUpperCase();
    const dateTimeElement = document.getElementById('currentDateTime');
    
    if (dateTimeElement) {
        dateTimeElement.textContent = dateTimeString;
    }
}

function addLoadingStates() {
    // Disabled loading states
    return;
}

function showLoadingState(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="loading"></span> Processing...';
    button.disabled = true;
    
    // Simulate processing time
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    }, 2000);
}

function initializeFormValidation() {
    // Add real-time validation
    const inputs = document.querySelectorAll('input[required]');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            validateField(this);
        });
    });
}

function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name;
    
    // Remove existing error styling
    field.classList.remove('border-red-500', 'ring-red-500');
    field.classList.add('border-gray-300', 'ring-custom-secondary');
    
    // Remove existing error message
    const existingError = field.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    if (fieldName === 'email') {
        validateEmail(field);
    }
}

function validateEmail(input) {
    if (!input || !input.value) return true;
    const email = input.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email && !emailRegex.test(email)) {
        showFieldError(input, 'Please enter a valid email address');
        return false;
    }
    
    clearEmailError(input);
    return true;
}

function clearEmailError(input) {
    if (!input || !input.classList) return;
    input.classList.remove('border-red-500', 'ring-red-500');
    input.classList.add('border-gray-300', 'ring-custom-secondary');
    
    const errorMessage = input.parentNode?.querySelector('.error-message');
    if (errorMessage) {
        errorMessage.remove();
    }
}

function validatePassword(input) {
    if (!input || !input.value) return true;
    const password = input.value.trim();
    
    if (password && password.length < 6) {
        showFieldError(input, 'Password must be at least 6 characters long');
        return false;
    }
    
    clearPasswordError(input);
    return true;
}

function clearPasswordError(input) {
    if (!input || !input.classList) return;
    input.classList.remove('border-red-500', 'ring-red-500');
    input.classList.add('border-gray-300', 'ring-custom-secondary');
    
    const errorMessage = input.parentNode?.querySelector('.error-message');
    if (errorMessage) {
        errorMessage.remove();
    }
}

function showFieldError(field, message) {
    field.classList.remove('border-gray-300', 'ring-custom-secondary');
    field.classList.add('border-red-500', 'ring-red-500');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message text-red-500 text-sm mt-1';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

function handleLoginSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const email = form.email.value.trim();
    const password = form.password.value.trim();
    
    // Validate email
    if (!validateEmail(form.email)) {
        return;
    }
    
    // Validate password
    if (!validatePassword(form.password)) {
        return;
    }
    
    // Send OTP first
    sendOTP(email, password);
}

function sendOTP(email, password) {
    // First validate credentials
    fetch('./gsm_login/Login/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'check_credentials', email: email, password: password })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Credentials valid, send OTP
            return fetch('./gsm_login/Login/send_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'send_otp', email: email })
            });
        } else {
            throw new Error('Invalid credentials');
        }
    })
    .then(response => response.json())
    .then(otpData => {
        if (otpData.success) {
            sessionStorage.setItem('temp_email', email);
            sessionStorage.setItem('temp_password', password);
            showNotification(otpData.message, 'success');
            openOtpModal();
        } else {
            showNotification('OTP Error: ' + otpData.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Login failed: ' + error.message, 'error');
    });
}

function handleSocialLogin(event) {
    event.preventDefault();
    const button = event.target.closest('button');
    const buttonText = button.textContent.trim();
    
    if (buttonText.includes('Google')) {
        initiateGoogleLogin();
    } else {
        showNotification('This social login option is not yet implemented.', 'info');
    }
}

function handleGoogleSignIn(response) {
    const credential = response.credential;
    
    fetch('google_auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ credential: credential })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const redirectUrl = data.data?.redirect || '../../administrator/index.php';
            window.location.href = redirectUrl;
        } else if (data.data?.needs_registration) {
            // Show Google registration form
            showGoogleRegistrationForm(data.data);
        } else {
            showNotification('Google login failed: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Google login error. Please try again.', 'error');
    });
}

function showRegisterForm() {
    const container = document.getElementById('registerFormContainer');
    const mainCard = document.querySelector('.glass-card');
    if (container && mainCard) {
        container.classList.remove('hidden');
        // Optionally dim the main card
        mainCard.classList.add('opacity-40');
    }
}

function hideRegisterForm() {
    const container = document.getElementById('registerFormContainer');
    const mainCard = document.querySelector('.glass-card');
    if (container && mainCard) {
        container.classList.add('hidden');
        mainCard.classList.remove('opacity-40');
    }
}

function handleRegisterSubmit(event) {
    event.preventDefault();
    alert('Registration form submitted!');
    const form = event.target;
    const data = serializeForm(form);
    console.log('Form data:', data);
    
    if (!document.getElementById('agreeTerms').checked) {
        showNotification('You must agree to the Terms and Privacy Policy.', 'warning');
        return;
    }
    if (data.regPassword !== data.confirmPassword) {
        showNotification('Passwords do not match.', 'error');
        return;
    }
    
    fetch('./gsm_login/Login/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...data, action: 'register' })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('Registration successful! You can now login.', 'success');
            hideRegisterForm();
            form.reset();
        } else {
            showNotification('Registration failed: ' + result.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Network error. Please try again.', 'error');
    });
}

function validateRegPassword(inputEl, showMessage = false) {
    if (!inputEl) return false;
    const value = inputEl.value || '';
    const isValid = /[A-Z]/.test(value) && /[a-z]/.test(value) && /\d/.test(value) && /[^A-Za-z0-9]/.test(value) && value.length >= 10;
    // Clear previous message
    const parent = inputEl.parentNode;
    const existing = parent.querySelector('.pwd-error');
    if (existing) existing.remove();
    inputEl.classList.remove('border-red-500', 'ring-red-500');
    if (!isValid && showMessage) {
        inputEl.classList.add('border-red-500', 'ring-red-500');
        // No verbose error text per request; visual cue only
    }
    return isValid;
}

function validateConfirmPassword(showMessage = false) {
    const pwd = document.getElementById('regPassword');
    const confirm = document.getElementById('confirmPassword');
    if (!pwd || !confirm) return false;
    const matches = (confirm.value || '') === (pwd.value || '');
    const wrapper = confirm.parentNode; // .relative wrapper
    // Place error message AFTER the wrapper so absolute eye icon stays aligned
    const existing = wrapper.parentNode.querySelector('.confirm-error');
    if (existing && existing.previousElementSibling !== wrapper) {
        existing.remove();
    }
    confirm.classList.remove('border-red-500', 'ring-red-500');
    if (!matches && showMessage) {
        confirm.classList.add('border-red-500', 'ring-red-500');
        let msg = wrapper.parentNode.querySelector('.confirm-error');
        if (!msg) {
            msg = document.createElement('div');
            msg.className = 'confirm-error text-red-500 text-sm mt-1';
            // insert after wrapper
            if (wrapper.nextSibling) {
                wrapper.parentNode.insertBefore(msg, wrapper.nextSibling);
            } else {
                wrapper.parentNode.appendChild(msg);
            }
        }
        msg.textContent = 'Passwords do not match.';
    }
    return matches;
}

function updatePasswordChecklist(value) {
    const checks = {
        length: value.length >= 10,
        upper: /[A-Z]/.test(value),
        lower: /[a-z]/.test(value),
        number: /\d/.test(value),
        special: /[^A-Za-z0-9]/.test(value)
    };
    const list = document.getElementById('pwdChecklist');
    if (!list) return;
    Object.keys(checks).forEach(key => {
        const item = list.querySelector(`.req-item[data-check="${key}"]`);
        if (!item) return;
        if (checks[key]) {
            item.classList.add('met');
        } else {
            item.classList.remove('met');
        }
    });
}

function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
    
    // Set notification style based on type
    switch (type) {
        case 'success':
            notification.classList.add('bg-green-500', 'text-white');
            break;
        case 'error':
            notification.classList.add('bg-red-500', 'text-white');
            break;
        case 'warning':
            notification.classList.add('bg-yellow-500', 'text-white');
            break;
        default:
            notification.classList.add('bg-blue-500', 'text-white');
    }
    
    notification.innerHTML = `
        <div class="flex items-center space-x-2">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }
    }, 5000);
}

function getNotificationIcon(type) {
    switch (type) {
        case 'success':
            return 'check-circle';
        case 'error':
            return 'exclamation-circle';
        case 'warning':
            return 'exclamation-triangle';
        default:
            return 'info-circle';
    }
}

function addSmoothScrolling() {
    // Add smooth scrolling to all anchor links
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Utility function for form data serialization
function serializeForm(form) {
    const formData = new FormData(form);
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    return data;
}

// Utility function for API calls
async function makeAPICall(url, data, method = 'POST') {
    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('API call failed:', error);
        showNotification('Network error. Please try again.', 'error');
        throw error;
    }
}

function showGoogleRegistrationForm(userData) {
    // Create and show Google registration modal
    const modal = document.createElement('div');
    modal.id = 'googleRegModal';
    modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-semibold mb-4 text-center">Complete Your Registration</h3>
            <p class="text-sm text-gray-600 mb-4 text-center">Please fill in your details to complete your Google account setup.</p>
            <form id="googleRegForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Select Your Role *</label>
                    <select name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Choose your role</option>
                        <option value="administrator">Administrator</option>
                        <option value="operator">Operator</option>
                        <option value="commuter">Commuter</option>
                    </select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">First Name *</label>
                        <input type="text" name="firstName" value="${userData.given_name}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Last Name *</label>
                        <input type="text" name="lastName" value="${userData.family_name}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Email Address</label>
                    <input type="email" value="${userData.email}" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Password *</label>
                        <input type="password" name="regPassword" minlength="6" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Confirm Password *</label>
                        <input type="password" name="confirmPassword" minlength="6" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="googleAgreeTerms" required class="mr-2">
                    <label for="googleAgreeTerms" class="text-sm">I agree to the Terms of Use and Privacy Policy</label>
                </div>
                <div class="flex justify-between space-x-3 pt-2">
                    <button type="button" onclick="closeGoogleRegModal()" class="flex-1 bg-gray-500 text-white px-4 py-2 rounded-lg">Cancel</button>
                    <button type="submit" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg">Complete Registration</button>
                </div>
            </form>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.classList.add('overflow-hidden');
    
    // Store user data for form submission
    window.googleUserData = userData;
    
    // Handle form submission
    document.getElementById('googleRegForm').addEventListener('submit', handleGoogleRegistration);
}

function closeGoogleRegModal() {
    const modal = document.getElementById('googleRegModal');
    if (modal) {
        modal.remove();
        document.body.classList.remove('overflow-hidden');
    }
    delete window.googleUserData;
}

function handleGoogleRegistration(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Validation
    if (!data.role) {
        showNotification('Please select a role', 'error');
        return;
    }
    
    if (!data.firstName || !data.lastName) {
        showNotification('Please fill in your name', 'error');
        return;
    }
    
    if (!data.regPassword || data.regPassword.length < 6) {
        showNotification('Password must be at least 6 characters', 'error');
        return;
    }
    
    if (data.regPassword !== data.confirmPassword) {
        showNotification('Passwords do not match', 'error');
        return;
    }
    
    if (!document.getElementById('googleAgreeTerms').checked) {
        showNotification('You must agree to the Terms and Privacy Policy', 'error');
        return;
    }
    
    const userData = window.googleUserData;
    
    fetch('./gsm_login/Login/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'register',
            firstName: data.firstName,
            lastName: data.lastName,
            regEmail: userData.email,
            regPassword: data.regPassword,
            role: data.role
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            closeGoogleRegModal();
            showNotification('Registration successful! You can now login with your email and password.', 'success');
        } else {
            showNotification('Registration failed: ' + result.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Registration error. Please try again.', 'error');
    });
}

// Export functions for use in other scripts
window.GSM = {
    showNotification,
    validateEmail,
    makeAPICall
};

// OTP modal logic
let otpIntervalId = null;
let otpExpiresAt = null;

function openOtpModal() {
    const modal = document.getElementById('otpModal');
    const resend = document.getElementById('resendOtp');
    const error = document.getElementById('otpError');
    const submit = document.getElementById('submitOtp');
    if (!modal) return;
    error.classList.add('hidden');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
    startOtpTimer(180); // 3 minutes
    resend.disabled = true;
    submit.disabled = false;
    const inputs = Array.from(document.querySelectorAll('#otpInputs .otp-input'));
    inputs.forEach(i => i.value = '');
    setupOtpInputs(inputs);
    if (inputs[0]) inputs[0].focus();
}

function closeOtpModal() {
    const modal = document.getElementById('otpModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
    stopOtpTimer();
}

function startOtpTimer(seconds) {
    otpExpiresAt = Date.now() + seconds * 1000;
    updateOtpTimer();
    if (otpIntervalId) clearInterval(otpIntervalId);
    otpIntervalId = setInterval(updateOtpTimer, 1000);
}

function stopOtpTimer() {
    if (otpIntervalId) clearInterval(otpIntervalId);
    otpIntervalId = null;
}

function updateOtpTimer() {
    const timerEl = document.getElementById('otpTimer');
    const resend = document.getElementById('resendOtp');
    const submit = document.getElementById('submitOtp');
    const remaining = Math.max(0, Math.floor((otpExpiresAt - Date.now()) / 1000));
    const mm = String(Math.floor(remaining / 60)).padStart(2, '0');
    const ss = String(remaining % 60).padStart(2, '0');
    if (timerEl) timerEl.textContent = `${mm}:${ss}`;
    if (remaining === 0) {
        if (resend) resend.disabled = false;
        if (submit) submit.disabled = true;
        stopOtpTimer();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const cancelOtp = document.getElementById('cancelOtp');
    const otpForm = document.getElementById('otpForm');
    const resend = document.getElementById('resendOtp');
    const modal = document.getElementById('otpModal');
    const submitOtp = document.getElementById('submitOtp');
    if (cancelOtp) cancelOtp.addEventListener('click', closeOtpModal);
    
    // Direct click handler for verify button
    if (submitOtp) {
        submitOtp.addEventListener('click', (e) => {
            e.preventDefault();
            console.log('Verify button clicked - processing OTP');
            handleOtpVerification();
        });
    }
    if (resend) resend.addEventListener('click', () => {
        const email = sessionStorage.getItem('temp_email');
        if (email && !resend.disabled) {
            resend.disabled = true;
            resend.textContent = 'Sending...';
            
            fetch('./gsm_login/Login/send_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'send_otp',
                    email: email
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('New OTP sent to your email!', 'success');
                    startOtpTimer(180);
                    resend.textContent = 'Resend OTP';
                } else {
                    showNotification('Error: ' + data.message, 'error');
                    resend.disabled = false;
                    resend.textContent = 'Resend OTP';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error. Please try again.', 'error');
                resend.disabled = false;
                resend.textContent = 'Resend OTP';
            });
        }
    });
    if (otpForm) otpForm.addEventListener('submit', (e) => {
        e.preventDefault();
        console.log('OTP form submitted!');
        const code = collectOtpCode();
        const error = document.getElementById('otpError');
        console.log('Collected OTP code:', code);
        if (!code || code.length !== 6) {
            error.textContent = 'Please enter the 6-digit OTP.';
            error.classList.remove('hidden');
            return;
        }
        if (document.getElementById('submitOtp').disabled) {
            error.textContent = 'OTP expired. Please resend a new OTP.';
            error.classList.remove('hidden');
            return;
        }
        const email = sessionStorage.getItem('temp_email');
        const password = sessionStorage.getItem('temp_password');
        console.log('Email from session:', email);
        
        // Verify OTP
        fetch('./gsm_login/Login/send_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'verify_otp',
                email: email,
                otp: code
            })
        })
        .then(response => {
            console.log('OTP verify response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('OTP verification response:', data);
            if (data.success) {
                error.classList.add('hidden');
                showNotification('OTP verified! Logging in...', 'success');
                // OTP verified, now login
                proceedWithLogin(email, password);
            } else {
                console.log('OTP verification failed:', data.message);
                error.textContent = data.message;
                error.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const errorEl = document.getElementById('otpError');
            errorEl.textContent = 'Network error. Please try again.';
            errorEl.classList.remove('hidden');
        });
    });
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeOtpModal();
        });
    }
});

function setupOtpInputs(inputs) {
    inputs.forEach((input, idx) => {
        input.addEventListener('input', (e) => {
            const value = e.target.value.replace(/\D/g, '').slice(0,1);
            e.target.value = value;
            if (value && idx < inputs.length - 1) inputs[idx + 1].focus();
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && idx > 0) {
                inputs[idx - 1].focus();
            }
            if (e.key === 'Enter') {
                handleOtpVerification();
            }
        });
        input.addEventListener('paste', (e) => {
            const text = (e.clipboardData || window.clipboardData).getData('text');
            if (!text) return;
            const digits = text.replace(/\D/g, '').slice(0, inputs.length).split('');
            inputs.forEach((i, iIdx) => { i.value = digits[iIdx] || ''; });
            e.preventDefault();
            const nextIndex = Math.min(digits.length, inputs.length - 1);
            inputs[nextIndex].focus();
        });
    });
}

function collectOtpCode() {
    const inputs = Array.from(document.querySelectorAll('#otpInputs .otp-input'));
    const code = inputs.map(i => i.value).join('');
    console.log('OTP inputs found:', inputs.length, 'Code collected:', code);
    return code;
}

function handleOtpVerification() {
    const code = collectOtpCode();
    const error = document.getElementById('otpError');
    
    if (!code || code.length !== 6) {
        error.textContent = 'Please enter the 6-digit OTP.';
        error.classList.remove('hidden');
        return;
    }
    
    const email = sessionStorage.getItem('temp_email');
    const password = sessionStorage.getItem('temp_password');
    
    fetch('./gsm_login/Login/send_otp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'verify_otp', email: email, otp: code })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            error.classList.add('hidden');
            proceedWithLogin(email, password);
        } else {
            error.textContent = data.message;
            error.classList.remove('hidden');
        }
    })
    .catch(err => {
        error.textContent = 'Network error. Please try again.';
        error.classList.remove('hidden');
    });
}

function proceedWithLogin(email, password) {
    fetch('./gsm_login/Login/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'login', email: email, password: password })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeOtpModal();
            sessionStorage.removeItem('temp_email');
            sessionStorage.removeItem('temp_password');
            const redirectUrl = data.data?.redirect || '../../administrator/index.php';
            window.location.href = redirectUrl;
        } else {
            closeOtpModal();
            showNotification('Login failed: ' + data.message, 'error');
        }
    })
    .catch(error => {
        closeOtpModal();
        showNotification('Network error. Please try again.', 'error');
    });
}
