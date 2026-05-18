<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin User Management Portal</title>
    <link rel="stylesheet" href="<?= base_url('/public/assets/css/tailwind.min.css') ?>">
    <style>
        .user-card {
            transition: all 0.3s ease;
        }

        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen">
    <div class="container mx-auto p-6">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">Admin User Management</h1>
                        <p class="text-gray-600">Manage user access and impersonation</p>
                        <div class="text-sm text-gray-500 mt-2">
                            Session: <?= $_SERVER['REMOTE_ADDR'] ?? 'Unknown' ?> | <?= date('Y-m-d H:i:s') ?>
                        </div>
                    </div>
                    <div class="text-6xl opacity-20">🛡️</div>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php if (session()->getFlashdata('error')): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <strong>Error:</strong> <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="<?= base_url('index.php/secret/quick-admin/' . $secret_key) ?>"
                        class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 text-center shadow-md hover:shadow-lg">
                        Quick Admin Access
                    </a>
                    <a href="<?= base_url('index.php/admin/dashboard/') ?>"
                        class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 text-center shadow-md hover:shadow-lg">
                        Dashboard
                    </a>
                    <a href="<?= base_url('index.php/secret/exit-god-mode/' . $secret_key) ?>"
                        onclick="return confirm('Exit god mode and destroy all sessions?')"
                        class="bg-gradient-to-r from-red-500 to-red-700 hover:from-red-600 hover:to-red-800 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 text-center shadow-md hover:shadow-lg">
                        🚪 Exit God Mode
                    </a>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Find Users</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <input type="text" id="searchInput" placeholder="Search by name, email, or department..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <select id="roleFilter" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">All Roles</option>
                            <option value="admin">Admins Only</option>
                            <option value="user">Regular Users</option>
                        </select>
                    </div>
                    <div>
                        <select id="statusFilter" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="oauth">OAuth Users</option>
                            <option value="local">Local Users</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-between items-center mt-4">
                    <div class="text-sm text-gray-600">
                        <span id="userCount">0</span> users found
                    </div>
                </div>
            </div>

            <!-- System Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Users</p>
                            <p class="text-3xl font-bold text-blue-600"><?= count($users) ?></p>
                        </div>
                        <div class="text-3xl text-blue-500">👥</div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Admin Users</p>
                            <p class="text-3xl font-bold text-red-600"><?= count(array_filter($users, fn($u) => $u['admin'] == 1)) ?></p>
                        </div>
                        <div class="text-3xl text-red-500">👑</div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">OAuth Users</p>
                            <p class="text-3xl font-bold text-green-600"><?= count(array_filter($users, fn($u) => $u['edoc'] == 1)) ?></p>
                        </div>
                        <div class="text-3xl text-green-500">🔗</div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Active Users</p>
                            <p class="text-3xl font-bold text-purple-600"><?= count(array_filter($users, fn($u) => $u['active'] == 1)) ?></p>
                        </div>
                        <div class="text-3xl text-purple-500">✅</div>
                    </div>
                </div>
            </div>

            <!-- User Grid -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">User Directory</h2>
                </div>

                <!-- Loading State -->
                <div id="loadingState" class="hidden text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    <p class="mt-4 text-gray-600">Searching users...</p>
                </div>

                <!-- Users Grid -->
                <div id="usersGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <!-- Users will be loaded here -->
                </div>
            </div>

            <!-- Security Notice -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mt-8">
                <div class="flex items-center">
                    <div class="text-yellow-600 text-2xl mr-3">⚠️</div>
                    <div>
                        <h3 class="text-lg font-semibold text-yellow-800">Security Notice</h3>
                        <p class="text-yellow-700 text-sm mt-1">
                            All access attempts are logged and monitored. Use this portal responsibly and only for legitimate administrative purposes.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Use protocol-relative URLs to match the current page protocol
        const isLocalhost = ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname);
        const currentProtocol = window.location.protocol;
        
        const secretKey = '<?= $secret_key ?>';
        // Use protocol-relative URLs to avoid mixed content issues
        // If page is loaded via HTTPS, use HTTPS; otherwise use HTTP
        let baseUrl = '<?= base_url() ?>';
        
        // Match the current page protocol to avoid mixed content
        if (currentProtocol === 'https:') {
            baseUrl = baseUrl.replace('http://', 'https://');
        } else {
            baseUrl = baseUrl.replace('https://', 'http://');
        }
    </script>
    <script>
        // admin-search.js - Simple AJAX search for admin portal

        let searchTimeout;

        document.addEventListener('DOMContentLoaded', function() {
            // Load users when page loads
            loadUsers();

            // Search input with delay
            document.getElementById('searchInput').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    loadUsers();
                }, 500);
            });

            // Filter dropdowns
            document.getElementById('roleFilter').addEventListener('change', loadUsers);
            document.getElementById('statusFilter').addEventListener('change', loadUsers);
        });

        function loadUsers() {
            const searchTerm = document.getElementById('searchInput').value;
            const roleFilter = document.getElementById('roleFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;

            showLoading(true);

            const params = new URLSearchParams({
                search: searchTerm,
                role: roleFilter,
                status: statusFilter
            });

            fetch(`${baseUrl}index.php/secret/search-users/${secretKey}?${params}`)
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    if (data.success) {
                        displayUsers(data.users);
                        updateUserCount(data.users.length);
                    } else {
                        showError('Search failed');
                    }
                })
                .catch(error => {
                    showLoading(false);
                    showError('Connection error');
                });
        }

        function showLoading(show) {
            const loadingState = document.getElementById('loadingState');
            const usersGrid = document.getElementById('usersGrid');

            if (show) {
                loadingState.classList.remove('hidden');
                usersGrid.innerHTML = '';
            } else {
                loadingState.classList.add('hidden');
            }
        }

        function showError(message) {
            const usersGrid = document.getElementById('usersGrid');
            usersGrid.innerHTML = `
        <div class="col-span-full text-center py-8">
            <p class="text-red-600">${message}</p>
            <button onclick="loadUsers()" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded">Try Again</button>
        </div>
    `;
        }

        function displayUsers(users) {
            const usersGrid = document.getElementById('usersGrid');

            if (users.length === 0) {
                usersGrid.innerHTML = `
            <div class="col-span-full text-center py-8">
                <p class="text-gray-500">No users found</p>
            </div>
        `;
                return;
            }

            usersGrid.innerHTML = users.map(user => createUserCard(user)).join('');
        }

        function createUserCard(user) {
            // Ensure profile picture uses http
            let profilePicUrl = user.profile_picture || '';
            if (profilePicUrl) {
                profilePicUrl = profilePicUrl.replace(/^https:/i, 'http:');
            }
            
            const profilePic = profilePicUrl ?
                `<img src="${profilePicUrl}" class="w-12 h-12 rounded-full mr-3" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">` :
                '';
            
            const fallbackPic = `<div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center mr-3 text-white font-bold" ${profilePicUrl ? 'style="display:none;"' : ''}>
             ${(user.gf_name || 'U').charAt(0).toUpperCase()}
           </div>`;

            const badges = [];
            if (user.admin == 1) badges.push('<span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">Admin</span>');
            if (user.edoc == 1) badges.push('<span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">OAuth</span>');
            if (user.active == 1) badges.push('<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">Active</span>');

            return `
        <div class="user-card bg-gray-50 rounded-lg p-5 border">
            <div class="flex items-center mb-4">
                ${profilePic}${fallbackPic}
                <div>
                    <div class="font-semibold">${user.gf_name} ${user.gl_name}</div>
                    <div class="text-xs text-gray-500">ID: ${user.uid}</div>
                </div>
            </div>
            
            <div class="space-y-2 mb-4">
                <div class="text-sm text-gray-600">${user.email}</div>
                ${user.major ? `<div class="text-sm text-gray-600">${user.major}</div>` : ''}
                <div class="flex gap-1">${badges.join('')}</div>
            </div>
            
            <button onclick="loginAsUser(${user.uid}, '${user.email}')" 
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded text-sm">
                Login as User
            </button>
        </div>
    `;
        }

        function updateUserCount(count) {
            document.getElementById('userCount').textContent = count;
        }

        function loginAsUser(userId, email) {
            // Direct login without confirmation for faster access
            window.location.href = `${baseUrl}index.php/secret/direct-login/${secretKey}/${userId}`;
        }
    </script>
</body>

</html>
