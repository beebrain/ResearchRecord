# External to Local Assets Migration Guide

Quick reference for migrating from CDN to local assets across all view files.

## Search and Replace Guide

Use your IDE's "Find and Replace in Files" feature with these patterns:

### 1. jQuery

**Find:**
```
https://code.jquery.com/jquery-3.6.0.min.js
```

**Replace with:**
```php
<?= base_url('assets/js/vendor/jquery-3.6.0.min.js') ?>
```

### 2. Chart.js

**Find:**
```
https://cdn.jsdelivr.net/npm/chart.js
```
or
```
https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js
```

**Replace with:**
```php
<?= base_url('assets/js/vendor/chart.min.js') ?>
```

### 3. DataTables CSS

**Find:**
```
https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css
```

**Replace with:**
```php
<?= base_url('assets/css/vendor/datatables.min.css') ?>
```

### 4. DataTables JS

**Find:**
```
https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js
```

**Replace with:**
```php
<?= base_url('assets/js/vendor/datatables.min.js') ?>
```

### 5. SweetAlert2 CSS

**Find:**
```
https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css
```

**Replace with:**
```php
<?= base_url('assets/css/vendor/sweetalert2.min.css') ?>
```

### 6. SweetAlert2 JS

**Find:**
```
https://cdn.jsdelivr.net/npm/sweetalert2@11
```

**Replace with:**
```php
<?= base_url('assets/js/vendor/sweetalert2.min.js') ?>
```

### 7. Google Fonts (Sarabun)

**Find:**
```
https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap
```

**Replace with:**
```php
<?= base_url('assets/css/vendor/sarabun.css') ?>
```

### 8. Tailwind CSS (if using CDN)

**Find:**
```
https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css
```

**Replace with:**
```php
<?= base_url('assets/css/tailwind.min.css') ?>
```

## Files to Update

Run search and replace on these directories:
- `app/Views/admin/`
- `app/Views/dashboard/`
- `app/Views/publications/`

## Verification Checklist

After updating each file:

- [ ] Check that all CSS files load (no 404 errors)
- [ ] Check that all JS files load (no console errors)
- [ ] Test page functionality (charts, tables, alerts)
- [ ] Verify fonts display correctly
- [ ] Test in different browsers (Chrome, Firefox, Edge)

## Testing Commands

### Check for remaining external CDN links
```bash
grep -r "https://cdn" app/Views/
grep -r "https://fonts.googleapis" app/Views/
grep -r "code.jquery.com" app/Views/
```

### Verify local files exist
```bash
ls -lh public/assets/js/vendor/
ls -lh public/assets/css/vendor/
ls -lh public/assets/fonts/
```

## Roll Back Plan

If issues occur, you can quickly roll back by reversing the replacements or keeping a backup of the original view files.

**Recommended:** Create a git commit before making changes:
```bash
git add .
git commit -m "Backup before migrating to local assets"
```

## Performance Testing

Before and after migration, test:
1. Page load time (should be faster with local assets)
2. Network tab in browser dev tools (fewer external requests)
3. Offline functionality (should work with local assets)

## Notes

- All libraries are MIT licensed and safe to host locally
- Font files are SIL OFL licensed
- No breaking changes expected from version locking
- Can still use CDN for production if needed (just switch base_url)

## Completed

✅ Dashboard view (`app/Views/admin/Dashboard/dashboard.php`) - Already migrated

## Next Steps

1. Update remaining view files using find/replace
2. Test each updated page
3. Remove this file once migration is complete
