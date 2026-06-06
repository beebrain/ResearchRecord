/**
 * CSRF Token Auto-Inject for jQuery AJAX
 *
 * Reads the token from <meta name="csrf-token"> (rendered server-side via
 * csrf_hash()) and attaches it as the X-CSRF-TOKEN header on every
 * non-GET AJAX request.
 *
 * CI4 regenerates the token after each successful state-changing request
 * (Security::$regenerate = true), so this script also listens for the new
 * token in the response header and rotates the in-memory + meta tag value
 * before the next AJAX call.
 *
 * Phase 1 of CSRF rollout: include this on pages that POST. Backend
 * enforcement is still OFF (Filters.php $globals['before'] commented out).
 * Once every AJAX path is verified to send the header, enable enforcement
 * on a per-route-group basis.
 */
(function () {
    if (typeof window.jQuery === 'undefined') {
        console.warn('[csrf-setup] jQuery not loaded — CSRF auto-inject disabled');
        return;
    }
    var $ = window.jQuery;

    var HEADER_NAME = 'X-CSRF-TOKEN';
    var META_SELECTOR = 'meta[name="csrf-token"]';

    function readToken() {
        var el = document.querySelector(META_SELECTOR);
        return el ? el.getAttribute('content') : '';
    }

    function writeToken(value) {
        var el = document.querySelector(META_SELECTOR);
        if (el) el.setAttribute('content', value);
    }

    var currentToken = readToken();

    if (!currentToken) {
        console.warn('[csrf-setup] no <meta name="csrf-token"> on page — header will not be sent');
        return;
    }

    $.ajaxPrefilter(function (options, originalOptions, xhr) {
        var method = (options.type || options.method || 'GET').toUpperCase();
        if (method === 'GET' || method === 'HEAD' || method === 'OPTIONS') return;
        // Allow callers to skip with options.skipCsrf === true
        if (originalOptions.skipCsrf) return;
        // Don't clobber an explicit header on the call
        var headers = options.headers || {};
        if (headers[HEADER_NAME]) return;
        xhr.setRequestHeader(HEADER_NAME, currentToken);
    });

    // Rotate cached token if server returned a new one
    $(document).ajaxComplete(function (_event, xhr) {
        var next = xhr.getResponseHeader(HEADER_NAME);
        if (next && next !== currentToken) {
            currentToken = next;
            writeToken(next);
        }
    });
})();
