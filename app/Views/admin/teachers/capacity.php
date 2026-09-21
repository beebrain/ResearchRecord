<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ศักยภาพการจัดการอาจารย์</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-common.css') ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <style>
        .stat-card { transition: transform .15s ease, box-shadow .15s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(15,23,42,.08); }
        .tab-btn.active { background:#2563eb; color:#fff; }
        .gap-dot { width:.55rem; height:.55rem; border-radius:999px; display:inline-block; }
        .gap-ok { background:#16a34a; }
        .gap-bad { background:#dc2626; }
    </style>
</head>

<body class="min-h-full">
<div class="min-h-full bg-gray-50">
    <?php
    $pageTitle = 'ศักยภาพการจัดการอาจารย์';
    $pageSubtitle = 'ตรวจอัตราส่วน / หลักสูตรสังกัด / ช่องว่างการบริหาร';
    ob_start();
    ?>
    <a href="<?= site_url('admin/manage-user-curriculum') ?>"
       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
        ไปจัดการหลักสูตรของผู้ใช้
    </a>
    <?php $headerActions = ob_get_clean(); ?>

    <?= view('admin/partials/header', [
        'pageTitle' => $pageTitle,
        'pageSubtitle' => $pageSubtitle,
        'headerActions' => $headerActions,
    ]) ?>

    <div class="flex">
        <?= view('admin/partials/navigation') ?>

        <main class="flex-1 p-6 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">ภาพรวมศักยภาพอาจารย์</h2>
                    <p class="text-sm text-gray-500 mt-1">อาจารย์ที่ยังไม่มีหลักสูตรสังกัด · อยู่หลายหลักสูตร · หลักสูตรที่ขาดอาจารย์ประจำ/ผู้รับผิดชอบ/ประธาน</p>
                </div>
                <div class="w-full sm:w-72">
                    <label class="block text-xs font-medium text-gray-600 mb-1">กรองตามคณะ</label>
                    <select id="facultyFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="all">ทุกคณะ</option>
                    </select>
                </div>
            </div>

            <div id="summaryCards" class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-4 gap-4">
                <!-- filled by JS -->
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="flex flex-wrap gap-2 mb-4" id="tabs">
                    <button type="button" data-tab="unassigned" class="tab-btn active px-3 py-1.5 rounded-lg text-sm font-medium bg-gray-100 text-gray-700">ยังไม่มีหลักสูตรสังกัด</button>
                    <button type="button" data-tab="multi" class="tab-btn px-3 py-1.5 rounded-lg text-sm font-medium bg-gray-100 text-gray-700">อยู่หลายหลักสูตร</button>
                    <button type="button" data-tab="gaps" class="tab-btn px-3 py-1.5 rounded-lg text-sm font-medium bg-gray-100 text-gray-700">หลักสูตรที่ยังไม่ครบ</button>
                    <button type="button" data-tab="faculty" class="tab-btn px-3 py-1.5 rounded-lg text-sm font-medium bg-gray-100 text-gray-700">แยกตามคณะ</button>
                </div>

                <div id="panel-unassigned" class="tab-panel">
                    <table id="tableUnassigned" class="display" style="width:100%">
                        <thead><tr><th>ชื่อ</th><th>อีเมล</th><th>คณะสังกัด</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div id="panel-multi" class="tab-panel hidden">
                    <table id="tableMulti" class="display" style="width:100%">
                        <thead><tr><th>ชื่อ</th><th>จำนวนหลักสูตร</th><th>หลักสูตร / บทบาท</th><th>คณะสังกัด</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div id="panel-gaps" class="tab-panel hidden">
                    <table id="tableGaps" class="display" style="width:100%">
                        <thead><tr><th>หลักสูตร</th><th>คณะ</th><th>สมาชิก</th><th>อาจารย์ประจำ</th><th>ผู้รับผิดชอบ</th><th>ประธาน</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div id="panel-faculty" class="tab-panel hidden">
                    <table id="tableFaculty" class="display" style="width:100%">
                        <thead><tr><th>คณะ</th><th>อาจารย์</th><th>ยังไม่สังกัด</th><th>หลักสูตร</th><th>ครอบคลุม (%)</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    const BASE_URL = '<?= rtrim(base_url(), '/') ?>';
</script>
<script src="<?= base_url('assets/js/app-routes.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/app-routes.js') ?: time() ?>"></script>
<script>
(function () {
    let payload = null;
    let tables = {};

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function card(label, value, hint, tone) {
        const tones = {
            blue: 'border-blue-200 bg-blue-50 text-blue-900',
            amber: 'border-amber-200 bg-amber-50 text-amber-900',
            green: 'border-emerald-200 bg-emerald-50 text-emerald-900',
            red: 'border-red-200 bg-red-50 text-red-900',
            slate: 'border-slate-200 bg-white text-slate-900',
        };
        return `<div class="stat-card rounded-xl border p-4 ${tones[tone] || tones.slate}">
            <div class="text-xs font-medium opacity-80">${esc(label)}</div>
            <div class="text-2xl font-bold mt-1">${esc(value)}</div>
            ${hint ? `<div class="text-xs mt-1 opacity-70">${esc(hint)}</div>` : ''}
        </div>`;
    }

    function renderSummary(s) {
        $('#summaryCards').html([
            card('อาจารย์ทั้งหมด', s.teachers_total, `ครอบคลุมหลักสูตร ${s.coverage_pct}%`, 'blue'),
            card('ยังไม่มีหลักสูตรสังกัด', s.teachers_unassigned, 'ต้องจัดเข้าหลักสูตร', s.teachers_unassigned > 0 ? 'amber' : 'green'),
            card('อยู่หลายหลักสูตร', s.teachers_multi, `เฉลี่ย ${s.avg_teachers_per_curriculum} คน/หลักสูตร`, 'slate'),
            card('การมอบหมายทั้งหมด', s.assignments_total, `ประจำ ${s.instructors} · ผู้รับผิดชอบ ${s.coordinators}`, 'slate'),
            card('หลักสูตรที่ใช้งาน', s.curricula_active, `พหุสาขา ${s.curricula_multidisciplinary}`, 'blue'),
            card('ขาดอาจารย์ประจำ', s.curricula_no_instructor, 'ต่อหลักสูตร', s.curricula_no_instructor > 0 ? 'red' : 'green'),
            card('ขาดผู้รับผิดชอบ', s.curricula_no_coordinator, 'role = coordinator', s.curricula_no_coordinator > 0 ? 'amber' : 'green'),
            card('ยังไม่มีประธาน', s.curricula_no_chair, 'chair_email ว่าง', s.curricula_no_chair > 0 ? 'amber' : 'green'),
        ].join(''));
    }

    function destroyTable(key) {
        if (tables[key] && $.fn.DataTable.isDataTable(tables[key])) {
            $(tables[key]).DataTable().destroy();
            $(tables[key] + ' tbody').empty();
        }
    }

    function dtOpts() {
        return {
            pageLength: 15,
            ordering: true,
            searching: true,
            language: {
                search: 'ค้นหา:',
                lengthMenu: 'แสดง _MENU_',
                info: '_START_–_END_ จาก _TOTAL_',
                emptyTable: 'ไม่มีข้อมูล',
                zeroRecords: 'ไม่พบข้อมูล',
                paginate: { first: 'แรก', last: 'สุดท้าย', next: 'ถัดไป', previous: 'ก่อน' }
            }
        };
    }

    function renderTables(data) {
        destroyTable('unassigned');
        destroyTable('multi');
        destroyTable('gaps');
        destroyTable('faculty');

        const uBody = data.unassigned.map(r => `<tr>
            <td>${esc(r.name)}</td><td>${esc(r.email)}</td><td>${esc(r.faculty_name)}</td>
        </tr>`).join('');
        $('#tableUnassigned tbody').html(uBody);
        tables.unassigned = '#tableUnassigned';
        $('#tableUnassigned').DataTable(dtOpts());

        const mBody = data.multi_curriculum.map(r => {
            const list = (r.curricula || []).map(c => {
                const role = c.role === 'coordinator' ? 'ผู้รับผิดชอบ' : (c.role === 'assistant' ? 'ผู้ช่วย' : 'อาจารย์ประจำ');
                return `<div class="text-xs text-gray-700">• ${esc(c.name)} <span class="text-gray-400">(${esc(role)})</span></div>`;
            }).join('');
            return `<tr>
                <td>${esc(r.name)}<div class="text-xs text-gray-500">${esc(r.email)}</div></td>
                <td><span class="font-semibold text-blue-700">${esc(r.curriculum_count)}</span></td>
                <td>${list}</td>
                <td>${esc(r.faculty_name)}</td>
            </tr>`;
        }).join('');
        $('#tableMulti tbody').html(mBody);
        tables.multi = '#tableMulti';
        $('#tableMulti').DataTable(dtOpts());

        const gBody = data.curricula_gaps.map(r => `<tr>
            <td>${esc(r.name)} <span class="text-xs text-gray-400">${esc(r.code || '')}</span></td>
            <td>${esc(r.faculty_name)}</td>
            <td>${esc(r.member_count)}</td>
            <td><span class="gap-dot ${r.has_instructor ? 'gap-ok' : 'gap-bad'}"></span> <span class="font-semibold">${esc(r.instructor_count ?? 0)}</span> คน</td>
            <td><span class="gap-dot ${r.has_coordinator ? 'gap-ok' : 'gap-bad'}"></span> ${r.has_coordinator ? 'มี' : 'ไม่มี'}</td>
            <td><span class="gap-dot ${r.has_chair ? 'gap-ok' : 'gap-bad'}"></span> ${r.has_chair ? 'มี' : 'ไม่มี'}</td>
        </tr>`).join('');
        $('#tableGaps tbody').html(gBody);
        tables.gaps = '#tableGaps';
        $('#tableGaps').DataTable(dtOpts());

        const fBody = data.faculty_breakdown.map(r => `<tr>
            <td>${esc(r.name)} <span class="text-xs text-gray-400">(${esc(r.code)})</span></td>
            <td>${esc(r.teachers)}</td>
            <td>${esc(r.unassigned)}</td>
            <td>${esc(r.curricula)}</td>
            <td>${esc(r.coverage_pct)}%</td>
        </tr>`).join('');
        $('#tableFaculty tbody').html(fBody);
        tables.faculty = '#tableFaculty';
        $('#tableFaculty').DataTable(dtOpts());
    }

    function fillFacultySelect(faculties, selected) {
        const $sel = $('#facultyFilter');
        const cur = selected || $sel.val() || 'all';
        $sel.find('option:not([value=all])').remove();
        (faculties || []).forEach(f => {
            $sel.append(`<option value="${esc(f.id)}">${esc(f.name)}</option>`);
        });
        $sel.val(cur);
    }

    function loadData() {
        const facultyId = $('#facultyFilter').val() || 'all';
        $.ajax({
            url: appRoute('admin/getTeacherCapacityData'),
            method: 'POST',
            dataType: 'json',
            data: { faculty_id: facultyId },
            beforeSend: xhr => xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'),
            success: function (res) {
                if (!res.success) {
                    alert(res.message || 'โหลดข้อมูลไม่สำเร็จ');
                    return;
                }
                payload = res.data;
                fillFacultySelect(payload.faculties, facultyId);
                renderSummary(payload.summary);
                renderTables(payload);
            },
            error: function () {
                alert('ไม่สามารถโหลดข้อมูลศักยภาพอาจารย์ได้');
            }
        });
    }

    $('.tab-btn').on('click', function () {
        const tab = $(this).data('tab');
        $('.tab-btn').removeClass('active').addClass('bg-gray-100 text-gray-700');
        $(this).addClass('active').removeClass('bg-gray-100 text-gray-700');
        $('.tab-panel').addClass('hidden');
        $('#panel-' + tab).removeClass('hidden');
        // DataTables needs columns adjust when unhidden
        setTimeout(() => {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        }, 50);
    });

    $('#facultyFilter').on('change', loadData);
    loadData();
})();
</script>
</body>
</html>
