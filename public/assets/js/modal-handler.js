/**
 * Global Modal Handler
 * Handles click-outside-to-close functionality for all modals
 * Handles ESC key to close modals
 */

document.addEventListener('DOMContentLoaded', function() {
    // Find all modal elements
    const modalSelectors = [
        '[id$="Modal"]',      // IDs ending with "Modal"
        '[id$="modal"]',      // IDs ending with "modal"
        '.modal',             // Class "modal"
        '[class*="modal"]'    // Any class containing "modal"
    ];

    const allModals = document.querySelectorAll(modalSelectors.join(','));
    const modals = Array.from(allModals).filter(function(el) {
        // Exclude known inner modal components
        const isInner = el.classList.contains('modal-content') || 
                        el.classList.contains('modal-dialog') || 
                        el.classList.contains('modal-body') || 
                        el.classList.contains('modal-header') || 
                        el.classList.contains('modal-footer') ||
                        el.classList.contains('view-modal-card');
        if (isInner) return false;
        
        // Exclude if it has an ancestor that is also a modal container
        return !el.parentElement || !el.parentElement.closest('[id$="Modal"], [id$="modal"], .modal');
    });

    modals.forEach(function(modal) {
        // Skip if already has handler
        if (modal.dataset.modalHandlerAttached) {
            return;
        }

        modal.dataset.modalHandlerAttached = 'true';

        // Click outside to close (unless data-no-outside-click is set)
        modal.addEventListener('click', function(event) {
            // Check if click is directly on the modal backdrop (not on children)
            if (event.target === modal) {
                // Skip if modal has data-no-outside-click attribute
                if (modal.dataset.noOutsideClick === 'true' || modal.id === 'aiModal' || modal.id === 'aiWaitingModal') {
                    console.log('Modal click-outside blocked for:', modal.id);
                    return;
                }
                closeModal(modal);
            }
        });

        // Find modal content container (the inner dialog box)
        const modalContent = modal.querySelector('[class*="modal-content"], .bg-white, [class*="rounded"]');

        if (modalContent) {
            // Prevent clicks inside modal content from closing
            modalContent.addEventListener('click', function(event) {
                event.stopPropagation();
            });
        }

        // Find close buttons
        const closeButtons = modal.querySelectorAll('[onclick*="close"], [class*="close"], button[type="button"]');
        closeButtons.forEach(function(btn) {
            // Only add if text suggests it's a close button
            const btnText = btn.textContent.toLowerCase();
            if (btnText.includes('ปิด') || btnText.includes('close') || btnText.includes('ยกเลิก') || btnText.includes('cancel')) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    closeModal(modal);
                });
            }
        });
    });

    // ESC key to close all visible modals (unless blocked)
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' || event.keyCode === 27) {
            const visibleModals = Array.from(modals).filter(function(modal) {
                return !modal.classList.contains('hidden') &&
                       (modal.style.display === 'flex' || modal.style.display === 'block' ||
                        getComputedStyle(modal).display !== 'none');
            });

            // Close the topmost modal (last one in array)
            if (visibleModals.length > 0) {
                const topModal = visibleModals[visibleModals.length - 1];
                
                // Skip if modal blocks ESC key (AI modals during processing)
                if (topModal.dataset.noEscClose === 'true' || topModal.id === 'aiWaitingModal') {
                    console.log('Modal ESC close blocked for:', topModal.id);
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }
                
                // For AI modal, only allow ESC if not processing
                if (topModal.id === 'aiModal') {
                    const aiWaitingModal = document.getElementById('aiWaitingModal');
                    if (aiWaitingModal && !aiWaitingModal.classList.contains('hidden')) {
                        console.log('AI Modal ESC blocked - still processing');
                        event.preventDefault();
                        event.stopPropagation();
                        return;
                    }
                }
                
                closeModal(topModal);
            }
        }
    });

    /**
     * Close modal helper function
     */
    function closeModal(modal) {
        // Add hidden class
        modal.classList.add('hidden');

        // Set display to none
        modal.style.display = 'none';

        // Trigger custom close event
        const closeEvent = new CustomEvent('modalClosed', { detail: { modal: modal } });
        modal.dispatchEvent(closeEvent);

        // Re-enable body scroll
        document.body.style.overflow = '';
    }

    /**
     * Observer to handle dynamically added modals - DISABLED
     * (Causing issues with modal auto-closing)
     */
    // const observer = new MutationObserver(function(mutations) {
    //     mutations.forEach(function(mutation) {
    //         mutation.addedNodes.forEach(function(node) {
    //             if (node.nodeType === 1) { // Element node
    //                 // Check if the added node is a modal
    //                 const addedModals = node.matches && node.matches(modalSelectors.join(','))
    //                     ? [node]
    //                     : Array.from(node.querySelectorAll ? node.querySelectorAll(modalSelectors.join(',')) : []);

    //                 addedModals.forEach(function(modal) {
    //                     if (!modal.dataset.modalHandlerAttached) {
    //                         // Attach handlers to dynamically added modals (without reloading)
    //                         modal.dataset.modalHandlerAttached = 'true';

    //                         modal.addEventListener('click', function(event) {
    //                             if (event.target === modal) {
    //                                 closeModal(modal);
    //                             }
    //                         });

    //                         const modalContent = modal.querySelector('[class*="modal-content"], .bg-white, [class*="rounded"]');
    //                         if (modalContent) {
    //                             modalContent.addEventListener('click', function(event) {
    //                                 event.stopPropagation();
    //                             });
    //                         }
    //                     }
    //                 });
    //             }
    //         });
    //     });
    // });

    // observer.observe(document.body, {
    //     childList: true,
    //     subtree: true
    // });

    console.log('✓ Modal handler initialized for ' + modals.length + ' modals');
});

/**
 * Global function to open modal (optional helper)
 */
window.openModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevent background scroll
    }
};

/**
 * Global function to close modal (optional helper)
 */
window.closeModal = function(modalId) {
    const modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        document.body.style.overflow = ''; // Re-enable scroll
    }
};
