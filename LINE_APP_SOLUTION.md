# LINE App Access Solution

## Problem
When users click links to the research record system from LINE app, they see a security warning: "The site is potentially unsafe. Please close this screen"

## Solution
Created a landing page that detects LINE in-app browser and provides instructions to open in external browser.

---

## Files Created

### 1. Landing Page
**File**: `public/line-redirect.html`

**Features**:
- ✅ Auto-detects LINE in-app browser
- ✅ Shows step-by-step Thai instructions with visual guides
- ✅ Copy link button for easy sharing
- ✅ Auto-redirect button with LINE deep link support
- ✅ Fully responsive design
- ✅ Works without PHP (pure HTML/JS)
- ✅ Auto-redirects if opened in regular browser

### 2. PHP Helper Functions
**File**: `app/Helpers/line_safe_helper.php`

**Functions**:
- `line_safe_url($url)` - Generate LINE-safe URL
- `is_line_browser()` - Check if user is in LINE app
- `is_in_app_browser()` - Check if user is in any in-app browser
- `get_shareable_link($url)` - Get optimized shareable link

---

## How to Use

### Option A: Share Landing Page Directly

When sharing links in LINE, use this URL instead:
```
https://research.academic.uru.ac.th/line-redirect.html
```

Or with specific target page:
```
https://research.academic.uru.ac.th/line-redirect.html?url=https://research.academic.uru.ac.th/index.php/login
```

### Option B: Use PHP Helper in Code

First, load the helper (add to autoload or load manually):

```php
// In app/Config/Autoload.php
public $helpers = ['year', 'line_safe'];

// Or load manually in controller
helper('line_safe');
```

Then use in your views:

```php
<!-- Generate LINE-safe link -->
<a href="<?= line_safe_url('index.php/dashboard') ?>">
    เข้าสู่ระบบ
</a>

<!-- Check if user is in LINE browser -->
<?php if (is_line_browser()): ?>
    <div class="alert">
        กรุณาเปิดในเบราว์เซอร์ภายนอกเพื่อใช้งานเต็มรูปแบบ
    </div>
<?php endif; ?>

<!-- Get shareable link for social media -->
<?php
$shareLink = get_shareable_link('index.php/publications/view/123');
?>
<button onclick="copyToClipboard('<?= $shareLink ?>')">
    แชร์ผลงาน
</button>
```

### Option C: Auto-Redirect in Controllers

Add this to your main controllers to auto-redirect LINE users:

```php
// In app/Controllers/BaseController.php or specific controllers
public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
{
    parent::initController($request, $response, $logger);

    helper('line_safe');

    // Auto-redirect LINE users to landing page
    if (is_line_browser() && !$this->request->is('cli')) {
        $currentUrl = current_url();
        $landingUrl = base_url('line-redirect.html?url=' . urlencode($currentUrl));
        return redirect()->to($landingUrl);
    }
}
```

---

## How It Works

### User Flow

1. **User clicks link in LINE**
   ```
   User sees: "The site is potentially unsafe"
   ```

2. **User goes to landing page instead**
   ```
   URL: https://research.academic.uru.ac.th/line-redirect.html
   ```

3. **Landing page detects LINE browser**
   - Shows yellow warning banner
   - Displays 3-step instructions in Thai
   - Shows copy link button
   - Shows auto-redirect button

4. **User follows instructions**
   - Taps three-dot menu (⋮)
   - Selects "เปิดในเบราว์เซอร์ภายนอก"
   - Site opens in Safari/Chrome

5. **Site works normally**
   - No more security warnings
   - Full functionality available

### Technical Details

**LINE Deep Link**:
```javascript
line://openInExternalBrowser?url=<target-url>
```

**Browser Detection**:
```javascript
const ua = navigator.userAgent.toLowerCase();
const isLINE = ua.indexOf('line') > -1;
```

**Auto-Redirect Logic**:
```javascript
if (not in in-app browser) {
    // User opened link directly in browser
    redirect to target URL after 1.5 seconds
} else {
    // User is in LINE/Facebook/Instagram app
    show instructions
}
```

---

## Testing

### Test 1: LINE App
1. Send this link in LINE chat:
   ```
   https://research.academic.uru.ac.th/line-redirect.html
   ```
2. Click the link
3. Should see landing page with yellow warning
4. Follow instructions to open in external browser

### Test 2: Regular Browser
1. Open this URL in Chrome/Safari:
   ```
   https://research.academic.uru.ac.th/line-redirect.html
   ```
2. Should auto-redirect to main site after 1.5 seconds

### Test 3: With Target URL
1. Send this link:
   ```
   https://research.academic.uru.ac.th/line-redirect.html?url=https://research.academic.uru.ac.th/index.php/dashboard
   ```
2. After opening in external browser, should go directly to dashboard

---

## For End Users

### Quick Guide (Thai)

**เมื่อเห็นข้อความ "The site is potentially unsafe":**

1. **กดปุ่ม ⋮** ที่มุมบนขวา
2. **เลือก** "เปิดในเบราว์เซอร์ภายนอก"
3. **เว็บไซต์จะเปิด**ใน Chrome/Safari และใช้งานได้ปกติ

**หรือ:**
- กดปุ่ม "คัดลอกลิงก์"
- เปิด Chrome หรือ Safari
- วางลิงก์และกด Enter

---

## Deployment Steps

### Already Complete
✅ Landing page created at `public/line-redirect.html`
✅ PHP helper functions created at `app/Helpers/line_safe_helper.php`
✅ Documentation created

### Optional Steps

1. **Add Helper to Autoload** (Optional)
   ```php
   // In app/Config/Autoload.php
   public $helpers = ['year', 'line_safe'];
   ```

2. **Share Landing Page URL** (Recommended)
   - Share this URL in LINE groups:
     ```
     https://research.academic.uru.ac.th/line-redirect.html
     ```
   - Add to LINE official account auto-reply

3. **Add Detection Banner** (Optional)
   Add to main layout to show banner for LINE users:
   ```php
   <?php helper('line_safe'); ?>
   <?php if (is_line_browser()): ?>
   <div class="bg-yellow-100 border-b border-yellow-300 px-4 py-3 text-center">
       <p class="text-sm text-yellow-800">
           🔔 กรุณาเปิดในเบราว์เซอร์ภายนอกเพื่อใช้งานเต็มรูปแบบ
           <a href="<?= line_safe_url() ?>" class="underline font-medium">
               คลิกที่นี่เพื่อดูวิธีการ
           </a>
       </p>
   </div>
   <?php endif; ?>
   ```

4. **Update Shared Links** (When sharing)
   Instead of sharing:
   ```
   https://research.academic.uru.ac.th/index.php
   ```

   Share:
   ```
   https://research.academic.uru.ac.th/line-redirect.html
   ```

---

## Alternative Solutions (Not Implemented)

### If You Get Server Access Later

**Option 1: Install SSL Certificate**
```bash
# Contact university IT department
# Request SSL installation for research.academic.uru.ac.th
# Use Let's Encrypt (free) or university certificate
```

**Option 2: Force HTTPS Redirect**
```apache
# In .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## Troubleshooting

### Issue: Landing page not showing
**Solution**: Check file exists at `public/line-redirect.html`

### Issue: Link doesn't redirect
**Solution**: Check URL parameter is properly encoded:
```php
urlencode('https://research.academic.uru.ac.th/index.php')
```

### Issue: Auto-redirect not working
**Solution**: LINE deep link syntax may change. Fallback to manual instructions.

### Issue: Users still see warning
**Solution**: They need to follow instructions to open in external browser. No way to bypass LINE's security from our side.

---

## Browser Compatibility

| Browser | Status |
|---------|--------|
| LINE In-App Browser | ✅ Shows instructions |
| Facebook In-App | ✅ Shows instructions |
| Instagram In-App | ✅ Shows instructions |
| Chrome/Safari | ✅ Auto-redirects |
| Firefox | ✅ Auto-redirects |
| Edge | ✅ Auto-redirects |

---

## Support

For questions:
- Check browser console for JavaScript errors
- Verify file paths are correct
- Test in different browsers
- Contact IT if SSL installation is possible

---

## Summary

✅ **Created**: LINE-safe landing page with instructions
✅ **Created**: PHP helper functions for easy integration
✅ **Works**: Without server modification
✅ **User-friendly**: Thai language with visual guides
✅ **Automatic**: Detects browser and shows appropriate UI
✅ **Fallback**: Copy link option if auto-redirect fails

**Next Steps**:
1. Test the landing page in LINE app
2. Share the landing page URL instead of direct links
3. Optionally add helper to autoload for easier use
4. Consider requesting SSL certificate from university IT for long-term solution
