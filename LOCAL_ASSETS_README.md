# Local Assets Documentation

All external CSS and JavaScript libraries have been downloaded to local storage for better performance, reliability, and offline capability.

## Directory Structure

```
public/assets/
├── css/
│   ├── vendor/
│   │   ├── datatables.min.css       (DataTables styling)
│   │   ├── sweetalert2.min.css      (SweetAlert2 styling)
│   │   └── sarabun.css              (Sarabun font CSS)
│   ├── tailwind.min.css
│   ├── admin-common.css
│   └── browser-popup-fix.css
├── js/
│   ├── vendor/
│   │   ├── jquery-3.6.0.min.js      (jQuery 3.6.0)
│   │   ├── chart.min.js             (Chart.js 4.4.0)
│   │   ├── datatables.min.js        (DataTables 1.13.7)
│   │   ├── sweetalert2.min.js       (SweetAlert2 11.x)
│   │   ├── pdfmake.min.js           (PDFMake 0.2.7)
│   │   └── vfs_fonts.min.js         (PDFMake VFS Fonts 0.2.7)
│   ├── dashboard-statistics.js
│   └── browser-popup-fix.js
└── fonts/
    ├── sarabun-300.ttf              (Sarabun Light)
    ├── sarabun-400.ttf              (Sarabun Regular)
    ├── sarabun-500.ttf              (Sarabun Medium)
    ├── sarabun-600.ttf              (Sarabun Semi-Bold)
    └── sarabun-700.ttf              (Sarabun Bold)
```

## Downloaded Libraries

### JavaScript Libraries

| Library           | Version | CDN Source           | Local Path                             |
| ----------------- | ------- | -------------------- | -------------------------------------- |
| jQuery            | 3.6.0   | code.jquery.com      | `assets/js/vendor/jquery-3.6.0.min.js` |
| Chart.js          | 4.4.0   | cdn.jsdelivr.net     | `assets/js/vendor/chart.min.js`        |
| DataTables        | 1.13.7  | cdn.datatables.net   | `assets/js/vendor/datatables.min.js`   |
| SweetAlert2       | 11.x    | cdn.jsdelivr.net     | `assets/js/vendor/sweetalert2.min.js`  |
| PDFMake           | 0.2.7   | cdnjs.cloudflare.com | `assets/js/vendor/pdfmake.min.js`      |
| PDFMake VFS Fonts | 0.2.7   | cdnjs.cloudflare.com | `assets/js/vendor/vfs_fonts.min.js`    |

### CSS Libraries

| Library      | Version | CDN Source           | Local Path                              |
| ------------ | ------- | -------------------- | --------------------------------------- |
| DataTables   | 1.13.7  | cdn.datatables.net   | `assets/css/vendor/datatables.min.css`  |
| SweetAlert2  | 11.x    | cdn.jsdelivr.net     | `assets/css/vendor/sweetalert2.min.css` |
| Sarabun Font | -       | fonts.googleapis.com | `assets/css/vendor/sarabun.css`         |

### Fonts

| Font    | Weights                 | Format   | Local Path                   |
| ------- | ----------------------- | -------- | ---------------------------- |
| Sarabun | 300, 400, 500, 600, 700 | TrueType | `assets/fonts/sarabun-*.ttf` |

## Usage in Views

### Updated Dashboard Example

**Before (CDN):**

```php
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
```

**After (Local):**

```php
<!-- Local CSS -->
<link rel="stylesheet" href="<?= base_url('assets/css/vendor/datatables.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/vendor/sarabun.css') ?>">

<!-- Local JavaScript -->
<script src="<?= base_url('assets/js/vendor/jquery-3.6.0.min.js') ?>"></script>
<script src="<?= base_url('assets/js/vendor/chart.min.js') ?>"></script>
<script src="<?= base_url('assets/js/vendor/datatables.min.js') ?>"></script>
```

## Benefits of Local Assets

✅ **Faster Loading** - No DNS lookup or external server delays
✅ **Offline Capability** - Application works without internet
✅ **No CDN Dependency** - No reliance on third-party services
✅ **Better Privacy** - No external tracking or requests
✅ **Version Control** - Locked versions prevent breaking changes
✅ **Security** - No risk of CDN compromise
✅ **Cost Reduction** - No bandwidth costs from external sources

## Files Updated

The following view files have been updated to use local assets:

### ✅ Already Updated

- [x] `app/Views/admin/Dashboard/dashboard.php` - Main dashboard

### 🔄 Need to Update

- [ ] `app/Views/admin/faculty_curriculum.php`
- [ ] `app/Views/admin/user_roles.php`
- [ ] `app/Views/admin/publications/add.php`
- [ ] `app/Views/admin/publications/summary.php`
- [ ] `app/Views/admin/publications/managePublication.php`
- [ ] `app/Views/publications/create.php`
- [ ] `app/Views/publications/index.php`
- [ ] `app/Views/dashboard/settings.php`

## How to Update Other Views

Replace external CDN links with local paths:

### jQuery

```php
<!-- Replace: -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- With: -->
<script src="<?= base_url('assets/js/vendor/jquery-3.6.0.min.js') ?>"></script>
```

### Chart.js

```php
<!-- Replace: -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- With: -->
<script src="<?= base_url('assets/js/vendor/chart.min.js') ?>"></script>
```

### DataTables

```php
<!-- Replace: -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<!-- With: -->
<link rel="stylesheet" href="<?= base_url('assets/css/vendor/datatables.min.css') ?>">
<script src="<?= base_url('assets/js/vendor/datatables.min.js') ?>"></script>
```

### SweetAlert2

```php
<!-- Replace: -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- With: -->
<link rel="stylesheet" href="<?= base_url('assets/css/vendor/sweetalert2.min.css') ?>">
<script src="<?= base_url('assets/js/vendor/sweetalert2.min.js') ?>"></script>
```

### Google Fonts (Sarabun)

```php
<!-- Replace: -->
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- With: -->
<link rel="stylesheet" href="<?= base_url('assets/css/vendor/sarabun.css') ?>">
```

## Updating Libraries

To update a library to a newer version:

1. Download the new version from the CDN
2. Replace the file in `public/assets/js/vendor/` or `public/assets/css/vendor/`
3. Test the application to ensure compatibility
4. Update version numbers in this documentation

### Example: Update jQuery

```bash
cd public/assets/js/vendor
curl -L -o jquery-3.7.0.min.js "https://code.jquery.com/jquery-3.7.0.min.js"
# Then update all references in view files
```

## Cache Busting

To force browsers to reload updated assets, add version query strings:

```php
<script src="<?= base_url('assets/js/vendor/jquery-3.6.0.min.js?v=1.0') ?>"></script>
```

Or use CodeIgniter's file modification time:

```php
<?php $version = filemtime(FCPATH . 'assets/js/vendor/jquery-3.6.0.min.js'); ?>
<script src="<?= base_url('assets/js/vendor/jquery-3.6.0.min.js?v=' . $version) ?>"></script>
```

## Performance Considerations

### Compression

All minified files are already compressed. For additional performance:

1. Enable gzip compression in Apache/Nginx
2. Use browser caching headers
3. Consider using a CDN for production (optional)

### Browser Caching

Add to `.htaccess`:

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType font/ttf "access plus 1 year"
</IfModule>
```

## Troubleshooting

### Fonts Not Loading

- Check file paths in `assets/css/vendor/sarabun.css`
- Verify font files exist in `assets/fonts/`
- Check browser console for 404 errors

### JavaScript Errors

- Ensure jQuery loads before other libraries
- Check browser console for specific errors
- Verify file paths are correct

### CSS Not Applied

- Clear browser cache
- Check file paths in `<link>` tags
- Verify CSS files exist in correct location

## Maintenance

- **Monthly**: Check for library updates
- **Quarterly**: Review and update documentation
- **Annually**: Audit for unused libraries

## License Information

All downloaded libraries are open source:

- jQuery: MIT License
- Chart.js: MIT License
- DataTables: MIT License
- SweetAlert2: MIT License
- PDFMake: MIT License
- Sarabun Font: SIL Open Font License

Original sources and licenses preserved in respective library files.
