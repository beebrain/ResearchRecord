<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ดูแบบฟอร์มขอเปิดรับนักศึกษาใหม่ - <?= esc($form['curriculum_name_display'] ?? '') ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/sarabun.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-common.css') ?>">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }
    </style>
</head>

<body class="min-h-full bg-gray-50">
    <?php
    $pageTitle = 'ดูแบบฟอร์มเปิดรับนักศึกษา';
    $pageSubtitle = $form['curriculum_name_display'] ?? '';
    ?>
    <?= view('admin/partials/header', ['pageTitle' => $pageTitle, 'pageSubtitle' => $pageSubtitle]) ?>

    <div class="flex">
        <?= view('admin/partials/navigation') ?>

        <main class="flex-1 p-4 lg:p-6">
            <div class="bg-white rounded-lg shadow-sm p-4 mb-4 flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">แบบเสนอขอเปิดรับนักศึกษาใหม่ ประจำปีการศึกษา <?= esc($form['academic_year']) ?></h1>
                    <p class="text-gray-600">คณะ<?= esc($form['faculty_name'] ?? '-') ?> | หลักสูตร<?= esc($form['curriculum_name_display'] ?? '-') ?></p>
                </div>
                <div class="flex gap-2">
                    <a href="<?= site_url('admin/admission') ?>" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg">← กลับ</a>
                    <a href="<?= site_url('admin/admission/edit/' . $form['id']) ?>" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">✏️ แก้ไข</a>
                    <a href="<?= site_url('admin/admission/print/' . $form['id']) ?>" target="_blank" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">🖨️ พิมพ์</a>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Section 1 -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๑. ข้อมูลหลักสูตร</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div><span class="text-gray-500">ชื่อหลักสูตร:</span> <strong><?= esc($form['curriculum_name'] ?? '-') ?></strong></div>
                        <div><span class="text-gray-500">ฉบับปี พ.ศ.:</span> <strong><?= esc($form['curriculum_version_year'] ?? '-') ?></strong></div>
                    </div>
                </div>

                <!-- Section 2 -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๒. การพิจารณาความสอดคล้อง</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div><span class="text-gray-500">วันที่ได้รับการพิจารณาจาก สป.อว.:</span> <strong><?= $form['ministry_approval_date'] ? date('d/m/Y', strtotime($form['ministry_approval_date'])) : '-' ?></strong></div>
                        <div><span class="text-gray-500">สภามหาวิทยาลัยเห็นชอบ:</span> <strong><?= $form['university_approval_date'] ? date('d/m/Y', strtotime($form['university_approval_date'])) : '-' ?></strong></div>
                    </div>
                </div>

                <!-- Section 3 -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๓. ผลการประเมินคุณภาพ ๒ ปีย้อนหลัง</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>ปีการศึกษา <?= esc($form['quality_assessment_year1'] ?? '-') ?>: <strong><?= esc($form['quality_assessment_result1'] ?? '-') ?></strong></div>
                        <div>ปีการศึกษา <?= esc($form['quality_assessment_year2'] ?? '-') ?>: <strong><?= esc($form['quality_assessment_result2'] ?? '-') ?></strong></div>
                    </div>
                </div>

                <!-- Section 4: Teachers -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๔. อาจารย์ผู้รับผิดชอบหลักสูตร</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-center border">ที่</th>
                                    <th class="px-3 py-2 text-center border">ตำแหน่ง</th>
                                    <th class="px-3 py-2 border">ชื่อ-นามสกุล</th>
                                    <?php for ($y = 5; $y >= 1; $y--): ?>
                                        <th class="px-2 py-2 text-center border"><?= ($form['academic_year'] ?? 2568) - $y ?></th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($form['teachers'] ?? []) as $i => $teacher): ?>
                                    <tr>
                                        <td class="px-3 py-2 text-center border"><?= $i + 1 ?></td>
                                        <td class="px-3 py-2 text-center border"><?= esc($teacher['position'] ?? '-') ?></td>
                                        <td class="px-3 py-2 border"><?= esc($teacher['full_name'] ?? '-') ?></td>
                                        <?php for ($y = 5; $y >= 1; $y--): ?>
                                            <td class="px-2 py-2 text-center border"><?= ($teacher["pub_year_{$y}"] ?? 0) ? '✓' : '' ?></td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 text-sm text-gray-600">
                        <p><strong>การคงอยู่:</strong> <?= ($form['teachers_status'] ?? '') === 'complete' ? 'ครบ' : 'ไม่ครบ' ?></p>
                        <?php if (($form['teachers_status'] ?? '') === 'incomplete'):
                            $incompleteTeachers = [];
                            if (!empty($form['teachers_incomplete_teachers'])) {
                                $incompleteTeachers = json_decode($form['teachers_incomplete_teachers'], true) ?: [];
                            } else if (!empty($form['teachers_incomplete_order'])) {
                                $teacher = ($form['teachers'] ?? [])[($form['teachers_incomplete_order'] ?? 1) - 1] ?? null;
                                if ($teacher) {
                                    $incompleteTeachers = [[
                                        'name' => $teacher['full_name'] ?? ($teacher['thai_name'] ?? '') . ' ' . ($teacher['thai_lastname'] ?? '')
                                    ]];
                                }
                            }
                            $teacherNames = array_column($incompleteTeachers, 'name');
                        ?>
                            <p><strong>อาจารย์ที่ไม่ครบ:</strong> <?= esc(implode(', ', $teacherNames) ?: '-') ?></p>
                            <p><strong>เนื่องจาก:</strong> <?= esc($form['teachers_incomplete_reason'] ?? '-') ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Section 5: Publications -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๕. ผลงานทางวิชาการ</h2>
                    <?php if (!empty($form['publications'])): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-center border">ลำดับ</th>
                                        <th class="px-3 py-2 border">ชื่อ-สกุล</th>
                                        <th class="px-3 py-2 text-center border">ประเภท</th>
                                        <th class="px-3 py-2 border">ชื่อผลงาน</th>
                                        <th class="px-3 py-2 text-center border">ปี พ.ศ.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($form['publications'] as $index => $pub): ?>
                                        <tr>
                                            <td class="px-3 py-2 text-center border"><?= $index + 1 ?></td>
                                            <td class="px-3 py-2 border"><?= esc($pub['author_name_th'] ?? $pub['author_name'] ?? '-') ?></td>
                                            <td class="px-3 py-2 text-center border"><?= esc($pub['publication_type'] ?? '-') ?></td>
                                            <td class="px-3 py-2 border text-sm"><?= esc($pub['title'] ?? '-') ?></td>
                                            <td class="px-3 py-2 text-center border"><?= ($pub['publication_year'] ?? 0) + 543 ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-4">ไม่มีข้อมูลผลงาน</p>
                    <?php endif; ?>
                </div>

                <!-- Section 6-7: Admission Info -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๖-๗. ข้อมูลการรับนักศึกษา</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div><span class="text-gray-500">แผนรับ:</span> <strong><?= esc($form['admission_plan_count'] ?? '-') ?> คน</strong></div>
                        <div><span class="text-gray-500">กลุ่มมัธยม:</span> <strong><?= esc($form['target_highschool_count'] ?? '-') ?> คน</strong></div>
                        <div><span class="text-gray-500">กลุ่ม ปวส.:</span> <strong><?= esc($form['target_diploma_count'] ?? '-') ?> คน</strong></div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-500">คุณสมบัติ (มัธยม):</p>
                            <div class="bg-gray-50 p-2 rounded mt-1 text-sm whitespace-pre-line"><?= esc($form['qualification_highschool'] ?? '-') ?></div>
                        </div>
                        <div>
                            <p class="text-gray-500">คุณสมบัติ (ปวส.):</p>
                            <div class="bg-gray-50 p-2 rounded mt-1 text-sm whitespace-pre-line"><?= esc($form['qualification_diploma'] ?? '-') ?></div>
                        </div>
                    </div>
                </div>

                <!-- Section 9: Plan -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๙. แผนพัฒนานักศึกษาค้างชั้น</h2>
                    <div class="space-y-4">
                        <div>
                            <p class="text-gray-500">วิธีการดำเนินการ:</p>
                            <div class="bg-gray-50 p-3 rounded mt-1 whitespace-pre-line"><?= esc($form['remaining_student_plan'] ?? '-') ?></div>
                        </div>
                        <div>
                            <p class="text-gray-500">ตัวชี้วัดความสำเร็จ:</p>
                            <div class="bg-gray-50 p-3 rounded mt-1 whitespace-pre-line"><?= esc($form['remaining_student_kpi'] ?? '-') ?></div>
                        </div>
                    </div>
                </div>

                <!-- Approvals -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">ความเห็นชอบ</h2>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="p-4 bg-green-50 rounded-lg">
                            <p class="font-medium text-green-700">ความเห็นชอบของประธานหลักสูตร</p>
                            <p class="text-sm text-gray-600 mt-2">นำเสนอและผ่านความเห็นชอบของคณะกรรมการบริหารหลักสูตรแล้วเมื่อ</p>
                            <p class="text-sm text-gray-500"><?= $form['curriculum_head_approval_date'] ? date('d/m/Y', strtotime($form['curriculum_head_approval_date'])) : '-' ?></p>
                            <p class="mt-3 font-medium">ชื่อ: <?= esc($form['curriculum_head_name'] ?? '-') ?></p>
                        </div>
                        <div class="p-4 bg-orange-50 rounded-lg">
                            <p class="font-medium text-orange-700">ความเห็นชอบของคณบดี</p>
                            <p class="mt-2 font-medium"><?= esc($form['dean_name'] ?? '-') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>