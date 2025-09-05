import { MessageHandler } from './notifications.js';
import { createIcon, CSS_CLASSES } from './utils.js';

/**
 * Form validation for auth pages
 */
export const FormValidation = {
  init() {
    const usernameInput = document.querySelector('input[name="username"]');
    const passwordInput = document.querySelector('input[name="password"]');
    
    if (usernameInput) {
      usernameInput.addEventListener('input', () => {
        this.validateUsername(usernameInput);
      });
    }
    
    if (passwordInput) {
      passwordInput.addEventListener('input', () => {
        this.validatePassword(passwordInput);
      });
    }
  },

  validateUsername(input) {
    if (input.value.length > 0 && input.value.length < 3) {
      input.classList.add(CSS_CLASSES.errorState);
    } else {
      input.classList.remove(CSS_CLASSES.errorState);
    }
  },

  validatePassword(input) {
    if (input.value.length > 0 && input.value.length < 6) {
      input.classList.add(CSS_CLASSES.errorState);
    } else {
      input.classList.remove(CSS_CLASSES.errorState);
    }
  }
};

/**
 * Enhanced Auth Handler with permanent login support
 */
export const AuthHandler = {
  init() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    if (loginForm) {
      loginForm.addEventListener('submit', this.handleLogin);
      
      const permanentCheckbox = document.getElementById('permanentLogin');
      if (permanentCheckbox) {
        permanentCheckbox.addEventListener('change', this.updateSessionInfo);
        this.updateSessionInfo();
      }
    }
    
    if (registerForm) {
      registerForm.addEventListener('submit', this.handleRegister);
    }
  },

  updateSessionInfo() {
    const sessionInfo = document.getElementById('sessionInfo');
    const tempInfo = sessionInfo?.querySelector('.temporary');
    const permInfo = sessionInfo?.querySelector('.permanent');
    const checkbox = document.getElementById('permanentLogin');
    
    if (!sessionInfo) return;
    
    if (checkbox?.checked) {
      sessionInfo.style.display = 'block';
      sessionInfo.classList.add('permanent');
      if (tempInfo) tempInfo.style.display = 'none';
      if (permInfo) permInfo.style.display = 'block';
    } else {
      sessionInfo.style.display = 'block';
      sessionInfo.classList.remove('permanent');
      if (tempInfo) tempInfo.style.display = 'block';
      if (permInfo) permInfo.style.display = 'none';
    }
  },

  async handleLogin(e) {
    e.preventDefault();
    
    const form = e.target;
    const loginBtn = document.getElementById('loginBtn');
    const msg = document.getElementById('msg');
    
    loginBtn.disabled = true;
    loginBtn.innerHTML = '<span>Signing in...</span>';
    form.classList.add(CSS_CLASSES.loading);
    MessageHandler.hide(msg);
    
    try {
      const formData = new FormData(form);
      
      const response = await fetch('/api/login', {
        method: 'POST',
        body: new URLSearchParams(formData)
      });
      
      const data = await response.json();
      
      if (response.ok && data.success) {
        loginBtn.innerHTML = '<span>Success! Redirecting...</span>';
        setTimeout(() => {
          location.href = '/notes';
        }, 500);
      } else {
        MessageHandler.show(msg, data.error || 'Login failed', 'error');
        AuthHandler.resetLoginButton(loginBtn, form);
      }
    } catch (error) {
      MessageHandler.show(msg, 'Network error. Please try again.', 'error');
      AuthHandler.resetLoginButton(loginBtn, form);
    }
  },

  async handleRegister(e) {
    e.preventDefault();
    
    const form = e.target;
    const registerBtn = document.getElementById('registerBtn');
    const msg = document.getElementById('msg');
    
    registerBtn.disabled = true;
    registerBtn.innerHTML = '<span>Creating account...</span>';
    form.classList.add(CSS_CLASSES.loading);
    MessageHandler.hide(msg);
    
    try {
      const response = await fetch('/api/register', {
        method: 'POST',
        body: new URLSearchParams(new FormData(form))
      });
      
      const data = await response.json();
      
      if (response.ok && data.success) {
        MessageHandler.show(msg, 'Account created successfully! You can now sign in.', 'success');
        form.reset();
        registerBtn.innerHTML = '<span>Account Created ✓</span>';
        
        setTimeout(() => {
          location.href = '/login';
        }, 2000);
      } else {
        MessageHandler.show(msg, data.error || 'Registration failed', 'error');
        AuthHandler.resetRegisterButton(registerBtn, form);
      }
    } catch (error) {
      MessageHandler.show(msg, 'Network error. Please try again.', 'error');
      AuthHandler.resetRegisterButton(registerBtn, form);
    }
  },

  resetLoginButton(button, form) {
    button.disabled = false;
    button.innerHTML = '';
    button.appendChild(createIcon('sign-in'));
    button.appendChild(document.createTextNode(' Login'));
    form.classList.remove(CSS_CLASSES.loading);
  },

  resetRegisterButton(button, form) {
    button.disabled = false;
    button.innerHTML = '';
    button.appendChild(createIcon('user-plus'));
    button.appendChild(document.createTextNode(' Create Account'));
    form.classList.remove(CSS_CLASSES.loading);
  }
};