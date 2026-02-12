/**
 * Library Management System - Client-Side JavaScript
 * 
 * This file contains client-side functionality:
 * - Delete confirmation dialogs
 * - Auto-dismiss flash alerts
 * - Client-side form validation
 * - Active nav link highlighting
 */

document.addEventListener('DOMContentLoaded', function () {

    // ====================================================
    // 1. DELETE CONFIRMATION
    // ====================================================
    // When a user clicks a delete button/link, show a confirmation dialog.
    // This prevents accidental deletion of records.
    
    const deleteButtons = document.querySelectorAll('.delete-confirm');
    deleteButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const name = this.dataset.name || 'this item';
            if (!confirm('Are you sure you want to delete "' + name + '"? This action cannot be undone.')) {
                e.preventDefault(); // Cancel the navigation if user clicks "Cancel"
            }
        });
    });

    // ====================================================
    // 2. AUTO-DISMISS FLASH ALERTS
    // ====================================================
    // Flash messages (success, error, etc.) automatically fade out after 5 seconds.
    
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            // Use Bootstrap's built-in alert dismiss
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000); // 5 seconds
    });

    // ====================================================
    // 3. CLIENT-SIDE FORM VALIDATION
    // ====================================================
    // Uses Bootstrap's built-in validation styles.
    // The 'novalidate' attribute on forms lets us handle validation via JS.
    
    const forms = document.querySelectorAll('form[novalidate]');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // ====================================================
    // 4. PASSWORD MATCH VALIDATION (Registration Form)
    // ====================================================
    
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        if (password && confirmPassword) {
            confirmPassword.addEventListener('input', function () {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match.');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            });
            
            password.addEventListener('input', function () {
                if (confirmPassword.value && password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match.');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            });
        }
    }

    // ====================================================
    // 5. ACTIVE NAV LINK HIGHLIGHTING
    // ====================================================
    // Automatically highlights the current page in the navigation bar.
    
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    navLinks.forEach(function (link) {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href.replace(/^\/lib/, ''))) {
            link.classList.add('active');
        }
    });

    // ====================================================
    // 6. TOOLTIP INITIALIZATION
    // ====================================================
    // Initialize Bootstrap tooltips if any exist on the page
    
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

});
