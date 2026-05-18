# Faculty & Curriculum Management - Codebase Analysis

## 1. MODELS FOUND

### CurriculumModel
**File**: `app/Models/CurriculumModel.php`  
**Table**: `curriculum`  
**PK**: `id`

**Fields**:
- `faculty_id` - FK to faculties
- `name` - 3-255 chars
- `code` - 2-20 chars (unique per faculty)
- `degree_level` - bachelor|master|doctoral
- `status` - active|inactive
- `created_at` - timestamp

**Key Methods**:
- getWithFaculty() - Active curricula with faculty info
- getByFaculty($facultyId)
- getByDegreeLevel($degreeLevel)
- getWithUserCount() - Join user table
- getForSelect($facultyId)
- isCodeUniqueInFaculty()
- getStats() - Aggregated by degree/faculty
- getWithPublicationStats()

**Validation**:
```
faculty_id: required|integer
name: required|min_length[3]|max_length[255]
code: required|min_length[2]|max_length[20]
degree_level: required|in_list[bachelor,master,doctoral]
status: in_list[active,inactive]
```

### FacultyModel
**File**: `app/Models/FacultyModel.php`  
**Table**: `faculties`  
**PK**: `id`

**Fields**:
- `name` - 3-255 chars
- `code` - 2-10 chars (UNIQUE)
- `status` - active|inactive
- `created_at`

**Key Methods**:
- getActive()
- getWithCurriculumCount() - Join curriculum
- getByCode($code)
- getForSelect()
- getWithPublicationCount()
- getWithCurriculums() - Nested structure

**Validation**:
```
name: required|min_length[3]|max_length[255]
code: required|min_length[2]|max_length[10]|is_unique[faculties.code,id,{id}]
status: in_list[active,inactive]
```

### UserModel
**File**: `app/Models/UserModel.php`  
**Table**: `user`  
**PK**: `uid`

**Related Field**:
- `curriculum_id` - FK to curriculum


## 2. DATABASE RELATIONSHIPS

```
faculties (1) -----< curriculum (M)
                         |
                         v
                     user (M)
                    /     |     \
            authors     publications
         (secondary emails)  (by user)
```

## 3. CONTROLLERS

### AdminController
**Filter**: `adminauth` (Admin-only)

**View Routes**:
- `index()` → Admin dashboard
- `manageuserCuriculum()` → Drag-drop assignment
- `manageEmails()` → Email management

**AJAX Data Routes**:
- `getStatistics()` - Total publications, authors, faculties
- `getDashboardPublications()` - Publications list
- `getFacultyData()` - Faculty publication counts
- `getYearData()` - Trend by year
- `getCurriculumData()` - Curriculum stats
- `getUsersWithEmails()` - User list with emails

**AJAX Mutation Routes**:
- `addSecondaryEmail(POST)`
- `updateSecondaryEmail(POST)`
- `deleteSecondaryEmail(POST)`

### FacultyCurriculumController
- `getByFaculty()` - Publications by faculty
- `getByCurriculum()` - Publications by curriculum
- `getPublications()` - With metadata
- `getStats()` - Aggregated counts
- `getWithCurriculums()` - Faculties with nested curricula

### CurriculumController
- `index()` - Curriculum list (AJAX)
- `getForSelect()` - Dropdown options
- `getByFaculty()` - Filter by faculty
- `getStats()` - Statistics
- `getWithPublicationStats()` - Publication counts


## 4. ROUTES (Routes.php)

**Admin Routes** (filter: adminauth):
```
GET  /admin/dashboard
GET  /admin/manage-user-curriculum
GET  /admin/manageEmails
POST /admin/addSecondaryEmail
POST /admin/updateSecondaryEmail
POST /admin/deleteSecondaryEmail
```

**API Routes** (filter: adminauth):
```
GET  /api/dashboard/statistics
GET  /api/dashboard/publications
GET  /api/dashboard/faculty-data
GET  /api/dashboard/year-data
GET  /api/dashboard/curriculum-data
```

**Public Data Routes**:
```
GET  /faculty-curriculum/getByFaculty
GET  /faculty-curriculum/getByCurriculum
GET  /faculty-curriculum/getPublications
GET  /curriculum/
GET  /curriculum/getForSelect
GET  /curriculum/getByFaculty
GET  /curriculum/getStats
GET  /curriculum/getWithPublicationStats
```

**Protected Routes** (filter: adminauth):
```
GET  /user/getUnassigned
GET  /user/getWithCurriculum
POST /user/updateCurriculum
```


## 5. EXISTING ADMIN VIEWS

### Dashboard
**File**: `app/Views/admin/Dashboard/dashboard.php`
- Statistics cards
- Faculty publication chart (Chart.js)
- Publications by year trend
- Curriculum summary with faculty filter
- All publications DataTable
- Sidebar navigation

### Email Management
**File**: `app/Views/admin/manageUser/manageEmail.php`
- User search
- User card grid
- User detail modal
- Add/edit/delete secondary emails
- Statistics cards

### Curriculum Assignment
**File**: `app/Views/admin/manageuserCuriculum/curiculumuser.php`
- Drag-and-drop interface
- Faculty filter
- User search
- Gradient visual design


## 6. ASSET FILES

**CSS**:
- `public/assets/css/admin-common.css`
- `public/assets/css/publication-form.css`
- `public/assets/css/author-search.css`

**JavaScript**:
- `public/assets/js/dashboard-statistics.js`
- `public/assets/js/curriculum-user-manager.js`
- `public/assets/js/publication-form.js`
- `public/assets/js/publication-upload.js`

**External Libraries**:
- Tailwind CSS
- Chart.js
- jQuery 3.6.0
- DataTables 1.13.7


## 7. DESIGN PATTERNS

**Views**:
- Tailwind CSS for styling
- Sidebar navigation
- Card-based layouts
- Modal dialogs
- Toast notifications
- Empty states

**Controllers**:
- AJAX-first approach
- Try-catch error handling
- JSON responses
- Model-level validation

**Models**:
- Query builder pattern
- Custom domain methods
- Relationship joins
- Data transformation

**Database**:
- Active/Inactive status
- Foreign key relationships
- Auto timestamps
- Code uniqueness per parent


## 8. WHAT'S IMPLEMENTED

- Faculty data model and queries
- Curriculum data model and queries
- Faculty-curriculum relationship
- User-curriculum assignment (drag-drop UI)
- Email management (primary + secondary)
- Publication tracking by faculty/curriculum
- Dashboard with statistics
- AJAX endpoints for all major operations
- Admin authorization (adminauth filter)
- Data validation


## 9. WHAT'S MISSING

- Faculty management CRUD interface
- Curriculum management CRUD interface
- Status toggle UI for faculty/curriculum
- Bulk import/export
- Advanced filtering in admin UIs
- Audit logging


## 10. KEY FILES REFERENCE

**Models**:
- `app/Models/CurriculumModel.php`
- `app/Models/FacultyModel.php`
- `app/Models/UserModel.php`

**Controllers**:
- `app/Controllers/AdminController.php`
- `app/Controllers/FacultyCurriculumController.php`
- `app/Controllers/CurriculumController.php`

**Views**:
- `app/Views/admin/Dashboard/dashboard.php`
- `app/Views/admin/manageUser/manageEmail.php`
- `app/Views/admin/manageuserCuriculum/curiculumuser.php`

**Config**:
- `app/Config/Routes.php`


## 11. NEXT STEPS

To create Faculty & Curriculum Management interface:

1. Create controllers with CRUD methods
2. Create views following existing patterns
3. Add JavaScript for form handling
4. Add routes for CRUD operations
5. Update sidebar navigation
6. Follow Tailwind CSS styling
7. Use same modal/form patterns
8. Use same toast notifications


## 12. KEY CONVENTIONS

**CodeIgniter 4**:
- Namespace: `App\Controllers`, `App\Models`
- Request: `$this->request->getGet/Post()`
- Response: `$this->response->setJSON()`

**Database**:
- Table names: plural lowercase
- Status: active|inactive
- Foreign keys in curriculum/user tables

**Frontend**:
- Tailwind CSS
- Card-based design
- Modal dialogs
- Toast notifications
- AJAX for data operations
