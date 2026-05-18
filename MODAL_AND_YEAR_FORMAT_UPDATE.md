# Modal Click-Outside-to-Close & Year Format Update

## Overview

This update adds two major features:
1. **Global Modal Handler** - Click outside modal to close, ESC key support
2. **Thai Buddhist Year Format** - Display years as "พ.ศ. 2567 (ค.ศ. 2024)" throughout the system
3. **Publication Sorting** - Sort publications by year (latest first)

## Changes Made

### 1. Modal Handler (Click Outside to Close)

#### Created Files
- ✅ [public/assets/js/modal-handler.js](c:\xampp\htdocs\researchRecord\public\assets\js\modal-handler.js)

#### Features
- ✅ **Auto-detects all modals** - Finds modals by ID, class, or naming pattern
- ✅ **Click outside to close** - Clicking backdrop closes modal
- ✅ **ESC key support** - Press ESC to close topmost modal
- ✅ **Prevents inner clicks** - Clicking inside modal doesn't close it
- ✅ **Dynamic modal support** - Handles modals added after page load
- ✅ **Global helpers** - `openModal(id)` and `closeModal(id)` functions

#### How It Works

```javascript
// Automatically detects modals with:
- IDs ending with "Modal" or "modal"
- Classes containing "modal"
- Any element with modal-related attributes

// Usage (automatic - no code changes needed)
// Just add the script to your page:
<script src="<?= base_url('assets/js/modal-handler.js') ?>"></script>

// Optional: Use global functions
openModal('editModal');   // Open modal
closeModal('editModal');  // Close modal
```

#### Modals Affected
All existing modals now support click-outside-to-close:
- `#facultyModal` - Faculty management
- `#curriculumModal` - Curriculum management
- `#roleModal` - User roles
- `#detail-modal` - Publication details
- `#add-user-modal` - Add user
- `#aiWaitingModal` - AI processing
- `#aiModal` - AI results
- `#user-detail-modal` - User details
- `#editModal` - Edit publication

### 2. Year Format Helper (พ.ศ./ค.ศ.)

#### Created Files
- ✅ [app/Helpers/year_helper.php](c:\xampp\htdocs\researchRecord\app\Helpers\year_helper.php)

#### Auto-loaded
Modified [app/Config/Autoload.php](c:\xampp\htdocs\researchRecord\app\Config\Autoload.php):
```php
public $helpers = ['year'];
```

#### Available Functions

```php
// Format year as "พ.ศ. 2567 (ค.ศ. 2024)"
format_year_thai(2024)              // "พ.ศ. 2567 (ค.ศ. 2024)"
format_year_thai(2024, true)        // "2567(2024)" - short format

// Convert years
format_year_be(2024)                // 2567 (Christian → Buddhist)
format_year_ce(2567)                // 2024 (Buddhist → Christian)

// Current year
get_current_year_be()               // Current Buddhist year

// Date formatting
format_date_thai('2024-03-15')      // "15/03/2567"
format_month_thai(3)                // "มีนาคม"
format_month_thai(3, true)          // "มี.ค." - short format

// Publication date formatting
format_publication_date(2024, 3)    // "มีนาคม พ.ศ. 2567"
format_publication_date(2024)       // "พ.ศ. 2567"
```

#### Usage in Views

```php
<!-- Old way -->
<td><?= $publication['publication_year'] ?></td>
<!-- Shows: 2024 -->

<!-- New way -->
<td><?= format_year_thai($publication['publication_year']) ?></td>
<!-- Shows: พ.ศ. 2567 (ค.ศ. 2024) -->

<!-- Short format -->
<td><?= format_year_thai($publication['publication_year'], true) ?></td>
<!-- Shows: 2567(2024) -->
```

#### JavaScript Implementation

Updated [managePublication.php](c:\xampp\htdocs\researchRecord\app\Views\admin\publications\managePublication.php):

```javascript
// Old function
function toBuddhistYear(christianYear) {
    if (!christianYear) return '-';
    return parseInt(christianYear) + 543;
}
// Output: 2567

// New function
function toBuddhistYear(christianYear) {
    if (!christianYear) return '-';
    const beYear = parseInt(christianYear) + 543;
    return `${beYear}(${christianYear})`;
}
// Output: 2567(2024)
```

### 3. Publication Sorting by Year

#### Updated Model
Modified [app/Models/PublicationModel.php](c:\xampp\htdocs\researchRecord\app\Models\PublicationModel.php)

#### Methods Updated
All query methods now sort by:
1. `publication_year DESC` (latest year first)
2. `publication_month DESC` (latest month first)
3. `created_at DESC` (latest created first)

```php
// Example: getUserPublications()
return $this->where('created_by', $userId)
    ->orderBy('publication_year', 'DESC')
    ->orderBy('publication_month', 'DESC')
    ->orderBy('created_at', 'DESC')
    ->findAll();
```

#### Affected Methods
- ✅ `getUserPublications()` - User's own publications
- ✅ `getUserPublicationsWithAuthors()` - User publications with author names
- ✅ `searchUserPublications()` - Search results
- ✅ `getAllPublicationsWithAuthors()` - Admin dashboard
- ✅ `getPublicationsByUser()` - Publications by specific user
- ✅ `getPublicationsByFaculties()` - Faculty-filtered publications

## Files Modified

### New Files
1. `public/assets/js/modal-handler.js` - Global modal handler
2. `app/Helpers/year_helper.php` - Year formatting functions

### Modified Files
1. `app/Config/Autoload.php` - Added year helper to autoload
2. `app/Models/PublicationModel.php` - Updated all ORDER BY clauses
3. `app/Views/admin/Dashboard/dashboard.php` - Added modal-handler.js
4. `app/Views/admin/publications/managePublication.php` - Updated toBuddhistYear()

## Testing

### Test Modal Functionality

1. **Click Outside to Close**
   ```
   1. Open any modal (Faculty, Curriculum, User, etc.)
   2. Click outside the modal (on gray backdrop)
   3. Modal should close
   ```

2. **ESC Key**
   ```
   1. Open any modal
   2. Press ESC key
   3. Modal should close
   ```

3. **Multiple Modals**
   ```
   1. Open modal A
   2. Open modal B (if nested modals exist)
   3. Press ESC
   4. Should close top modal (B) first
   5. Press ESC again
   6. Should close modal A
   ```

### Test Year Formatting

1. **PHP Helper Functions**
   ```php
   // Test in controller or view
   <?php
   echo format_year_thai(2024);           // พ.ศ. 2567 (ค.ศ. 2024)
   echo format_year_thai(2024, true);     // 2567(2024)
   echo format_year_be(2024);             // 2567
   echo format_year_ce(2567);             // 2024
   echo format_publication_date(2024, 3); // มีนาคม พ.ศ. 2567
   ?>
   ```

2. **JavaScript Year Format**
   ```
   1. Go to Publications → Manage Publications
   2. Check Year column
   3. Should display as: 2567(2024)
   ```

3. **Publication Sorting**
   ```
   1. Go to any publication list
   2. Verify publications are sorted by year (latest first)
   3. Publications from 2024 should appear before 2023
   4. Within same year, latest month first
   ```

## Browser Console Output

When page loads with modal-handler.js, you'll see:
```
✓ Modal handler initialized for X modals
```

This confirms the script is working and shows how many modals were detected.

## Migration Guide for Other Pages

### Adding Modal Support to New Pages

```html
<!-- Add this script to any page with modals -->
<script src="<?= base_url('assets/js/modal-handler.js') ?>"></script>

<!-- That's it! All modals will automatically support:
     - Click outside to close
     - ESC key to close
     - No additional code needed
-->
```

### Using Year Format in New Views

```php
<!-- In PHP views -->
<td>Year: <?= format_year_thai($year) ?></td>
<td>Short: <?= format_year_thai($year, true) ?></td>
<td>Date: <?= format_publication_date($year, $month) ?></td>

<!-- In JavaScript -->
<script>
function formatYear(ceYear) {
    if (!ceYear) return '-';
    const beYear = parseInt(ceYear) + 543;
    return `${beYear}(${ceYear})`;
}
</script>
```

## Affected Pages

### Pages with Modal Support
- ✅ Admin Dashboard
- ✅ Manage Publications
- ✅ Add Publication
- ✅ Faculty & Curriculum Management
- ✅ User Roles Management
- ✅ User Management
- ✅ Publication Summary

### Pages with Year Formatting
- ✅ Manage Publications (JavaScript updated)
- 📋 Dashboard (can add PHP helper)
- 📋 Publication Index (can add PHP helper)
- 📋 CV View (can add PHP helper)
- 📋 Publication Summary (can add PHP helper)

## Performance Impact

### Modal Handler
- ✅ **Minimal** - Only runs once on page load
- ✅ **Event delegation** - Uses efficient event handling
- ✅ **< 5KB** - Small script size

### Year Helper
- ✅ **No impact** - Simple arithmetic operations
- ✅ **Auto-loaded** - Available everywhere without manual loading

## Security Considerations

### Modal Handler
- ✅ **No XSS risk** - Only handles DOM events
- ✅ **No data manipulation** - Only controls visibility
- ✅ **Safe event handlers** - No eval() or dangerous operations

### Year Helper
- ✅ **Input validation** - Handles empty/invalid values
- ✅ **Type casting** - Ensures integer conversion
- ✅ **No SQL injection** - Pure calculation functions

## Future Enhancements

### Possible Additions

1. **Modal Animations**
   - Fade in/out effects
   - Slide animations
   - Custom transitions

2. **Year Formatting Options**
   - Configurable format patterns
   - Multiple language support
   - Custom separators

3. **Advanced Sorting**
   - Sort by multiple fields simultaneously
   - Custom sort orders per user preference
   - Remember last sort selection

## Troubleshooting

### Modal Won't Close on Click Outside

**Problem**: Modal stays open when clicking backdrop

**Solutions**:
1. Check browser console for errors
2. Verify modal-handler.js is loaded
3. Check modal structure has backdrop element
4. Ensure no JavaScript errors blocking execution

```html
<!-- Correct modal structure -->
<div id="myModal" class="fixed inset-0 bg-black bg-opacity-50">
    <div class="modal-content bg-white">
        <!-- Content here -->
    </div>
</div>
```

### Year Shows as "NaN(NaN)"

**Problem**: Year formatting returns invalid result

**Solutions**:
1. Check year value is not null/undefined
2. Verify year is numeric
3. Use conditional rendering

```javascript
// Safe usage
const year = publication.publication_year;
const formatted = year ? toBuddhistYear(year) : '-';
```

### Publications Not Sorted by Year

**Problem**: Publications appear in wrong order

**Solutions**:
1. Clear cache: `rm -rf writable/cache/*`
2. Verify database has publication_year column
3. Check NULL values in publication_year
4. Run query directly:

```sql
SELECT title, publication_year
FROM publications
ORDER BY publication_year DESC
LIMIT 10;
```

## Rollback Plan

If issues occur:

### Remove Modal Handler
```html
<!-- Comment out or remove this line -->
<!-- <script src="<?= base_url('assets/js/modal-handler.js') ?>"></script> -->
```

### Revert Year Format
```php
// In app/Config/Autoload.php
public $helpers = []; // Remove 'year'
```

### Revert Sorting
```php
// In app/Models/PublicationModel.php
// Change back to:
->orderBy('created_at', 'DESC')
```

## Support

For issues or questions:
1. Check browser console for JavaScript errors
2. Check CodeIgniter logs: `writable/logs/log-YYYY-MM-DD.log`
3. Verify file permissions
4. Test in different browsers

## Version History

- **v1.0** (2025-11-12)
  - Initial release
  - Modal click-outside-to-close
  - Thai Buddhist year formatting
  - Publication sorting by year

## Credits

- Modal Handler: Universal modal management system
- Year Helper: Thai Buddhist Era conversion utilities
- Publication Sorting: Improved chronological ordering
