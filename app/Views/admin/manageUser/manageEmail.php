<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการอีเมลผู้ใช้</title>
    <link rel="stylesheet" href="<?= base_url('/public/assets/css/tailwind.min.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin-common.css') ?>">
</head>

<body class="min-h-full">
    <div class="min-h-full bg-gray-50">
        <!-- Top Navigation Bar -->
        <nav class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">จัดการอีเมลผู้ใช้</h1>
                    <p class="text-sm text-gray-600"><?= session()->get('institution_name') ?? 'มหาวิทยาลัยของคุณ' ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-600">
                        <span class="font-medium">ผู้ใช้:</span>
                        <span id="user-count-badge" class="ml-1 bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-semibold">0</span>
                    </div>
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
                <!-- Page Header -->
                <div class="mb-6">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">จัดการอีเมลผู้ใช้</h2>
                    <p class="text-gray-600">จัดการอีเมลหลักและอีเมลรองของผู้ใช้ในระบบ</p>
                </div>

                <!-- Statistics Overview -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 stat-card">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">👥</div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">ผู้ใช้ทั้งหมด</p>
                                <p id="total-users" class="text-2xl font-bold text-gray-900">0</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 stat-card">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">📧</div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">อีเมลทั้งหมด</p>
                                <p id="total-emails" class="text-2xl font-bold text-gray-900">0</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 stat-card">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">📨</div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">อีเมลรอง</p>
                                <p id="total-secondary-emails" class="text-2xl font-bold text-gray-900">0</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <label for="search-users" class="block text-sm font-medium text-gray-700 mb-2">ค้นหาผู้ใช้</label>
                            <input type="text" id="search-users" placeholder="ค้นหาด้วยชื่อ, นามสกุล, หรืออีเมล..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                </div>

                <!-- Users List -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">รายการผู้ใช้</h3>
                        <div class="text-sm text-gray-500">คลิกเพื่อดูรายละเอียด</div>
                    </div>

                    <!-- Empty State -->
                    <div id="empty-state" class="text-center py-12 hidden">
                        <div class="text-gray-400 text-6xl mb-4">👥</div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">ไม่พบผู้ใช้</h3>
                        <p class="text-gray-500">ลองปรับเปลี่ยนคำค้นหา</p>
                    </div>

                    <!-- Users Grid -->
                    <div id="users-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- User cards will be populated here -->
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- User Detail Modal -->
    <div id="user-detail-modal" class="modal">
        <div class="modal-content bg-white rounded-xl shadow-2xl max-w-3xl mx-auto mt-20 overflow-hidden">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold text-white">รายละเอียดอีเมลผู้ใช้</h3>
                    <p id="detail-user-name" class="text-blue-100 text-sm mt-1"></p>
                </div>
                <button onclick="closeDetailModal()" class="text-white hover:text-gray-200 text-2xl font-bold">&times;</button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <!-- Primary Email Section -->
                <div class="mb-6">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">อีเมลหลัก</h4>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600">
                                    ✓
                                </div>
                                <div>
                                    <span id="detail-primary-email" class="text-green-800 font-medium"></span>
                                    <p class="text-xs text-green-600 mt-1">* ไม่สามารถแก้ไขได้</p>
                                </div>
                            </div>
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium">หลัก</span>
                        </div>
                    </div>
                </div>

                <!-- Secondary Emails Section -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-gray-700">อีเมลรอง</h4>
                        <button onclick="showAddEmailForm()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-lg text-sm font-medium transition-colors">
                            + เพิ่มอีเมล
                        </button>
                    </div>

                    <!-- Add New Email Form (Hidden by default) -->
                    <div id="add-email-form" class="hidden mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex gap-2">
                            <input type="email" id="new-detail-secondary-email" placeholder="กรอกอีเมลรอง..." class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <button onclick="saveNewDetailSecondaryEmail()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                บันทึก
                            </button>
                            <button onclick="hideAddEmailForm()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                ยกเลิก
                            </button>
                        </div>
                    </div>

                    <!-- Secondary Emails List -->
                    <div id="detail-secondary-emails" class="space-y-3">
                        <!-- Secondary emails will be populated here -->
                    </div>

                    <!-- Empty State for Secondary Emails -->
                    <div id="no-secondary-emails" class="text-center py-6 text-gray-400 hidden">
                        <p class="text-sm">ยังไม่มีอีเมลรอง</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end">
                <button onclick="closeDetailModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-lg font-medium transition-colors">
                    ปิด
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed top-4 right-4 z-50 hidden">
        <div class="bg-white border-l-4 rounded-lg shadow-lg p-4 max-w-md">
            <div class="flex items-center">
                <div id="toast-icon" class="mr-3"></div>
                <p id="toast-message" class="text-sm font-medium"></p>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= rtrim(base_url(), '/') ?>';
        let users = [];
        let currentDetailUser = null;
        let userCount = 0;
        let emailCount = 0;

        async function loadUsers(searchTerm = '') {
            try {
                const url = searchTerm ?
                    `${BASE_URL}/index.php/admin/getUsersWithEmails?search=${encodeURIComponent(searchTerm)}` :
                    `${BASE_URL}/index.php/admin/getUsersWithEmails`;

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    users = result.data;
                    userCount = users.length;
                    renderUsers();
                    updateUserCount();
                } else {
                    showMessage('ไม่สามารถโหลดข้อมูลผู้ใช้', 'error');
                }
            } catch (error) {
                console.error('Error loading users:', error);
                showMessage('เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
            }
        }

        function renderUsers() {
            const emptyState = document.getElementById('empty-state');
            const usersList = document.getElementById('users-list');

            if (users.length === 0) {
                emptyState.querySelector('h3').textContent = 'ไม่พบผู้ใช้';
                emptyState.querySelector('p').textContent = 'ลองปรับเปลี่ยนคำค้นหา';
                emptyState.classList.remove('hidden');
                usersList.classList.add('hidden');
                return;
            }

            emptyState.classList.add('hidden');
            usersList.classList.remove('hidden');

            usersList.innerHTML = users.map(user => {
                const secondaryEmails = user.secondary_emails ?
                    user.secondary_emails.split(',').filter(email => email.trim()) : [];
                const displayName = user.thai_name && user.thai_lastname ?
                    `${user.thai_name} ${user.thai_lastname}` :
                    user.username;

                return `
                    <div class="user-card bg-white border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-500" onclick="openDetailModal('${user.uid}')">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-lg">
                                    ${displayName.charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">${displayName}</h4>
                                    <p class="text-xs text-gray-500">${user.faculty_name || 'ไม่ระบุคณะ'}</p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center text-sm">
                                <span class="text-gray-500 w-16">อีเมลหลัก:</span>
                                <span class="text-gray-700 truncate flex-1">${user.primary_email || user.email}</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="text-gray-500 w-16">อีเมลรอง:</span>
                                <span class="text-blue-600 font-medium">${secondaryEmails.length} อีเมล</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function updateUserCount() {
            let totalSecondary = 0;
            users.forEach(user => {
                const secondaryEmails = user.secondary_emails ?
                    user.secondary_emails.split(',').filter(email => email.trim()) : [];
                totalSecondary += secondaryEmails.length;
            });

            document.getElementById('total-users').textContent = userCount;
            document.getElementById('total-emails').textContent = userCount + totalSecondary;
            document.getElementById('total-secondary-emails').textContent = totalSecondary;
            document.getElementById('user-count-badge').textContent = userCount;
        }

        async function openDetailModal(userUid) {
            const user = users.find(u => u.uid === userUid);
            if (!user) return;

            currentDetailUser = user;
            const displayName = user.thai_name && user.thai_lastname ?
                `${user.thai_name} ${user.thai_lastname}` :
                user.username;

            document.getElementById('detail-user-name').textContent = displayName;
            document.getElementById('detail-primary-email').textContent = user.primary_email || user.email;

            await loadSecondaryEmails(userUid);

            document.getElementById('user-detail-modal').style.display = 'block';
        }

        async function loadSecondaryEmails(userUid) {
            try {
                const response = await fetch(`${BASE_URL}/index.php/admin/getSecondaryEmails?user_uid=${userUid}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    renderSecondaryEmails(result.data);
                }
            } catch (error) {
                console.error('Error loading secondary emails:', error);
            }
        }

        function renderSecondaryEmails(emails) {
            const container = document.getElementById('detail-secondary-emails');
            const noEmailsMsg = document.getElementById('no-secondary-emails');

            if (!emails || emails.length === 0) {
                container.innerHTML = '';
                noEmailsMsg.classList.remove('hidden');
                return;
            }

            noEmailsMsg.classList.add('hidden');
            container.innerHTML = emails.map((email, index) => `
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 flex items-center justify-between">
                    <div class="flex items-center gap-3 flex-1">
                        <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-gray-600 text-sm">
                            ${index + 1}
                        </div>
                        <span class="text-gray-700 flex-1" id="email-text-${email.id}">${email.email}</span>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="editSecondaryEmail('${email.id}', '${email.email}')" class="text-blue-600 hover:text-blue-800 px-3 py-1 text-sm font-medium">
                            แก้ไข
                        </button>
                        <button onclick="deleteSecondaryEmail('${email.id}')" class="text-red-600 hover:text-red-800 px-3 py-1 text-sm font-medium">
                            ลบ
                        </button>
                    </div>
                </div>
            `).join('');
        }

        async function editSecondaryEmail(authorId, currentEmail) {
            const newEmail = prompt('แก้ไขอีเมล:', currentEmail);
            if (!newEmail || newEmail === currentEmail) {
                return;
            }

            if (!validateEmail(newEmail)) {
                showMessage('รูปแบบอีเมลไม่ถูกต้อง', 'error');
                return;
            }

            try {
                const response = await fetch(`${BASE_URL}/index.php/admin/updateSecondaryEmail`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        author_id: authorId,
                        email: newEmail,
                        user_uid: currentDetailUser.uid
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('แก้ไขอีเมลสำเร็จ', 'success');
                    await loadSecondaryEmails(currentDetailUser.uid);
                    await loadUsers();
                } else {
                    showMessage(result.message || 'ไม่สามารถแก้ไขอีเมลได้', 'error');
                }
            } catch (error) {
                console.error('Error updating email:', error);
                showMessage('เกิดข้อผิดพลาด', 'error');
            }
        }

        async function deleteSecondaryEmail(authorId) {
            if (!confirm('คุณต้องการลบอีเมลนี้หรือไม่?')) {
                return;
            }

            try {
                const response = await fetch(`${BASE_URL}/index.php/admin/deleteSecondaryEmail`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        author_id: authorId,
                        user_uid: currentDetailUser.uid
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('ลบอีเมลสำเร็จ', 'success');
                    await loadSecondaryEmails(currentDetailUser.uid);
                    await loadUsers();
                } else {
                    showMessage(result.message || 'ไม่สามารถลบอีเมลได้', 'error');
                }
            } catch (error) {
                console.error('Error deleting email:', error);
                showMessage('เกิดข้อผิดพลาด', 'error');
            }
        }

        function showAddEmailForm() {
            document.getElementById('add-email-form').classList.remove('hidden');
            document.getElementById('new-detail-secondary-email').focus();
        }

        function hideAddEmailForm() {
            document.getElementById('add-email-form').classList.add('hidden');
            document.getElementById('new-detail-secondary-email').value = '';
        }

        async function saveNewDetailSecondaryEmail() {
            const newEmail = document.getElementById('new-detail-secondary-email').value.trim();

            if (!newEmail) {
                showMessage('กรุณากรอกอีเมล', 'error');
                return;
            }

            if (!validateEmail(newEmail)) {
                showMessage('รูปแบบอีเมลไม่ถูกต้อง', 'error');
                return;
            }

            try {
                const response = await fetch(`${BASE_URL}/index.php/admin/addSecondaryEmail`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        user_uid: currentDetailUser.uid,
                        email: newEmail
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('เพิ่มอีเมลสำเร็จ', 'success');
                    document.getElementById('new-detail-secondary-email').value = '';
                    hideAddEmailForm();
                    await loadSecondaryEmails(currentDetailUser.uid);
                    await loadUsers();
                } else {
                    showMessage(result.message || 'ไม่สามารถเพิ่มอีเมลได้', 'error');
                }
            } catch (error) {
                console.error('Error adding email:', error);
                showMessage('เกิดข้อผิดพลาด', 'error');
            }
        }

        function closeDetailModal() {
            document.getElementById('user-detail-modal').style.display = 'none';
            currentDetailUser = null;
            hideAddEmailForm();
        }

        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        function showMessage(message, type) {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            const toastIcon = document.getElementById('toast-icon');

            toastMessage.textContent = message;

            if (type === 'success') {
                toastIcon.innerHTML = '<div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center text-green-600">✓</div>';
                toast.querySelector('.border-l-4').classList.remove('border-red-500');
                toast.querySelector('.border-l-4').classList.add('border-green-500');
            } else {
                toastIcon.innerHTML = '<div class="w-6 h-6 bg-red-100 rounded-full flex items-center justify-center text-red-600">✕</div>';
                toast.querySelector('.border-l-4').classList.remove('border-green-500');
                toast.querySelector('.border-l-4').classList.add('border-red-500');
            }

            toast.classList.remove('hidden');

            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        }

        // Search functionality with debounce
        let searchTimeout;
        document.getElementById('search-users').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadUsers(e.target.value);
            }, 300);
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('user-detail-modal');
            if (event.target === modal) {
                closeDetailModal();
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
        });
    </script>
</body>

</html>
