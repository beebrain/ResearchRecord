/**
 * Email Auto-Complete for Author Management
 * Clean and organized auto-complete functionality
 */

// ==========================================
// Configuration & Initialization
// ==========================================

// Ensure BASE_URL is available (fallback to window.BASE_URL or empty string)
const _BASE_URL_EMAIL = (typeof BASE_URL !== 'undefined') ? BASE_URL : (window.BASE_URL || '');

function appRouteEmail(path) {
    if (window.API_ENDPOINTS?.searchAuthorEmail && path === 'publications/search-author-email') {
        return window.API_ENDPOINTS.searchAuthorEmail;
    }
    const base = _BASE_URL_EMAIL.replace(/\/+$/, '');
    return `${base}/index.php?/${path.replace(/^\//, '')}`;
}

const EmailAutoComplete = {
    config: {
        debounceDelay: 500,
        apiEndpoint: appRouteEmail('publications/search-author-email'),
        cache: new Map(),
        maxCacheSize: 100
    },
    
    init() {
        this.setupExistingInputs();
        this.setupMutationObserver();
    }
};

// Initialize when DOM is ready
$(document).ready(() => {
    EmailAutoComplete.init();
});

// ==========================================
// Setup Functions
// ==========================================

EmailAutoComplete.setupExistingInputs = function() {
    $('input[name*="[email]"]').each((index, input) => {
        this.setupEmailInput($(input));
    });
};

EmailAutoComplete.setupMutationObserver = function() {
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) {
                    const $emailInputs = $(node).find('input[name*="[email]"]');
                    $emailInputs.each((index, input) => {
                        this.setupEmailInput($(input));
                    });
                }
            });
        });
    });

    // Watch both add form and edit modal containers
    const containers = ['authorsContainer', 'edit_authors_container'];
    containers.forEach(containerId => {
        const container = document.getElementById(containerId);
        if (container) {
            observer.observe(container, {
                childList: true,
                subtree: true
            });
        }
    });
};

EmailAutoComplete.setupEmailInput = function($input) {
    // Prevent duplicate setup
    if ($input.hasClass('email-autocomplete-enabled')) {
        return;
    }
    
    $input.addClass('email-autocomplete-enabled');
    
    let debounceTimer;
    
    // Input event handler
    $input.on('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            this.handleEmailInput($input);
        }, this.config.debounceDelay);
    });
    
    // Blur event handler
    $input.on('blur', () => {
        setTimeout(() => {
            this.hideStatus($input);
        }, 150);
    });
    
    // Focus event handler
    $input.on('focus', () => {
        const email = $input.val().trim();
        if (email && this.isValidEmail(email)) {
            // Show cached result if available
            const cached = this.config.cache.get(email);
            if (cached) {
                this.handleSearchResult($input, cached);
            }
        }
    });
};

// ==========================================
// Email Processing
// ==========================================

EmailAutoComplete.handleEmailInput = function($input) {
    const email = $input.val().trim();

    // Clear if empty
    if (email.length === 0) {
        this.clearAuthorData($input);
        this.hideStatus($input);
        return;
    }

    // Require minimum 3 characters
    if (email.length < 3) {
        this.hideStatus($input);
        return;
    }

    // Check cache first
    if (this.config.cache.has(email)) {
        const cachedResult = this.config.cache.get(email);
        this.handleSearchResult($input, cachedResult);
        return;
    }

    // Show loading and search (no longer requires valid email format)
    this.showLoading($input);
    this.searchAuthor(email, $input);
};

EmailAutoComplete.searchAuthor = function(email, $input) {
    const url = this.config.apiEndpoint.indexOf('index.php?/') >= 0
        ? this.config.apiEndpoint + '?email=' + encodeURIComponent(email)
        : this.config.apiEndpoint + (this.config.apiEndpoint.indexOf('?') >= 0 ? '&' : '?') + 'email=' + encodeURIComponent(email);
    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        timeout: 10000,
        success: (response) => {
            this.cacheResult(email, response);
            this.handleSearchResult($input, response);
        },
        error: (xhr, status, error) => {
            console.error('Author search failed:', error);
            this.showError($input, 'Search failed');
        }
    });
};

EmailAutoComplete.handleSearchResult = function($input, response) {
    if (response.success && response.found) {
        this.fillAuthorData($input, response.author);
        const message = response.is_user ? 'User found' : 'Author found';
        this.showSuccess($input, message);
    } else {
        this.clearAuthorData($input);
        this.showNotFound($input);
    }
};

// ==========================================
// Data Management
// ==========================================

EmailAutoComplete.fillAuthorData = function($input, author) {
    const $row = $input.closest('.author-row');
    
    // Fill name field
    const $nameInput = $row.find('input[name*="[name]"]');
    if ($nameInput.length && author.name) {
        $nameInput.val(author.name);
        $nameInput.addClass('auto-filled');
    }
    
    // Fill affiliation field
    const $affiliationInput = $row.find('input[name*="[affiliation]"]');
    if ($affiliationInput.length && author.affiliation) {
        $affiliationInput.val(author.affiliation);
        $affiliationInput.addClass('auto-filled');
    }
    
    // Store metadata
    $input.data({
        'author-id': author.id,
        'user-uid': author.user_uid
    });

    $input.addClass('auto-filled');

    // Render chip card so the matched author is shown like an inline badge
    if (window.AuthorNameSearch && typeof AuthorNameSearch.renderChip === 'function') {
        $row.data('matched-user', {
            id: author.id,
            uid: author.user_uid,
            name: $nameInput.val() || author.name || '',
            email: $input.val() || author.email || '',
            affiliation: $affiliationInput.val() || author.affiliation || ''
        });
        AuthorNameSearch.renderChip($row);
    }
};

EmailAutoComplete.clearAuthorData = function($input) {
    const $row = $input.closest('.author-row');
    
    // Clear auto-filled fields (except email)
    $row.find('input.auto-filled').each(function() {
        if ($(this).attr('type') !== 'email') {
            $(this).val('').removeClass('auto-filled');
        }
    });
    
    // Clear metadata
    $input.removeData('author-id user-uid').removeClass('auto-filled');
};

EmailAutoComplete.cacheResult = function(email, response) {
    // Manage cache size
    if (this.config.cache.size >= this.config.maxCacheSize) {
        const firstKey = this.config.cache.keys().next().value;
        this.config.cache.delete(firstKey);
    }
    
    this.config.cache.set(email, response);
};

// ==========================================
// UI Status Management
// ==========================================

EmailAutoComplete.showLoading = function($input) {
    this.hideStatus($input);
    
    const $status = $('<span class="email-status loading" role="status" aria-live="polite">' +
                      '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">' +
                      '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
                      '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>' +
                      '</svg>' +
                      'Searching...' +
                      '</span>');
    
    $input.after($status);
    $input.addClass('border-blue-300');
};

EmailAutoComplete.showSuccess = function($input, message) {
    this.hideStatus($input);
    
    const $status = $('<span class="email-status success" role="status" aria-live="polite">' +
                      '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                      '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>' +
                      '</svg>' +
                      message +
                      '</span>');
    
    $input.after($status);
    $input.removeClass('border-blue-300').addClass('border-green-500');
};

EmailAutoComplete.showNotFound = function($input) {
    this.hideStatus($input);
    
    const $status = $('<span class="email-status not-found" role="status" aria-live="polite">' +
                      '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                      '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>' +
                      '</svg>' +
                      'New author' +
                      '</span>');
    
    $input.after($status);
    $input.removeClass('border-blue-300 border-green-500');
};

EmailAutoComplete.showError = function($input, message) {
    this.hideStatus($input);
    
    const $status = $('<span class="email-status error" role="status" aria-live="assertive">' +
                      '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                      '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>' +
                      '</svg>' +
                      message +
                      '</span>');
    
    $input.after($status);
    $input.removeClass('border-blue-300 border-green-500').addClass('border-red-500');
};

EmailAutoComplete.hideStatus = function($input) {
    $input.siblings('.email-status').remove();
    $input.removeClass('border-blue-300 border-green-500 border-red-500');
};

// ==========================================
// Utility Functions
// ==========================================

EmailAutoComplete.isValidEmail = function(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
};

EmailAutoComplete.getAuthorData = function() {
    const authors = [];
    
    $('.author-row').each(function(index) {
        const $row = $(this);
        const $nameInput = $row.find('input[name*="[name]"]');
        const $emailInput = $row.find('input[name*="[email]"]');
        const $affiliationInput = $row.find('input[name*="[affiliation]"]');
        
        const name = $nameInput.val().trim();
        if (name) {
            authors.push({
                name: name,
                email: $emailInput.val().trim(),
                affiliation: $affiliationInput.val().trim(),
                author_id: $emailInput.data('author-id') || null,
                user_uid: $emailInput.data('user-uid') || null,
                order: index + 1
            });
        }
    });
    
    return authors;
};

// ==========================================
// Debug & Development Tools
// ==========================================

EmailAutoComplete.debug = {
    getCacheStats() {
        return {
            size: EmailAutoComplete.config.cache.size,
            maxSize: EmailAutoComplete.config.maxCacheSize,
            keys: Array.from(EmailAutoComplete.config.cache.keys())
        };
    },
    
    clearCache() {
        EmailAutoComplete.config.cache.clear();
        console.log('Email auto-complete cache cleared');
    },
    
    testEmail(email) {
        const $testInput = $('<input type="email">').val(email);
        $('body').append($testInput);
        EmailAutoComplete.setupEmailInput($testInput);
        EmailAutoComplete.handleEmailInput($testInput);
        return $testInput;
    }
};

// ==========================================
// Performance Monitoring (Optional)
// ==========================================

EmailAutoComplete.performance = {
    searchTimes: [],
    
    startSearch(email) {
        this.currentSearch = {
            email: email,
            startTime: performance.now()
        };
    },
    
    endSearch() {
        if (this.currentSearch) {
            const duration = performance.now() - this.currentSearch.startTime;
            this.searchTimes.push(duration);
            
            // Keep only last 100 measurements
            if (this.searchTimes.length > 100) {
                this.searchTimes.shift();
            }
            
            console.debug(`Email search for ${this.currentSearch.email} took ${duration.toFixed(2)}ms`);
            this.currentSearch = null;
        }
    },
    
    getAverageSearchTime() {
        if (this.searchTimes.length === 0) return 0;
        const sum = this.searchTimes.reduce((a, b) => a + b, 0);
        return sum / this.searchTimes.length;
    }
};

// ==========================================
// Accessibility Features
// ==========================================

EmailAutoComplete.accessibility = {
    announceResult($input, message) {
        const $announcement = $('<div class="sr-only" aria-live="polite"></div>');
        $announcement.text(message);
        $input.after($announcement);
        
        setTimeout(() => {
            $announcement.remove();
        }, 1000);
    },
    
    addKeyboardSupport($input) {
        $input.on('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideStatus($input);
            }
        });
    }
};

// Export for global access
window.EmailAutoComplete = EmailAutoComplete;