// Dark Mode Initialization Script
// This script can be included in any module to add dark mode functionality

function initializeDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const darkModeIcon = document.getElementById('darkModeIcon');
    const body = document.body;
    
    if (!darkModeToggle || !darkModeIcon) {
        console.warn('Dark mode toggle elements not found');
        return;
    }
    
    // Check for saved theme preference or default to light mode
    const savedTheme = localStorage.getItem('theme') || 'light';
    
    // Apply saved theme
    if (savedTheme === 'dark') {
        body.setAttribute('data-theme', 'dark');
        darkModeIcon.className = 'fas fa-sun';
    } else {
        body.setAttribute('data-theme', 'light');
        darkModeIcon.className = 'fas fa-moon';
    }
    
    // Toggle dark mode
    darkModeToggle.addEventListener('click', function() {
        const currentTheme = body.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        // Add rotation animation
        darkModeToggle.classList.add('rotating');
        
        setTimeout(() => {
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Update icon
            if (newTheme === 'dark') {
                darkModeIcon.className = 'fas fa-sun';
            } else {
                darkModeIcon.className = 'fas fa-moon';
            }
            
            // Remove rotation animation
            darkModeToggle.classList.remove('rotating');
        }, 150);
    });
}

// Auto-initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeDarkMode();
});