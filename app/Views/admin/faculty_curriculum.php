<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคณะและหลักสูตร</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-common.css') ?>">
    <script src="<?= base_url('assets/js/modal-handler.js') ?>"></script>
</head>

<body class="min-h-full">
    <div class="min-h-full bg-gray-50">
        <!-- Top Navigation Bar -->
        <nav class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">จัดการคณะและหลักสูตร</h1>
                    <p class="text-sm text-gray-600"><?= session()->get('institution_name') ?? 'มหาวิทยาลัยของคุณ' ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="window.location.href='<?= site_url('admin/dashboard') ?>'"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                        ← กลับไปหน้าแดชบอร์ด
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
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Faculty Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900">คณะ</h2>
                                    <p class="text-sm text-gray-600 mt-1">จัดการคณะของมหาวิทยาลัย</p>
                                </div>
                                <button onclick="openFacultyModal()"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center">
                                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    เพิ่มคณะ
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
                            <table id="facultyTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>รหัส</th>
                                        <th>ชื่อคณะ</th>
                                        <th>คณบดี</th>
                                        <th>สถานะ</th>
                                        <th>หลักสูตร</th>
                                        <th>การจัดการ</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Curriculum Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900">หลักสูตร</h2>
                                    <p class="text-sm text-gray-600 mt-1">จัดการหลักสูตรของคณะ</p>
                                </div>
                                <button onclick="openCurriculumModal()"
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center">
                                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    เพิ่มหลักสูตร
                                </button>
                            </div>
                            <!-- Filter by Faculty -->
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">กรองตามคณะ</label>
                                <select id="facultyFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">คณะทั้งหมด</option>
                                </select>
                            </div>
                        </div>
                        <div class="p-6">
                            <table id="curriculumTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>รหัส</th>
                                        <th>ชื่อหลักสูตร</th>
                                        <th>คณะ</th>
                                        <th>ระดับการศึกษา</th>
                                        <th>สถานะ</th>
                                        <th>การจัดการ</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Faculty Modal -->
    <div id="facultyModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 id="facultyModalTitle" class="text-xl font-bold text-gray-900">เพิ่มคณะ</h3>
                <button onclick="closeFacultyModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="facultyForm">
                <input type="hidden" id="facultyId" name="id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">รหัสคณะ *</label>
                    <input type="text" id="facultyCode" name="code" required maxlength="10"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        placeholder="เช่น กษ">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อคณะ *</label>
                    <input type="text" id="facultyName" name="name" required maxlength="255"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        placeholder="เช่น คณะเกษตรศาสตร์">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">คณบดี</label>
                    <select id="facultyDean" name="dean_email"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- ไม่ระบุ --</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">เลือกคณบดีจากรายชื่อผู้ใช้ในระบบ</p>
                </div>
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" id="facultyStatus" name="status" checked
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">ใช้งาน</span>
                    </label>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeFacultyModal()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Curriculum Modal -->
    <div id="curriculumModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 id="curriculumModalTitle" class="text-xl font-bold text-gray-900">เพิ่มหลักสูตร</h3>
                <button onclick="closeCurriculumModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="curriculumForm">
                <input type="hidden" id="curriculumId" name="id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">คณะ *</label>
                    <select id="curriculumFaculty" name="faculty_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">เลือกคณะ</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">รหัสหลักสูตร *</label>
                    <input type="text" id="curriculumCode" name="code" required maxlength="20"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        placeholder="เช่น CS2024">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อหลักสูตร *</label>
                    <input type="text" id="curriculumName" name="name" required maxlength="255"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        placeholder="เช่น วิทยาการคอมพิวเตอร์">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">ระดับการศึกษา *</label>
                    <select id="curriculumDegree" name="degree_level" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">เลือกระดับการศึกษา</option>
                        <option value="bachelor">ปริญญาตรี</option>
                        <option value="master">ปริญญาโท</option>
                        <option value="doctoral">ปริญญาเอก</option>
                    </select>
                </div>
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" id="curriculumStatus" name="status" checked
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">ใช้งาน</span>
                    </label>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeCurriculumModal()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= rtrim(base_url(), '/') ?>';
    </script>
    <script src="<?= base_url('assets/js/app-routes.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/app-routes.js') ?: time() ?>"></script>
    <script src="<?= base_url('assets/js/faculty-curriculum-manager.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/faculty-curriculum-manager.js') ?: time() ?>"></script>
</body>

</html>
