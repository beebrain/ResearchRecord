<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขแบบฟอร์มขอเปิดรับนักศึกษาใหม่ - <?= esc($form['curriculum_name_display'] ?? '') ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/sarabun.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-common.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }

        .section-title {
            @apply text-lg font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-4;
        }

        .form-label {
            @apply block text-sm font-medium text-gray-700 mb-1;
        }

        .form-input {
            @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
        }

        .form-textarea {
            @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 resize-y;
        }
    </style>
</head>

<body class="min-h-full bg-gray-50">
    <?php
    $pageTitle = 'แก้ไขแบบฟอร์มเปิดรับนักศึกษา';
    $pageSubtitle = $form['curriculum_name_display'] ?? '';
    ?>
    <?= view('admin/partials/header', ['pageTitle' => $pageTitle, 'pageSubtitle' => $pageSubtitle]) ?>

    <div class="flex">
        <?= view('admin/partials/navigation') ?>

        <main class="flex-1 p-4 lg:p-6">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-sm p-4 mb-4 flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">แบบเสนอขอเปิดรับนักศึกษาใหม่ ประจำปีการศึกษา <?= esc($form['academic_year']) ?></h1>
                    <p class="text-gray-600">คณะ<?= esc($form['faculty_name'] ?? '-') ?> | หลักสูตร<?= esc($form['curriculum_name_display'] ?? '-') ?></p>
                </div>
                <div class="flex gap-2">
                    <a href="<?= site_url('admin/admission') ?>" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg">← กลับ</a>
                    <button type="button" onclick="saveForm()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">💾 บันทึก</button>
                    <button type="button" onclick="saveAndSubmit()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">📤 บันทึกและส่ง</button>
                </div>
            </div>

            <form id="admissionForm" class="space-y-6">
                <!-- Section 1: Curriculum Info -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๑. ข้อมูลหลักสูตร</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">ชื่อหลักสูตรสาขาวิชา</label>
                            <input type="text" name="curriculum_name" value="<?= esc($form['curriculum_name'] ?? $form['curriculum_name_display'] ?? '') ?>" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">ฉบับปี พ.ศ.</label>
                            <input type="number" name="curriculum_version_year" value="<?= esc($form['curriculum_version_year'] ?? '') ?>" class="form-input" placeholder="เช่น 2565">
                        </div>
                    </div>
                </div>

                <!-- Section 2: Ministry Approval -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๒. หลักสูตรได้รับการพิจารณาความสอดคล้องจาก สป.อว.</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">วันที่ได้รับการพิจารณาความสอดคล้องจาก สป.อว.</label>
                            <input type="date" name="ministry_approval_date" value="<?= esc($form['ministry_approval_date'] ?? '') ?>" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">สภามหาวิทยาลัยเห็นชอบเมื่อวันที่ (กรณีปรับปรุง)</label>
                            <input type="date" name="university_approval_date" value="<?= esc($form['university_approval_date'] ?? '') ?>" class="form-input">
                        </div>
                    </div>
                </div>

                <!-- Section 3: Quality Assessment -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๓. ผลการประเมินคุณภาพการศึกษาระดับหลักสูตร ๒ ปีย้อนหลัง</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="form-label">ปีการศึกษา (ปีแรก)</label>
                                    <input type="number" name="quality_assessment_year1" value="<?= esc($form['quality_assessment_year1'] ?? '') ?>" class="form-input">
                                </div>
                                <div>
                                    <label class="form-label">ผลการประเมินอยู่ในเกณฑ์</label>
                                    <input type="text" name="quality_assessment_result1" value="<?= esc($form['quality_assessment_result1'] ?? '') ?>" class="form-input" placeholder="ดี, ดีมาก, ...">
                                </div>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="form-label">ปีการศึกษา (ปีที่สอง)</label>
                                    <input type="number" name="quality_assessment_year2" value="<?= esc($form['quality_assessment_year2'] ?? '') ?>" class="form-input">
                                </div>
                                <div>
                                    <label class="form-label">ผลการประเมินอยู่ในเกณฑ์</label>
                                    <input type="text" name="quality_assessment_result2" value="<?= esc($form['quality_assessment_result2'] ?? '') ?>" class="form-input">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Responsible Teachers -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๔. ข้อมูลอาจารย์ผู้รับผิดชอบหลักสูตร</h2>

                    <!-- Teachers Table -->
                    <div class="overflow-x-auto mb-4">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 border">ที่</th>
                                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 border">ตำแหน่ง</th>
                                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 border">ชื่อ-นามสกุล</th>
                                    <?php for ($y = 5; $y >= 1; $y--): ?>
                                        <th class="px-2 py-2 text-center text-xs font-semibold text-gray-600 border"><?= ($form['academic_year'] ?? 2568) - $y ?></th>
                                    <?php endfor; ?>
                                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 border">ปี พ.ศ.<br>ที่รับ นศ.</th>
                                </tr>
                            </thead>
                            <tbody id="teachersTable">
                                <?php
                                $teachers = $form['teachers'] ?? [];
                                for ($i = 1; $i <= 5; $i++):
                                    $teacher = $teachers[$i - 1] ?? [];
                                ?>
                                    <tr>
                                        <td class="px-3 py-2 text-center border"><?= $i ?></td>
                                        <td class="px-2 py-1 border"><input type="text" name="teachers[<?= $i ?>][position]" value="<?= esc($teacher['position'] ?? '') ?>" class="w-full px-2 py-1 border rounded text-sm"></td>
                                        <td class="px-2 py-1 border"><input type="text" name="teachers[<?= $i ?>][full_name]" value="<?= esc($teacher['full_name'] ?? '') ?>" class="w-full px-2 py-1 border rounded text-sm"></td>
                                        <?php for ($y = 5; $y >= 1; $y--): ?>
                                            <td class="px-2 py-1 text-center border">
                                                <input type="checkbox" name="teachers[<?= $i ?>][pub_year_<?= $y ?>]" value="1" <?= ($teacher["pub_year_{$y}"] ?? 0) ? 'checked' : '' ?> class="w-4 h-4">
                                            </td>
                                        <?php endfor; ?>
                                        <td class="px-2 py-1 border"><input type="number" name="teachers[<?= $i ?>][admission_year]" value="<?= esc($teacher['admission_year'] ?? '') ?>" class="w-20 px-2 py-1 border rounded text-sm"></td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 4.1 Teachers Status -->
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <h3 class="font-medium text-gray-700 mb-2">๔.๑) การคงอยู่ของอาจารย์ในหลักสูตร</h3>
                        <div class="flex gap-6 mb-3">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="teachers_status" value="complete" <?= ($form['teachers_status'] ?? '') === 'complete' ? 'checked' : '' ?> onchange="toggleIncompleteFields()"> ครบ
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="teachers_status" value="incomplete" <?= ($form['teachers_status'] ?? '') === 'incomplete' ? 'checked' : '' ?> onchange="toggleIncompleteFields()"> ไม่ครบ
                            </label>
                        </div>
                        <div id="incomplete-fields" style="display: <?= ($form['teachers_status'] ?? '') === 'incomplete' ? 'block' : 'none' ?>;">
                            <div class="mb-3">
                                <label class="form-label">เลือกอาจารย์ที่ไม่ครบ (สามารถเลือกได้หลายคน)</label>
                                <div class="border rounded-lg p-3 max-h-48 overflow-y-auto bg-white">
                                    <?php
                                    // Parse incomplete teachers from JSON or old field
                                    $incompleteTeachers = [];
                                    if (!empty($form['teachers_incomplete_teachers'])) {
                                        $incompleteTeachers = json_decode($form['teachers_incomplete_teachers'], true) ?: [];
                                    } else if (!empty($form['teachers_incomplete_order'])) {
                                        // Fallback to old field
                                        $teacher = ($form['teachers'] ?? [])[($form['teachers_incomplete_order'] ?? 1) - 1] ?? null;
                                        if ($teacher) {
                                            $incompleteTeachers = [[
                                                'user_id' => $teacher['user_id'] ?? $teacher['id'] ?? null,
                                                'name' => $teacher['full_name'] ?? ($teacher['thai_name'] ?? '') . ' ' . ($teacher['thai_lastname'] ?? '')
                                            ]];
                                        }
                                    }
                                    $selectedIds = array_column($incompleteTeachers, 'user_id');
                                    foreach (($form['teachers'] ?? []) as $teacher):
                                        $teacherId = $teacher['user_id'] ?? $teacher['id'] ?? null;
                                        $isSelected = in_array($teacherId, $selectedIds);
                                        // full_name already includes titleThai, so use it directly
                                        $teacherName = $teacher['full_name'] ?? (($teacher['titleThai'] ?? '') . ' ' . ($teacher['thai_name'] ?? '') . ' ' . ($teacher['thai_lastname'] ?? ''));
                                    ?>
                                        <label class="flex items-center gap-2 py-1 hover:bg-gray-50 cursor-pointer">
                                            <input type="checkbox" name="teachers_incomplete_teachers[]" value="<?= $teacherId ?>" <?= $isSelected ? 'checked' : '' ?> class="w-4 h-4">
                                            <span class="text-sm"><?= esc($teacherName) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">เนื่องจาก</label>
                                <input type="text" name="teachers_incomplete_reason" value="<?= esc($form['teachers_incomplete_reason'] ?? '') ?>" class="form-input">
                            </div>
                        </div>
                    </div>

                    <!-- 4.2 Retiring Teachers -->
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="font-medium text-gray-700">๔.๒) อาจารย์ที่เกษียณอายุราชการ</h3>
                            <button type="button" onclick="addRetiringTeacher()" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">+ เพิ่มรายการ</button>
                        </div>
                        <div id="retiring-teachers-list" class="space-y-3">
                            <?php
                            // Parse retiring teachers from JSON or old fields
                            $retiringTeachers = [];
                            if (!empty($form['retiring_teachers'])) {
                                $retiringTeachers = json_decode($form['retiring_teachers'], true) ?: [];
                            } else {
                                // Fallback to old fields
                                for ($i = 1; $i <= 3; $i++) {
                                    $year = $form["retiring_year{$i}"] ?? null;
                                    $count = $form["retiring_count{$i}"] ?? null;
                                    if ($year || $count) {
                                        $retiringTeachers[] = ['year' => $year, 'count' => $count];
                                    }
                                }
                            }

                            if (empty($retiringTeachers)):
                            ?>
                                <p class="text-gray-500 py-2">ไม่มี</p>
                            <?php else: ?>
                                <?php foreach ($retiringTeachers as $index => $item): ?>
                                    <div class="flex items-center gap-2 retiring-teacher-item">
                                        <span>ในปี พ.ศ.</span>
                                        <input type="number" name="retiring_teachers[<?= $index ?>][year]" value="<?= esc($item['year'] ?? '') ?>" class="w-24 px-2 py-1 border rounded" placeholder="เช่น 2565">
                                        <span>จำนวน</span>
                                        <input type="number" name="retiring_teachers[<?= $index ?>][count]" value="<?= esc($item['count'] ?? '') ?>" class="w-16 px-2 py-1 border rounded" min="0" placeholder="0">
                                        <span>คน</span>
                                        <button type="button" onclick="removeRetiringTeacher(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 4.3 Teachers Pursuing Further Studies -->
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="font-medium text-gray-700">๔.๓) อาจารย์ศึกษาต่อ</h3>
                            <button type="button" onclick="addStudyingTeacher()" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">+ เพิ่มรายการ</button>
                        </div>
                        <div id="studying-teachers-list" class="space-y-3">
                            <?php
                            // Parse studying teachers from JSON or old fields
                            $studyingTeachers = [];
                            if (!empty($form['studying_teachers'])) {
                                $studyingTeachers = json_decode($form['studying_teachers'], true) ?: [];
                            } else {
                                // Fallback to old fields
                                for ($i = 1; $i <= 3; $i++) {
                                    $year = $form["studying_year{$i}"] ?? null;
                                    $count = $form["studying_count{$i}"] ?? null;
                                    if ($year || $count) {
                                        $studyingTeachers[] = ['year' => $year, 'count' => $count];
                                    }
                                }
                            }

                            if (empty($studyingTeachers)):
                            ?>
                                <p class="text-gray-500 py-2">ไม่มี</p>
                            <?php else: ?>
                                <?php foreach ($studyingTeachers as $index => $item): ?>
                                    <div class="flex items-center gap-2 studying-teacher-item">
                                        <span>ในปี พ.ศ.</span>
                                        <input type="number" name="studying_teachers[<?= $index ?>][year]" value="<?= esc($item['year'] ?? '') ?>" class="w-24 px-2 py-1 border rounded" placeholder="เช่น 2565">
                                        <span>จำนวน</span>
                                        <input type="number" name="studying_teachers[<?= $index ?>][count]" value="<?= esc($item['count'] ?? '') ?>" class="w-16 px-2 py-1 border rounded" min="0" placeholder="0">
                                        <span>คน</span>
                                        <button type="button" onclick="removeStudyingTeacher(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Teacher Publications (from publications table) -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๕. ผลงานทางวิชาการของอาจารย์ผู้รับผิดชอบหลักสูตร</h2>
                    <p class="text-sm text-gray-500 mb-4">* ข้อมูลดึงอัตโนมัติจากระบบผลงานวิชาการ ตามรายชื่ออาจารย์ในข้อ ๔</p>

                    <?php if (!empty($form['publications'])): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 border">ลำดับ</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 border">ชื่อ-สกุล</th>
                                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 border">ประเภท</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 border">ชื่อผลงาน</th>
                                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 border">ปี พ.ศ.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($form['publications'] as $index => $pub): ?>
                                        <tr class="hover:bg-gray-50">
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
                        <div class="text-center py-8 text-gray-500">
                            <p>ยังไม่มีข้อมูลผลงาน กรุณาเพิ่มอาจารย์ในข้อ ๔ และบันทึก</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Section 6: Admission Target Groups -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๖. ข้อมูลกลุ่มผู้เรียน จำนวนรับ และคุณสมบัติของนักศึกษา</h2>

                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="has_major_minor" value="0" <?= !($form['has_major_minor'] ?? 0) ? 'checked' : '' ?> onchange="toggleMajorSection()"> หลักสูตรไม่มีวิชาเอก/แขนง
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="has_major_minor" value="1" <?= ($form['has_major_minor'] ?? 0) ? 'checked' : '' ?> onchange="toggleMajorSection()"> หลักสูตรมีวิชาเอก/แขนง
                            </label>
                        </div>

                        <!-- Major Details Section (shown when has_major_minor = 1) -->
                        <div id="major-section" class="p-4 bg-gray-50 rounded-lg" style="display: <?= ($form['has_major_minor'] ?? 0) ? 'block' : 'none' ?>">
                            <div class="flex justify-between items-center mb-3">
                                <label class="font-medium text-gray-700">รายละเอียดวิชาเอกแต่ละตัว</label>
                                <button type="button" onclick="addMajor()" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">+ เพิ่มวิชาเอก</button>
                            </div>
                            <div id="majors-list" class="space-y-2">
                                <!-- Majors will be dynamically added here -->
                            </div>
                            <input type="hidden" name="major_count" id="major_count" value="<?= esc($form['major_count'] ?? '') ?>">
                        </div>

                        <div class="flex items-center gap-2">
                            <span>แผนรับนักศึกษาตามรายละเอียดหลักสูตร จำนวน</span>
                            <input type="number" name="admission_plan_count" value="<?= esc($form['admission_plan_count'] ?? '') ?>" class="w-20 px-2 py-1 border rounded">
                            <span>คน</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div class="p-4 bg-blue-50 rounded-lg">
                                <label class="flex items-center gap-2 font-medium text-blue-700 mb-2">
                                    <input type="checkbox" name="target_highschool" value="1" <?= ($form['target_highschool'] ?? 0) ? 'checked' : '' ?>>
                                    มัธยมศึกษาตอนปลายหรือเทียบเท่า
                                </label>
                                <div class="flex items-center gap-2">
                                    <span>จำนวน</span>
                                    <input type="number" name="target_highschool_count" value="<?= esc($form['target_highschool_count'] ?? '') ?>" class="w-20 px-2 py-1 border rounded">
                                    <span>คน</span>
                                </div>
                            </div>
                            <div class="p-4 bg-purple-50 rounded-lg">
                                <label class="flex items-center gap-2 font-medium text-purple-700 mb-2">
                                    <input type="checkbox" name="target_diploma" value="1" <?= ($form['target_diploma'] ?? 0) ? 'checked' : '' ?>>
                                    ปวส./อนุปริญญา
                                </label>
                                <div class="flex items-center gap-2">
                                    <span>จำนวน</span>
                                    <input type="number" name="target_diploma_count" value="<?= esc($form['target_diploma_count'] ?? '') ?>" class="w-20 px-2 py-1 border rounded">
                                    <span>คน</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 7: Qualifications -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๗. คุณสมบัติของผู้เรียน</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="form-label">คุณสมบัติผู้เรียน มัธยมศึกษาตอนปลายหรือเทียบเท่า</label>
                            <textarea name="qualification_highschool" rows="5" class="form-textarea" placeholder="ระบุคุณสมบัติ แต่ละข้อขึ้นบรรทัดใหม่"><?= esc($form['qualification_highschool'] ?? '') ?></textarea>
                        </div>
                        <div>
                            <label class="form-label">คุณสมบัติผู้เรียน ปวส./อนุปริญญา</label>
                            <textarea name="qualification_diploma" rows="5" class="form-textarea" placeholder="ระบุคุณสมบัติ แต่ละข้อขึ้นบรรทัดใหม่"><?= esc($form['qualification_diploma'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 8: Current Students -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๘. ข้อมูลจำนวนนักศึกษาปัจจุบัน</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- High School Track -->
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <h3 class="font-medium text-blue-700 mb-3">มัธยมศึกษาตอนปลายหรือเทียบเท่า</h3>
                            <div class="space-y-2">
                                <?php for ($y = 1; $y <= 4; $y++): ?>
                                    <div class="flex items-center gap-2">
                                        <span class="w-24">ชั้นปีที่ <?= $y ?></span>
                                        <input type="number" name="current_highschool_year<?= $y ?>" value="<?= esc($form["current_highschool_year{$y}"] ?? '') ?>" class="w-20 px-2 py-1 border rounded">
                                        <span>คน</span>
                                    </div>
                                <?php endfor; ?>
                                <div class="flex items-center gap-2">
                                    <span class="w-24">สำเร็จการศึกษา</span>
                                    <input type="number" name="current_highschool_graduated" value="<?= esc($form['current_highschool_graduated'] ?? '') ?>" class="w-20 px-2 py-1 border rounded">
                                    <span>คน</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-24">นักศึกษาค้างชั้น</span>
                                    <input type="number" name="current_highschool_remain" value="<?= esc($form['current_highschool_remain'] ?? '') ?>" class="w-20 px-2 py-1 border rounded">
                                    <span>คน</span>
                                </div>
                            </div>
                        </div>
                        <!-- Diploma Track -->
                        <div class="p-4 bg-purple-50 rounded-lg">
                            <h3 class="font-medium text-purple-700 mb-3">ปวส./อนุปริญญา</h3>
                            <div class="space-y-2">
                                <?php for ($y = 1; $y <= 4; $y++): ?>
                                    <div class="flex items-center gap-2">
                                        <span class="w-24">ชั้นปีที่ <?= $y ?></span>
                                        <input type="number" name="current_diploma_year<?= $y ?>" value="<?= esc($form["current_diploma_year{$y}"] ?? '') ?>" class="w-20 px-2 py-1 border rounded">
                                        <span>คน</span>
                                    </div>
                                <?php endfor; ?>
                                <div class="flex items-center gap-2">
                                    <span class="w-24">สำเร็จการศึกษา</span>
                                    <input type="number" name="current_diploma_graduated" value="<?= esc($form['current_diploma_graduated'] ?? '') ?>" class="w-20 px-2 py-1 border rounded">
                                    <span>คน</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-24">นักศึกษาค้างชั้น</span>
                                    <input type="number" name="current_diploma_remain" value="<?= esc($form['current_diploma_remain'] ?? '') ?>" class="w-20 px-2 py-1 border rounded">
                                    <span>คน</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 9: Remaining Student Development Plan -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๙. แผนพัฒนานักศึกษาค้างชั้น</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="form-label">วิธีดำเนินการ</label>
                            <textarea name="remaining_student_plan" rows="4" class="form-textarea"><?= esc($form['remaining_student_plan'] ?? '') ?></textarea>
                        </div>
                        <div>
                            <label class="form-label">ตัวชี้วัดความสำเร็จ</label>
                            <textarea name="remaining_student_kpi" rows="4" class="form-textarea"><?= esc($form['remaining_student_kpi'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Approvals Section -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">ความเห็นชอบ</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-4 bg-green-50 rounded-lg">
                            <h3 class="font-medium text-green-700 mb-3">ความเห็นชอบของประธานหลักสูตร</h3>
                            <div class="space-y-2">
                                <div>
                                    <label class="form-label">นำเสนอและผ่านความเห็นชอบของคณะกรรมการบริหารหลักสูตรแล้วเมื่อ</label>
                                    <input type="date" name="curriculum_head_approval_date" value="<?= esc($form['curriculum_head_approval_date'] ?? '') ?>" class="form-input">
                                </div>
                                <div>
                                    <label class="form-label">ชื่อประธานหลักสูตร</label>
                                    <input type="text" name="curriculum_head_name" value="<?= esc($form['curriculum_head_name'] ?? '') ?>" class="form-input">
                                </div>
                            </div>
                        </div>
                        <div class="p-4 bg-orange-50 rounded-lg">
                            <h3 class="font-medium text-orange-700 mb-3">ความเห็นชอบของคณบดี</h3>
                            <div>
                                <label class="form-label">ชื่อคณบดี</label>
                                <input type="text" name="dean_name" value="<?= esc($form['dean_name'] ?? '') ?>" class="form-input">
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="status" id="formStatus" value="<?= esc($form['status'] ?? 'draft') ?>">
            </form>

            <!-- Footer Actions -->
            <div class="bg-white rounded-lg shadow-sm p-4 mt-4 flex justify-end gap-2">
                <a href="<?= site_url('admin/admission') ?>" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg">← กลับ</a>
                <button type="button" onclick="saveForm()" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">💾 บันทึก</button>
                <button type="button" onclick="saveAndSubmit()" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium">📤 บันทึกและส่ง</button>
            </div>
        </main>
    </div>

    <script>
        const BASE_URL = '<?= rtrim(base_url(), '/') ?>';
    </script>
    <script src="<?= base_url('assets/js/app-routes.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/app-routes.js') ?: time() ?>"></script>
    <script>
        const FORM_ID = <?= $form['id'] ?>;

        // Toggle incomplete fields visibility
        function toggleIncompleteFields() {
            const incompleteFields = document.getElementById('incomplete-fields');
            const isIncomplete = document.querySelector('input[name="teachers_status"]:checked')?.value === 'incomplete';
            if (incompleteFields) {
                incompleteFields.style.display = isIncomplete ? 'block' : 'none';
            }
        }

        // Add/Remove functions for retiring teachers
        function addRetiringTeacher() {
            const list = document.getElementById('retiring-teachers-list');

            // Remove "ไม่มี" text if exists
            const noDataText = list.querySelector('p');
            if (noDataText) {
                noDataText.remove();
            }

            const index = list.querySelectorAll('.retiring-teacher-item').length;
            const item = document.createElement('div');
            item.className = 'flex items-center gap-2 retiring-teacher-item';
            item.innerHTML = `
                <span>ในปี พ.ศ.</span>
                <input type="number" name="retiring_teachers[${index}][year]" class="w-24 px-2 py-1 border rounded" placeholder="เช่น 2565">
                <span>จำนวน</span>
                <input type="number" name="retiring_teachers[${index}][count]" class="w-16 px-2 py-1 border rounded" min="0" placeholder="0">
                <span>คน</span>
                <button type="button" onclick="removeRetiringTeacher(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>
            `;
            list.appendChild(item);
        }

        function removeRetiringTeacher(button) {
            const list = document.getElementById('retiring-teachers-list');
            const items = list.querySelectorAll('.retiring-teacher-item');

            if (items.length > 1) {
                button.closest('.retiring-teacher-item').remove();
            } else {
                // Remove the last item and show "ไม่มี"
                button.closest('.retiring-teacher-item').remove();
                const noData = document.createElement('p');
                noData.className = 'text-gray-500 py-2';
                noData.textContent = 'ไม่มี';
                list.appendChild(noData);
            }
        }

        // Add/Remove functions for studying teachers
        function addStudyingTeacher() {
            const list = document.getElementById('studying-teachers-list');

            // Remove "ไม่มี" text if exists
            const noDataText = list.querySelector('p');
            if (noDataText) {
                noDataText.remove();
            }

            const index = list.querySelectorAll('.studying-teacher-item').length;
            const item = document.createElement('div');
            item.className = 'flex items-center gap-2 studying-teacher-item';
            item.innerHTML = `
                <span>ในปี พ.ศ.</span>
                <input type="number" name="studying_teachers[${index}][year]" class="w-24 px-2 py-1 border rounded" placeholder="เช่น 2565">
                <span>จำนวน</span>
                <input type="number" name="studying_teachers[${index}][count]" class="w-16 px-2 py-1 border rounded" min="0" placeholder="0">
                <span>คน</span>
                <button type="button" onclick="removeStudyingTeacher(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>
            `;
            list.appendChild(item);
        }

        function removeStudyingTeacher(button) {
            const list = document.getElementById('studying-teachers-list');
            const items = list.querySelectorAll('.studying-teacher-item');

            if (items.length > 1) {
                button.closest('.studying-teacher-item').remove();
            } else {
                // Remove the last item and show "ไม่มี"
                button.closest('.studying-teacher-item').remove();
                const noData = document.createElement('p');
                noData.className = 'text-gray-500 py-2';
                noData.textContent = 'ไม่มี';
                list.appendChild(noData);
            }
        }

        async function saveForm(status = null) {
            const formData = new FormData(document.getElementById('admissionForm'));
            const data = Object.fromEntries(formData.entries());

            // Handle teachers data
            const teachers = [];
            for (let i = 1; i <= 5; i++) {
                teachers.push({
                    order_num: i,
                    position: data[`teachers[${i}][position]`] || '',
                    full_name: data[`teachers[${i}][full_name]`] || '',
                    pub_year_1: data[`teachers[${i}][pub_year_1]`] ? 1 : 0,
                    pub_year_2: data[`teachers[${i}][pub_year_2]`] ? 1 : 0,
                    pub_year_3: data[`teachers[${i}][pub_year_3]`] ? 1 : 0,
                    pub_year_4: data[`teachers[${i}][pub_year_4]`] ? 1 : 0,
                    pub_year_5: data[`teachers[${i}][pub_year_5]`] ? 1 : 0,
                    admission_year: data[`teachers[${i}][admission_year]`] || null
                });
            }
            data.teachers = teachers;

            // Handle incomplete teachers - convert checkbox array to JSON
            const incompleteTeacherIds = Array.from(document.querySelectorAll('input[name="teachers_incomplete_teachers[]"]:checked')).map(cb => cb.value);
            if (incompleteTeacherIds.length > 0) {
                const teachers = <?= json_encode($form['teachers'] ?? []) ?>;
                const incompleteTeachers = incompleteTeacherIds.map(userId => {
                    const teacher = teachers.find(t => (t.user_id || t.id) == userId);
                    if (teacher) {
                        return {
                            user_id: teacher.user_id || teacher.id,
                            name: teacher.full_name || (teacher.thai_name + ' ' + teacher.thai_lastname)
                        };
                    }
                    return {
                        user_id: userId,
                        name: ''
                    };
                });
                data.teachers_incomplete_teachers = JSON.stringify(incompleteTeachers);
            } else {
                data.teachers_incomplete_teachers = null;
            }

            // Handle retiring teachers - convert array to JSON
            const retiringTeachers = [];
            const retiringKeys = Object.keys(data).filter(k => k.startsWith('retiring_teachers['));
            retiringKeys.forEach(key => {
                const match = key.match(/retiring_teachers\[(\d+)\]\[(year|count)\]/);
                if (match) {
                    const index = parseInt(match[1]);
                    const field = match[2];
                    if (!retiringTeachers[index]) {
                        retiringTeachers[index] = {};
                    }
                    retiringTeachers[index][field] = data[key];
                }
            });
            // Filter out empty entries and convert to JSON
            const validRetiring = retiringTeachers.filter(item => item.year || item.count);
            data.retiring_teachers = validRetiring.length > 0 ? JSON.stringify(validRetiring) : null;

            // Handle studying teachers - convert array to JSON
            const studyingTeachers = [];
            const studyingKeys = Object.keys(data).filter(k => k.startsWith('studying_teachers['));
            studyingKeys.forEach(key => {
                const match = key.match(/studying_teachers\[(\d+)\]\[(year|count)\]/);
                if (match) {
                    const index = parseInt(match[1]);
                    const field = match[2];
                    if (!studyingTeachers[index]) {
                        studyingTeachers[index] = {};
                    }
                    studyingTeachers[index][field] = data[key];
                }
            });
            // Filter out empty entries and convert to JSON
            const validStudying = studyingTeachers.filter(item => item.year || item.count);
            data.studying_teachers = validStudying.length > 0 ? JSON.stringify(validStudying) : null;

            // Handle majors detail - convert array to JSON
            const majors = [];
            document.querySelectorAll('.major-item').forEach((item, index) => {
                const majorName = item.querySelector('.major-name')?.value.trim();
                const admissionCount = item.querySelector('.major-count')?.value;
                if (majorName) {
                    majors.push({
                        major_name: majorName,
                        admission_count: admissionCount ? parseInt(admissionCount) : 0
                    });
                }
            });
            data.majors_detail = majors.length > 0 ? JSON.stringify(majors) : null;
            data.major_count = majors.length;

            if (status) {
                data.status = status;
            }

            try {
                const response = await fetch(appRoute('admin/admission/save/' + FORM_ID), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    Swal.fire('สำเร็จ!', result.message, 'success');
                } else {
                    Swal.fire('ผิดพลาด', result.message, 'error');
                }
            } catch (e) {
                Swal.fire('ผิดพลาด', 'ไม่สามารถบันทึกได้', 'error');
            }
        }

        // Major management functions
        function toggleMajorSection() {
            const hasMajor = document.querySelector('input[name="has_major_minor"]:checked')?.value === '1';
            document.getElementById('major-section').style.display = hasMajor ? 'block' : 'none';
            if (hasMajor && document.querySelectorAll('.major-item').length === 0) {
                addMajor();
            }
        }

        function addMajor() {
            const list = document.getElementById('majors-list');
            const index = list.children.length;
            const div = document.createElement('div');
            div.className = 'major-item flex gap-2 items-center';
            div.innerHTML = `
                <span class="text-gray-500">${index + 1}.</span>
                <input type="text" class="major-name flex-1 px-3 py-2 border rounded" placeholder="ชื่อวิชาเอก">
                <span class="text-gray-600">จำนวน</span>
                <input type="number" class="major-count w-20 px-3 py-2 border rounded" placeholder="0">
                <span class="text-gray-600">คน</span>
                <button type="button" onclick="removeMajor(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>
            `;
            list.appendChild(div);
            updateMajorNumbers();
        }

        function removeMajor(btn) {
            const list = document.getElementById('majors-list');
            if (list.children.length > 1) {
                btn.closest('.major-item').remove();
                updateMajorNumbers();
            } else {
                Swal.fire('แจ้งเตือน', 'ต้องมีอย่างน้อย 1 วิชาเอก', 'warning');
            }
        }

        function updateMajorNumbers() {
            document.querySelectorAll('.major-item').forEach((item, index) => {
                item.querySelector('span:first-child').textContent = `${index + 1}.`;
            });
            document.getElementById('major_count').value = document.querySelectorAll('.major-item').length;
        }

        // Load existing majors data
        function loadMajorsData() {
            const majorsDetail = <?= json_encode($form['majors_detail'] ?? '[]') ?>;
            let majors = [];
            try {
                majors = typeof majorsDetail === 'string' ? JSON.parse(majorsDetail) : majorsDetail;
                if (!Array.isArray(majors)) majors = [];
            } catch (e) {
                majors = [];
            }

            const list = document.getElementById('majors-list');
            list.innerHTML = '';

            if (majors.length > 0) {
                majors.forEach((major, index) => {
                    const div = document.createElement('div');
                    div.className = 'major-item flex gap-2 items-center';
                    div.innerHTML = `
                        <span class="text-gray-500">${index + 1}.</span>
                        <input type="text" class="major-name flex-1 px-3 py-2 border rounded" placeholder="ชื่อวิชาเอก" value="${major.major_name || ''}">
                        <span class="text-gray-600">จำนวน</span>
                        <input type="number" class="major-count w-20 px-3 py-2 border rounded" placeholder="0" value="${major.admission_count || ''}">
                        <span class="text-gray-600">คน</span>
                        <button type="button" onclick="removeMajor(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>
                    `;
                    list.appendChild(div);
                });
            } else if (document.querySelector('input[name="has_major_minor"]:checked')?.value === '1') {
                addMajor();
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleMajorSection();
            loadMajorsData();
        });

        function saveAndSubmit() {
            Swal.fire({
                title: 'ยืนยันการส่งแบบฟอร์ม?',
                text: 'เมื่อส่งแล้วจะไม่สามารถแก้ไขได้จนกว่าจะถูกตีกลับ',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'ส่ง',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    saveForm('submitted');
                }
            });
        }
    </script>
</body>

</html>