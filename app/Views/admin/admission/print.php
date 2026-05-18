<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>แบบเสนอขอเปิดรับนักศึกษาใหม่ ประจำปีการศึกษา <?= esc($form['academic_year']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 1.5cm;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            font-size: 14px;
            line-height: 1.6;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: 600;
            color: #0066cc;
            margin: 0;
        }

        .header p {
            margin: 5px 0;
        }

        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .section-title {
            font-weight: 600;
            color: #0066cc;
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 5px 8px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
            font-weight: 600;
        }

        .text-center {
            text-align: center;
        }

        .signature-box {
            display: inline-block;
            width: 45%;
            margin-top: 30px;
            text-align: center;
            vertical-align: top;
        }

        .signature-line {
            border-bottom: 1px dotted #000;
            width: 80%;
            margin: 30px auto 5px;
        }

        @media print {
            button {
                display: none;
            }
        }
    </style>
</head>

<body>
    <button onclick="window.print()" style="position:fixed;top:10px;right:10px;padding:10px 20px;background:#0066cc;color:#fff;border:none;border-radius:5px;cursor:pointer;">🖨️ พิมพ์</button>

    <div class="header">
        <p style="text-align:right;font-size:12px;">(ระดับหลักสูตร)</p>
        <h1>แบบเสนอขอเปิดรับนักศึกษาใหม่ ประจำปีการศึกษา <?= esc($form['academic_year']) ?></h1>
        <p>คณะ<?= esc($form['faculty_name'] ?? '...............') ?></p>
    </div>

    <div class="section">
        <p><strong>๑.</strong> ชื่อหลักสูตรสาขาวิชา <strong><?= esc($form['curriculum_name'] ?? '...............') ?></strong> ฉบับปี พ.ศ. <strong><?= esc($form['curriculum_version_year'] ?? '...............') ?></strong></p>
    </div>

    <div class="section">
        <p><strong>๒.</strong> หลักสูตรได้รับการพิจารณาความสอดคล้องจากสำนักปลัดกระทรวงอุดมศึกษา วิทยาศาสตร์ วิจัยและนวัตกรรม เมื่อวันที่ <?= $form['ministry_approval_date'] ? date('d/m/Y', strtotime($form['ministry_approval_date'])) : '..................' ?></p>
        <p style="margin-left:20px;">กรณีหลักสูตรอยู่ในระหว่างการพัฒนาหรือปรับปรุง สภามหาวิทยาลัยเห็นชอบแล้วเมื่อวันที่ <?= $form['university_approval_date'] ? date('d/m/Y', strtotime($form['university_approval_date'])) : '..................' ?></p>
    </div>

    <div class="section">
        <p><strong>๓.</strong> ผลการประเมินคุณภาพการศึกษาระดับหลักสูตร ๒ ปีย้อนหลัง</p>
        <p style="margin-left:40px;">ปีการศึกษา <?= esc($form['quality_assessment_year1'] ?? '............') ?> ผลการประเมินอยู่ในเกณฑ์ <?= esc($form['quality_assessment_result1'] ?? '.....................') ?></p>
        <p style="margin-left:40px;">ปีการศึกษา <?= esc($form['quality_assessment_year2'] ?? '............') ?> ผลการประเมินอยู่ในเกณฑ์ <?= esc($form['quality_assessment_result2'] ?? '.....................') ?></p>
    </div>

    <div class="section">
        <p><strong>๔.</strong> ข้อมูลอาจารย์ผู้รับผิดชอบหลักสูตร</p>
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width:30px;">ที่</th>
                    <th style="width:80px;">ตำแหน่ง</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th colspan="5" class="text-center">มีผลงานทางวิชาการที่เผยแพร่แล้วอยู่ในปี</th>
                    <th style="width:60px;" class="text-center">*ปี พ.ศ. ที่รับ นศ.</th>
                </tr>
                <tr>
                    <th colspan="3"></th>
                    <?php for ($y = 5; $y >= 1; $y--): ?>
                        <th class="text-center" style="width:45px;"><?= ($form['academic_year'] ?? 2568) - $y ?></th>
                    <?php endfor; ?>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $teachers = $form['teachers'] ?? [];
                for ($i = 0; $i < 5; $i++):
                    $teacher = $teachers[$i] ?? [];
                ?>
                    <tr>
                        <td class="text-center"><?= $i + 1 ?></td>
                        <td><?= esc($teacher['position'] ?? '') ?></td>
                        <td><?= esc($teacher['full_name'] ?? '') ?></td>
                        <?php for ($y = 5; $y >= 1; $y--): ?>
                            <td class="text-center"><?= ($teacher["pub_year_{$y}"] ?? 0) ? '✓' : '' ?></td>
                        <?php endfor; ?>
                        <td class="text-center"><?= esc($teacher['admission_year'] ?? '') ?></td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
        <p style="margin-top:10px;">๔.๑) การคงอยู่ของอาจารย์ในหลักสูตร</p>
        <p style="margin-left:40px;">○ ครบ <?= ($form['teachers_status'] ?? '') === 'complete' ? '(✓)' : '' ?></p>
        <p style="margin-left:40px;">○ ไม่ครบ <?= ($form['teachers_status'] ?? '') === 'incomplete' ? '(✓)' : '' ?></p>
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
            <p style="margin-left:60px;">อาจารย์ที่ไม่ครบ: <?= esc(implode(', ', $teacherNames) ?: '......') ?></p>
            <p style="margin-left:60px;">เนื่องจาก: <?= esc($form['teachers_incomplete_reason'] ?? '...............') ?></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($form['publications'])): ?>
        <div class="section">
            <p><strong>๕.</strong> ผลงานทางวิชาการของอาจารย์ผู้รับผิดชอบหลักสูตร</p>
            <table>
                <thead>
                    <tr>
                        <th class="text-center" style="width:40px;">ลำดับ</th>
                        <th style="width:120px;">ชื่อ-สกุล</th>
                        <th style="width:80px;">ประเภทผลงาน</th>
                        <th>ชื่อผลงาน</th>
                        <th style="width:60px;" class="text-center">ปีที่เผยแพร่</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($form['publications'] as $index => $pub): ?>
                        <tr>
                            <td class="text-center"><?= $index + 1 ?></td>
                            <td><?= esc($pub['author_name_th'] ?? $pub['author_name'] ?? '') ?></td>
                            <td><?= esc($pub['publication_type'] ?? '') ?></td>
                            <td style="font-size:12px;"><?= esc($pub['title'] ?? '') ?></td>
                            <td class="text-center"><?= ($pub['publication_year'] ?? 0) + 543 ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="section">
        <p><strong>๙.</strong> แผนพัฒนานักศึกษาค้างชั้น</p>
        <p style="margin-left:20px;">วิธีดำเนินการ</p>
        <div style="border:1px solid #333;padding:10px;min-height:60px;margin:5px 20px;"><?= nl2br(esc($form['remaining_student_plan'] ?? '')) ?></div>
        <p style="margin-left:20px;margin-top:10px;">ตัวชี้วัดความสำเร็จ</p>
        <div style="border:1px solid #333;padding:10px;min-height:60px;margin:5px 20px;"><?= nl2br(esc($form['remaining_student_kpi'] ?? '')) ?></div>
    </div>

    <div style="margin-top:40px;">
        <p style="color:#0066cc;font-weight:600;">ความเห็นชอบของประธานหลักสูตร</p>
        <p style="margin-left:20px;">นำเสนอและผ่านความเห็นชอบของคณะกรรมการบริหารหลักสูตรแล้วเมื่อ............................</p>
        <div class="signature-box">
            <div class="signature-line"></div>
            <p>ลงชื่อ...................................ประธานหลักสูตร</p>
            <p>(<?= esc($form['curriculum_head_name'] ?? '...................................') ?>)</p>
            <p>........./........../...........</p>
        </div>
    </div>

    <div style="margin-top:30px;">
        <p style="color:#0066cc;font-weight:600;">ความเห็นชอบของคณบดี</p>
        <p style="margin-left:20px;">รับทราบและเห็นควรนำเสนอคณะกรรมการ(ของคณะ).............................</p>
        <div class="signature-box">
            <div class="signature-line"></div>
            <p>ลงชื่อ...................................คณบดี</p>
            <p>(<?= esc($form['dean_name'] ?? '...................................') ?>)</p>
            <p>........./........../...........</p>
        </div>
    </div>
</body>

</html>