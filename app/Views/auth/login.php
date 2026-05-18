<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'เข้าสู่ระบบ - ระบบจัดการผลงานวิจัย' ?></title>
    <link rel="stylesheet" href="<?= base_url('/public/assets/css/tailwind.min.css') ?>">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-shadow {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .btn-hover {
            transition: all 0.3s ease;
        }

        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Login Card -->
        <div class="bg-white rounded-2xl card-shadow overflow-hidden fade-in">
            <!-- Header -->
            <div class="gradient-bg text-white p-8 text-center">
                <div class="text-5xl mb-4">🎓</div>
                <h1 class="text-2xl font-bold mb-2">พอร์ทัลวิจัย</h1>
                <p class="text-blue-100">ระบบจัดการผลงานวิจัย</p>
            </div>

            <!-- Content -->
            <div class="p-8">
                <!-- Flash Messages -->
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <?= session()->getFlashdata('error') ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <?= session()->getFlashdata('success') ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Login Instructions -->
                <div class="text-center mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-3">เข้าสู่ระบบผ่าน URU Portal</h2>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        ใช้บัญชี URU Portal ของคุณเพื่อเข้าสู่ระบบจัดการผลงานวิจัย
                    </p>
                    <p class="text-gray-500 text-xs mt-2">
                        ระบบ Authentication โดย สำนักวิทยาบริการ มหาวิทยาลัยราชภัฏอุตรดิตถ์
                    </p>
                </div>

                <!-- OAuth Login Button -->
                <div class="space-y-4">
                    <a href="<?= $auth_url ?>"
                        class="btn-hover w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold py-4 px-6 rounded-xl flex items-center justify-center space-x-3 hover:from-blue-700 hover:to-indigo-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        <span>เข้าสู่ระบบด้วย URU Portal</span>
                    </a>
                </div>

                <!-- Info Section -->
                <div class="mt-8 pt-6 border-t border-gray-100">
                    <div class="text-center">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">สิ่งที่คุณสามารถทำได้:</h3>
                        <div class="grid grid-cols-2 gap-4 text-xs text-gray-600">
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                                    📚
                                </div>
                                <span>จัดการผลงานวิจัย</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center">
                                    👥
                                </div>
                                <span>ติดตามผู้เขียน</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center">
                                    📊
                                </div>
                                <span>ดูสถิติ</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 bg-yellow-100 rounded-full flex items-center justify-center">
                                    🔍
                                </div>
                                <span>ค้นหาและกรอง</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-500">
                        ต้องการความช่วยเหลือในการเข้าถึงบัญชีของคุณ?
                        <a href="#" class="text-blue-600 hover:text-blue-800 underline">ติดต่อฝ่ายสนับสนุน IT</a>
                    </p>
                </div>

                <!-- Security Notice -->
                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-600 leading-relaxed">
                                การเข้าสู่ระบบของคุณได้รับการรักษาความปลอดภัยผ่านระบบ URU Portal Authentication
                                ของสำนักวิทยาบริการ มหาวิทยาลัยราชภัฏอุตรดิตถ์
                                เราไม่เก็บรหัสผ่านของคุณและปฏิบัติตามโปรโตคอลความปลอดภัยของมหาวิทยาลัย
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-white text-sm opacity-75">
            <p>&copy; <?= date('Y') ?> ระบบจัดการผลงานวิจัย</p>
            <p class="text-xs mt-1">ออกแบบระบบโดย ผู้ช่วยศาสตรจารย์ พิศิษฐ์ นาคใจ ปี 2568</p>
            <p class="text-xs mt-1">ระบบ Authentication โดย สำนักวิทยาบริการ มหาวิทยาลัยราชภัฏอุตรดิตถ์</p>
        </div>
    </div>

    <script>
        // Auto-hide flash messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.bg-red-50, .bg-green-50');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);

        // Add loading state to login button
        document.querySelector('a[href*="oauth"]').addEventListener('click', function(e) {
            const button = e.currentTarget;
            const originalContent = button.innerHTML;

            button.innerHTML = `
                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>กำลังเปลี่ยนเส้นทางไปยัง URU Portal...</span>
            `;

            button.classList.add('opacity-75', 'cursor-not-allowed');
            button.onclick = function() {
                return false;
            };
        });

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const loginButton = document.querySelector('a[href*="oauth"]');
                if (loginButton) {
                    loginButton.click();
                }
            }
        });
    </script>
</body>

</html>