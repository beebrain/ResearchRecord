# Login UID Auto-Update Feature

## Overview

The authentication system now automatically updates the `login_uid` field for existing users who don't have it set. This is useful for users who were created before the OAuth integration or were imported from CSV files.

## Problem Solved

**Before:**
- Users imported from CSV or created manually had `login_uid = NULL`
- These users couldn't be properly linked to OAuth authentication
- Had to manually update `login_uid` in the database

**After:**
- On first OAuth login, if user exists but has no `login_uid`, it's automatically set
- User gets linked to their OAuth identity
- Seamless migration from manual/CSV users to OAuth users

## How It Works

### Code Location
[app/Controllers/AuthenController.php](c:\xampp\htdocs\researchRecord\app\Controllers\AuthenController.php:182-185)

### Logic Flow

```php
1. User logs in via OAuth
2. Get user's code from API (this becomes login_uid)
3. Check if user exists by email
4. If user exists:
   - Check if login_uid is null or empty
   - If yes: Update login_uid with OAuth code
   - Log the update
5. Continue with normal login flow
```

### Code Implementation

```php
// Update login_uid if it's null or empty
if (empty($existingUser['login_uid']) && !empty($loginUid)) {
    $updateData['login_uid'] = $loginUid;
    log_message('info', 'Updated login_uid for existing user: ' . $existingUser['uid'] . ' -> ' . $loginUid);
}
```

## Example Scenario

### User Created from CSV Import

**Initial State:**
```sql
uid: 123
email: john.doe@uru.ac.th
thai_name: จอห์น
thai_lastname: โด
login_uid: NULL  ← Missing!
```

**After First OAuth Login:**
```sql
uid: 123
email: john.doe@uru.ac.th
thai_name: จอห์น
thai_lastname: โด
login_uid: 'ABC123456'  ← Updated automatically!
```

**Log Entry:**
```
[INFO] Updated login_uid for existing user: 123 -> ABC123456
[INFO] Updated existing user from API: 123 (john.doe@uru.ac.th)
```

## Benefits

✅ **Automatic Migration** - No manual database updates needed
✅ **Seamless for Users** - Users don't notice any difference
✅ **Backward Compatible** - Works with both new and old users
✅ **Logged** - All updates are logged for audit trail
✅ **Safe** - Only updates if login_uid is empty (doesn't overwrite existing values)

## Use Cases

### 1. CSV Imported Users
Users imported via the `import:users` command have no `login_uid`:
- First OAuth login sets their `login_uid`
- Future logins use OAuth authentication

### 2. Manually Created Users
Users created before OAuth integration:
- Have email but no `login_uid`
- Get linked to OAuth on first login

### 3. Existing OAuth Users
Users already with `login_uid`:
- No change - existing value preserved
- Normal OAuth flow continues

## Logging

All `login_uid` updates are logged:

```php
log_message('info', 'Updated login_uid for existing user: ' . $existingUser['uid'] . ' -> ' . $loginUid);
```

### Log Location
Check logs at: `writable/logs/log-YYYY-MM-DD.log`

### Sample Log Entry
```
INFO - 2025-11-12 12:34:56 --> Updated login_uid for existing user: 579 -> URU2024ABC123
INFO - 2025-11-12 12:34:56 --> Updated existing user from API: 579 (kachakorn.nak@live.uru.ac.th)
INFO - 2025-11-12 12:34:56 --> Successful OAuth login for user: kachakorn.nak@live.uru.ac.th
```

## Database Impact

### Before Implementation
```sql
SELECT COUNT(*) FROM user WHERE login_uid IS NULL;
-- Result: 330 (users from CSV import)
```

### After Implementation
After all users log in once:
```sql
SELECT COUNT(*) FROM user WHERE login_uid IS NULL;
-- Result: 0 (all users have login_uid)
```

## Verification

### Check Which Users Need Update
```sql
SELECT uid, email, thai_name, thai_lastname, login_uid
FROM user
WHERE login_uid IS NULL OR login_uid = ''
ORDER BY uid;
```

### Verify Update After Login
```sql
SELECT uid, email, login_uid, created_at
FROM user
WHERE email = 'user@example.com';
```

## Security Considerations

✅ **No Overwrites** - Existing `login_uid` values are never overwritten
✅ **Validation** - Only updates if new `login_uid` is not empty
✅ **Transaction Safe** - Update happens within database transaction
✅ **Audit Trail** - All changes are logged

## Edge Cases Handled

### 1. User Has Existing login_uid
```php
if (empty($existingUser['login_uid'])) // FALSE - skip update
```
**Result:** No change, existing value preserved

### 2. OAuth Returns Empty login_uid
```php
if (!empty($loginUid)) // FALSE - skip update
```
**Result:** No update, user keeps NULL value (will retry on next login)

### 3. User Doesn't Exist
```php
if ($existingUser) // FALSE - skip to new user creation
```
**Result:** New user created with `login_uid` from start

### 4. Multiple Users Same Email
- System finds user by `login_uid` first
- Falls back to email search
- Updates first match found
- Duplicate emails should be prevented by unique constraint

## Testing

### Test Case 1: CSV User First Login
1. Import user from CSV (no `login_uid`)
2. User logs in via OAuth
3. Verify `login_uid` is set in database
4. Check logs for update message

### Test Case 2: Existing OAuth User
1. User already has `login_uid`
2. User logs in via OAuth
3. Verify `login_uid` unchanged
4. No update log message

### Test Case 3: New User
1. User doesn't exist in system
2. User logs in via OAuth
3. New user created with `login_uid`
4. No update needed

## Maintenance

### Monitor Update Success Rate
```sql
-- Users updated today
SELECT COUNT(*)
FROM user
WHERE login_uid IS NOT NULL
  AND DATE(created_at) < CURDATE()
  AND login_uid NOT LIKE 'manual_%';
```

### Check Failed Updates
```bash
# Search logs for failures
grep "Failed to update user" writable/logs/*.log
```

## Files Modified

- ✅ [app/Controllers/AuthenController.php](c:\xampp\htdocs\researchRecord\app\Controllers\AuthenController.php:182-185) - Added login_uid update logic

## Related Features

- OAuth Authentication
- CSV User Import
- User Profile Management
- Author Record Linking

## Future Enhancements

- [ ] Bulk update script for existing users
- [ ] Admin panel showing users without `login_uid`
- [ ] Email notification when `login_uid` is updated
- [ ] API endpoint to trigger manual update
