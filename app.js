/**
 * EasyComp Platform - Main JavaScript
 * Handles animations, interactions, and dynamic UI elements
 */

document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on a form page (like create-competition or edit-competition)
    const formCards = document.querySelectorAll('.form-card');
    const isFormPage = formCards.length > 0 || 
                        window.location.href.includes('create-competition.php') || 
                        window.location.href.includes('edit-competition.php');
    
    // Initialize all components - skip animations on form pages
    initThemeToggle();
    initUserDropdown();
    initSidebar();
    
    if (!isFormPage) {
        // Only run these on non-form pages
        initTableAnimations();
        initScrollAnimations();
        initCardAnimations();
        enhancePageTransitions();  
    } else {
        // Explicitly disable any transforms on form pages
        disableFormAnimations();
    }
    
    // These are safe to run on all pages
    resetFormCards();
    initFormAnimations();
    initButtonEffects();
    
    // Notification handling
    handleNotifications();
});

/**
 * Theme Toggle Functionality
 * Switches between light and dark theme with animation
 */
function initThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;
    
    const html = document.documentElement;
    const icon = themeToggle.querySelector('i');
    
    // Apply stored theme on load
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', savedTheme);
    updateThemeIcon(icon, savedTheme);
    
    // Toggle theme on click with enhanced animation
    themeToggle.addEventListener('click', () => {
        // Add rotation animation
        themeToggle.classList.add('rotating');
        
        // Wait for animation to complete before changing theme
        setTimeout(() => {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            // Apply new theme
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Update icon
            updateThemeIcon(icon, newTheme);
            
            // Remove animation class
            themeToggle.classList.remove('rotating');
        }, 300);
    });
}

/**
 * Updates the theme toggle icon based on current theme
 */
function updateThemeIcon(icon, theme) {
    if (theme === 'light') {
        icon.className = 'fas fa-moon';
    } else {
        icon.className = 'fas fa-sun';
    }
}

/**
 * User Dropdown Functionality
 * Handles the user profile dropdown with animations
 */
function initUserDropdown() {
    const userProfile = document.querySelector('.user-profile');
    if (!userProfile) return;
    
    userProfile.addEventListener('click', function(e) {
        e.stopPropagation();
        this.classList.toggle('active');
        
        const dropdown = this.querySelector('.user-dropdown');
        if (dropdown) {
            if (this.classList.contains('active')) {
                // Animate dropdown items when opening
                const items = dropdown.querySelectorAll('a');
                items.forEach((item, index) => {
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(10px)';
                    
                    setTimeout(() => {
                        item.style.transition = 'all 0.3s ease';
                        item.style.transform = 'translateY(0)';
                        item.style.opacity = '1';
                    }, 50 * index);
                });
            }
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!userProfile.contains(e.target)) {
            userProfile.classList.remove('active');
        }
    });
}

/**
 * Sidebar Functionality
 * Handles responsive sidebar toggle and animations
 */
function initSidebar() {
    const sidebarToggle = document.querySelector('.nav-toggle');
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    
    // Add animation to sidebar menu items
    const menuItems = sidebar.querySelectorAll('.sidebar-menu a');
    menuItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-10px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.3s ease';
            item.style.transform = 'translateX(0)';
            item.style.opacity = '1';
        }, 50 * index);
    });
    
    // Handle sidebar toggle on mobile
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 992 && 
                !sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }
}

/**
 * Table Animations
 * Add subtle animations to table rows and content
 */
function initTableAnimations() {
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(10px)';
            
            setTimeout(() => {
                row.style.transition = 'all 0.3s ease';
                row.style.transform = 'translateY(0)';
                row.style.opacity = '1';
            }, 30 * index);
        });
    });
}

/**
 * Form Animations
 * Enhance form interactions with animations
 */
function initFormAnimations() {
    // Add focus effects to form controls with parent check
    const formControls = document.querySelectorAll('.form-control');
    formControls.forEach(control => {
        // Only apply animations if the form-group parent exists
        if (control.parentElement && control.parentElement.classList.contains('form-group')) {
            control.addEventListener('focus', () => {
                control.parentElement.classList.add('focused');
            });
            
            control.addEventListener('blur', () => {
                control.parentElement.classList.remove('focused');
            });
        }
    });
    
    // Animate form submission with a more subtle animation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitButton = this.querySelector('[type="submit"]');
            if (submitButton) {
                // Use a more subtle animation
                submitButton.classList.add('btn-submitting');
                
                // Prevent multiple rapid submissions
                if (submitButton.classList.contains('submitting')) {
                    // Don't add extra animations if already submitting
                    return;
                }
                
                submitButton.classList.add('submitting');
            }
        });
    });
}

/**
 * Button Effects
 * Add interactive effects to buttons
 */
function initButtonEffects() {
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        // Add ripple effect on click
        button.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
}

/**
 * Scroll Animations
 * Animate elements as they scroll into view
 */
function initScrollAnimations() {
    // Get all elements to animate on scroll
    const elements = document.querySelectorAll('.card, .stat-card, .dashboard-header');
    
    // Create Intersection Observer
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    // Observe each element
    elements.forEach(element => {
        observer.observe(element);
    });
}

/**
 * Card Animations
 * Add hover and interaction effects to cards
 */
function initCardAnimations() {
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        // Skip animations for competition form cards
        const isCompetitionForm = 
            window.location.href.includes('create-competition.php') || 
            window.location.href.includes('edit-competition.php');
        
        // Skip animation for form cards or if we're on the competition pages
        if (card.querySelector('form') || isCompetitionForm) {
            return;
        }
        
        // Add subtle 3D rotation effect on hover
        card.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 20;
            const rotateY = (centerX - x) / 20;
            
            this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
        });
        
        // Reset on mouse leave
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
}

/**
 * Page Transitions
 * Smooth transitions between pages
 */
function enhancePageTransitions() {
    // Fade out before page navigation
    document.querySelectorAll('a').forEach(link => {
        // Skip links with special behavior
        if (link.getAttribute('target') === '_blank' || 
            link.getAttribute('href') === '#' ||
            link.getAttribute('href').startsWith('javascript:') ||
            link.getAttribute('href').startsWith('#')) {
            return;
        }
        
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            // Skip if modifier keys are pressed
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.which !== 1) return;
            
            e.preventDefault();
            
            // Fade out content
            document.body.classList.add('page-transition');
            
            // Navigate after transition
            setTimeout(() => {
                window.location.href = href;
            }, 300);
        });
    });
    
    // Fade in on page load
    window.addEventListener('pageshow', function() {
        document.body.classList.add('page-loaded');
    });
}

/**
 * Notification Handling
 * Show and hide notification alerts with animations
 */
function handleNotifications() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        // Animate alert on appearance
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-20px)';
        
        setTimeout(() => {
            alert.style.transition = 'all 0.5s ease';
            alert.style.transform = 'translateY(0)';
            alert.style.opacity = '1';
        }, 100);
        
        // Add close button if not exists
        if (!alert.querySelector('.close-btn')) {
            const closeBtn = document.createElement('button');
            closeBtn.className = 'close-btn';
            closeBtn.innerHTML = '&times;';
            closeBtn.style.marginLeft = 'auto';
            closeBtn.style.background = 'none';
            closeBtn.style.border = 'none';
            closeBtn.style.fontSize = '1.2rem';
            closeBtn.style.cursor = 'pointer';
            closeBtn.style.opacity = '0.7';
            
            closeBtn.addEventListener('click', () => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                
                setTimeout(() => {
                    alert.remove();
                }, 300);
            });
            
            alert.appendChild(closeBtn);
        }
        
        // Auto-hide after 5 seconds for success and info alerts
        if (alert.classList.contains('alert-success') || alert.classList.contains('alert-info')) {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }, 5000);
        }
    });
}

/**
 * Reset Form Cards
 * Ensure form cards have no transform effects applied
 */
function resetFormCards() {
    const formCards = document.querySelectorAll('.form-card');
    formCards.forEach(card => {
        // Reset any transforms
        card.style.transform = 'none';
        
        // Find any form elements and ensure they have no transform
        const formElements = card.querySelectorAll('input, select, textarea');
        formElements.forEach(element => {
            element.style.transform = 'none';
        });
    });
}

/**
 * Disable Form Animations
 * Explicitly disable any transforms on form pages
 */
function disableFormAnimations() {
    const formCards = document.querySelectorAll('.form-card');
    formCards.forEach(card => {
        // Remove all transitions and animations
        card.style.transition = 'none';
        card.style.animation = 'none';
        card.style.transform = 'none';
        
        // Find all elements inside the form card and disable animations
        const allElements = card.querySelectorAll('*');
        allElements.forEach(element => {
            element.style.transition = 'none';
            element.style.animation = 'none';
            element.style.transform = 'none';
            
            // Remove any animation classes
            element.classList.remove('animated');
            element.classList.remove('animating');
        });
    });
    
    // Also target the entire container
    const containers = document.querySelectorAll('.create-container, .edit-container');
    containers.forEach(container => {
        container.style.transition = 'none';
        container.style.animation = 'none';
        container.style.transform = 'none';
    });
    
    // Add a style tag to disable animations for form cards
    const style = document.createElement('style');
    style.textContent = `
        .form-card, .form-card * {
            transition: none !important;
            animation: none !important;
            transform: none !important;
            animation-delay: 0s !important;
            animation-duration: 0s !important;
        }
    `;
    document.head.appendChild(style);
} 