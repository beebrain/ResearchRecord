<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'จัดการ CV') ?></title>
    <link rel="stylesheet" href="<?= base_url('/public/assets/css/tailwind.min.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #f8f9fa; }
        .section-title { font-size: 14px; text-transform: uppercase; letter-spacing: 2px; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 16px; }
        .sortable-ghost { opacity: 0.4; background: #e5e7eb; }
        .entry-card { transition: all 0.2s ease; }
        .entry-card:hover { background-color: #f3f4f6; transform: translateX(2px); }
        .cv-section-item { transition: all 0.2s ease; }
    </style>
</head>

<body class="min-h-screen py-8">
    <!-- Quick Access Bar -->
    <div class="max-w-6xl mx-auto px-4 mb-6">
        <div class="flex items-center justify-between bg-white rounded-xl shadow-sm px-4 py-3 border border-gray-100">
            <div class="flex items-center gap-4">
                <a href="<?= base_url('index.php/dashboard') ?>" class="text-gray-500 hover:text-gray-700 text-sm">← กลับ Dashboard</a>
                <span class="text-gray-300">|</span>
                <span class="font-semibold text-gray-700">📝 จัดการ CV</span>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= base_url('index.php/dashboard/cv') ?>" class="px-3 py-1.5 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50">📄 ดู CV</a>
                <a href="<?= base_url('index.php/dashboard/orcid') ?>" class="px-3 py-1.5 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50">🔗 ORCID</a>
                <a href="<?= base_url('index.php/dashboard/settings') ?>" class="px-3 py-1.5 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50">⚙️ ตั้งค่า</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-4">
        <!-- Header + Create Section Row -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-light text-gray-800 tracking-wide">CV SECTIONS MANAGEMENT</h1>
                    <p class="text-sm text-gray-500">จัดการหัวข้อและรายการใน CV ของคุณ</p>
                </div>
                
                <!-- Quick Stats -->
                <div class="flex items-center gap-6 text-sm">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-800"><?= count($cv_sections ?? []) ?></p>
                        <p class="text-xs text-gray-500 uppercase">Sections</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-800"><?php 
                            $totalEntries = 0;
                            foreach ($cv_sections ?? [] as $s) {
                                $totalEntries += count($s['entries'] ?? []);
                            }
                            echo $totalEntries;
                        ?></p>
                        <p class="text-xs text-gray-500 uppercase">Entries</p>
                    </div>
                </div>
            </div>
            
            <!-- Create Section Form - Compact Inline -->
            <div class="px-6 pb-6 border-t border-gray-100 pt-4 bg-gray-50">
                <form id="createSectionForm" action="<?= base_url('index.php/dashboard/settings/section') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="flex-1 min-w-[300px]">
                            <label class="block text-xs text-gray-500 uppercase mb-1">ชื่อหัวข้อ</label>
                            <input type="text" name="title" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:border-gray-400 outline-none" placeholder="เช่น Education, Work Experience, Awards..." required>
                        </div>
                        <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white py-2 px-6 rounded-lg text-sm font-medium transition whitespace-nowrap">
                            + เพิ่มหัวข้อ
                        </button>
                    </div>
                    <input type="hidden" name="sort_order" value="0">
                    <input type="hidden" name="type" value="custom">
                </form>
            </div>
        </div>

        <!-- CV Sections List -->
        <?php if (!empty($cv_sections)): ?>
            <div id="cv-sections-container" class="space-y-4">
            <?php foreach ($cv_sections as $section): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden cv-section-item" data-section-id="<?= $section['id'] ?>">
                    <!-- Section Header -->
                    <div class="flex items-center justify-between px-5 py-4 bg-gradient-to-r from-gray-50 to-white cursor-pointer hover:from-gray-100 transition border-b border-gray-100" onclick="toggleSection(<?= $section['id'] ?>)">
                        <div class="flex items-center gap-4">
                            <span class="cv-section-handle cursor-grab text-gray-300 hover:text-gray-500 text-lg" onclick="event.stopPropagation()">⋮⋮</span>
                            <div>
                                <span class="font-semibold text-gray-800 text-lg"><?= esc($section['title']) ?></span>
                                <span class="text-sm text-gray-400 ml-2">(<?= count($section['entries'] ?? []) ?> entries)</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button onclick="event.stopPropagation(); deleteSection(<?= $section['id'] ?>, '<?= esc($section['title']) ?>')" class="text-red-400 hover:text-red-600 text-sm px-2 py-1 rounded hover:bg-red-50 transition">🗑️ ลบ</button>
                            <span class="text-gray-400 text-lg transition-transform duration-200" id="toggle-<?= $section['id'] ?>">▼</span>
                        </div>
                    </div>
                    
                    <!-- Section Content -->
                    <div class="hidden" id="content-<?= $section['id'] ?>">
                        <!-- Entries List -->
                        <div class="p-5 cv-entries-container" id="entries-<?= $section['id'] ?>" data-section-id="<?= $section['id'] ?>">
                            <?php if (!empty($section['entries'])): ?>
                                <div class="space-y-3">
                                <?php foreach ($section['entries'] as $entry): ?>
                                    <div class="entry-card flex items-start gap-4 p-4 bg-gray-50 rounded-xl cv-entry-item border border-transparent hover:border-gray-200" data-entry-id="<?= $entry['id'] ?>">
                                        <span class="cv-entry-handle cursor-grab text-gray-300 hover:text-gray-500 mt-1">⋮⋮</span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-start gap-2">
                                                <h4 class="font-medium text-gray-800"><?= esc($entry['title']) ?></h4>
                                                <?php if (!empty($entry['organization'])): ?>
                                                    <span class="text-sm text-gray-500">• <?= esc($entry['organization']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($entry['location']) || !empty($entry['start_date'])): ?>
                                                <div class="flex flex-wrap gap-3 mt-1 text-xs text-gray-400">
                                                    <?php if (!empty($entry['location'])): ?>
                                                        <span>📍 <?= esc($entry['location']) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($entry['start_date'])): ?>
                                                        <span>📅 <?= date('M Y', strtotime($entry['start_date'])) ?><?= !empty($entry['end_date']) ? ' - ' . date('M Y', strtotime($entry['end_date'])) : ($entry['is_current'] ? ' - Present' : '') ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($entry['description'])): ?>
                                                <p class="text-sm text-gray-500 mt-2 line-clamp-2"><?= esc($entry['description']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex gap-2 shrink-0">
                                            <button onclick="editEntry(<?= $section['id'] ?>, <?= $entry['id'] ?>)" class="text-gray-500 hover:text-gray-700 text-sm px-3 py-1.5 rounded-lg hover:bg-gray-200 transition">✏️ แก้ไข</button>
                                            <button onclick="deleteEntry(<?= $entry['id'] ?>, '<?= esc($entry['title']) ?>')" class="text-red-500 hover:text-red-700 text-sm px-3 py-1.5 rounded-lg hover:bg-red-50 transition">🗑️ ลบ</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-sm text-gray-400 text-center py-6">ยังไม่มีรายการ</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Add Entry Form -->
                        <div class="border-t border-gray-100 p-5 bg-gradient-to-b from-gray-50 to-white">
                            <p class="text-xs text-gray-500 uppercase mb-4 font-medium">➕ เพิ่มรายการใหม่</p>
                            <form class="cv-entry-form" action="<?= base_url('index.php/dashboard/settings/entry') ?>" method="POST" data-section-id="<?= $section['id'] ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="section_id" value="<?= $section['id'] ?>">
                                <input type="hidden" name="entry_id" value="" id="entry-id-<?= $section['id'] ?>">
                                
                                <div class="space-y-4">
                                    <!-- Title - Full Width -->
                                    <div>
                                        <label class="block text-xs text-gray-500 uppercase mb-1">Title *</label>
                                        <input type="text" name="entry_title" class="w-full text-sm border border-gray-200 rounded-lg px-4 py-3 focus:border-gray-400 focus:ring-2 focus:ring-gray-100 outline-none" placeholder="ชื่อหัวข้อ เช่น Ph.D. in Computer Science" required id="entry-title-<?= $section['id'] ?>">
                                    </div>
                                    
                                    <!-- Organization & Location - Two Columns -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs text-gray-500 uppercase mb-1">Organization</label>
                                            <input type="text" name="organization" class="w-full text-sm border border-gray-200 rounded-lg px-4 py-3 focus:border-gray-400 outline-none" placeholder="หน่วยงาน / มหาวิทยาลัย" id="entry-org-<?= $section['id'] ?>">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 uppercase mb-1">Location</label>
                                            <input type="text" name="location" class="w-full text-sm border border-gray-200 rounded-lg px-4 py-3 focus:border-gray-400 outline-none" placeholder="สถานที่ เช่น Bangkok, Thailand" id="entry-location-<?= $section['id'] ?>">
                                        </div>
                                    </div>
                                    
                                    <!-- Dates - Two Columns -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs text-gray-500 uppercase mb-1">Start Date</label>
                                            <input type="date" name="start_date" class="w-full text-sm border border-gray-200 rounded-lg px-4 py-3 focus:border-gray-400 outline-none" id="entry-start-<?= $section['id'] ?>">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 uppercase mb-1">End Date</label>
                                            <input type="date" name="end_date" class="w-full text-sm border border-gray-200 rounded-lg px-4 py-3 focus:border-gray-400 outline-none" id="entry-end-<?= $section['id'] ?>">
                                        </div>
                                    </div>
                                    
                                    <!-- Description - Full Width -->
                                    <div>
                                        <label class="block text-xs text-gray-500 uppercase mb-1">Description</label>
                                        <textarea name="entry_description" class="w-full text-sm border border-gray-200 rounded-lg px-4 py-3 focus:border-gray-400 outline-none resize-none" placeholder="รายละเอียดเพิ่มเติม..." rows="3" id="entry-desc-<?= $section['id'] ?>"></textarea>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="flex justify-between items-center pt-2">
                                        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                                            <input type="checkbox" name="is_current" value="1" id="current-<?= $section['id'] ?>" class="rounded border-gray-300 text-gray-800 focus:ring-gray-400">
                                            <span>ปัจจุบัน (Current)</span>
                                        </label>
                                        <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white text-sm rounded-lg px-6 py-2.5 font-medium transition">
                                            💾 บันทึก
                                        </button>
                                    </div>
                                </div>

                                <input type="hidden" name="funding_amount" value="" id="entry-amount-<?= $section['id'] ?>">
                                <input type="hidden" name="entry_sort_order" value="0">
                                <input type="hidden" name="extra_info" value="">
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="text-center py-16 text-gray-400">
                    <p class="text-5xl mb-4">📝</p>
                    <p class="text-lg">ยังไม่มีหัวข้อ</p>
                    <p class="text-sm mt-2">กรุณาสร้างหัวข้อใหม่ด้านบน</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleSection(id) {
            const content = document.getElementById(`content-${id}`);
            const toggle = document.getElementById(`toggle-${id}`);
            content.classList.toggle('hidden');
            toggle.textContent = content.classList.contains('hidden') ? '▼' : '▲';
            toggle.style.transform = content.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
        }

        async function editEntry(sectionId, entryId) {
            const response = await fetch(`<?= base_url('index.php/dashboard/settings/entry/') ?>${entryId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await response.json();
            if (result.success && result.entry) {
                const e = result.entry;
                document.getElementById(`entry-id-${sectionId}`).value = e.id;
                document.getElementById(`entry-title-${sectionId}`).value = e.title || '';
                document.getElementById(`entry-org-${sectionId}`).value = e.organization || '';
                document.getElementById(`entry-location-${sectionId}`).value = e.location || '';
                document.getElementById(`entry-start-${sectionId}`).value = e.start_date || '';
                document.getElementById(`entry-end-${sectionId}`).value = e.end_date || '';
                document.getElementById(`current-${sectionId}`).checked = e.is_current == 1;
                document.getElementById(`entry-desc-${sectionId}`).value = e.description || '';
                
                // Expand section and scroll to form
                document.getElementById(`content-${sectionId}`).classList.remove('hidden');
                document.getElementById(`toggle-${sectionId}`).textContent = '▲';
                document.getElementById(`entry-title-${sectionId}`).focus();
                document.getElementById(`entry-title-${sectionId}`).scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        async function deleteEntry(entryId, title) {
            const result = await Swal.fire({
                title: 'ยืนยันการลบ?',
                text: `ลบ "${title}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#374151',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก'
            });
            if (result.isConfirmed) {
                await fetch(`<?= base_url('index.php/dashboard/settings/entry/delete/') ?>${entryId}`, {
                    method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                location.reload();
            }
        }

        async function deleteSection(sectionId, title) {
            const result = await Swal.fire({
                title: 'ลบหัวข้อนี้?',
                text: `ลบ "${title}" และรายการทั้งหมดในหัวข้อนี้?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'ลบทั้งหมด',
                cancelButtonText: 'ยกเลิก'
            });
            if (result.isConfirmed) {
                await fetch(`<?= base_url('index.php/dashboard/settings/section/delete/') ?>${sectionId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: `<?= csrf_token() ?>=<?= csrf_hash() ?>`
                });
                location.reload();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('cv-sections-container');
            if (container) {
                new Sortable(container, {
                    animation: 150,
                    handle: '.cv-section-handle',
                    ghostClass: 'sortable-ghost',
                    onEnd: async () => {
                        const order = Array.from(container.querySelectorAll('.cv-section-item')).map((el, i) => ({id: el.dataset.sectionId, order: i}));
                        await fetch('<?= base_url('index.php/dashboard/settings/section/reorder') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                            body: `order=${encodeURIComponent(JSON.stringify(order))}&<?= csrf_token() ?>=<?= csrf_hash() ?>`
                        });
                    }
                });
            }

            // Initialize Sortable for each entries container
            document.querySelectorAll('.cv-entries-container').forEach(container => {
                new Sortable(container, {
                    animation: 150,
                    handle: '.cv-entry-handle',
                    ghostClass: 'sortable-ghost',
                    onEnd: async () => {
                        const sectionId = container.dataset.sectionId;
                        const order = Array.from(container.querySelectorAll('.cv-entry-item')).map((el, i) => ({id: el.dataset.entryId, order: i}));
                        await fetch('<?= base_url('index.php/dashboard/settings/entry/reorder') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                            body: `section_id=${sectionId}&order=${encodeURIComponent(JSON.stringify(order))}&<?= csrf_token() ?>=<?= csrf_hash() ?>`
                        });
                    }
                });
            });

            document.querySelectorAll('.cv-entry-form').forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const res = await fetch(form.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData(form) });
                    const data = await res.json();
                    if (data.success) location.reload();
                    else Swal.fire('ผิดพลาด', data.message, 'error');
                });
            });
        });
    </script>
</body>

</html>
