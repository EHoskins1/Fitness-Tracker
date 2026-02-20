/**
 * Fitness Tracker - JavaScript
 * Minimal JS for interactions
 */

(function() {
    'use strict';

    // ==========================================
    // NAVBAR TOGGLE (Mobile)
    // ==========================================
    const navbarToggle = document.getElementById('navbarToggle');
    const navbarMenu = document.querySelector('.navbar-menu');
    const navbarActions = document.querySelector('.navbar-actions');

    if (navbarToggle) {
        navbarToggle.addEventListener('click', function() {
            navbarMenu?.classList.toggle('active');
            navbarActions?.classList.toggle('active');
        });
    }

    // ==========================================
    // AUTO-DISMISS ALERTS
    // ==========================================
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 5000);
    });

    // ==========================================
    // FORM VALIDATION FEEDBACK
    // ==========================================
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(function(input) {
            // Real-time validation feedback
            input.addEventListener('blur', function() {
                validateInput(this);
            });

            input.addEventListener('input', function() {
                // Clear error state on input
                this.closest('.form-group')?.classList.remove('has-error');
            });
        });
    });

    function validateInput(input) {
        const formGroup = input.closest('.form-group');
        if (!formGroup) return;

        let isValid = true;

        // Required check
        if (input.hasAttribute('required') && !input.value.trim()) {
            isValid = false;
        }

        // Min length check
        if (input.hasAttribute('minlength')) {
            const minLength = parseInt(input.getAttribute('minlength'));
            if (input.value.length < minLength) {
                isValid = false;
            }
        }

        // Pattern check
        if (input.hasAttribute('pattern') && input.value) {
            const pattern = new RegExp(input.getAttribute('pattern'));
            if (!pattern.test(input.value)) {
                isValid = false;
            }
        }

        // Email check
        if (input.type === 'email' && input.value) {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(input.value)) {
                isValid = false;
            }
        }

        if (!isValid) {
            formGroup.classList.add('has-error');
        } else {
            formGroup.classList.remove('has-error');
        }

        return isValid;
    }

    // ==========================================
    // PASSWORD CONFIRMATION
    // ==========================================
    const passwordConfirm = document.getElementById('password_confirm');
    const password = document.getElementById('password');

    if (passwordConfirm && password) {
        passwordConfirm.addEventListener('input', function() {
            const formGroup = this.closest('.form-group');
            if (this.value !== password.value) {
                formGroup?.classList.add('has-error');
            } else {
                formGroup?.classList.remove('has-error');
            }
        });
    }

    // ==========================================
    // DATE INPUT - PREVENT FUTURE DATES
    // ==========================================
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(function(input) {
        if (input.hasAttribute('max') && input.getAttribute('max') === new Date().toISOString().split('T')[0]) {
            input.addEventListener('change', function() {
                const selected = new Date(this.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selected > today) {
                    this.value = today.toISOString().split('T')[0];
                }
            });
        }
    });

    // ==========================================
    // INTENSITY SLIDER FEEDBACK
    // ==========================================
    const intensitySlider = document.getElementById('intensity');
    const intensityValue = document.getElementById('intensity_value');

    if (intensitySlider && intensityValue) {
        intensitySlider.addEventListener('input', function() {
            intensityValue.textContent = this.value;
            
            // Update color based on intensity
            const value = parseInt(this.value);
            if (value <= 3) {
                intensityValue.style.color = '#22c55e'; // green
            } else if (value <= 6) {
                intensityValue.style.color = '#eab308'; // yellow
            } else {
                intensityValue.style.color = '#ef4444'; // red
            }
        });
    }

    // ==========================================
    // DROPDOWN KEYBOARD NAVIGATION
    // ==========================================
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(function(dropdown) {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (toggle && menu) {
            toggle.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    menu.classList.toggle('show');
                }
            });

            // Close on escape
            dropdown.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    menu.classList.remove('show');
                    toggle.focus();
                }
            });
        }
    });

    // ==========================================
    // AUTO-SAVE INDICATOR (for forms)
    // ==========================================
    function showSaveIndicator(message = 'Saving...') {
        let indicator = document.querySelector('.save-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'save-indicator';
            indicator.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: #1a1d24; border: 1px solid #2a2f3a; padding: 10px 20px; border-radius: 8px; z-index: 1000;';
            document.body.appendChild(indicator);
        }
        indicator.textContent = message;
        indicator.style.opacity = '1';
        
        return indicator;
    }

    function hideSaveIndicator() {
        const indicator = document.querySelector('.save-indicator');
        if (indicator) {
            indicator.style.opacity = '0';
            setTimeout(() => indicator.remove(), 300);
        }
    }

    // ==========================================
    // CONFIRM NAVIGATION WITH UNSAVED CHANGES
    // ==========================================
    let formChanged = false;
    const trackedForms = document.querySelectorAll('form.track-changes');
    
    trackedForms.forEach(function(form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(function(input) {
            input.addEventListener('change', function() {
                formChanged = true;
            });
        });

        form.addEventListener('submit', function() {
            formChanged = false;
        });
    });

    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // ==========================================
    // SMOOTH SCROLL
    // ==========================================
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // ==========================================
    // UTILITY FUNCTIONS
    // ==========================================
    window.FitnessTracker = {
        showSaveIndicator: showSaveIndicator,
        hideSaveIndicator: hideSaveIndicator,
        
        // Format duration (minutes to hours/minutes)
        formatDuration: function(minutes) {
            const h = Math.floor(minutes / 60);
            const m = minutes % 60;
            return h > 0 ? `${h}h ${m}m` : `${m}m`;
        },

        // Format date
        formatDate: function(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        // Debounce function
        debounce: function(func, wait) {
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
    };

})();
