<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'ทดสอบ Curriculum API') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --bg: #f1f5f9;
            --card: #ffffff;
            --accent: #1d4ed8;
            --accent-soft: #dbeafe;
            --ok: #15803d;
            --bad: #b91c1c;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Sarabun', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1200px 500px at 10% -10%, #dbeafe 0%, transparent 55%),
                radial-gradient(900px 400px at 100% 0%, #e2e8f0 0%, transparent 50%),
                var(--bg);
        }
        .top {
            background: #1e3a5f;
            color: #fff;
            padding: 12px 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px 18px;
            align-items: center;
            justify-content: space-between;
        }
        .top a { color: #93c5fd; text-decoration: none; }
        .top a:hover { text-decoration: underline; }
        .wrap {
            max-width: 960px;
            margin: 0 auto;
            padding: 28px 16px 48px;
        }
        h1 {
            margin: 0 0 6px;
            font-size: 1.65rem;
            letter-spacing: -0.02em;
        }
        .sub { color: var(--muted); margin: 0 0 22px; font-size: 0.95rem; }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.05);
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        @media (max-width: 720px) {
            .grid { grid-template-columns: 1fr; }
        }
        .full { grid-column: 1 / -1; }
        label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 5px;
        }
        input, select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            font: inherit;
            background: #fff;
        }
        input:focus, select:focus {
            outline: 2px solid var(--accent-soft);
            border-color: var(--accent);
        }
        .hint { font-size: 0.78rem; color: var(--muted); margin-top: 4px; }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
            align-items: center;
        }
        button {
            font: inherit;
            font-weight: 600;
            border: 0;
            border-radius: 9px;
            padding: 10px 16px;
            cursor: pointer;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: #1e40af; }
        .btn-primary:disabled { opacity: 0.55; cursor: wait; }
        .btn-ghost { background: #e2e8f0; color: #0f172a; }
        .status {
            font-size: 0.9rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 999px;
        }
        .status.ok { background: #dcfce7; color: var(--ok); }
        .status.bad { background: #fee2e2; color: var(--bad); }
        .status.wait { background: #e2e8f0; color: #475569; }
        .meta {
            margin-top: 14px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.75rem;
            color: #475569;
            word-break: break-all;
            background: #f8fafc;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 10px 12px;
        }
        pre {
            margin: 16px 0 0;
            padding: 14px;
            border-radius: 10px;
            background: #0f172a;
            color: #e2e8f0;
            overflow: auto;
            max-height: 62vh;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.78rem;
            line-height: 1.45;
        }
        .examples {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        .chip {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 0.8rem;
            cursor: pointer;
        }
        .chip:hover { border-color: var(--accent); background: var(--accent-soft); }
    </style>
</head>
<body>
<div class="top">
    <div><strong>Research Record</strong> — ทดสอบ Curriculum Detail API</div>
    <div>
        <a href="<?= esc(site_url('docs')) ?>">Swagger /docs</a>
        ·
        <a href="<?= esc(site_url('api/openapi.json')) ?>">openapi.json</a>
    </div>
</div>

<div class="wrap">
    <h1>ทดสอบ API หลักสูตร</h1>
    <p class="sub">หน้านี้ใช้ <code>URLSearchParams</code> encode ชื่อไทยให้อัตโนมัติ — ไม่ต้องกังวลเรื่อง percent-encoding แบบ curl</p>

    <div class="card">
        <form id="apiForm" class="grid" autocomplete="off">
            <div class="full">
                <label for="token">X-Curriculum-Api-Token</label>
                <input id="token" type="password" placeholder="วาง CURRICULUM_API_TOKEN จาก .env" required>
                <div class="hint">เก็บใน localStorage ของเบราว์เซอร์นี้เท่านั้น · ไม่ส่งขึ้นเซิร์ฟเวอร์ของหน้านี้</div>
            </div>

            <div class="full">
                <label for="curriculum_name">curriculum_name *</label>
                <input id="curriculum_name" type="text" value="การศึกษาปฐมวัย" required>
                <div class="examples" id="examples"></div>
            </div>

            <div>
                <label for="curriculum_code">curriculum_code (optional)</label>
                <input id="curriculum_code" type="text" placeholder="เช่น ED02">
            </div>
            <div>
                <label for="degree_level">degree_level (optional)</label>
                <select id="degree_level">
                    <option value="" selected>— ไม่ระบุ —</option>
                    <option value="bachelor">bachelor / ป.ตรี</option>
                    <option value="master">master / ป.โท</option>
                    <option value="doctoral">doctoral / ป.เอก</option>
                </select>
                <div class="hint">ถ้าระบุผิดระดับ (เช่น master ทั้งที่เป็น ป.ตรี) จะได้ CURRICULUM_NOT_FOUND</div>
            </div>
            <div>
                <label for="faculty_id">faculty_id (optional)</label>
                <input id="faculty_id" type="number" min="1" placeholder="เช่น 14 = คณะครุศาสตร์">
                <div class="hint">การศึกษาปฐมวัย อยู่คณะ id 14 — อย่าใส่ 1 ถ้าไม่แน่ใจ</div>
            </div>
            <div>
                <label for="endpoint">API endpoint</label>
                <input id="endpoint" type="text" value="<?= esc($apiUrl) ?>" readonly>
            </div>

            <div class="full actions">
                <button type="submit" class="btn-primary" id="runBtn">เรียก API</button>
                <button type="button" class="btn-ghost" id="clearBtn">ล้างผลลัพธ์</button>
                <span class="status wait" id="status">พร้อมทดสอบ</span>
            </div>
        </form>

        <div class="meta" id="requestUrl">request URL จะแสดงหลังกดเรียก</div>
        <pre id="output">{}</pre>
    </div>
</div>

<script>
(function () {
    const TOKEN_KEY = 'rr_curriculum_api_token';
    const examples = [
        'การศึกษาปฐมวัย',
        'วิทยาการคอมพิวเตอร์',
        'วิทยาศาสตร์และนวัตกรรมเพื่อการเรียนรู้',
        'อาหารและโภชนาการ',
    ];

    const $ = (id) => document.getElementById(id);
    const tokenEl = $('token');
    const nameEl = $('curriculum_name');
    const codeEl = $('curriculum_code');
    const degreeEl = $('degree_level');
    const facultyEl = $('faculty_id');
    const endpointEl = $('endpoint');
    const statusEl = $('status');
    const outEl = $('output');
    const urlEl = $('requestUrl');
    const runBtn = $('runBtn');

    tokenEl.value = localStorage.getItem(TOKEN_KEY) || '';

    const exBox = $('examples');
    examples.forEach((name) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'chip';
        b.textContent = name;
        b.addEventListener('click', () => { nameEl.value = name; });
        exBox.appendChild(b);
    });

    function setStatus(text, kind) {
        statusEl.textContent = text;
        statusEl.className = 'status ' + kind;
    }

    $('clearBtn').addEventListener('click', () => {
        outEl.textContent = '{}';
        urlEl.textContent = 'request URL จะแสดงหลังกดเรียก';
        setStatus('พร้อมทดสอบ', 'wait');
    });

    $('apiForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const token = tokenEl.value.trim();
        if (!token) {
            setStatus('ต้องใส่ token', 'bad');
            return;
        }
        localStorage.setItem(TOKEN_KEY, token);

        const params = new URLSearchParams();
        params.set('curriculum_name', nameEl.value.trim());
        if (codeEl.value.trim()) params.set('curriculum_code', codeEl.value.trim());
        if (degreeEl.value) params.set('degree_level', degreeEl.value);
        if (facultyEl.value.trim()) params.set('faculty_id', facultyEl.value.trim());

        const url = endpointEl.value.replace(/\?.*$/, '') + '?' + params.toString();
        urlEl.textContent = 'GET ' + url;
        setStatus('กำลังเรียก…', 'wait');
        runBtn.disabled = true;
        outEl.textContent = '';

        try {
            const res = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Curriculum-Api-Token': token,
                },
            });
            const text = await res.text();
            let body;
            try {
                body = JSON.parse(text);
            } catch (_) {
                body = { raw: text };
            }
            outEl.textContent = JSON.stringify(body, null, 2);
            if (res.ok && body.success) {
                const cname = body.curriculum?.name || '';
                setStatus(res.status + ' OK · ' + cname, 'ok');
            } else {
                let msg = res.status + ' · ' + (body.error || body.message || 'failed');
                if (res.status === 401) {
                    msg += ' — ใช้ token จาก C:\\inetpub\\ResearchRecord\\.env บน win-kc (ไม่ใช่ค่าเก่าใน .env.win-kc)';
                }
                setStatus(msg, 'bad');
            }
        } catch (err) {
            outEl.textContent = String(err);
            setStatus('network error', 'bad');
        } finally {
            runBtn.disabled = false;
        }
    });
})();
</script>
</body>
</html>
