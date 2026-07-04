<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'ยืนยันชื่อและนามสกุล') ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/tailwind.min.css') ?>">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-shadow {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
    </style>
</head>

<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-2xl card-shadow overflow-hidden">
            <div class="gradient-bg text-white p-8 text-center">
                <div class="text-4xl mb-3">✍️</div>
                <h1 class="text-2xl font-bold mb-2">ยืนยันชื่อและนามสกุล</h1>
                <p class="text-blue-100 text-sm">จำเป็นสำหรับการใช้งานระบบ (ครั้งแรกที่เข้าใช้)</p>
            </div>

            <div class="p-8">
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                        <?= esc(session()->getFlashdata('error')) ?>
                    </div>
                <?php endif; ?>

                <p class="text-sm text-gray-600 mb-4">
                    ระบบ SSO ไม่ได้รับชื่อจาก Portal ครบถ้วน — กรุณาระบุ<strong>ชื่อและนามสกุลจริง</strong>
                    ตามเอกสารทางการ เพื่อใช้ในเอกสารและรายงานผลงานวิจัย
                </p>
                <ul class="text-sm text-gray-600 mb-6 list-disc pl-5 space-y-1">
                    <li>บุคลากรไทย — กรอกเป็นภาษาไทย</li>
                    <li>ชาวต่างชาติ — กรอกเป็นภาษาอังกฤษ (ตามหนังสือเดินทางหรือเอกสารทางการ)</li>
                </ul>

                <dl class="mb-6 rounded-lg bg-gray-50 border border-gray-200 px-4 py-3 text-sm">
                    <div class="flex justify-between gap-4 py-1">
                        <dt class="text-gray-500">อีเมล</dt>
                        <dd class="text-gray-900 font-medium text-right break-all"><?= esc($user['email'] ?? '') ?></dd>
                    </div>
                    <?php
                    $eng = trim(($user['gf_name'] ?? '') . ' ' . ($user['gl_name'] ?? ''));
                    if ($eng !== ''): ?>
                    <div class="flex justify-between gap-4 py-1 border-t border-gray-200 mt-1 pt-2">
                        <dt class="text-gray-500">ชื่อจากระบบ (อังกฤษ)</dt>
                        <dd class="text-gray-700 text-right"><?= esc($eng) ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>

                <form method="post" action="<?= site_url('auth/complete-thai-name') ?>" class="space-y-4">
                    <?= csrf_field() ?>
                    <div>
                        <label for="thai_name" class="block text-sm font-medium text-gray-700 mb-1">
                            ชื่อจริง <span class="text-red-600">*</span>
                        </label>
                        <input type="text" id="thai_name" name="thai_name" required maxlength="100"
                            value="<?= esc(old('thai_name', $user['thai_name'] ?? '')) ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="เช่น ทัศนีย์ หรือ Min" autocomplete="given-name">
                    </div>
                    <div>
                        <label for="thai_lastname" class="block text-sm font-medium text-gray-700 mb-1">
                            นามสกุล <span class="text-red-600">*</span>
                        </label>
                        <input type="text" id="thai_lastname" name="thai_lastname" required maxlength="100"
                            value="<?= esc(old('thai_lastname', $user['thai_lastname'] ?? '')) ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="เช่น อร่าม หรือ Xiao" autocomplete="family-name">
                    </div>
                    <button type="submit"
                        class="w-full gradient-bg text-white font-semibold py-3 px-4 rounded-lg hover:opacity-95 transition-opacity">
                        บันทึกและเข้าใช้งาน
                    </button>
                </form>

                <p class="mt-6 text-xs text-gray-500 text-center">
                    หากชื่อใน Portal ผิด สามารถแก้ไขได้ที่นี่ — Super Admin แก้ใน User Roles ได้เช่นกัน
                </p>
            </div>
        </div>
    </div>
</body>

</html>
