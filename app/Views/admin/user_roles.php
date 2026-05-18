<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Role Management</title>
    <link rel="stylesheet" href="<?= base_url('/public/assets/css/tailwind.min.css') ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin-common.css') ?>">
</head>

<body class="min-h-full">
    <div class="min-h-full bg-gray-50">
        <!-- Top Navigation Bar -->
        <nav class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">User Role Management</h1>
                    <p class="text-sm text-gray-600"><?= session()->get('institution_name') ?? 'Your University' ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="window.location.href='<?= base_url('index.php/admin/dashboard') ?>'"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                        ← Back to Dashboard
                    </button>
                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                        <?= substr(session()->get('user_name') ?? 'A', 0, 1) ?>
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex">
            <!-- Sidebar Navigation -->
            <?= view('admin/partials/navigation') ?>

            <!-- Main Content -->
            <main class="flex-1 p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">User Roles & Permissions</h2>
                                <p class="text-sm text-gray-600 mt-1">Manage user access levels and faculty assignments</p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="text-sm text-gray-600">
                                    <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs font-medium rounded">Super Admin</span> Full Access
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded ml-2">Faculty Admin</span> Faculty Scoped
                                    <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded ml-2">User</span> Own Publications
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <table id="usersTable" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Name (Type)</th>
                                    <th>Email</th>
                                    <th>Main Faculty</th>
                                    <th>Curriculum</th>
                                    <th>System Role</th>
                                    <th>Managed Faculties</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Role Assignment Modal -->
    <div id="roleModal" class="hidden fixed inset-0 z-50 flex items-start justify-center bg-gray-900 bg-opacity-70 overflow-y-auto py-10 px-4">
        <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl">
            <div class="flex justify-between items-center px-8 py-6 border-b border-gray-100">
                <div>
                    <p class="text-sm text-blue-600 font-semibold uppercase tracking-wide">Role Assignment</p>
                    <h3 class="text-2xl font-bold text-gray-900">ปรับสิทธิ์ผู้ใช้งาน</h3>
                </div>
                <button onclick="closeRoleModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="roleForm" class="px-8 py-6 space-y-5 max-h-[70vh] overflow-y-auto">
                <input type="hidden" id="userId" name="user_id">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">อีเมล</label>
                        <input type="text" id="userEmail" readonly
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ระบบบทบาท (System Role) *</label>
                        <select id="userRole" name="role" required onchange="toggleFacultySelection()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="user">User</option>
                            <option value="faculty_admin">Faculty Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">สิทธิ์ในระบบ (แยกจากประเภทผู้ใช้)</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                    <input type="text" id="userName" readonly
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                </div>

                <!-- User Type (Teacher/Staff) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">User Type *</label>
                    <select id="userType" name="user_type" required onchange="toggleUserTypeFaculty()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select User Type --</option>
                        <option value="TEACHER">Teacher (อาจารย์)</option>
                        <option value="STAFF">Staff (เจ้าหน้าที่)</option>
                        <option value="STUDENT">Student (นักศึกษา)</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Teachers must have a faculty affiliation</p>
                </div>

                <!-- Faculty (Required for TEACHER) -->
                <div id="userFacultyDiv">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Faculty (คณะ) <span id="facultyRequiredIndicator" class="text-red-600">*</span>
                    </label>
                    <select id="userFaculty" name="faculty_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select Faculty --</option>
                        <!-- Faculties will be populated here -->
                    </select>
                    <p id="facultyHelpText" class="text-xs text-gray-500 mt-1">Select the faculty this user belongs to</p>
                </div>

                <div id="facultySelectionDiv" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Managed Faculties (for Faculty Admin) *</label>
                    <div id="facultyCheckboxes" class="max-h-60 overflow-y-auto border border-gray-300 rounded-lg p-3">
                        <!-- Checkboxes will be populated here -->
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Select faculties this admin can manage</p>
                </div>
            </form>
            <div class="flex justify-end space-x-3 px-8 py-6 border-t border-gray-100 rounded-b-2xl">
                    <button type="button" onclick="closeRoleModal()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" form="roleForm"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Save Role
                    </button>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= rtrim(base_url(), '/') ?>';
    </script>
    <script src="<?= base_url('public/assets/js/user-roles-manager.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const roleModal = document.getElementById('roleModal');
            if (roleModal) {
                roleModal.addEventListener('click', (event) => {
                    if (event.target === roleModal) {
                        closeRoleModal();
                    }
                });
            }
        });
    </script>
</body>

</html>
