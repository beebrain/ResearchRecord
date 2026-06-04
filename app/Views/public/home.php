<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบหลักสูตรและผลงานเผยแพร่ | Research Record</title>
    <link rel="stylesheet" href="<?= asset_url('css/tailwind.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/sarabun.css') ?>">
    <style>
        :root{
            --rr-ink:#0b1220;
            --rr-muted:#475569;
            --rr-ring:rgba(99,102,241,.55);
            --rr-accent:#4f46e5;
        }
        html{scroll-behavior:smooth;}
        body{font-family:"Sarabun",ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;}
        /* subtle grain overlay for depth */
        .rr-grain:before{
            content:"";
            position:fixed;
            inset:0;
            pointer-events:none;
            z-index:-1;
            opacity:.18;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='160' height='160' filter='url(%23n)' opacity='.45'/%3E%3C/svg%3E");
            mix-blend-mode:multiply;
        }
        @media (prefers-reduced-motion: reduce){ html{scroll-behavior:auto;} }
        .rr-card{position:relative; transform:translateY(0); transition:transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease;}
        .rr-card:hover{transform:translateY(-2px); box-shadow:0 14px 40px rgba(2,6,23,.10);}
        .rr-kicker{letter-spacing:.12em; text-transform:uppercase;}
        .rr-linkline{background-size:200% 2px; background-position:0 100%; background-repeat:no-repeat; background-image:linear-gradient(90deg, transparent 0%, rgba(79,70,229,.35) 35%, rgba(14,165,233,.35) 65%, transparent 100%); }
        .rr-linkline:hover{background-position:100% 100%;}
        /* chip-card system (no gradients) */
        .rr-cardShell{border-color:rgba(15,23,42,.10); box-shadow:0 1px 0 rgba(255,255,255,.85), 0 18px 55px rgba(2,6,23,.06);}
        .rr-accent{--rr-chipBg:rgba(79,70,229,.10); --rr-chipText:rgb(49,46,129); --rr-rail:rgb(79,70,229); --rr-soft:rgba(79,70,229,.10);}
        .rr-accent--sky{--rr-chipBg:rgba(2,132,199,.12); --rr-chipText:rgb(7,89,133); --rr-rail:rgb(2,132,199); --rr-soft:rgba(2,132,199,.10);}
        .rr-accent--emerald{--rr-chipBg:rgba(5,150,105,.12); --rr-chipText:rgb(6,95,70); --rr-rail:rgb(5,150,105); --rr-soft:rgba(5,150,105,.10);}
        .rr-accent--amber{--rr-chipBg:rgba(245,158,11,.16); --rr-chipText:rgb(146,64,14); --rr-rail:rgb(245,158,11); --rr-soft:rgba(245,158,11,.10);}
        .rr-accent--rose{--rr-chipBg:rgba(244,63,94,.14); --rr-chipText:rgb(159,18,57); --rr-rail:rgb(244,63,94); --rr-soft:rgba(244,63,94,.10);}
        .rr-rail{position:relative;}
        .rr-rail:before{content:""; position:absolute; left:0; top:14px; bottom:14px; width:4px; border-radius:999px; background:var(--rr-rail); opacity:.95;}
        .rr-chip{display:inline-flex; align-items:center; gap:.45rem; padding:.35rem .65rem; border-radius:999px; font-weight:700; font-size:.75rem; background:var(--rr-chipBg); color:var(--rr-chipText); border:1px solid rgba(15,23,42,.10);}
        .rr-chipDot{width:.45rem; height:.45rem; border-radius:999px; background:var(--rr-rail); box-shadow:0 0 0 3px var(--rr-soft);}
        /* teacher card accent (visible on name card) */
        .teacher-pill{position:relative; overflow:hidden;}
        .teacher-pill.rr-accent{background:var(--rr-soft); border-color:rgba(15,23,42,.10);}
        .teacher-pill.rr-accent:before{
            content:"";
            position:absolute;
            left:10px;
            top:14px;
            bottom:14px;
            width:6px;
            border-radius:999px;
            background:var(--rr-rail);
            box-shadow:0 0 0 3px var(--rr-soft);
            opacity:.95;
        }
        .teacher-pill.rr-accent > div{padding-left:14px;}
        .rr-pop{transition:transform 160ms ease, box-shadow 160ms ease;}
        .rr-pop:hover{transform:translateY(-1px) scale(1.01); box-shadow:0 10px 28px rgba(2,6,23,.10);}
    </style>
</head>

<body class="rr-grain bg-slate-50 text-slate-900 relative overflow-x-hidden">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 bg-white px-3 py-2 rounded-lg shadow">
        ข้ามไปยังเนื้อหา
    </a>

    <header class="border-b border-slate-200 bg-white sticky top-0 z-50 shadow-[0_1px_0_rgba(15,23,42,.06),0_14px_40px_rgba(2,6,23,.06)]">
        <div class="max-w-6xl mx-auto px-4 py-5 flex items-center justify-between gap-4">
            <div class="min-w-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-sm ring-1 ring-white/30">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="rr-kicker text-[11px] text-slate-500">URU • Research Record</div>
                        <h1 class="text-lg sm:text-xl font-semibold truncate leading-tight">ตรวจสอบหลักสูตรและผลงานเผยแพร่</h1>
                        <p class="text-sm text-slate-600">หน้าสาธารณะสำหรับตรวจสอบคณะ/หลักสูตร อาจารย์ผู้รับผิดชอบ และผลงานเผยแพร่</p>
                    </div>
                </div>
            </div>
            <div class="shrink-0">
                <a href="<?= site_url('auth/login') ?>"
                    class="group inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-[0_4px_12px_rgba(79,70,229,0.15)] hover:bg-indigo-700 hover:shadow-[0_6px_20px_rgba(79,70,229,0.25)] hover:-translate-y-[1px] active:translate-y-[1px] focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200">
                    <span>เข้าสู่ระบบ</span>
                    <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                        <polyline points="10 17 15 12 10 7" />
                        <line x1="15" y1="12" x2="3" y2="12" />
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <main id="main" class="max-w-6xl mx-auto px-4 py-8">
        <?php if (session()->getFlashdata('error')): ?>
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 text-red-800 px-4 py-3">
                <?= esc(session()->getFlashdata('error')) ?>
            </div>
        <?php endif; ?>

        <section class="mb-6">
            <div class="rr-card rr-cardShell rounded-2xl bg-white border border-slate-200 p-5 shadow-sm relative overflow-hidden">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold">เลือกดูตามคณะและหลักสูตร</h2>
                        <p class="text-sm text-slate-600 mt-1">เลือกคณะ → เลือกหลักสูตร → กดค้นหา เพื่อดูรายชื่ออาจารย์ผู้รับผิดชอบ และผลงานเผยแพร่ (เฉพาะที่อนุมัติแล้ว)</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rr-accent rr-chip"><span class="rr-chipDot" aria-hidden="true"></span>คณะ <?= is_array($faculties) ? count($faculties) : 0 ?></span>
                        <span class="rr-accent rr-accent--sky rr-chip"><span class="rr-chipDot" aria-hidden="true"></span>ค้นหาแบบทันที</span>
                        <span class="rr-accent rr-accent--emerald rr-chip"><span class="rr-chipDot" aria-hidden="true"></span>ผลงานอนุมัติแล้ว</span>
                    </div>
                </div>

                <form id="finder" class="mt-5 grid grid-cols-1 md:grid-cols-12 gap-3 items-end" action="javascript:void(0)" aria-label="ค้นหาหลักสูตร">
                    <div class="md:col-span-5">
                        <label for="faculty" class="block text-sm font-medium text-slate-800">คณะ</label>
                        <select id="faculty"
                            class="mt-2 w-full rounded-xl border border-slate-300 bg-white/90 px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 shadow-[inset_0_1px_0_rgba(255,255,255,.8)]">
                            <option value="">เลือกคณะ</option>
                        </select>
                    </div>
                    <div class="md:col-span-5">
                        <label for="curriculum" class="block text-sm font-medium text-slate-800">หลักสูตร</label>
                        <select id="curriculum" disabled
                            class="mt-2 w-full rounded-xl border border-slate-300 bg-white/90 px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 disabled:bg-slate-50 disabled:text-slate-500 shadow-[inset_0_1px_0_rgba(255,255,255,.8)]">
                            <option value="">เลือกหลักสูตร</option>
                        </select>
                    </div>
                    <div class="md:col-span-2 flex gap-2">
                        <button id="doSearch" type="submit" disabled
                            class="w-full rounded-xl bg-indigo-600 text-white px-4 py-3 text-sm font-semibold hover:bg-indigo-700 disabled:bg-slate-300 disabled:text-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600 active:translate-y-[1px] transition-transform">
                            ค้นหา
                        </button>
                        <button id="reset" type="button"
                            class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600">
                            ล้าง
                        </button>
                    </div>
                </form>

                <div id="result" class="mt-5 hidden" aria-live="polite"></div>
            </div>
        </section>

        <section class="space-y-4">
            <details class="rounded-2xl bg-white border border-slate-200 overflow-hidden shadow-sm">
                <summary class="cursor-pointer select-none px-5 py-4 bg-slate-50 text-slate-900 font-semibold">
                    ดูรายการคณะและหลักสูตรทั้งหมด
                    <span class="ml-2 text-sm font-normal text-slate-600">(สำหรับการดูภาพรวม)</span>
                </summary>
            <?php foreach (($faculties ?? []) as $faculty): ?>
                <?php $accent = ['rr-accent', 'rr-accent rr-accent--sky', 'rr-accent rr-accent--emerald', 'rr-accent rr-accent--amber', 'rr-accent rr-accent--rose']; ?>
                <?php $aClass = $accent[((int) ($faculty['id'] ?? 0)) % count($accent)]; ?>
                <article class="rr-card rr-cardShell rr-rail <?= esc($aClass) ?> rounded-2xl bg-white border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 flex items-start justify-between gap-4 bg-slate-50/60">
                        <div class="min-w-0">
                            <h3 class="font-semibold text-slate-900 truncate"><?= esc($faculty['name'] ?? '') ?></h3>
                            <p class="text-sm text-slate-600 mt-1">
                                <span class="rr-chip"><span class="rr-chipDot" aria-hidden="true"></span><?= esc($faculty['code'] ?? '-') ?></span>
                                <span class="ml-2 rr-chip"><span class="rr-chipDot" aria-hidden="true"></span>หลักสูตร <?= isset($faculty['curriculums']) ? count($faculty['curriculums']) : 0 ?></span>
                            </p>
                        </div>
                    </div>

                    <div class="px-5 py-4">
                        <?php if (empty($faculty['curriculums'])): ?>
                            <div class="text-sm text-slate-600">ยังไม่มีข้อมูลหลักสูตร</div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <?php foreach ($faculty['curriculums'] as $c): ?>
                                    <a href="<?= site_url('public/curriculum/' . (int) ($c['id'] ?? 0)) ?>"
                                        data-curriculum-name="<?= esc(($c['name'] ?? '') . ' ' . ($c['code'] ?? '') . ' ' . ($c['degree_level'] ?? '')) ?>"
                                        class="rr-pop group rounded-xl border border-slate-200 px-4 py-3 hover:border-indigo-200 hover:bg-indigo-50/40 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="font-medium text-slate-900 group-hover:text-indigo-700 truncate">
                                                    <?= esc($c['name'] ?? '') ?>
                                                </div>
                                        <div class="mt-2 text-xs text-slate-600">
                                                    <span class="inline-flex items-center gap-2">
                                                        <span class="rr-chip">
                                                            <span class="rr-chipDot" aria-hidden="true"></span>
                                                            <?= esc($c['code'] ?? '-') ?>
                                                        </span>
                                                        <span class="rr-chip">
                                                            <span class="rr-chipDot" aria-hidden="true"></span>
                                                            <?= esc($c['degree_level'] ?? '-') ?>
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>
                                            <svg class="w-5 h-5 text-slate-300 group-hover:text-indigo-600 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M9 18l6-6-6-6" />
                                            </svg>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
            </details>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white mt-10">
        <div class="max-w-6xl mx-auto px-4 py-6 text-sm text-slate-600">
            แสดงเฉพาะข้อมูลที่เผยแพร่ต่อสาธารณะ และผลงานที่อนุมัติแล้ว (approve=1)
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
                            ];
                        }, $f['curriculums'] ?? []),
                    ];
                }, $faculties ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            ?>;

            var facultyEl = document.getElementById('faculty');
            var curEl = document.getElementById('curriculum');
            var submitEl = document.getElementById('doSearch');
            var resetEl = document.getElementById('reset');
            var form = document.getElementById('finder');
            if (!facultyEl || !curEl || !submitEl || !resetEl || !form) return;

            var byId = {};
            faculties.forEach(function (f) { byId[String(f.id)] = f; });

            // populate faculties
            faculties.forEach(function (f) {
                var opt = document.createElement('option');
                opt.value = String(f.id);
                opt.textContent = (f.name || '-') + (f.code ? (' (' + f.code + ')') : '');
                facultyEl.appendChild(opt);
            });

            var reset = function () {
                facultyEl.value = '';
                curEl.innerHTML = '<option value=\"\">เลือกหลักสูตร</option>';
                curEl.disabled = true;
                submitEl.disabled = true;
            };

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
                submitEl.disabled = true;
            };

            facultyEl.addEventListener('change', function () {
                fillCurriculums(facultyEl.value);
            });

            curEl.addEventListener('change', function () {
                submitEl.disabled = !curEl.value;
            });

            resetEl.addEventListener('click', function () {
                reset();
                facultyEl.focus();
            });

            form.addEventListener('submit', function () {
                if (!curEl.value) return;
                var result = document.getElementById('result');
                if (!result) return;
                var id = curEl.value;

                submitEl.disabled = true;
                result.className = 'mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm';
                result.setAttribute('aria-busy', 'true');
                result.innerHTML = '' +
                    '<div class="flex items-center justify-between gap-3">' +
                    '  <div class="min-w-0">' +
                    '    <div class="h-3 w-24 bg-slate-200 rounded"></div>' +
                    '    <div class="h-5 w-64 bg-slate-200 rounded mt-2"></div>' +
                    '  </div>' +
                    '  <div class="h-9 w-28 bg-slate-200 rounded-xl"></div>' +
                    '</div>' +
                    '<div class="mt-5 space-y-5">' +
                    '  <div class="rounded-2xl border border-slate-200 p-4">' +
                    '    <div class="h-4 w-40 bg-slate-200 rounded"></div>' +
                    '    <div class="mt-4 space-y-2">' +
                    '      <div class="h-12 bg-slate-100 rounded-xl"></div>' +
                    '      <div class="h-12 bg-slate-100 rounded-xl"></div>' +
                    '    </div>' +
                    '  </div>' +
                    '  <div class="rounded-2xl border border-slate-200 p-4">' +
                    '    <div class="h-4 w-48 bg-slate-200 rounded"></div>' +
                    '    <div class="mt-4 space-y-2">' +
                    '      <div class="h-16 bg-slate-100 rounded-xl"></div>' +
                    '      <div class="h-16 bg-slate-100 rounded-xl"></div>' +
                    '    </div>' +
                    '  </div>' +
                    '</div>';
                result.classList.remove('hidden');
                result.scrollIntoView({
                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                    block: 'start'
                });

                fetch(<?= json_encode(site_url('public/api/curriculum/'), JSON_UNESCAPED_SLASHES) ?> + encodeURIComponent(id), {
                    headers: { 'Accept': 'application/json' }
                }).then(function (r) {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                }).then(function (data) {
                    if (!data || !data.ok) throw new Error((data && data.message) ? data.message : 'โหลดข้อมูลไม่สำเร็จ');

                    var c = data.curriculum || {};
                    var teachers = data.teachers || [];
                    var pubs = data.publications || [];
                    var pubCount = data.publicationCount || 0;

                    var esc = function (s) {
                        return String(s || '').replace(/[&<>"']/g, function (ch) {
                            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]);
                        });
                    };

                    var accentFor = function (label) {
                        var s = String(label || '').trim().toLowerCase();
                        var h = 0;
                        for (var i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) >>> 0;
                        var pick = h % 5;
                        return pick === 1 ? 'rr-accent rr-accent--sky'
                            : pick === 2 ? 'rr-accent rr-accent--emerald'
                            : pick === 3 ? 'rr-accent rr-accent--amber'
                            : pick === 4 ? 'rr-accent rr-accent--rose'
                            : 'rr-accent';
                    };
                    var chip = function (label) {
                        if (!label) return '';
                        var cls = accentFor(label);
                        return '<span class="' + cls + ' rr-chip mr-2"><span class="rr-chipDot" aria-hidden="true"></span>' + esc(label) + '</span>';
                    };

                    var teacherHtml = teachers.length
                        ? '<div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3" id="teacherList">' + teachers.map(function (t) {
                            var thai = ((t.thai_name || '') + ' ' + (t.thai_lastname || '')).trim();
                            var en = ((t.gf_name || '') + ' ' + (t.gl_name || '')).trim();
                            var name = thai || en || (t.email || '');
                            var role = t.role || '';
                            var pubC = Number(t.publication_count || 0);
                            var badge = (role === 'chair') ? 'ประธานหลักสูตร'
                                : (role === 'coordinator') ? 'ผู้รับผิดชอบหลักสูตร'
                                : (role === 'assistant') ? 'ผู้ช่วยผู้รับผิดชอบ'
                                : 'อาจารย์';
                            return (
                                '<button type="button" class="teacher-pill w-full text-left rounded-xl border border-slate-200 px-4 py-3 hover:bg-slate-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600" data-teacher-email="' + esc(t.email || '') + '" data-teacher-name="' + esc(name) + '" aria-pressed="false">' +
                                    '<div class="flex items-start justify-between gap-3">' +
                                        '<div class="min-w-0">' +
                                            '<div class="font-medium text-slate-900 truncate">' + esc(name) + '</div>' +
                                            (t.email ? '<div class="text-xs text-slate-600 mt-1 truncate">' + esc(t.email) + '</div>' : '') +
                                            '<div class="text-xs text-slate-600 mt-2">ผลงานที่อนุมัติแล้ว: <span class="font-semibold text-slate-900">' + esc(pubC) + '</span></div>' +
                                        '</div>' +
                                        '<span class="shrink-0 rounded-lg bg-indigo-50 text-indigo-800 px-2 py-1 text-xs font-semibold">' + esc(badge) + '</span>' +
                                    '</div>' +
                                '</button>'
                            );
                        }).join('') + '</div>'
                        : '<div class="mt-3 text-sm text-slate-600">ยังไม่มีข้อมูลอาจารย์ผู้รับผิดชอบ</div>';

                    var pubHtml = pubs.length
                        ? '<div class="mt-3 space-y-3" id="pubList">' + pubs.map(function (p) {
                            var year = p.publication_year || '';
                            var type = p.publication_type || '';
                            var doi = (p.doi || '').trim();
                            var authors = (p.authors || '').trim();
                            var creator = (p.created_by_name || '').trim() || (p.created_by_email || '');
                            var source = p.source || '';
                            var authorEmails = (p.author_emails || '').trim();
                            return (
                                '<article class="pub-item rounded-xl border border-slate-200 px-4 py-3 hover:bg-slate-50 transition-colors" data-author-emails="' + esc(authorEmails) + '">' +
                                    '<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">' +
                                        '<div class="min-w-0">' +
                                            '<div class="font-medium text-slate-900">' + esc(p.title || '') + '</div>' +
                                            (authors ? '<div class="pub-authors-text text-sm text-slate-700 mt-1">' + esc(authors) + '</div>' : '') +
                                            (creator ? '<div class="text-xs text-slate-600 mt-1">บันทึกโดย: <span class="font-medium text-slate-800">' + esc(creator) + '</span></div>' : '') +
                                            '<div class="text-xs text-slate-600 mt-2">' +
                                                chip(type) +
                                                (year ? chip('ปี ' + year) : '') +
                                                chip(source) +
                                            '</div>' +
                                        '</div>' +
                                        (doi ? '<div class="shrink-0"><a class="text-sm text-indigo-700 hover:text-indigo-900 underline" href="' + esc('https://doi.org/' + doi) + '" target="_blank" rel="noopener">DOI</a></div>' : '') +
                                    '</div>' +
                                '</article>'
                            );
                        }).join('') + '</div>'
                        : '<div class="mt-3 text-sm text-slate-600">ยังไม่พบผลงานเผยแพร่ที่อนุมัติสำหรับหลักสูตรนี้</div>';

                    var openUrl = <?= json_encode(site_url('public/curriculum/'), JSON_UNESCAPED_SLASHES) ?> + encodeURIComponent(c.id || id);
                    result.innerHTML =
                        '<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">' +
                            '<div class="min-w-0">' +
                                '<div class="text-sm text-slate-600">ผลการค้นหา</div>' +
                                '<div class="text-lg font-semibold text-slate-900 truncate">' + esc(c.name || '') + '</div>' +
                                '<div class="text-sm text-slate-600 mt-1">คณะ: <span class="font-medium text-slate-800">' + esc(c.faculty_name || '') + '</span></div>' +
                            '</div>' +
                            '<div class="shrink-0 flex items-center gap-2">' +
                                '<a class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="' + esc(openUrl) + '">เปิดหน้าเต็ม</a>' +
                            '</div>' +
                        '</div>' +
                        '<div class="mt-5 space-y-5">' +
                            '<section class="rounded-2xl border border-slate-200 p-4">' +
                                '<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">' +
                                '  <div>' +
                                '    <h3 class="text-base font-semibold">รายชื่ออาจารย์</h3>' +
                                '    <div id="teacherFilterHint" class="hidden text-sm text-slate-600 mt-1">กำลังกรองผลงานของ <span class="font-semibold text-slate-900" id="teacherFilterName"></span></div>' +
                                '  </div>' +
                                '  <div class="flex items-center gap-3 text-sm text-slate-600">' +
                                '    <span>พบ <span class="font-semibold text-slate-900">' + esc(teachers.length) + '</span> คน</span>' +
                                '    <button type="button" id="clearTeacherFilter" class="hidden rounded-xl border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600">แสดงทั้งหมด</button>' +
                                '  </div>' +
                                '</div>' +
                                teacherHtml +
                            '</section>' +
                            '<section class="rounded-2xl border border-slate-200 p-4">' +
                                '<div class="flex items-center justify-between"><h3 class="text-base font-semibold">ผลงานเผยแพร่ (อนุมัติแล้ว)</h3><div class="text-sm text-slate-600"><span id="pubCountAllWrap">ทั้งหมด <span class="font-semibold text-slate-900" id="pubCountAll">' + esc(pubCount) + '</span></span><span id="pubCountFilteredWrap" class="hidden">พบ <span class="font-semibold text-slate-900" id="pubCountFiltered">0</span></span></div></div>' +
                                pubHtml +
                            '</section>' +
                        '</div>';
                    // wire teacher → filter publications (AJAX result only)
                    setTimeout(function () {
                        var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        var tBtns = Array.prototype.slice.call(result.querySelectorAll('.teacher-pill'));
                        var pItems = Array.prototype.slice.call(result.querySelectorAll('.pub-item'));
                        var hint = result.querySelector('#teacherFilterHint');
                        var hintName = result.querySelector('#teacherFilterName');
                        var clearBtn = result.querySelector('#clearTeacherFilter');
                        var countAllWrap = result.querySelector('#pubCountAllWrap');
                        var countFilteredWrap = result.querySelector('#pubCountFilteredWrap');
                        var countFilteredEl = result.querySelector('#pubCountFiltered');
                        if (!tBtns.length || !pItems.length || !hint || !hintName || !clearBtn || !countAllWrap || !countFilteredWrap || !countFilteredEl) return;

                        var activeEmail = '';
                        var palette = [
                            'rr-accent',
                            'rr-accent rr-accent--sky',
                            'rr-accent rr-accent--emerald',
                            'rr-accent rr-accent--amber',
                            'rr-accent rr-accent--rose'
                        ];
                        var teacherMap = {};
                        tBtns.forEach(function (b, idx) {
                            var email = String(b.getAttribute('data-teacher-email') || '');
                            var name = String(b.getAttribute('data-teacher-name') || '');
                            if (!email) return;
                            var cls = palette[idx % palette.length];
                            teacherMap[email] = { name: name || email, cls: cls };
                            b.classList.add.apply(b.classList, cls.split(' '));
                        });

                        // decorate publications with chips for matching teachers
                        pItems.forEach(function (it) {
                            var list = String(it.getAttribute('data-author-emails') || '').split(',').map(function (x) { return String(x || '').trim(); }).filter(Boolean);
                            var found = [];
                            list.forEach(function (email) { if (teacherMap[email]) found.push(email); });
                            if (!found.length) return;
                            var authorsText = it.querySelector('.pub-authors-text');
                            if (authorsText) authorsText.classList.add('hidden');
                            var row = document.createElement('div');
                            row.className = 'pub-teacher-chips mt-2 flex flex-wrap gap-2';
                            row.innerHTML = found.map(function (email) {
                                var t = teacherMap[email];
                                return '<span class="' + t.cls + ' rr-chip"><span class="rr-chipDot" aria-hidden="true"></span>' + esc(t.name || email) + '</span>';
                            }).join('');
                            var titleBlock = it.querySelector('.min-w-0');
                            if (titleBlock) titleBlock.appendChild(row);
                        });

                        var apply = function (email, name) {
                            activeEmail = email || '';
                            tBtns.forEach(function (b) {
                                var on = (String(b.getAttribute('data-teacher-email') || '') === activeEmail) && activeEmail !== '';
                                b.setAttribute('aria-pressed', on ? 'true' : 'false');
                                b.classList.toggle('ring-2', on);
                                b.classList.toggle('ring-indigo-600', on);
                                b.classList.toggle('bg-indigo-50/60', on);
                                b.classList.toggle('border-indigo-200', on);
                            });

                            var shown = 0;
                            pItems.forEach(function (it) {
                                var list = String(it.getAttribute('data-author-emails') || '');
                                var has = activeEmail && ((',' + list.replace(/\s+/g, '') + ',').indexOf(',' + String(activeEmail).replace(/\s+/g, '') + ',') !== -1);
                                var show = !activeEmail || has;
                                if (show) shown++;
                                if (!reduced) it.style.transition = 'opacity 180ms ease, transform 180ms ease';
                                if (show) {
                                    it.classList.remove('hidden');
                                    if (!reduced) { it.style.opacity = '1'; it.style.transform = 'translateY(0px)'; }
                                } else {
                                    if (!reduced) {
                                        it.style.opacity = '0'; it.style.transform = 'translateY(6px)';
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

                        tBtns.forEach(function (b) {
                            b.addEventListener('click', function () {
                                var email = String(b.getAttribute('data-teacher-email') || '');
                                var name = String(b.getAttribute('data-teacher-name') || '');
                                apply(activeEmail === email ? '' : email, name);
                                var pubList = result.querySelector('#pubList');
                                if (pubList) pubList.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
                            });
                        });
                        clearBtn.addEventListener('click', function () { apply('', ''); });
                    }, 0);
                    result.removeAttribute('aria-busy');
                }).catch(function (e) {
                    result.className = 'mt-5 rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800 shadow-sm';
                    result.removeAttribute('aria-busy');
                    result.innerHTML = '' +
                        '<div class="text-sm font-medium">โหลดข้อมูลไม่สำเร็จ</div>' +
                        '<div class="text-sm mt-1">' + esc((e && e.message) ? e.message : '') + '</div>' +
                        '<div class="mt-4 flex flex-wrap gap-2">' +
                        '  <button type="button" id="retry" class="rounded-xl bg-red-700 text-white px-4 py-2 text-sm font-semibold hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-700">ลองใหม่</button>' +
                        '  <a class="rounded-xl border border-red-200 bg-white/60 px-4 py-2 text-sm font-medium text-red-800 hover:bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-700" href="<?= site_url('public') ?>">กลับหน้าแรก</a>' +
                        '</div>';
                    var retryBtn = document.getElementById('retry');
                    if (retryBtn) retryBtn.addEventListener('click', function () { form.dispatchEvent(new Event('submit')); });
                }).finally(function () {
                    submitEl.disabled = !curEl.value;
                });
            });

            reset();
        })();
    </script>
</body>

</html>

