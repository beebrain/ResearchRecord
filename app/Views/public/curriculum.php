<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($curriculum['name'] ?? 'หลักสูตร') ?> | Research Record</title>
    <link rel="stylesheet" href="<?= asset_url('css/tailwind.min.css') ?>">
</head>

<body class="bg-slate-50 text-slate-900 relative overflow-x-hidden">
    <header class="border-b border-slate-200 bg-white/90 backdrop-blur supports-[backdrop-filter]:bg-white/70 sticky top-0 z-10">
        <div class="max-w-6xl mx-auto px-4 py-5 flex items-center justify-between gap-4">
            <div class="min-w-0">
                <a href="<?= site_url('/') ?>" class="text-sm text-slate-600 hover:text-slate-900 inline-flex items-center gap-2">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M15 18l-6-6 6-6" />
                    </svg>
                    กลับหน้าแรก
                </a>
                <h1 class="mt-2 text-lg sm:text-xl font-semibold truncate"><?= esc($curriculum['name'] ?? '') ?></h1>
                <p class="text-sm text-slate-600 mt-1">
                    คณะ: <span class="font-medium text-slate-800"><?= esc($curriculum['faculty_name'] ?? '-') ?></span>
                    <span class="mx-2 text-slate-300">•</span>
                    รหัสหลักสูตร: <span class="font-medium text-slate-800"><?= esc($curriculum['code'] ?? '-') ?></span>
                    <span class="mx-2 text-slate-300">•</span>
                    ระดับ: <span class="font-medium text-slate-800"><?= esc($curriculum['degree_level'] ?? '-') ?></span>
                </p>
            </div>
            <div class="shrink-0">
                <a href="<?= site_url('auth/login') ?>"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900">
                    เข้าสู่ระบบ
                </a>
            </div>
        </div>
    </header>

    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 -top-32 -z-10">
        <div class="mx-auto max-w-6xl px-4">
            <div class="h-48 rounded-[2rem] bg-gradient-to-r from-indigo-200/60 via-sky-200/40 to-emerald-200/50 blur-2xl"></div>
        </div>
    </div>

    <main class="max-w-6xl mx-auto px-4 py-8 space-y-6">
        <section class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm relative overflow-hidden">
            <div aria-hidden="true" class="absolute -top-24 -right-24 h-56 w-56 rounded-full bg-indigo-100 blur-2xl"></div>
            <div aria-hidden="true" class="absolute -bottom-24 -left-24 h-56 w-56 rounded-full bg-emerald-100 blur-2xl"></div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold">ค้นหา/เปลี่ยนหลักสูตร</h2>
                    <p class="text-sm text-slate-600 mt-1">เลือกคณะ → เลือกหลักสูตร → กดค้นหา</p>
                </div>
            </div>

            <form id="finder" class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-3 items-end" action="javascript:void(0)" aria-label="ค้นหาหลักสูตร">
                <div class="md:col-span-5">
                    <label for="faculty" class="block text-sm font-medium text-slate-800">คณะ</label>
                    <select id="faculty"
                        class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600">
                        <option value="">เลือกคณะ</option>
                    </select>
                </div>
                <div class="md:col-span-5">
                    <label for="curriculumSel" class="block text-sm font-medium text-slate-800">หลักสูตร</label>
                    <select id="curriculumSel" disabled
                        class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 disabled:bg-slate-50 disabled:text-slate-500">
                        <option value="">เลือกหลักสูตร</option>
                    </select>
                </div>
                <div class="md:col-span-2 flex gap-2">
                    <button id="doSearch" type="submit" disabled
                        class="w-full rounded-xl bg-indigo-600 text-white px-4 py-3 text-sm font-semibold hover:bg-indigo-700 disabled:bg-slate-300 disabled:text-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600">
                        ค้นหา
                    </button>
                    <a href="<?= site_url('/') ?>"
                        class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600">
                        หน้าแรก
                    </a>
                </div>
            </form>
        </section>

        <section class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-base font-semibold">อาจารย์ผู้รับผิดชอบหลักสูตร</h2>
                <div class="text-sm text-slate-600">
                    พบ <span class="font-semibold text-slate-900"><?= is_array($teachers) ? count($teachers) : 0 ?></span> คน
                </div>
            </div>

            <?php if (empty($teachers)): ?>
                <div class="mt-4 text-sm text-slate-600">ยังไม่มีข้อมูลอาจารย์ผู้รับผิดชอบ</div>
            <?php else: ?>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <?php foreach ($teachers as $t): ?>
                        <?php
                        $thaiName = trim(($t['thai_name'] ?? '') . ' ' . ($t['thai_lastname'] ?? ''));
                        $enName   = trim(($t['gf_name'] ?? '') . ' ' . ($t['gl_name'] ?? ''));
                        $role     = (string) ($t['role'] ?? '');
                        $badge = match ($role) {
                            'chair' => 'ประธานหลักสูตร',
                            'coordinator' => 'ผู้รับผิดชอบหลักสูตร',
                            'assistant' => 'ผู้ช่วยผู้รับผิดชอบ',
                            default => 'อาจารย์',
                        };
                        ?>
                        <button type="button"
                            class="teacher-pill w-full text-left rounded-xl border border-slate-200 px-4 py-3 hover:bg-slate-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600"
                            data-teacher-email="<?= esc((string) ($t['email'] ?? '')) ?>"
                            data-teacher-name="<?= esc($thaiName !== '' ? $thaiName : $enName) ?>"
                            aria-pressed="false">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-medium text-slate-900 truncate"><?= esc($thaiName !== '' ? $thaiName : $enName) ?></div>
                                    <?php if ($thaiName !== '' && $enName !== ''): ?>
                                        <div class="text-xs text-slate-600 mt-1 truncate"><?= esc($enName) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($t['email'])): ?>
                                        <div class="text-xs text-slate-600 mt-1 truncate"><?= esc($t['email']) ?></div>
                                    <?php endif; ?>
                                    <div class="mt-2 text-xs text-slate-600">
                                        ผลงานที่อนุมัติแล้ว: <span class="font-semibold text-slate-900"><?= (int) ($t['publication_count'] ?? 0) ?></span>
                                    </div>
                                </div>
                                <span class="shrink-0 rounded-lg bg-indigo-50 text-indigo-800 px-2 py-1 text-xs font-semibold">
                                    <?= esc($badge) ?>
                                </span>
                            </div>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold">ผลงานเผยแพร่ (อนุมัติแล้ว)</h2>
                    <div id="pubFilterHint" class="mt-1 hidden text-sm text-slate-600">
                        กำลังแสดงเฉพาะผลงานของ <span class="font-semibold text-slate-900" id="pubFilterName"></span>
                    </div>
                </div>
                <div class="text-sm text-slate-600">
                    <span id="pubCountAllWrap">ทั้งหมด <span class="font-semibold text-slate-900" id="pubCountAll"><?= (int) ($publicationCount ?? 0) ?></span> รายการ</span>
                    <span id="pubCountFilteredWrap" class="hidden">พบ <span class="font-semibold text-slate-900" id="pubCountFiltered">0</span> รายการ</span>
                    <button type="button" id="pubClearFilter"
                        class="ml-3 hidden rounded-xl border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600">
                        แสดงทั้งหมด
                    </button>
                </div>
            </div>

            <?php if (empty($publications)): ?>
                <div class="mt-4 text-sm text-slate-600">ยังไม่พบผลงานเผยแพร่ที่อนุมัติสำหรับหลักสูตรนี้</div>
            <?php else: ?>
                <div class="mt-4 space-y-3" id="pubList">
                    <?php foreach ($publications as $p): ?>
                        <?php
                        $year    = (string) ($p['publication_year'] ?? '');
                        $type    = (string) ($p['publication_type'] ?? '');
                        $doi     = trim((string) ($p['doi'] ?? ''));
                        $authors = trim((string) ($p['authors'] ?? ''));
                        $creatorName  = trim((string) ($p['created_by_name'] ?? ''));
                        $creatorEmail = trim((string) ($p['created_by_email'] ?? ''));
                        $authorEmails = trim((string) ($p['author_emails'] ?? ''));
                        ?>
                        <article class="pub-item rounded-xl border border-slate-200 px-4 py-3 hover:bg-slate-50 transition-colors"
                            data-author-emails="<?= esc($authorEmails) ?>">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="font-medium text-slate-900">
                                        <?= esc($p['title'] ?? '') ?>
                                    </div>
                                    <?php if ($authors !== ''): ?>
                                        <div class="text-sm text-slate-700 mt-1">
                                            <?= esc($authors) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($creatorName !== '' || $creatorEmail !== ''): ?>
                                        <div class="text-xs text-slate-600 mt-1">
                                            บันทึกโดย: <span class="font-medium text-slate-800"><?= esc($creatorName !== '' ? $creatorName : $creatorEmail) ?></span>
                                            <?php if ($creatorName !== '' && $creatorEmail !== ''): ?>
                                                <span class="text-slate-400"> (<?= esc($creatorEmail) ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-xs text-slate-600 mt-2">
                                        <?php if ($type !== ''): ?>
                                            <span class="inline-flex items-center rounded-lg bg-indigo-50 text-indigo-800 px-2 py-0.5 mr-2 font-medium"><?= esc($type) ?></span>
                                        <?php endif; ?>
                                        <?php if ($year !== ''): ?>
                                            <span class="inline-flex items-center rounded-lg bg-emerald-50 text-emerald-800 px-2 py-0.5 mr-2 font-medium">ปี <?= esc($year) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($p['source'])): ?>
                                            <span class="inline-flex items-center rounded-lg bg-sky-50 text-sky-800 px-2 py-0.5 mr-2 font-medium"><?= esc($p['source']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($doi !== ''): ?>
                                    <div class="shrink-0">
                                        <a class="text-sm text-indigo-700 hover:text-indigo-900 underline"
                                            href="<?= esc('https://doi.org/' . $doi) ?>" target="_blank" rel="noopener">
                                            DOI
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white mt-10">
        <div class="max-w-6xl mx-auto px-4 py-6 text-sm text-slate-600">
            หมายเหตุ: การผูกผลงานกับหลักสูตรอ้างอิงจากการผูกอาจารย์กับหลักสูตร (teacher_curriculum) และผลงานที่อนุมัติแล้ว (approve=1)
        </div>
    </footer>

    <script>
        (function () {
            var faculties = <?=
                json_encode(array_map(static function ($f) {
                    return [
                        'id' => (int) ($f['id'] ?? 0),
                        'name' => (string) ($f['name'] ?? ''),
                        'code' => (string) ($f['code'] ?? ''),
                        'curriculums' => array_map(static function ($c) {
                            return [
                                'id' => (int) ($c['id'] ?? 0),
                                'name' => (string) ($c['name'] ?? ''),
                                'code' => (string) ($c['code'] ?? ''),
                                'degree_level' => (string) ($c['degree_level'] ?? ''),
                                'faculty_id' => (int) ($c['faculty_id'] ?? 0),
                            ];
                        }, $f['curriculums'] ?? []),
                    ];
                }, $faculties ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            ?>;

            var currentFacultyId = <?= (int) ($curriculum['faculty_id'] ?? 0) ?>;
            var currentCurriculumId = <?= (int) ($curriculum['id'] ?? 0) ?>;

            var facultyEl = document.getElementById('faculty');
            var curEl = document.getElementById('curriculumSel');
            var submitEl = document.getElementById('doSearch');
            var form = document.getElementById('finder');
            if (!facultyEl || !curEl || !submitEl || !form) return;

            var byId = {};
            faculties.forEach(function (f) { byId[String(f.id)] = f; });

            faculties.forEach(function (f) {
                var opt = document.createElement('option');
                opt.value = String(f.id);
                opt.textContent = (f.name || '-') + (f.code ? (' (' + f.code + ')') : '');
                facultyEl.appendChild(opt);
            });

            var fillCurriculums = function (facultyId) {
                var f = byId[String(facultyId)];
                curEl.innerHTML = '<option value=\"\">เลือกหลักสูตร</option>';
                if (!f || !f.curriculums || !f.curriculums.length) {
                    curEl.disabled = true;
                    submitEl.disabled = true;
                    return;
                }
                f.curriculums.forEach(function (c) {
                    var opt = document.createElement('option');
                    opt.value = String(c.id);
                    opt.textContent = c.name + (c.code ? (' (' + c.code + ')') : '');
                    curEl.appendChild(opt);
                });
                curEl.disabled = false;
                submitEl.disabled = !curEl.value;
            };

            facultyEl.addEventListener('change', function () {
                fillCurriculums(facultyEl.value);
            });
            curEl.addEventListener('change', function () {
                submitEl.disabled = !curEl.value;
            });
            form.addEventListener('submit', function () {
                if (!curEl.value) return;
                window.location.href = <?= json_encode(site_url('public/curriculum/'), JSON_UNESCAPED_SLASHES) ?> + encodeURIComponent(curEl.value);
            });

            // init selection
            if (currentFacultyId) {
                facultyEl.value = String(currentFacultyId);
                fillCurriculums(currentFacultyId);
                if (currentCurriculumId) {
                    curEl.value = String(currentCurriculumId);
                    submitEl.disabled = false;
                }
            }

            // filter publications by teacher click
            var pubItems = Array.prototype.slice.call(document.querySelectorAll('.pub-item'));
            var teacherBtns = Array.prototype.slice.call(document.querySelectorAll('.teacher-pill'));
            var hint = document.getElementById('pubFilterHint');
            var hintName = document.getElementById('pubFilterName');
            var clearBtn = document.getElementById('pubClearFilter');
            var countAllWrap = document.getElementById('pubCountAllWrap');
            var countFilteredWrap = document.getElementById('pubCountFilteredWrap');
            var countFilteredEl = document.getElementById('pubCountFiltered');
            if (!pubItems.length || !teacherBtns.length || !hint || !hintName || !clearBtn || !countAllWrap || !countFilteredWrap || !countFilteredEl) return;

            var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var activeEmail = '';

            var setActive = function (email, name) {
                activeEmail = email || '';
                teacherBtns.forEach(function (b) {
                    var on = (String(b.getAttribute('data-teacher-email') || '') === activeEmail) && activeEmail !== '';
                    b.setAttribute('aria-pressed', on ? 'true' : 'false');
                    b.classList.toggle('ring-2', on);
                    b.classList.toggle('ring-indigo-600', on);
                    b.classList.toggle('bg-indigo-50/60', on);
                    b.classList.toggle('border-indigo-200', on);
                });

                var shown = 0;
                pubItems.forEach(function (it) {
                    var list = String(it.getAttribute('data-author-emails') || '');
                    var has = activeEmail && ((',' + list + ',').indexOf(',' + activeEmail + ',') !== -1);
                    var show = !activeEmail || has;
                    if (show) shown++;

                    if (!reduced) it.style.transition = 'opacity 180ms ease, transform 180ms ease';
                    if (show) {
                        it.classList.remove('hidden');
                        if (!reduced) {
                            it.style.opacity = '1';
                            it.style.transform = 'translateY(0px)';
                        }
                    } else {
                        if (!reduced) {
                            it.style.opacity = '0';
                            it.style.transform = 'translateY(6px)';
                            setTimeout(function () { it.classList.add('hidden'); }, 180);
                        } else {
                            it.classList.add('hidden');
                        }
                    }
                });

                if (activeEmail) {
                    hint.classList.remove('hidden');
                    hintName.textContent = name || activeEmail;
                    clearBtn.classList.remove('hidden');
                    countAllWrap.classList.add('hidden');
                    countFilteredWrap.classList.remove('hidden');
                    countFilteredEl.textContent = String(shown);
                } else {
                    hint.classList.add('hidden');
                    clearBtn.classList.add('hidden');
                    countAllWrap.classList.remove('hidden');
                    countFilteredWrap.classList.add('hidden');
                }
            };

            teacherBtns.forEach(function (b) {
                b.addEventListener('click', function () {
                    var email = String(b.getAttribute('data-teacher-email') || '');
                    var name = String(b.getAttribute('data-teacher-name') || '');
                    setActive(activeEmail === email ? '' : email, name);
                    var pubSection = document.getElementById('pubList');
                    if (pubSection) {
                        pubSection.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
                    }
                });
            });
            clearBtn.addEventListener('click', function () { setActive('', ''); });
        })();
    </script>
</body>

</html>

