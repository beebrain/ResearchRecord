# HTTPS Mixed Content Fix

## Problem

When accessing the site via HTTPS (`https://research.academic.uru.ac.th`), you get a "Mixed Content" error:

```
Mixed Content: The page at 'https://research.academic.uru.ac.th/public/index.php/auth/login'
was loaded over HTTPS, but requested an insecure stylesheet
'http://research.academic.uru.ac.th/public/public/assets/css/tailwind.min.css'.
This request has been blocked; the content must be served over HTTPS.
```

## Root Cause

The `baseURL` configuration was hardcoded to HTTP:
```php
public string $baseURL = 'http://localhost/researchRecord/';
```

When `base_url()` is called, it generates HTTP URLs even when the page is accessed via HTTPS, causing browsers to block the resources.

## Solution Implemented

Updated [app/Config/App.php](c:\xampp\htdocs\researchRecord\app\Config\App.php) to auto-detect HTTPS and adjust the base URL dynamically.

### Code Changes

```php
public string $baseURL = 'http://localhost/researchRecord/';

public function __construct()
{
    parent::__construct();

    // Auto-detect HTTPS and set base URL accordingly
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        // Running on HTTPS
        if (isset($_SERVER['HTTP_HOST'])) {
            $this->baseURL = 'https://' . $_SERVER['HTTP_HOST'] . '/public/';
        }
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        // Behind a reverse proxy with HTTPS
        if (isset($_SERVER['HTTP_HOST'])) {
            $this->baseURL = 'https://' . $_SERVER['HTTP_HOST'] . '/public/';
        }
    }
}
```

## How It Works

1. **Local Development (HTTP)**: Uses `http://localhost/researchRecord/`
2. **Production with HTTPS**: Detects `$_SERVER['HTTPS']` and switches to `https://`
3. **Behind Reverse Proxy**: Detects `X-Forwarded-Proto` header

## Testing

### On Production Server (HTTPS)
```bash
# Should now output: https://research.academic.uru.ac.th/public/
php -r "require 'app/Config/App.php'; \$_SERVER['HTTPS'] = 'on'; \$_SERVER['HTTP_HOST'] = 'research.academic.uru.ac.th'; \$app = new Config\\App(); echo \$app->baseURL;"
```

### On Local (HTTP)
```bash
# Should output: http://localhost/researchRecord/
php -r "require 'app/Config/App.php'; \$app = new Config\\App(); echo \$app->baseURL;"
```

## Additional Security (Optional)

For maximum security on production, you can also enable forced HTTPS in [app/Config/App.php](c:\xampp\htdocs\researchRecord\app\Config\App.php):

```php
public bool $forceGlobalSecureRequests = true;
```

**Warning:** Only enable this if your production server has a valid SSL certificate. It will redirect all HTTP requests to HTTPS.

## Server Configuration

### Apache (.htaccess)

Add HTTPS redirect to your `.htaccess`:

```apache
# Force HTTPS (production only)
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Nginx

Add to your nginx config:

```nginx
server {
    listen 80;
    server_name research.academic.uru.ac.th;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl;
    server_name research.academic.uru.ac.th;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # ... rest of config
}
```

## Verification Checklist

After deploying to production:

- [ ] Visit `https://research.academic.uru.ac.th`
- [ ] Open browser DevTools → Network tab
- [ ] Check that all resources load with `https://` URLs
- [ ] Verify no "Mixed Content" errors in console
- [ ] Test login functionality
- [ ] Test all pages load correctly

## Troubleshooting

### CSS/JS Still Loading via HTTP

**Problem:** Files still load as `http://` even after fix

**Solution:** Clear server cache:
```bash
# Clear CodeIgniter cache
rm -rf writable/cache/*
```

### Behind a Load Balancer/Proxy

**Problem:** Auto-detection not working behind proxy

**Solution:** Update your server to forward the protocol header:

**Apache:**
```apache
RequestHeader set X-Forwarded-Proto "https"
```

**Nginx:**
```nginx
proxy_set_header X-Forwarded-Proto $scheme;
```

### Local Development Broken

**Problem:** Local development now tries to use HTTPS

**Solution:** The auto-detection should handle this, but if needed, you can set environment-specific config in `.env`:

```ini
# .env file
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost/researchRecord/'
app.forceGlobalSecureRequests = false
```

## Files Modified

- ✅ [app/Config/App.php](c:\xampp\htdocs\researchRecord\app\Config\App.php) - Added HTTPS auto-detection

## Related Issues

- Browser "Mixed Content" blocking
- HTTPS redirect loops
- Cookie security (SameSite, Secure flags)

## Security Headers (Recommended)

Add these headers for better security:

```apache
# .htaccess or Apache config
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

Or in [app/Config/Filters.php](c:\xampp\htdocs\researchRecord\app\Config\Filters.php):

```php
public $globals = [
    'before' => [
        'secureheaders',
    ],
];
```

## Testing Commands

```bash
# Check if HTTPS is detected
curl -I https://research.academic.uru.ac.th/public/index.php/auth/login

# Should see:
# HTTP/2 200
# Content-Security-Policy: upgrade-insecure-requests (if enabled)
# Strict-Transport-Security: max-age=31536000
```

## Performance Impact

✅ **None** - The HTTPS detection happens once per request during config initialization.

## Deployment Notes

1. Deploy the updated `app/Config/App.php` to production
2. Clear application cache
3. Test HTTPS access
4. Monitor logs for any issues
5. Enable `forceGlobalSecureRequests` after verifying HTTPS works

## Rollback Plan

If issues occur, temporarily revert by setting a fixed HTTPS base URL:

```php
public string $baseURL = 'https://research.academic.uru.ac.th/public/';
```

This bypasses auto-detection and forces HTTPS.
