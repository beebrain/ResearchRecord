/**
 * Browser Popup Fix JavaScript
 *
 * Prevents browser password manager popups and extension dialogs
 * from being blocked by page content.
 *
 * Include this file in all authenticated pages:
 * <script src="<?= base_url('assets/js/browser-popup-fix.js') ?>"></script>
 */

(function() {
    'use strict';

    /**
     * Fix stacking context issues on page load
     */
    function fixStackingContexts() {
        // Reset body isolation
        document.body.style.isolation = 'auto';

        // Remove transforms from main content areas
        const mainElements = document.querySelectorAll('main, .main-content, .container');
        mainElements.forEach(function(element) {
            const computedStyle = window.getComputedStyle(element);
            if (computedStyle.transform !== 'none') {
                element.style.transform = 'none';
            }
        });

        // Ensure high z-index elements don't block browser UI
        const highZElements = document.querySelectorAll('[class*="z-"]');
        highZElements.forEach(function(element) {
            const zIndex = parseInt(window.getComputedStyle(element).zIndex);
            if (zIndex && zIndex > 50) {
                console.warn('Element has high z-index (' + zIndex + '), reducing to 30:', element);
                element.style.zIndex = '30';
            }
        });
    }

    /**
     * Detect if password manager popup is likely blocking interaction
     */
    function detectBlockedPopup() {
        // Check for Chrome's credential picker
        const credentialPicker = document.querySelector('#credential-picker-iframe');
        if (credentialPicker) {
            // Ensure it's on top
            credentialPicker.style.zIndex = '2147483647';
            credentialPicker.style.position = 'fixed';
            return true;
        }

        // Check for password manager related elements
        const passwordElements = document.querySelectorAll('[data-password-manager], [role="alert"][aria-live]');
        passwordElements.forEach(function(element) {
            element.style.zIndex = '2147483647';
            element.style.position = 'fixed';
        });

        return passwordElements.length > 0;
    }

    /**
     * Handle ESC key to close browser dialogs
     */
    function handleEscKey(event) {
        if (event.key === 'Escape' || event.keyCode === 27) {
            // Let browser handle its own dialogs
            event.stopPropagation();

            // Check if there's a visible password manager popup
            if (detectBlockedPopup()) {
                console.log('Password manager popup detected, allowing ESC to close it');
            }
        }
    }

    /**
     * Monitor DOM for new popup elements
     */
    function monitorForPopups() {
        // Create a MutationObserver to watch for new elements
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Check if it's a password manager element
                        if (node.id === 'credential-picker-iframe' ||
                            node.hasAttribute('data-password-manager')) {
                            node.style.zIndex = '2147483647';
                            node.style.position = 'fixed';
                            console.log('Password manager popup detected and fixed');
                        }
                    }
                });
            });
        });

        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        return observer;
    }

    /**
     * Fix page immediately if it's already causing issues
     */
    function emergencyFix() {
        // Remove all high z-index values
        const allElements = document.querySelectorAll('*');
        allElements.forEach(function(element) {
            const zIndex = parseInt(window.getComputedStyle(element).zIndex);
            if (zIndex && zIndex > 100) {
                element.style.zIndex = '30';
            }
        });

        // Remove all transforms
        allElements.forEach(function(element) {
            if (window.getComputedStyle(element).transform !== 'none') {
                element.style.transform = 'none';
            }
        });

        console.log('Emergency stacking context fix applied');
    }

    /**
     * Add user notification if popup is detected
     */
    function notifyUser() {
        const notification = document.createElement('div');
        notification.id = 'password-manager-help';
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #3b82f6;
            color: white;
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 40;
            font-size: 14px;
            max-width: 320px;
            animation: slideIn 0.3s ease-out;
        `;
        notification.innerHTML = `
            <div style="display: flex; align-items: start; gap: 12px;">
                <span style="font-size: 24px;">ℹ️</span>
                <div>
                    <strong>Browser popup detected</strong>
                    <p style="margin: 8px 0 0 0; font-size: 13px; opacity: 0.9;">
                        Press <kbd style="background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 4px;">ESC</kbd> to close any browser dialogs
                    </p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer; padding: 0; margin-left: auto;">&times;</button>
            </div>
        `;

        // Add animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
        document.body.appendChild(notification);

        // Auto-remove after 10 seconds
        setTimeout(function() {
            if (notification.parentElement) {
                notification.style.transition = 'opacity 0.3s ease';
                notification.style.opacity = '0';
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }
        }, 10000);
    }

    /**
     * Initialize all fixes
     */
    function init() {
        console.log('Browser popup fix initialized');

        // Fix stacking contexts immediately
        fixStackingContexts();

        // Check for popups
        const popupDetected = detectBlockedPopup();

        // Add ESC key handler
        document.addEventListener('keydown', handleEscKey, true);

        // Monitor for new popups
        monitorForPopups();

        // Show notification if popup detected
        if (popupDetected) {
            setTimeout(notifyUser, 500);
        }

        // Add double-click on body to trigger emergency fix
        let clickCount = 0;
        document.body.addEventListener('click', function(e) {
            if (e.target === document.body) {
                clickCount++;
                setTimeout(function() {
                    clickCount = 0;
                }, 500);
                if (clickCount === 3) {
                    emergencyFix();
                    alert('Emergency z-index fix applied. Page should be accessible now.');
                }
            }
        });

        console.log('Browser popup fix ready. Triple-click on background for emergency fix.');
    }

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Re-run fix after a short delay (for dynamically loaded content)
    setTimeout(function() {
        fixStackingContexts();
        detectBlockedPopup();
    }, 1000);

    // Expose emergency fix globally for debugging
    window.fixBrowserPopup = emergencyFix;

})();
