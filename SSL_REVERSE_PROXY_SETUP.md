# SSL Reverse Proxy Setup

## Scenario

Your server setup:
- **Backend Server**: No SSL certificate installed (HTTP only)
- **Frontend**: Accessed via HTTPS (`https://research.academic.uru.ac.th`)
- **Reverse Proxy/Load Balancer**: Handles SSL termination

## Solution

The application now **forces HTTPS** for the production domain, even though the backend server doesn't have SSL installed.

### Configuration

[app/Config/App.php](c:\xampp\htdocs\researchRecord\app\Config\App.php:32-34)

```php
// Production server - Always use HTTPS (SSL handled by reverse proxy)
if (strpos($host, 'research.academic.uru.ac.th') !== false) {
    $this->baseURL = 'https://' . $host . '/public/';
}
```

## How It Works

```
User Browser (HTTPS)
       ↓
Reverse Proxy/Load Balancer (SSL Termination)
       ↓ (HTTP)
Backend Server (Your PHP app)
       ↓
base_url() generates: https://research.academic.uru.ac.th/public/...
```

1. User accesses `https://research.academic.uru.ac.th`
2. Reverse proxy handles SSL/TLS
3. Proxy forwards request to backend as HTTP
4. Your app generates URLs with `https://` prefix
5. Browser receives all assets via HTTPS ✅

## Benefits

✅ **No SSL certificate needed** on backend server
✅ **No Mixed Content errors** - All resources load via HTTPS
✅ **Works with reverse proxy** - SSL handled at proxy level
✅ **Local development** still works with HTTP

## Reverse Proxy Configuration

Your reverse proxy (Nginx, Apache, HAProxy, etc.) should be configured like this:

### Nginx Example
```nginx
server {
    listen 443 ssl;
    server_name research.academic.uru.ac.th;

    ssl_certificate /path/to/cert.crt;
    ssl_certificate_key /path/to/key.key;

    location / {
        proxy_pass http://backend-server:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;  # Important!
    }
}
```

### Apache Example
```apache
<VirtualHost *:443>
    ServerName research.academic.uru.ac.th

    SSLEngine on
    SSLCertificateFile /path/to/cert.crt
    SSLCertificateKeyFile /path/to/key.key

    ProxyPass / http://backend-server:80/
    ProxyPassReverse / http://backend-server:80/

    RequestHeader set X-Forwarded-Proto "https"
    RequestHeader set X-Forwarded-Port "443"
</VirtualHost>
```

## Testing

After deploying the updated configuration:

```bash
# Test from command line
curl -I https://research.academic.uru.ac.th/public/

# Should return:
# HTTP/2 200
# All assets in HTML should use https:// URLs
```

### Browser Testing

1. Visit `https://research.academic.uru.ac.th`
2. Open DevTools → Console
3. Check for "Mixed Content" errors (should be none)
4. Open DevTools → Network tab
5. Verify all resources load with `https://` protocol

## Environment-Specific Behavior

| Environment | Domain | Protocol | Base URL |
|-------------|--------|----------|----------|
| **Production** | research.academic.uru.ac.th | HTTPS | `https://research.academic.uru.ac.th/public/` |
| **Local Dev** | localhost | HTTP | `http://localhost/researchRecord/` |
| **Local Dev** | 127.0.0.1 | HTTP | `http://127.0.0.1/researchRecord/` |

## Common Issues & Solutions

### Issue: Still Getting Mixed Content Errors

**Cause:** Browser cache or CDN cache

**Solution:**
```bash
# Clear server cache
rm -rf writable/cache/*

# Clear browser cache or use Ctrl+Shift+R
```

### Issue: Infinite Redirect Loop

**Cause:** Reverse proxy not configured correctly

**Solution:** Ensure proxy sends correct headers:
```
X-Forwarded-Proto: https
X-Forwarded-Port: 443
```

### Issue: Assets Return 404

**Cause:** Wrong base path

**Solution:** Verify your document root points to `/public/` folder

## Security Headers

Even without SSL on backend, add security headers via reverse proxy:

```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
```

## Files Modified

- ✅ [app/Config/App.php](c:\xampp\htdocs\researchRecord\app\Config\App.php) - Force HTTPS for production domain

## Deployment Steps

1. Upload updated `app/Config/App.php` to production server
2. Clear application cache: `rm -rf writable/cache/*`
3. Test HTTPS access
4. Verify no Mixed Content errors
5. Monitor server logs for issues

## Rollback

If issues occur, temporarily set a static URL in production:

```php
// In app/Config/App.php
public string $baseURL = 'https://research.academic.uru.ac.th/public/';

// Comment out the __construct() method
```

## Additional Notes

- Your reverse proxy certificate must be valid (not self-signed for production)
- Ensure firewall allows traffic on port 443
- Backend server only needs port 80 open to proxy
- No need to install SSL certificate on backend server
- All SSL/TLS encryption handled by reverse proxy

## Performance

No performance impact - URL generation happens once per request during initialization.
