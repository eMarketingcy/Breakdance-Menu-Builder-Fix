/**
 * Breakdance Menu Builder Fix JavaScript
 * Fixes active menu states and mobile behavior issues
 */

(function($) {
    'use strict';
    
    // Wait for DOM to be ready
    $(document).ready(function() {
        
        // Initialize fixes
        if (typeof bdmfSettings !== 'undefined') {
            if (bdmfSettings.enableDropdownFix) {
                initDropdownFix();
            }
            if (bdmfSettings.enableMobileFix) {
                initMobileFix();
            }
        }
        
    });
    
    /**
     * Fix dropdown menu active states
     */
    function initDropdownFix() {
        // Get the current URL of the page
        const currentUrl = window.location.href;
        const currentPath = window.location.pathname;
        
        // Find all the dropdown menus that contain submenu items
        const dropdownMenus = document.querySelectorAll('.breakdance-dropdown');
        
        dropdownMenus.forEach(dropdown => {
            // Get the top-level link of the dropdown
            const topLevelLink = dropdown.querySelector('.breakdance-dropdown-toggle > a');
            const topLevelItem = dropdown.closest('li');
            
            // Get all submenu links
            const submenuLinks = dropdown.querySelectorAll('.breakdance-dropdown-link');
            
            // Check each submenu link to see if its href matches the current URL
            submenuLinks.forEach(link => {
                const linkUrl = link.getAttribute('href');
                const linkPath = new URL(linkUrl, window.location.origin).pathname;
                
                // Check for exact match or if current URL contains the link URL
                if (currentUrl === linkUrl || 
                    currentPath === linkPath || 
                    (linkPath !== '/' && currentPath.startsWith(linkPath))) {
                    
                    // Mark the submenu item as active
                    const submenuItem = link.closest('.breakdance-dropdown-item');
                    if (submenuItem) {
                        submenuItem.classList.add('breakdance-dropdown-item--active');
                    }
                    
                    // Mark the parent menu item as active
                    if (topLevelLink && bdmfSettings.activeColor) {
                        topLevelLink.style.color = bdmfSettings.activeColor;
                        
                        // Add border if enabled
                        if (bdmfSettings.enableBorderFix) {
                            const borderStyle = `${bdmfSettings.activeBorderWidth}px ${bdmfSettings.activeBorderStyle} ${bdmfSettings.activeBorderColor}`;
                            topLevelLink.style[`border-${bdmfSettings.activeBorderPosition}`] = borderStyle;
                        }
                    }
                    
                    if (topLevelItem) {
                        topLevelItem.classList.add('bdmf-parent-active');
                    }
                }
            });
        });
        
        // Also handle regular menu items (non-dropdown)
        const regularMenuLinks = document.querySelectorAll('.breakdance-menu-link');
        regularMenuLinks.forEach(link => {
            const linkUrl = link.getAttribute('href');
            const linkPath = new URL(linkUrl, window.location.origin).pathname;
            
            if (currentUrl === linkUrl || 
                currentPath === linkPath || 
                (linkPath !== '/' && currentPath.startsWith(linkPath))) {
                
                const menuItem = link.closest('li');
                if (menuItem) {
                    menuItem.classList.add('current-menu-item');
                    
                    // Add border styling if enabled
                    if (bdmfSettings.enableBorderFix) {
                        const borderStyle = `${bdmfSettings.activeBorderWidth}px ${bdmfSettings.activeBorderStyle} ${bdmfSettings.activeBorderColor}`;
                        link.style[`border-${bdmfSettings.activeBorderPosition}`] = borderStyle;
                    }
                    
                    // Also mark parent items as ancestors
                    let parent = menuItem.parentElement.closest('li');
                    while (parent) {
                        parent.classList.add('current-menu-ancestor');
                        parent = parent.parentElement.closest('li');
                    }
                }
            }
        });
    }
    
    /**
     * Fix mobile menu behavior
     */
    function initMobileFix() {
        const menuLinks = document.querySelectorAll('.breakdance .breakdance-menu-list > li a.breakdance-menu-link');
        const mobileBreakpoint = bdmfSettings.mobileBreakpoint || 480;
        
        // Log for debugging
        console.log('Breakdance Menu Fix: Found', menuLinks.length, 'menu links');
        
        function handleMenuLinkClick(event) {
            const windowWidth = window.innerWidth;
            
            if (windowWidth <= mobileBreakpoint) {
                const parentItem = this.parentElement;
                
                // Check if this is a dropdown toggle
                if (parentItem.classList.contains('breakdance-dropdown-toggle') || 
                    parentItem.querySelector('.breakdance-dropdown')) {
                    
                    // If the link has a valid href and it's not just '#'
                    if (this.href && this.href !== '#' && !this.href.endsWith('#')) {
                        event.preventDefault();
                        event.stopPropagation();
                        
                        // Add a small delay to ensure any animations complete
                        setTimeout(() => {
                            window.location.href = this.href;
                        }, 100);
                    }
                }
            }
        }
        
        // Attach event listeners
        menuLinks.forEach(function(link) {
            link.addEventListener('click', handleMenuLinkClick);
        });
        
        // Handle window resize to recalculate behavior
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                // Reinitialize if needed
                console.log('Breakdance Menu Fix: Window resized to', window.innerWidth);
            }, 250);
        });
    }
    
    /**
     * Utility function to check if element is visible
     */
    function isElementVisible(element) {
        return element.offsetWidth > 0 && element.offsetHeight > 0;
    }
    
    /**
     * Debug function to log menu structure
     */
    function debugMenuStructure() {
        if (window.location.search.includes('bdmf_debug=1')) {
            console.log('=== Breakdance Menu Debug ===');
            console.log('Current URL:', window.location.href);
            console.log('Current Path:', window.location.pathname);
            
            const menus = document.querySelectorAll('.breakdance-menu');
            menus.forEach((menu, index) => {
                console.log(`Menu ${index + 1}:`, menu);
                
                const links = menu.querySelectorAll('a');
                links.forEach((link, linkIndex) => {
                    console.log(`  Link ${linkIndex + 1}:`, link.href, link.textContent.trim());
                });
            });
        }
    }
    
    // Run debug if requested
    debugMenuStructure();
    
})(jQuery);