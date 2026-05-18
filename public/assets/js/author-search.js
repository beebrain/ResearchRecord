/**
 * Author Name Search with Dropdown Menu - Complete Version
 * Searches user table and displays results as dropdown with auto-fill
 * Includes positioning fixes and all functionality
 */

// ==========================================
// Configuration
// ==========================================

// Ensure BASE_URL is available (fallback to window.BASE_URL or empty string)
const _BASE_URL = (typeof BASE_URL !== 'undefined') ? BASE_URL : (window.BASE_URL || '');

const AuthorNameSearch = {
    config: {
        searchEndpoint: _BASE_URL + '/index.php/publications/search-user-names',
        minSearchLength: 2,
        debounceDelay: 300,
        maxResults: 10,
        cache: new Map(),
        maxCacheSize: 50
    },
    
    init() {
        this.injectCriticalCSS();
        this.setupSearchInputs();
        this.setupMutationObserver();
        this.setupGlobalEvents();
        console.log('✅ Author Name Search initialized');
    }
};

// ==========================================
// Initialize when DOM is ready
// ==========================================

$(document).ready(() => {
    AuthorNameSearch.init();
});

// ==========================================
// CSS Injection for Proper Positioning
// ==========================================

AuthorNameSearch.injectCriticalCSS = function() {
    // Remove existing styles
    $('#author-name-search-styles').remove();
    
    const css = `
        <style id="author-name-search-styles">
        .name-search-container {
            position: relative !important;
            display: block !important;
            width: 100% !important;
        }
        
        .name-search-dropdown {
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 9999 !important;
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 0.375rem !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
            max-height: 200px !important;
            overflow-y: auto !important;
            animation: fadeInDown 0.15s ease-out;
        }
        
        .dropdown-item {
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.15s ease;
            margin: 0;
            padding: 0;
        }
        
        .dropdown-item:last-child {
            border-bottom: none;
        }
        
        .dropdown-item > div {
            padding: 0.75rem;
            cursor: pointer;
            margin: 0;
        }
        
        .dropdown-item:hover > div,
        .dropdown-item.highlighted > div {
            background-color: #f3f4f6 !important;
        }
        
        .dropdown-item:active > div {
            background-color: #e5e7eb !important;
        }
        
        .dropdown-item .font-medium {
            font-weight: 500;
            color: #111827;
            margin-bottom: 0.25rem;
            line-height: 1.25;
        }
        
        .dropdown-item small {
            color: #6b7280;
            font-size: 0.75rem;
            line-height: 1rem;
            display: block;
        }
        
        input.auto-filled {
            background-color: #f0fdf4 !important;
            border-color: #10b981 !important;
            transition: all 0.2s ease;
        }
        
        input.auto-filled:focus {
            background-color: white !important;
            border-color: #3b82f6 !important;
        }
        
        .name-search-dropdown::-webkit-scrollbar {
            width: 6px;
        }
        
        .name-search-dropdown::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }
        
        .name-search-dropdown::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        .name-search-dropdown::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .name-search-dropdown {
                max-height: 150px;
                font-size: 0.875rem;
            }
            
            .dropdown-item > div {
                padding: 0.5rem;
            }
            
            .dropdown-item small {
                font-size: 0.6875rem;
            }
        }
        </style>
    `;
    
    $('head').append(css);
};

// ==========================================
// Setup Functions
// ==========================================

AuthorNameSearch.setupSearchInputs = function() {
    // Setup existing name inputs
    $('input[name*="[name]"]').each((index, input) => {
        this.setupSingleNameInput($(input));
    });
};

AuthorNameSearch.setupMutationObserver = function() {
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) {
                    const $nameInputs = $(node).find('input[name*="[name]"]');
                    $nameInputs.each((index, input) => {
                        this.setupSingleNameInput($(input));
                    });
                }
            });
        });
    });
    
    const container = document.getElementById('authorsContainer');
    if (container) {
        observer.observe(container, {
            childList: true,
            subtree: true
        });
    }
};

AuthorNameSearch.setupGlobalEvents = function() {
    // Close dropdown when clicking outside
    $(document).on('click', (e) => {
        if (!$(e.target).closest('.name-search-container').length) {
            $('.name-search-dropdown').hide();
        }
    });
    
    // Handle escape key
    $(document).on('keydown', (e) => {
        if (e.key === 'Escape') {
            $('.name-search-dropdown').hide();
        }
    });
};

AuthorNameSearch.setupSingleNameInput = function($input) {
    // Prevent duplicate setup
    if ($input.hasClass('name-search-enabled')) {
        return;
    }
    
    $input.addClass('name-search-enabled');
    
    // Check if already wrapped
    if (!$input.closest('.name-search-container').length) {
        // Wrap input in container for positioning
        const $container = $('<div class="name-search-container"></div>');
        
        // Set container styles
        $container.css({
            position: 'relative',
            display: 'block',
            width: '100%'
        });
        
        $input.wrap($container);
    }
    
    let searchTimer;
    
    // Input event handler
    $input.on('input', (e) => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            this.handleNameInput($input);
        }, this.config.debounceDelay);
    });
    
    // Focus event handler
    $input.on('focus', () => {
        const name = $input.val().trim();
        if (name.length >= this.config.minSearchLength) {
            const cached = this.config.cache.get(name.toLowerCase());
            if (cached) {
                this.showDropdown($input, cached);
            }
        }
    });
    
    // Blur event handler (delayed to allow clicking dropdown)
    $input.on('blur', () => {
        setTimeout(() => {
            this.hideDropdown($input);
        }, 200);
    });
    
    // Keyboard navigation
    $input.on('keydown', (e) => {
        this.handleKeyboardNavigation($input, e);
    });
    
    console.log('Name input setup complete:', $input.attr('name'));
};

// ==========================================
// Search Functions
// ==========================================

AuthorNameSearch.handleNameInput = function($input) {
    const name = $input.val().trim();
    
    // Clear dropdown if input is too short
    if (name.length < this.config.minSearchLength) {
        this.hideDropdown($input);
        return;
    }
    
    // Check cache first
    const cacheKey = name.toLowerCase();
    if (this.config.cache.has(cacheKey)) {
        const cachedResults = this.config.cache.get(cacheKey);
        this.showDropdown($input, cachedResults);
        return;
    }
    
    // Show loading
    this.showLoading($input);
    
    // Search users
    this.searchUsers(name, $input);
};

AuthorNameSearch.searchUsers = function(name, $input) {
    $.ajax({
        url: this.config.searchEndpoint,
        method: 'GET',
        data: { 
            name: name,
            limit: this.config.maxResults
        },
        dataType: 'json',
        timeout: 10000,
        success: (response) => {
            this.handleSearchResponse($input, name, response);
        },
        error: (xhr, status, error) => {
            console.error('User name search failed:', error);
            this.showError($input, 'Search failed');
        }
    });
};

AuthorNameSearch.handleSearchResponse = function($input, searchTerm, response) {
    if (response.success && response.users && response.users.length > 0) {
        // Cache results
        this.cacheResults(searchTerm, response.users);
        
        // Show dropdown
        this.showDropdown($input, response.users);
    } else {
        this.showNoResults($input);
    }
};

// ==========================================
// Dropdown Display Functions
// ==========================================

AuthorNameSearch.showDropdown = function($input, users) {
    this.hideDropdown($input); // Remove existing dropdown
    
    const $dropdown = this.createDropdown(users);
    const $container = $input.closest('.name-search-container');
    
    // Ensure container has proper positioning
    $container.css('position', 'relative');
    
    // Append dropdown to container
    $container.append($dropdown);
    
    // Force proper positioning
    this.forceDropdownPositioning($dropdown);
    
    // Setup click handlers
    this.setupDropdownEvents($input, $dropdown);
    
    // Show with animation
    $dropdown.show();
    
    console.log('Dropdown shown with', users.length, 'users');
};

AuthorNameSearch.forceDropdownPositioning = function($dropdown) {
    const element = $dropdown[0];
    
    // Use setAttribute for maximum override power
    element.style.setProperty('position', 'absolute', 'important');
    element.style.setProperty('top', '100%', 'important');
    element.style.setProperty('left', '0', 'important');
    element.style.setProperty('right', '0', 'important');
    element.style.setProperty('z-index', '9999', 'important');
    element.style.setProperty('margin', '0', 'important');
    element.style.setProperty('padding', '0', 'important');
};

AuthorNameSearch.createDropdown = function(users) {
    const $dropdown = $('<div class="name-search-dropdown"></div>');
    
    // Create items
    users.forEach((user, index) => {
        const $item = this.createDropdownItem(user, index);
        $dropdown.append($item);
    });
    
    return $dropdown;
};

AuthorNameSearch.createDropdownItem = function(user, index) {
    const $item = $('<div class="dropdown-item"></div>');
    
    // Item content
    const displayName = user.display_name || user.name;
    const email = user.email ? ` (${user.email})` : '';
    const affiliation = user.affiliation ? `<br><small class="text-gray-500">${user.affiliation}</small>` : '';
    
    $item.html(`
        <div data-index="${index}">
            <div class="font-medium text-gray-900">${displayName}${email}</div>
            ${affiliation}
        </div>
    `);
    
    // Store user data
    $item.data('user', user);
    
    return $item;
};

AuthorNameSearch.setupDropdownEvents = function($input, $dropdown) {
    // Click handler
    $dropdown.on('click', '.dropdown-item', (e) => {
        const $item = $(e.currentTarget);
        const user = $item.data('user');
        this.selectUser($input, user);
    });
    
    // Hover handler
    $dropdown.on('mouseenter', '.dropdown-item', (e) => {
        $dropdown.find('.dropdown-item').removeClass('highlighted');
        $(e.currentTarget).addClass('highlighted');
    });
};

AuthorNameSearch.selectUser = function($input, user) {
    const $row = $input.closest('.author-row');
    
    // Fill name
    $input.val(user.display_name || user.name);
    $input.addClass('auto-filled');
    
    // Fill email if available
    const $emailInput = $row.find('input[name*="[email]"]');
    if ($emailInput.length && user.email) {
        $emailInput.val(user.email).addClass('auto-filled');
    }
    
    // Fill affiliation if available
    const $affiliationInput = $row.find('input[name*="[affiliation]"]');
    if ($affiliationInput.length && user.affiliation) {
        $affiliationInput.val(user.affiliation).addClass('auto-filled');
    }
    
    // Store user data
    $input.data({
        'user-id': user.id,
        'user-uid': user.uid
    });
    
    // Hide dropdown
    this.hideDropdown($input);
    
    // Focus next field
    const $nextInput = $emailInput.length ? $emailInput : $affiliationInput;
    if ($nextInput.length && !$nextInput.hasClass('auto-filled')) {
        setTimeout(() => $nextInput.focus(), 100);
    }
    
    console.log('User selected:', user.display_name || user.name);
};

AuthorNameSearch.hideDropdown = function($input) {
    const $container = $input.closest('.name-search-container');
    $container.find('.name-search-dropdown').remove();
};

// ==========================================
// Status Display Functions
// ==========================================

AuthorNameSearch.showLoading = function($input) {
    this.hideDropdown($input);
    
    const $container = $input.closest('.name-search-container');
    const $loading = $('<div class="name-search-dropdown loading"></div>');
    
    // Force positioning
    this.forceDropdownPositioning($loading);
    
    $loading.css({
        background: 'white',
        border: '1px solid #e5e7eb',
        borderRadius: '0.375rem',
        padding: '12px',
        boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)',
        color: '#6b7280'
    });
    
    $loading.html(`
        <div style="display: flex; align-items: center; justify-content: center;">
            <svg style="animation: spin 1s linear infinite; width: 16px; height: 16px; margin-right: 8px;" fill="none" viewBox="0 0 24 24">
                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Searching users...
        </div>
    `);
    
    $container.append($loading);
};

AuthorNameSearch.showNoResults = function($input) {
    this.hideDropdown($input);
    
    const $container = $input.closest('.name-search-container');
    const $noResults = $('<div class="name-search-dropdown empty"></div>');
    
    // Force positioning
    this.forceDropdownPositioning($noResults);
    
    $noResults.css({
        background: 'white',
        border: '1px solid #e5e7eb',
        borderRadius: '0.375rem',
        padding: '12px',
        color: '#6b7280',
        fontStyle: 'italic',
        boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)'
    });
    
    $noResults.html('<div>No users found</div>');
    $container.append($noResults);
    
    setTimeout(() => {
        $noResults.remove();
    }, 2000);
};

AuthorNameSearch.showError = function($input, message) {
    this.hideDropdown($input);
    
    const $container = $input.closest('.name-search-container');
    const $error = $('<div class="name-search-dropdown error"></div>');
    
    // Force positioning
    this.forceDropdownPositioning($error);
    
    $error.css({
        background: '#fef2f2',
        border: '1px solid #fecaca',
        borderRadius: '0.375rem',
        padding: '12px',
        color: '#991b1b',
        boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)'
    });
    
    $error.html(`<div>❌ ${message}</div>`);
    $container.append($error);
    
    setTimeout(() => {
        $error.remove();
    }, 3000);
};

// ==========================================
// Keyboard Navigation
// ==========================================

AuthorNameSearch.handleKeyboardNavigation = function($input, e) {
    const $dropdown = $input.closest('.name-search-container').find('.name-search-dropdown');
    if (!$dropdown.is(':visible')) return;
    
    const $items = $dropdown.find('.dropdown-item');
    let $highlighted = $items.filter('.highlighted');
    
    switch(e.key) {
        case 'ArrowDown':
            e.preventDefault();
            if ($highlighted.length === 0) {
                $items.first().addClass('highlighted');
            } else {
                $highlighted.removeClass('highlighted');
                const $next = $highlighted.next('.dropdown-item');
                if ($next.length) {
                    $next.addClass('highlighted');
                } else {
                    $items.first().addClass('highlighted');
                }
            }
            break;
            
        case 'ArrowUp':
            e.preventDefault();
            if ($highlighted.length === 0) {
                $items.last().addClass('highlighted');
            } else {
                $highlighted.removeClass('highlighted');
                const $prev = $highlighted.prev('.dropdown-item');
                if ($prev.length) {
                    $prev.addClass('highlighted');
                } else {
                    $items.last().addClass('highlighted');
                }
            }
            break;
            
        case 'Enter':
            e.preventDefault();
            if ($highlighted.length) {
                const user = $highlighted.data('user');
                this.selectUser($input, user);
            }
            break;
            
        case 'Escape':
            this.hideDropdown($input);
            break;
    }
};

// ==========================================
// Cache Management
// ==========================================

AuthorNameSearch.cacheResults = function(searchTerm, users) {
    // Manage cache size
    if (this.config.cache.size >= this.config.maxCacheSize) {
        const firstKey = this.config.cache.keys().next().value;
        this.config.cache.delete(firstKey);
    }
    
    this.config.cache.set(searchTerm.toLowerCase(), users);
};

// ==========================================
// Utility Functions
// ==========================================

AuthorNameSearch.clearAutoFill = function($input) {
    const $row = $input.closest('.author-row');
    $row.find('input.auto-filled').removeClass('auto-filled');
    $input.removeData('user-id user-uid');
};

// ==========================================
// API Integration Helper
// ==========================================

AuthorNameSearch.getUserData = function() {
    const userData = [];
    
    $('.author-row').each(function(index) {
        const $row = $(this);
        const $nameInput = $row.find('input[name*="[name]"]');
        const name = $nameInput.val().trim();
        
        if (name) {
            userData.push({
                name: name,
                email: $row.find('input[name*="[email]"]').val().trim(),
                affiliation: $row.find('input[name*="[affiliation]"]').val().trim(),
                user_id: $nameInput.data('user-id') || null,
                user_uid: $nameInput.data('user-uid') || null,
                order: index + 1
            });
        }
    });
    
    return userData;
};

// ==========================================
// Debug Functions
// ==========================================

AuthorNameSearch.debug = {
    getCacheStats() {
        return {
            size: AuthorNameSearch.config.cache.size,
            maxSize: AuthorNameSearch.config.maxCacheSize,
            keys: Array.from(AuthorNameSearch.config.cache.keys())
        };
    },
    
    clearCache() {
        AuthorNameSearch.config.cache.clear();
        console.log('Name search cache cleared');
    },
    
    testSearch(name) {
        const $testInput = $('<input type="text">').val(name);
        $('body').append($testInput);
        AuthorNameSearch.setupSingleNameInput($testInput);
        AuthorNameSearch.handleNameInput($testInput);
        return $testInput;
    },
    
    forceFixPositioning() {
        $('.name-search-dropdown').each(function() {
            AuthorNameSearch.forceDropdownPositioning($(this));
        });
        
        $('.name-search-container').each(function() {
            this.style.setProperty('position', 'relative', 'important');
        });
        
        console.log('🔧 Emergency positioning fix applied');
    }
};

// ==========================================
// Emergency Positioning Fix
// ==========================================

AuthorNameSearch.emergencyPositioningFix = function() {
    // Auto-fix positioning whenever dropdown is created
    $(document).on('DOMNodeInserted', '.name-search-dropdown', function() {
        const $dropdown = $(this);
        setTimeout(() => {
            AuthorNameSearch.forceDropdownPositioning($dropdown);
        }, 10);
    });
    
    console.log('🚨 Emergency positioning fix activated');
};

// ==========================================
// Global Exports
// ==========================================

// Export for global access
window.AuthorNameSearch = AuthorNameSearch;

// Export debug functions
window.debugNameSearch = AuthorNameSearch.debug.forceFixPositioning;
window.clearNameSearchCache = AuthorNameSearch.debug.clearCache;

// ==========================================
// Auto-fix positioning on load
// ==========================================

$(document).ready(() => {
    // Activate emergency positioning fix
    AuthorNameSearch.emergencyPositioningFix();
    
    console.log('🎯 Author Name Search fully loaded and ready!');
});

console.log('📝 Author Name Search module loaded');