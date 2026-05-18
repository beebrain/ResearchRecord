# Auto-Detection Configuration

## Overview

The application now **automatically detects** whether it's running on localhost (development) or production, eliminating the need for hardcoded URLs.

## What Was Changed

### 1. App Configuration - [app/Config/App.php](app/Config/App.php)

**Auto-detects:**
- ✅ **Hostname**: Localhost vs Production domain
- ✅ **Protocol**: HTTP vs HTTPS (via multiple methods)
- ✅ **Base Path**: `/researchRecord/` vs `/public/`

**Detection Methods:**
```php
// Protocol Detection
$isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
          (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
          (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
          (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

// Environment Detection
$isLocalhost = (strpos($host, 'localhost') !== false) ||
              (strpos($host, '127.0.0.1') !== false) ||
              (strpos($host, '::1') !== false);
```

**Behavior:**
- **Localhost**: `http://localhost/researchRecord/`
- **Production**: `https://{any-domain}/public/`

### 2. Apache Configuration - [public/.htaccess](public/.htaccess)

**Auto-redirects HTTP → HTTPS** for all non-localhost domains:

```apache
# Force HTTPS for production (non-localhost domains)
RewriteCond %{HTTPS} !=on
RewriteCond %{HTTP:X-Forwarded-Proto} !=https
RewriteCond %{HTTP_HOST} !^localhost$ [NC]
RewriteCond %{HTTP_HOST} !^127\.0\.0\.1$ [NC]
RewriteCond %{HTTP_HOST} !^::1$ [NC]
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
```

**Features:**
- ✅ Handles reverse proxy setups (X-Forwarded-Proto)
- ✅ Excludes localhost/127.0.0.1/::1
- ✅ Works with any production domain

### 3. Backdoor View - [app/Views/secret/backdoor.php](app/Views/secret/backdoor.php)

**Client-side protection:**

```javascript
// Auto-detect localhost
const isLocalhost = ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname);

// Force HTTPS redirect for production
if (!isLocalhost && window.location.protocol === 'http:') {
    window.location.href = window.location.href.replace('http://', 'https://');
}

// Force HTTPS for AJAX calls on production
if (!isLocalhost) {
    baseUrl = baseUrl.replace('http://', 'https://');
}
```

## How It Works

### Multi-Layer Security

**3 layers of protection ensure HTTPS on production:**

1. **Server Layer** (.htaccess)
   - Redirects HTTP → HTTPS before page loads
   - Handles reverse proxy scenarios
   - 301 permanent redirect for SEO

2. **Application Layer** (App.php)
   - Auto-detects protocol via multiple methods
   - Forces HTTPS for production baseURL
   - Supports reverse proxy headers

3. **Client Layer** (JavaScript)
   - Fallback HTTPS redirect
   - Forces HTTPS for all AJAX requests
   - Prevents CORS issues

## Deployment

### No Configuration Needed!

**Just deploy to any server and it will:**
- ✅ Auto-detect the domain name
- ✅ Auto-detect if it's localhost or production
- ✅ Auto-detect the base path
- ✅ Force HTTPS on production
- ✅ Allow HTTP on localhost

## Testing

### Localhost (Development)
```
http://localhost/researchRecord/
✓ Works with HTTP
✓ No forced HTTPS redirect
```

### Production (Any Domain)
```
http://research.academic.uru.ac.th/public/
→ Automatically redirects to →
https://research.academic.uru.ac.th/public/
✓ All requests use HTTPS
✓ No CORS errors
```

### Different Production Domain (Example)
```
http://example.com/public/
→ Automatically redirects to →
https://example.com/public/
✓ Works without code changes
```

## Benefits

✅ **Portable**: Works on any domain without configuration
✅ **Secure**: Always uses HTTPS on production
✅ **Flexible**: Supports reverse proxy setups
✅ **SEO-Friendly**: Uses 301 redirects
✅ **Developer-Friendly**: HTTP works on localhost
✅ **No Hardcoding**: No domain names in code

## Supported Environments

### Development
- localhost
- 127.0.0.1
- ::1 (IPv6 localhost)
- Uses HTTP (no forced HTTPS)

### Production
- Any public domain
- Behind reverse proxy (Nginx/Apache)
- Behind load balancer
- Always forces HTTPS

## Reverse Proxy Support

The configuration supports these reverse proxy headers:
- `X-Forwarded-Proto`
- `X-Forwarded-SSL`
- `HTTPS` server variable
- `SERVER_PORT` (443 detection)

## Troubleshooting

### Issue: Still getting CORS errors

**Solution**: Clear browser cache and reload with Ctrl+F5

### Issue: HTTP not redirecting to HTTPS

**Check**:
1. `.htaccess` file is uploaded to `/public/` directory
2. `mod_rewrite` is enabled in Apache
3. Reverse proxy is passing `X-Forwarded-Proto` header

### Issue: Base URL is wrong

**Check**:
1. Verify `SCRIPT_NAME` server variable is set correctly
2. Check if running in a subdirectory
3. Manually inspect `<?= base_url() ?>` output

## Files Modified

1. [app/Config/App.php](app/Config/App.php) - Auto-detect baseURL
2. [public/.htaccess](public/.htaccess) - Force HTTPS redirect
3. [app/Views/secret/backdoor.php](app/Views/secret/backdoor.php) - Client-side HTTPS enforcement

---

**Last Updated**: 2025-11-14
**Version**: Auto-Detection v1.0
