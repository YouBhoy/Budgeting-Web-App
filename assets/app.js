// BudgetFlix Enhanced JavaScript
document.addEventListener('DOMContentLoaded', function() {
    
    // Theme Management
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;
    
    // Load saved theme preference
    const savedTheme = localStorage.getItem('budgetflix-theme') || 'dark';
    body.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
    
    // Theme toggle functionality
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('budgetflix-theme', newTheme);
            updateThemeIcon(newTheme);
            
            // Add transition effect
            body.style.transition = 'all 0.3s ease';
            setTimeout(() => {
                body.style.transition = '';
            }, 300);
        });
    }
    
    function updateThemeIcon(theme) {
        if (themeToggle) {
            const icon = themeToggle.querySelector('.icon');
            if (icon) {
                icon.textContent = theme === 'dark' ? '☀️' : '🌙';
            }
        }
    }
    
    // Enhanced form interactions
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            // Add floating label effect
            if (input.type !== 'submit' && input.type !== 'button') {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.classList.remove('focused');
                    }
                });
            }
            
            // Real-time validation feedback
            input.addEventListener('input', function() {
                validateField(this);
            });
        });
        
        // Form submission with loading state
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            }
        });
    });
    
    // Enhanced table interactions
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            row.addEventListener('click', function() {
                // Remove active class from other rows
                rows.forEach(r => r.classList.remove('active'));
                // Add active class to clicked row
                this.classList.add('active');
            });
        });
    });
    
    // Enhanced card animations
    const cards = document.querySelectorAll('.card');
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const cardObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
                cardObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    cards.forEach(card => {
        cardObserver.observe(card);
    });
    
    // Enhanced button interactions
    const buttons = document.querySelectorAll('button, .action-btn');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Create ripple effect
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    // Enhanced search functionality
    const searchInputs = document.querySelectorAll('input[type="search"], input[placeholder*="search"]');
    searchInputs.forEach(input => {
        let searchTimeout;
        
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(this.value);
            }, 300);
        });
    });
    
    // Enhanced navigation
    const navLinks = document.querySelectorAll('.navbar-links a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Add loading state to navigation
            document.body.classList.add('page-transitioning');
            
            setTimeout(() => {
                document.body.classList.remove('page-transitioning');
            }, 300);
        });
    });
    
    // Enhanced alerts
    function showAlert(message, type = 'info', duration = 5000) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} fade-in`;
        alert.textContent = message;
        
        // Add close button
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.className = 'alert-close';
        closeBtn.addEventListener('click', () => alert.remove());
        alert.appendChild(closeBtn);
        
        // Insert at top of container
        const container = document.querySelector('.container');
        if (container) {
            container.insertBefore(alert, container.firstChild);
        }
        
        // Auto remove after duration
        setTimeout(() => {
            if (alert.parentNode) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }
        }, duration);
    }
    
    // Enhanced validation
    function validateField(field) {
        const value = field.value.trim();
        const fieldType = field.type;
        const fieldName = field.name;
        
        // Remove existing error states
        field.classList.remove('error', 'success');
        const existingError = field.parentElement.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Validation rules
        let isValid = true;
        let errorMessage = '';
        
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        } else if (fieldType === 'email' && value && !isValidEmail(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        } else if (fieldType === 'number' && value && isNaN(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid number';
        } else if (fieldName === 'amount' && value && parseFloat(value) <= 0) {
            isValid = false;
            errorMessage = 'Amount must be greater than 0';
        }
        
        // Apply validation state
        if (value) {
            if (isValid) {
                field.classList.add('success');
            } else {
                field.classList.add('error');
                const errorElement = document.createElement('div');
                errorElement.className = 'field-error';
                errorElement.textContent = errorMessage;
                field.parentElement.appendChild(errorElement);
            }
        }
        
        return isValid;
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Enhanced search functionality
    function performSearch(query) {
        const searchableElements = document.querySelectorAll('[data-searchable]');
        
        searchableElements.forEach(element => {
            const text = element.textContent.toLowerCase();
            const isMatch = text.includes(query.toLowerCase());
            
            if (query === '') {
                element.style.display = '';
                element.classList.remove('search-hidden');
            } else {
                if (isMatch) {
                    element.style.display = '';
                    element.classList.remove('search-hidden');
                    element.classList.add('search-highlight');
                } else {
                    element.style.display = 'none';
                    element.classList.add('search-hidden');
                    element.classList.remove('search-highlight');
                }
            }
        });
    }
    
    // Enhanced accessibility
    function enhanceAccessibility() {
        // Add skip links if not present
        if (!document.querySelector('.skip-link')) {
            const skipLink = document.createElement('a');
            skipLink.href = '#main-content';
            skipLink.className = 'skip-link';
            skipLink.textContent = 'Skip to main content';
            document.body.insertBefore(skipLink, document.body.firstChild);
        }
        
        // Add ARIA labels to interactive elements
        const interactiveElements = document.querySelectorAll('button, a, input, select, textarea');
        interactiveElements.forEach(element => {
            if (!element.hasAttribute('aria-label') && !element.textContent.trim()) {
                const title = element.getAttribute('title') || element.getAttribute('placeholder');
                if (title) {
                    element.setAttribute('aria-label', title);
                }
            }
        });
    }
    
    // Enhanced performance
    function enhancePerformance() {
        // Lazy load images
        const images = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
        
        // Debounce scroll events
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                // Handle scroll-based animations
                const scrollElements = document.querySelectorAll('[data-scroll-animate]');
                scrollElements.forEach(element => {
                    const rect = element.getBoundingClientRect();
                    if (rect.top < window.innerHeight && rect.bottom > 0) {
                        element.classList.add('scroll-animated');
                    }
                });
            }, 100);
        });
    }
    
    // Initialize enhancements
    enhanceAccessibility();
    enhancePerformance();
    
    // Expose functions globally for use in PHP
    window.BudgetFlix = {
        showAlert,
        validateField,
        performSearch
    };
    
    // Add CSS for new features
    const style = document.createElement('style');
    style.textContent = `
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .field-error {
            color: var(--danger-color);
            font-size: 0.9rem;
            margin-top: 4px;
            animation: fadeIn 0.3s ease;
        }
        
        input.error {
            border-color: var(--danger-color) !important;
        }
        
        input.success {
            border-color: var(--success-color) !important;
        }
        
        .alert-close {
            background: none;
            border: none;
            color: inherit;
            font-size: 1.5rem;
            cursor: pointer;
            float: right;
            margin-left: 10px;
        }
        
        .search-highlight {
            background: rgba(255, 107, 107, 0.2);
            border-radius: 4px;
        }
        
        .page-transitioning {
            opacity: 0.8;
            pointer-events: none;
        }
        
        .scroll-animated {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .lazy {
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .lazy.loaded {
            opacity: 1;
        }
    `;
    document.head.appendChild(style);
});
