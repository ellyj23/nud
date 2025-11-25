/**
 * Feza Logistics - Form Validation JavaScript
 * Reusable, professional form validation with real-time feedback
 */

(function() {
  'use strict';

  // ==========================================================================
  // Configuration
  // ==========================================================================
  
  const config = {
    debounceDelay: 300,
    minPasswordLength: 8,
    minUsernameLength: 3,
    passwordStrengthLevels: {
      weak: 1,
      fair: 2,
      good: 3,
      strong: 4
    }
  };

  // ==========================================================================
  // Utility Functions
  // ==========================================================================
  
  /**
   * Debounce function to limit how often a function can run
   */
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  /**
   * Check if a value is a valid email format
   */
  function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  /**
   * Check password strength and return a score
   */
  function getPasswordStrength(password) {
    let score = 0;
    
    if (!password || password.length < config.minPasswordLength) {
      return { score: 0, label: 'Too short', class: 'weak' };
    }
    
    // Length bonus
    if (password.length >= 8) score += 1;
    if (password.length >= 12) score += 1;
    
    // Character variety
    if (/[a-z]/.test(password)) score += 1;
    if (/[A-Z]/.test(password)) score += 1;
    if (/[0-9]/.test(password)) score += 1;
    if (/[^a-zA-Z0-9]/.test(password)) score += 1;
    
    // Determine strength level
    if (score <= 2) return { score: 1, label: 'Weak', class: 'weak' };
    if (score <= 3) return { score: 2, label: 'Fair', class: 'fair' };
    if (score <= 4) return { score: 3, label: 'Good', class: 'good' };
    return { score: 4, label: 'Strong', class: 'strong' };
  }

  /**
   * Create a validation feedback element
   */
  function createFeedbackElement(type, message) {
    const feedback = document.createElement('div');
    feedback.className = `validation-feedback validation-${type}`;
    feedback.innerHTML = `
      <span class="validation-icon">${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}</span>
      <span class="validation-message">${message}</span>
    `;
    return feedback;
  }

  /**
   * Show validation feedback for an input
   */
  function showFeedback(input, type, message) {
    // Remove existing feedback
    const existingFeedback = input.parentElement.querySelector('.validation-feedback');
    if (existingFeedback) {
      existingFeedback.remove();
    }
    
    // Update input classes
    input.classList.remove('is-valid', 'is-invalid');
    if (type === 'success') {
      input.classList.add('is-valid');
    } else if (type === 'error') {
      input.classList.add('is-invalid');
    }
    
    // Add new feedback
    if (message) {
      const feedback = createFeedbackElement(type, message);
      input.parentElement.appendChild(feedback);
    }
  }

  /**
   * Clear validation feedback for an input
   */
  function clearFeedback(input) {
    input.classList.remove('is-valid', 'is-invalid');
    const existingFeedback = input.parentElement.querySelector('.validation-feedback');
    if (existingFeedback) {
      existingFeedback.remove();
    }
  }

  // ==========================================================================
  // Validation Functions
  // ==========================================================================
  
  /**
   * Validate username or email field
   */
  function validateUsernameEmail(input) {
    const value = input.value.trim();
    
    if (!value) {
      clearFeedback(input);
      return false;
    }
    
    // Check if it's an email (contains @)
    if (value.includes('@')) {
      if (isValidEmail(value)) {
        showFeedback(input, 'success', 'Valid email format');
        return true;
      } else {
        showFeedback(input, 'error', 'Please enter a valid email address');
        return false;
      }
    } else {
      // Validate as username
      if (value.length < config.minUsernameLength) {
        showFeedback(input, 'error', `Username must be at least ${config.minUsernameLength} characters`);
        return false;
      }
      showFeedback(input, 'success', 'Valid username');
      return true;
    }
  }

  /**
   * Validate password field with strength indicator
   */
  function validatePassword(input, showStrength = true) {
    const value = input.value;
    const wrapper = input.closest('.input-wrapper') || input.parentElement;
    
    // Remove existing strength meter
    let strengthMeter = wrapper.querySelector('.password-strength-meter');
    
    if (!value) {
      clearFeedback(input);
      if (strengthMeter) strengthMeter.remove();
      return false;
    }
    
    const strength = getPasswordStrength(value);
    
    // Create or update strength meter
    if (showStrength) {
      if (!strengthMeter) {
        strengthMeter = document.createElement('div');
        strengthMeter.className = 'password-strength-meter';
        wrapper.appendChild(strengthMeter);
      }
      
      strengthMeter.innerHTML = `
        <div class="strength-bar">
          <div class="strength-fill strength-${strength.class}" style="width: ${strength.score * 25}%"></div>
        </div>
        <span class="strength-label strength-${strength.class}">${strength.label}</span>
        <span class="char-count">${value.length} characters</span>
      `;
    }
    
    if (value.length < config.minPasswordLength) {
      showFeedback(input, 'error', `Password must be at least ${config.minPasswordLength} characters`);
      return false;
    }
    
    if (strength.score >= 2) {
      showFeedback(input, 'success', '');
      return true;
    } else {
      showFeedback(input, 'warning', 'Consider adding uppercase, numbers, or special characters');
      return false;
    }
  }

  /**
   * Validate email field
   */
  function validateEmail(input) {
    const value = input.value.trim();
    
    if (!value) {
      clearFeedback(input);
      return false;
    }
    
    if (isValidEmail(value)) {
      showFeedback(input, 'success', 'Valid email address');
      return true;
    } else {
      showFeedback(input, 'error', 'Please enter a valid email address');
      return false;
    }
  }

  /**
   * Validate required field
   */
  function validateRequired(input) {
    const value = input.value.trim();
    
    if (!value) {
      showFeedback(input, 'error', 'This field is required');
      return false;
    }
    
    showFeedback(input, 'success', '');
    return true;
  }

  // ==========================================================================
  // Password Visibility Toggle
  // ==========================================================================
  
  /**
   * Initialize password visibility toggle for an input
   */
  function initPasswordToggle(input) {
    const wrapper = input.closest('.input-wrapper') || input.parentElement;
    
    // Check if toggle already exists
    if (wrapper.querySelector('.password-toggle')) return;
    
    // Create toggle button
    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'password-toggle';
    toggle.setAttribute('aria-label', 'Toggle password visibility');
    toggle.innerHTML = `
      <svg class="eye-icon eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
        <circle cx="12" cy="12" r="3"></circle>
      </svg>
      <svg class="eye-icon eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
        <line x1="1" y1="1" x2="23" y2="23"></line>
      </svg>
    `;
    
    toggle.addEventListener('click', function() {
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      
      const openIcon = toggle.querySelector('.eye-open');
      const closedIcon = toggle.querySelector('.eye-closed');
      openIcon.style.display = isPassword ? 'none' : 'block';
      closedIcon.style.display = isPassword ? 'block' : 'none';
    });
    
    wrapper.style.position = 'relative';
    wrapper.appendChild(toggle);
  }

  // ==========================================================================
  // Caps Lock Detection
  // ==========================================================================
  
  /**
   * Initialize caps lock warning for an input
   */
  function initCapsLockWarning(input) {
    const wrapper = input.closest('.input-wrapper') || input.parentElement;
    
    // Create warning element
    let warning = wrapper.querySelector('.caps-lock-warning');
    if (!warning) {
      warning = document.createElement('div');
      warning.className = 'caps-lock-warning';
      warning.innerHTML = '⚠ Caps Lock is ON';
      warning.style.display = 'none';
      wrapper.appendChild(warning);
    }
    
    input.addEventListener('keyup', function(e) {
      const isCapsLock = e.getModifierState && e.getModifierState('CapsLock');
      warning.style.display = isCapsLock ? 'block' : 'none';
    });
    
    input.addEventListener('blur', function() {
      warning.style.display = 'none';
    });
  }

  // ==========================================================================
  // Loading States
  // ==========================================================================
  
  /**
   * Set form loading state
   */
  function setFormLoading(form, isLoading) {
    const submitBtn = form.querySelector('button[type="submit"]');
    
    if (isLoading) {
      form.classList.add('is-loading');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.dataset.originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = `
          <span class="loading-spinner"></span>
          <span>Processing...</span>
        `;
      }
    } else {
      form.classList.remove('is-loading');
      if (submitBtn && submitBtn.dataset.originalText) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = submitBtn.dataset.originalText;
      }
    }
  }

  // ==========================================================================
  // Form Initialization
  // ==========================================================================
  
  /**
   * Initialize validation for a form
   */
  function initFormValidation(form, options = {}) {
    const defaults = {
      validateOnInput: true,
      validateOnBlur: true,
      showPasswordStrength: true,
      enablePasswordToggle: true,
      enableCapsLockWarning: true,
      onSubmit: null
    };
    
    const settings = { ...defaults, ...options };
    
    // Find all inputs that need validation
    const usernameInputs = form.querySelectorAll('[data-validate="username-email"], [name="login_identifier"]');
    const emailInputs = form.querySelectorAll('[data-validate="email"], [type="email"]');
    const passwordInputs = form.querySelectorAll('[data-validate="password"], [type="password"]');
    const requiredInputs = form.querySelectorAll('[required]');
    
    // Initialize username/email validation
    usernameInputs.forEach(input => {
      if (settings.validateOnInput) {
        input.addEventListener('input', debounce(() => validateUsernameEmail(input), config.debounceDelay));
      }
      if (settings.validateOnBlur) {
        input.addEventListener('blur', () => validateUsernameEmail(input));
      }
    });
    
    // Initialize email validation
    emailInputs.forEach(input => {
      if (!input.closest('[data-validate="username-email"]') && !input.hasAttribute('data-validate')) {
        if (settings.validateOnInput) {
          input.addEventListener('input', debounce(() => validateEmail(input), config.debounceDelay));
        }
        if (settings.validateOnBlur) {
          input.addEventListener('blur', () => validateEmail(input));
        }
      }
    });
    
    // Initialize password validation
    passwordInputs.forEach(input => {
      if (settings.showPasswordStrength && !input.dataset.noStrength) {
        if (settings.validateOnInput) {
          input.addEventListener('input', () => validatePassword(input, true));
        }
      }
      
      if (settings.enablePasswordToggle) {
        initPasswordToggle(input);
      }
      
      if (settings.enableCapsLockWarning) {
        initCapsLockWarning(input);
      }
    });
    
    // Form submission handling
    form.addEventListener('submit', function(e) {
      let isValid = true;
      
      // Validate all required fields
      requiredInputs.forEach(input => {
        if (!input.value.trim()) {
          isValid = false;
          showFeedback(input, 'error', 'This field is required');
        }
      });
      
      // Validate username/email inputs
      usernameInputs.forEach(input => {
        if (!validateUsernameEmail(input)) {
          isValid = false;
        }
      });
      
      // Validate password inputs
      passwordInputs.forEach(input => {
        if (input.value && input.value.length < config.minPasswordLength) {
          isValid = false;
        }
      });
      
      if (!isValid) {
        e.preventDefault();
        return false;
      }
      
      // Show loading state
      setFormLoading(form, true);
      
      if (settings.onSubmit) {
        e.preventDefault();
        settings.onSubmit(form, e);
      }
    });
    
    return {
      validate: () => {
        let isValid = true;
        usernameInputs.forEach(input => {
          if (!validateUsernameEmail(input)) isValid = false;
        });
        passwordInputs.forEach(input => {
          if (!validatePassword(input, settings.showPasswordStrength)) isValid = false;
        });
        return isValid;
      },
      setLoading: (loading) => setFormLoading(form, loading),
      reset: () => {
        form.querySelectorAll('.validation-feedback').forEach(el => el.remove());
        form.querySelectorAll('.password-strength-meter').forEach(el => el.remove());
        form.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
          el.classList.remove('is-valid', 'is-invalid');
        });
      }
    };
  }

  // ==========================================================================
  // CSS Styles for Validation
  // ==========================================================================
  
  const validationStyles = `
    /* Validation Styles */
    .validation-feedback {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-top: 6px;
      font-size: 0.813rem;
      animation: validationFadeIn 0.2s ease;
    }
    
    @keyframes validationFadeIn {
      from { opacity: 0; transform: translateY(-4px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .validation-success {
      color: #10b981;
    }
    
    .validation-error {
      color: #ef4444;
    }
    
    .validation-warning {
      color: #f59e0b;
    }
    
    .validation-icon {
      font-weight: 600;
    }
    
    /* Input States */
    .form-input.is-valid,
    input.is-valid {
      border-color: #10b981 !important;
    }
    
    .form-input.is-valid:focus,
    input.is-valid:focus {
      box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1) !important;
    }
    
    .form-input.is-invalid,
    input.is-invalid {
      border-color: #ef4444 !important;
    }
    
    .form-input.is-invalid:focus,
    input.is-invalid:focus {
      box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1) !important;
    }
    
    /* Password Strength Meter */
    .password-strength-meter {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-top: 8px;
      font-size: 0.813rem;
    }
    
    .strength-bar {
      flex: 1;
      height: 4px;
      background: #e5e7eb;
      border-radius: 9999px;
      overflow: hidden;
    }
    
    .strength-fill {
      height: 100%;
      transition: width 0.3s ease, background-color 0.3s ease;
      border-radius: 9999px;
    }
    
    .strength-weak .strength-fill,
    .strength-fill.strength-weak {
      background: #ef4444;
    }
    
    .strength-fair .strength-fill,
    .strength-fill.strength-fair {
      background: #f59e0b;
    }
    
    .strength-good .strength-fill,
    .strength-fill.strength-good {
      background: #3b82f6;
    }
    
    .strength-strong .strength-fill,
    .strength-fill.strength-strong {
      background: #10b981;
    }
    
    .strength-label {
      font-weight: 500;
    }
    
    .strength-label.strength-weak { color: #ef4444; }
    .strength-label.strength-fair { color: #f59e0b; }
    .strength-label.strength-good { color: #3b82f6; }
    .strength-label.strength-strong { color: #10b981; }
    
    .char-count {
      color: #6b7280;
    }
    
    /* Password Toggle */
    .password-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      padding: 4px;
      color: #9ca3af;
      transition: color 0.2s;
      z-index: 10;
    }
    
    .password-toggle:hover {
      color: #6b7280;
    }
    
    .password-toggle .eye-icon {
      width: 20px;
      height: 20px;
    }
    
    /* Caps Lock Warning */
    .caps-lock-warning {
      position: absolute;
      right: 48px;
      top: 50%;
      transform: translateY(-50%);
      background: #fef3c7;
      color: #92400e;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 500;
      white-space: nowrap;
      z-index: 10;
    }
    
    /* Loading Spinner */
    .loading-spinner {
      display: inline-block;
      width: 18px;
      height: 18px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-top-color: white;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    /* Form Loading State */
    .is-loading .form-input,
    .is-loading input,
    .is-loading select,
    .is-loading textarea {
      pointer-events: none;
      opacity: 0.7;
    }
    
    /* Security Badge */
    .security-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.75rem;
      color: #10b981;
      padding: 4px 10px;
      background: rgba(16, 185, 129, 0.1);
      border-radius: 9999px;
    }
    
    .security-badge svg {
      width: 14px;
      height: 14px;
    }
  `;

  // Inject styles
  if (typeof document !== 'undefined') {
    const styleSheet = document.createElement('style');
    styleSheet.textContent = validationStyles;
    document.head.appendChild(styleSheet);
  }

  // ==========================================================================
  // Export to Global
  // ==========================================================================
  
  window.FezaFormValidation = {
    init: initFormValidation,
    validate: {
      usernameEmail: validateUsernameEmail,
      email: validateEmail,
      password: validatePassword,
      required: validateRequired
    },
    utils: {
      debounce,
      isValidEmail,
      getPasswordStrength
    },
    feedback: {
      show: showFeedback,
      clear: clearFeedback
    },
    setLoading: setFormLoading,
    initPasswordToggle,
    initCapsLockWarning
  };

})();
