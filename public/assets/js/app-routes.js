/**
 * CI4 query-string routes for nginx without mod_rewrite.
 * Use appRoute('admin/getFaculties') instead of BASE_URL + '/index.php/admin/...'
 */
(function () {
    function baseUrl() {
        const raw = typeof BASE_URL !== 'undefined' ? BASE_URL : (window.BASE_URL || '');
        return String(raw).replace(/\/+$/, '');
    }

    window.appRoute = function appRoute(path) {
        const raw = String(path || '').replace(/^\//, '');
        const qIndex = raw.indexOf('?');
        const segment = qIndex >= 0 ? raw.slice(0, qIndex) : raw;
        const qs = qIndex >= 0 ? raw.slice(qIndex) : '';
        return `${baseUrl()}/index.php?/${segment}${qs}`;
    };
})();
