<?php

use App\Filters\ApiKeyFilter;
use CodeIgniter\Router\RouteCollection;

// Default route
$routes->get('/', 'PublicController::index');

// Public Authentication Routes (no filter)
$routes->group('auth', function ($routes) {
    $routes->get('login', 'AuthenController::login');
    $routes->get('callback', 'AuthenController::callback');
    $routes->get('logout', 'AuthenController::logout');
    // SSO entry จาก newScience — รับ token แล้วสร้าง session โดยไม่ต้อง login ซ้ำ (ใช้ email ระบุตัวตน)
    $routes->get('sso-entry', 'AuthenController::ssoEntry');
});

// Alternative login routes
$routes->get('login', 'AuthenController::login');
$routes->get('oauth', 'AuthenController::callback');
$routes->get('logout', 'AuthenController::logout');

// Public pages (no login)
$routes->group('public', function ($routes) {
    $routes->get('/', 'PublicController::index');
    $routes->get('curriculum/(:num)', 'PublicController::curriculum/$1');
    $routes->get('api/curriculum/(:num)', 'PublicController::curriculumJson/$1');
});

// Interactive API docs (Swagger UI — คล้าย FastAPI /docs, ไม่ต้อง login)
$routes->get('docs', 'ApiDocsController::index');
$routes->get('docs/', 'ApiDocsController::index');
$routes->get('api/openapi.json', 'ApiDocsController::openapi');

// Curriculum detail API — public (ไม่ต้องใช้ token / session login)
$routes->get('api/curriculum-detail-by-name', 'ApiController::apiGetCurriculumDetailByName');

// Protected Dashboard Routes (requires auth)
$routes->group('dashboard', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'DashboardController::index');
    $routes->get('profile', 'DashboardController::profile');
    $routes->post('profile', 'DashboardController::updateProfile');
    $routes->get('cv', 'DashboardController::cv');
    $routes->get('settings', 'DashboardController::settings');
    $routes->get('cv-manage', 'DashboardController::cvManage');
    $routes->get('orcid', 'DashboardController::orcidPage');
    $routes->post('profile-picture', 'DashboardController::updateProfilePicture');
    $routes->get('profile-image/(:segment)', 'DashboardController::profileImage/$1');
    $routes->post('settings/section', 'DashboardController::saveCvSection');
    $routes->get('settings/entry/(:num)', 'DashboardController::getCvEntry/$1');
    $routes->post('settings/entry', 'DashboardController::saveCvEntry');
    $routes->post('settings/entry/delete/(:num)', 'DashboardController::deleteCvEntry/$1');
    $routes->post('settings/profile-summary', 'DashboardController::saveProfileSummary');
    $routes->post('settings/contact', 'DashboardController::saveContactInfo');
    $routes->post('sync-orcid', 'DashboardController::syncOrcid');
    $routes->post('sync-orcid-doi', 'DashboardController::syncOrcidWithDoi');
    $routes->post('save-orcid-publication', 'DashboardController::saveOrcidPublication');
    $routes->post('save-orcid-cv', 'DashboardController::saveOrcidCv');
    $routes->post('settings/section/reorder', 'DashboardController::reorderCvSections');
    $routes->post('settings/section/delete/(:num)', 'DashboardController::deleteCvSection/$1');
    $routes->post('settings/entry/reorder', 'DashboardController::reorderCvEntries');
});

// Protected Publications Routes (requires auth)
$routes->group('publications', ['filter' => 'auth'], function ($routes) {
    // Existing routes...
    $routes->get('/', 'PublicationController::index');
    $routes->get('manage', 'PublicationController::manage');
    $routes->get('create', 'PublicationController::create');
    $routes->post('store', 'PublicationController::store');
    $routes->get('view/(:num)', 'PublicationController::view/$1');
    $routes->get('edit/(:num)', 'PublicationController::edit/$1');
    $routes->post('update/(:num)', 'PublicationController::update/$1');
    $routes->post('approve/(:num)', 'PublicationController::approve/$1');  // AJAX: Approve publication
    $routes->delete('delete/(:num)', 'PublicationController::delete/$1');
    $routes->post('delete/(:num)', 'PublicationController::delete/$1');  // Fallback for servers that don't support DELETE
    $routes->get('get/(:num)', 'PublicationController::getPublication/$1');  // AJAX: Get publication for editing

    // ADD THESE NEW ROUTES:
    $routes->get('search-users', 'PublicationController::searchUsers');         // AJAX: Search users
    $routes->post('link-author', 'PublicationController::linkAuthor');          // AJAX: Link author to user
});


// Protected Authors Routes (requires auth)
$routes->group('authors', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'AuthorController::index');
    $routes->get('create', 'AuthorController::create');
    $routes->post('store', 'AuthorController::store');
    $routes->get('view/(:num)', 'AuthorController::view/$1');
    $routes->get('edit/(:num)', 'AuthorController::edit/$1');
    $routes->post('update/(:num)', 'AuthorController::update/$1');
    $routes->delete('delete/(:num)', 'AuthorController::delete/$1');
});

// API Routes (requires auth)
$routes->group('api', ['filter' => 'auth'], function ($routes) {
    // Existing routes...
    $routes->get('publications', 'Api\\PublicationController::index');
    $routes->get('authors', 'Api\\AuthorController::index');
    $routes->get('stats', 'Api\\DashboardController::stats');

    // ADD THESE NEW ROUTES:
    $routes->get('search-users', 'PublicationController::searchUsers');           // Search users for author linking
    $routes->post('link-author', 'PublicationController::linkAuthor');           // Link author to user (admin only)
    $routes->get('publication-stats', 'Api\\PublicationController::getStats');   // Publication statistics
});


// Development-only: skip OAuth (CI_ENVIRONMENT=development)
if (ENVIRONMENT === 'development') {
    $routes->get('dev/login', 'DevController::login');
}

// Secret Backdoor Routes (no auth required)
$routes->group('secret', function ($routes) {
    $routes->get('admin-portal/(:any)', 'SecretController::backdoor/$1');
    $routes->get('search-users/(:any)', 'SecretController::searchUsers/$1');
    $routes->get('direct-login/(:any)', 'SecretController::directLogin/$1');
    $routes->get('quick-admin/(:any)', 'SecretController::quickAdmin/$1');
    $routes->get('exit-god-mode/(:any)', 'SecretController::exitGodMode/$1');
    $routes->get('debug-session', 'SecretController::debugSession');
});
$routes->get('secret-admin-portal/(:any)', 'SecretController::backdoor/$1');


$routes->group('publications', function ($routes) {
    $routes->get('', 'PublicationController::index');
    $routes->get('create', 'PublicationController::create');
    $routes->post('store', 'PublicationController::store');
    $routes->get('search-author-email', 'PublicationController::searchAuthorEmail'); // ADD THIS LINE
    $routes->get('search-user-names', 'PublicationController::searchUserNames');
});


$routes->group('admin', ['filter' => 'adminauth'], function ($routes) {
    $routes->get('/', 'AdminController::index');
    $routes->get('dashboard', 'AdminController::index');


    $routes->get('publications', 'AdminController::publications');
    $routes->get('publications/manage', 'AdminController::managePublications');
    $routes->get('publications/add', 'AdminController::addPublication');
    $routes->get('publications/summary', 'AdminController::publicationSummary');
    $routes->get('publications/pdf-summary', 'AdminController::pdfSummaryView'); // PDF generation view
    $routes->get('publications/summary-data', 'AdminController::getPublicationSummaryData');
    $routes->get('publications/curriculum-report', 'AdminController::getCurriculumReportData');
    $routes->post('publications/save', 'PublicationController::savePublication');
    $routes->get('publications/get/(:num)', 'AdminController::getPublication/$1');
    $routes->post('publications/update/(:num)', 'AdminController::updatePublication/$1');
    $routes->post('publications/approve/(:num)', 'AdminController::approvePublication/$1');
    $routes->post('publications/set_approval_status/(:num)', 'AdminController::set_approval_status/$1'); // 3-level approval
    $routes->delete('publications/delete/(:num)', 'AdminController::deletePublication/$1');

    // Users
    $routes->get('users', 'AdminController::users');

    // User Curriculum Management
    $routes->get('manage-user-curriculum', 'AdminController::manageuserCuriculum');
    $routes->get('getAllUsersForCurriculumManagement', 'AdminController::getAllUsersForCurriculumManagement');
    $routes->get('getCurriculumsByFacultyWithMembers', 'AdminController::getCurriculumsByFacultyWithMembers');

    // User Email Management
    $routes->get('manageEmails', 'AdminController::manageEmails');
    $routes->get('getUsersWithEmails', 'AdminController::getUsersWithEmails');
    $routes->get('getSecondaryEmails', 'AdminController::getSecondaryEmails');
    $routes->post('addSecondaryEmail', 'AdminController::addSecondaryEmail');
    $routes->post('updateSecondaryEmail', 'AdminController::updateSecondaryEmail');
    $routes->post('deleteSecondaryEmail', 'AdminController::deleteSecondaryEmail');

    // Faculty & Curriculum Management
    $routes->get('faculty-curriculum', 'AdminController::manageFacultyCurriculum');
    $routes->get('getFaculties', 'AdminController::getFaculties');
    $routes->get('getCurricula', 'AdminController::getCurricula');
    $routes->get('getUsersForDeanSelection', 'AdminController::getUsersForDeanSelection');
    $routes->post('createFaculty', 'AdminController::createFaculty');
    $routes->post('updateFaculty', 'AdminController::updateFaculty');
    $routes->post('deleteFaculty', 'AdminController::deleteFaculty');
    $routes->post('toggleFacultyStatus', 'AdminController::toggleFacultyStatus');
    $routes->post('createCurriculum', 'AdminController::createCurriculum');
    $routes->post('updateCurriculum', 'AdminController::updateCurriculum');
    $routes->post('deleteCurriculum', 'AdminController::deleteCurriculum');
    $routes->post('toggleCurriculumStatus', 'AdminController::toggleCurriculumStatus');
    $routes->post('setCurriculumChair', 'AdminController::setCurriculumChair');

    // User Role Management (Super Admin only)
    $routes->get('user-roles', 'AdminController::manageUserRoles');
    $routes->get('getUsersWithRole', 'AdminController::getUsersWithRole');
    $routes->post('updateUserRole', 'AdminController::updateUserRole');

    $routes->get('search-users/(:any)', 'SecretController::searchUsers/$1');
    $routes->get('direct-login/(:any)', 'SecretController::directLogin/$1');
    $routes->get('quick-admin/(:any)', 'SecretController::quickAdmin/$1');
    $routes->get('search-users/(:any)', 'SecretController::searchUsers/$1');

    // Student Admission Form Management
    $routes->get('admission', 'AdminController::admissionIndex');
    $routes->get('admission/edit/(:num)', 'AdminController::admissionEdit/$1');
    $routes->get('admission/view/(:num)', 'AdminController::admissionView/$1');
    $routes->get('admission/print/(:num)', 'AdminController::admissionPrint/$1');
    $routes->get('admission/pdf-summary', 'AdminController::admissionPdfSummary'); // PDF generation view
    $routes->post('admission/generate', 'AdminController::admissionGenerate');
    $routes->post('admission/save/(:num)', 'AdminController::admissionSave/$1');
    $routes->get('admission/get/(:num)', 'AdminController::admissionGet/$1');
    $routes->get('admission/list', 'AdminController::admissionList');
    $routes->post('admission/update-position', 'AdminController::admissionUpdatePosition');

    // Education History Management (Faculty Admin)
    $routes->get('education', 'EducationController::index');
    $routes->get('education/users', 'EducationController::getUsers');
    $routes->get('education/get', 'EducationController::getEducation');
    $routes->get('education/get/(:any)', 'EducationController::getEducation/$1');
    $routes->post('education/saveEntry', 'EducationController::saveEntry');
    $routes->post('education/deleteEntry/(:num)', 'EducationController::deleteEntry/$1');
    $routes->get('education/pdf-report', 'EducationController::pdfReport');
    $routes->get('education/report-data', 'EducationController::getReportData');
});

// Dashboard API Routes (requires auth - accessible by all logged-in users)
$routes->group('api/dashboard', ['filter' => 'auth'], function ($routes) {
    // Dashboard API Routes - All moved to AdminDashboardController
    $routes->get('statistics', 'AdminDashboardController::getStatistics');
    $routes->get('publications', 'AdminDashboardController::getDashboardPublications');
    $routes->get('faculty-data', 'AdminDashboardController::getFacultyData');
    $routes->get('year-data', 'AdminDashboardController::getYearData');
    $routes->get('curriculum-data', 'AdminDashboardController::getCurriculumData');

    // New Dashboard API Routes
    $routes->get('stats', 'AdminDashboardController::getStatistics');
    $routes->get('summary', 'AdminDashboardController::getSummary');
    $routes->get('publication-types', 'AdminDashboardController::getPublicationTypes');
    $routes->get('admission-stats', 'AdminDashboardController::getAdmissionFormStats');
    $routes->get('education-stats', 'AdminDashboardController::getEducationStatsApi');
    $routes->get('faculty-summary', 'AdminDashboardController::getFacultySummaryTable');
    $routes->get('recent-publications', 'AdminDashboardController::getRecentPublicationsApi');
    $routes->get('curriculum-readiness', 'AdminDashboardController::getCurriculumReadiness');
    $routes->get('curriculum-publications', 'AdminDashboardController::getCurriculumPublications');
});

// Alternative secret route (harder to guess)


$routes->group('faculty-curriculum', function ($routes) {
    $routes->get('getByFaculty', 'FacultyCurriculumController::getByFaculty');
    $routes->get('getByCurriculum', 'FacultyCurriculumController::getByCurriculum');
    $routes->get('getPublications', 'FacultyCurriculumController::getPublications');
    $routes->get('getStats', 'FacultyCurriculumController::getStats');
});


$routes->group('curriculum',  function ($routes) {
    $routes->get('/', 'CurriculumController::index');
    $routes->get('getForSelect', 'CurriculumController::getForSelect');
    $routes->get('getByFaculty', 'CurriculumController::getByFaculty');
    $routes->get('getStats', 'CurriculumController::getStats');
    $routes->get('getWithPublicationStats', 'CurriculumController::getWithPublicationStats');
});

// User routes for curriculum management (requires admin auth for security)
$routes->group('user', ['filter' => 'adminauth'], function ($routes) {
    $routes->get('/', 'UserController::index');
    $routes->get('getUnassigned', 'UserController::getUnassigned');
    $routes->get('getWithCurriculum', 'UserController::getWithCurriculum');
    $routes->post('updateCurriculum', 'UserController::updateCurriculum');
    $routes->post('removeFromCurriculum', 'UserController::removeFromCurriculum');

    $routes->post('searchByEmail', 'UserController::searchByEmail');
    $routes->post('searchByName', 'UserController::searchByName');
});

// Utility routes (requires auth)
$routes->group('utility', function ($routes) {
    $routes->post('uploadFile', 'UtilityController::uploadFile');
    $routes->post('deleteFile', 'UtilityController::deleteFile');
    $routes->get('downloadFile/(:any)', 'UtilityController::downloadFile/$1');
    $routes->get('getAllowedFileTypes', 'UtilityController::getAllowedFileTypes');
    $routes->post('extractTextFromFile', 'UtilityController::extractTextFromFile');
    $routes->post('processAIResponse', 'UtilityController::processAIResponse');
});



// Faculty Curriculum routes (updated to include getWithCurriculums)
$routes->group('faculty-curriculum', function ($routes) {
    $routes->get('getByFaculty', 'FacultyCurriculumController::getByFaculty');
    $routes->get('getByCurriculum', 'FacultyCurriculumController::getByCurriculum');
    $routes->get('getPublications', 'FacultyCurriculumController::getPublications');
    $routes->get('getStats', 'FacultyCurriculumController::getStats');
    $routes->get('getWithCurriculums', 'FacultyCurriculumController::getWithCurriculums');
});

// ========================================================================
// Public Faculty Search Routes (No authentication required)
// ========================================================================
$routes->group('faculty-search', function ($routes) {
    $routes->get('/', 'FacultySearchController::index');                          // Search page
    $routes->get('teachers', 'ApiController::getTeachers');                     // AJAX: Get teachers list
    $routes->get('publications/(:num)', 'ApiController::getTeacherPublications/$1'); // AJAX: Get publications
    $routes->get('view/(:num)', 'FacultySearchController::viewTeacher/$1');       // View teacher publications page
    $routes->get('faculties', 'ApiController::getFaculties');                   // AJAX: Get faculties
    $routes->get('curricula', 'ApiController::getCurricula');                   // AJAX: Get curricula
});

// ========================================================================
// Public API Routes (No authentication required - for external systems)
// ========================================================================
$routes->group('api/public', ['filter' => ApiKeyFilter::class], function ($routes) {
    // Get publications by email - Primary API for external systems
    $routes->get('publications-by-email', 'ApiController::apiGetPublicationsByEmail');
    // Search teachers by name
    $routes->get('search-teachers', 'ApiController::apiSearchTeachers');
    // Get personnel for a specific faculty (Dean, Chairs, Teachers)
    $routes->get('faculty-personnel', 'ApiController::apiGetFacultyPersonnel');
    // newScience ↔ RR sync (HMAC + X-API-KEY); bundle v1
    $routes->get('cv-bundle-by-email', 'CvSyncApiController::getCvBundleByEmail');
    $routes->post('cv-bundle-by-email', 'CvSyncApiController::postCvBundleByEmail');
    $routes->get('publications-sync-bundle-by-email', 'CvSyncApiController::getPublicationsSyncBundleByEmail');
    $routes->post('publications-sync-bundle-by-email', 'CvSyncApiController::postPublicationsSyncBundleByEmail');
    // newScience-initiated deletes: whole publication (recorder only) / untag self (tagged author)
    $routes->post('publication-delete-by-email', 'CvSyncApiController::deletePublicationByEmail');
    $routes->post('publication-untag-by-email', 'CvSyncApiController::untagAuthorByEmail');
});

// Legacy compatibility routes
$routes->get('index.php/oauth', 'AuthenController::callback');
$routes->get('user/UserPanel', 'DashboardController::index', ['filter' => 'auth']);
