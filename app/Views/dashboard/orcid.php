<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'ORCID Sync') ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #f8f9fa; }
        .section-title { font-size: 14px; text-transform: uppercase; letter-spacing: 2px; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 16px; }
        .spinner { width: 16px; height: 16px; border: 2px solid #e5e7eb; border-top-color: #374151; border-radius: 50%; animation: spin 0.7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>

<body class="min-h-screen py-8">
    <!-- Quick Access Bar -->
    <div class="max-w-5xl mx-auto px-4 mb-6">
        <div class="flex items-center justify-between bg-white rounded-xl shadow-sm px-4 py-3 border border-gray-100">
            <div class="flex items-center gap-4">
                <a href="<?= site_url('dashboard') ?>" class="text-gray-500 hover:text-gray-700 text-sm">← กลับ Dashboard</a>
                <span class="text-gray-300">|</span>
                <span class="font-semibold text-gray-700">🔗 ORCID Sync</span>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= site_url('dashboard/cv') ?>" class="px-3 py-1.5 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50">📄 ดู CV</a>
                <a href="<?= site_url('dashboard/cv-manage') ?>" class="px-3 py-1.5 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50">📝 จัดการ CV</a>
                <a href="<?= site_url('dashboard/settings') ?>" class="px-3 py-1.5 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50">⚙️ ตั้งค่า</a>
            </div>
        </div>
    </div>

    <!-- CV Style Layout -->
    <div class="max-w-5xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Header -->
            <div class="p-6 border-b border-gray-100">
                <h1 class="text-2xl font-light text-gray-800 tracking-wide">ORCID SYNCHRONIZATION</h1>
                <p class="text-sm text-gray-500">นำเข้าผลงานวิจัยจาก ORCID อัตโนมัติ</p>
            </div>

            <!-- Two Column Layout -->
            <div class="flex">
                <!-- Left Column - Status -->
                <div class="w-1/3 p-6 bg-gray-50 border-r border-gray-100">
                    <h3 class="section-title">Current Status</h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">ORCID iD</p>
                            <p class="font-medium text-gray-800">
                                <?= !empty($user_profile['orcid_id']) ? esc($user_profile['orcid_id']) : '—' ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Publications</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $publications_count ?? 0 ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Last Sync</p>
                            <p class="font-medium text-gray-800">
                                <?= !empty($user_profile['orcid_synced_at']) ? date('d M Y', strtotime($user_profile['orcid_synced_at'])) : 'Never' ?>
                            </p>
                        </div>
                    </div>

                    <div class="mt-8">
                        <h3 class="section-title">About ORCID</h3>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            ORCID provides a persistent digital identifier distinguishing you from other researchers and supports automated linkages between you and your professional activities.
                        </p>
                    </div>
                </div>

                <!-- Right Column - Sync Form -->
                <div class="w-2/3 p-6">
                    <h3 class="section-title">Sync Publications</h3>
                    
                    <!-- ORCID Input -->
                    <div class="mb-6">
                        <label class="block text-xs text-gray-500 uppercase mb-2">ORCID iD</label>
                        <input type="text" id="orcidInput" 
                            class="w-full text-center font-mono text-xl tracking-widest border border-gray-200 rounded-lg px-4 py-4 focus:border-gray-400 outline-none"
                            placeholder="0000-0001-2345-6789"
                            pattern="[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[0-9X]"
                            value="<?= esc($user_profile['orcid_id'] ?? '') ?>">
                        <p class="text-xs text-gray-400 mt-2">Format: 0000-0001-2345-6789</p>
                    </div>
                    
                    <!-- Sync Button -->
                    <div class="mb-6">
                        <button onclick="syncOrcid(true)" id="syncDetailedBtn" class="w-full flex items-center justify-center gap-2 bg-gray-800 hover:bg-gray-900 text-white py-3 rounded-lg font-medium transition">
                            <span id="syncDetailedText">🚀 Sync ORCID Data</span>
                            <span id="syncDetailedSpinner" class="spinner hidden"></span>
                        </button>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <p class="text-sm text-gray-600">
                            ดึงข้อมูลละเอียดจาก ORCID รวมถึง Publications, Education และ Employment
                        </p>
                    </div>

                    <!-- Progress Box -->
                    <div id="progressBox" class="hidden bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="spinner"></div>
                            <span id="progressText" class="text-sm text-gray-700">กำลังดำเนินการ...</span>
                        </div>
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div id="progressBar" class="h-full bg-gray-600 transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Response Data Display -->
                    <div id="responseDataBox" class="hidden mt-6 bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">📋 ORCID Data Retrieved</h4>
                        <div id="responseDataContent" class="text-sm text-gray-600 space-y-2"></div>
                        <details class="mt-3">
                            <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">🔍 Raw JSON Response</summary>
                            <pre id="rawJsonDisplay" class="mt-2 bg-gray-800 text-green-400 p-3 rounded text-xs overflow-x-auto max-h-64 overflow-y-auto"></pre>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('orcidInput').addEventListener('input', function(e) {
            let v = e.target.value.replace(/[^0-9X]/gi, '').toUpperCase();
            let f = '';
            for (let i = 0; i < v.length && i < 16; i++) {
                if (i > 0 && i % 4 === 0) f += '-';
                f += v[i];
            }
            e.target.value = f;
        });

        async function syncOrcid(detailed) {
            const orcidId = document.getElementById('orcidInput').value.trim();
            if (!/^[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[0-9X]$/.test(orcidId)) {
                Swal.fire({ icon: 'error', title: 'รูปแบบไม่ถูกต้อง', text: 'ตัวอย่าง: 0000-0001-2345-6789' });
                return;
            }

            const progress = document.getElementById('progressBox');
            const bar = document.getElementById('progressBar');
            const text = document.getElementById('progressText');
            const responseBox = document.getElementById('responseDataBox');
            const responseContent = document.getElementById('responseDataContent');
            const rawJsonDisplay = document.getElementById('rawJsonDisplay');
            
            progress.classList.remove('hidden');
            responseBox.classList.add('hidden');
            bar.style.width = '20%';
            text.textContent = 'กำลังเชื่อมต่อ ORCID...';

            const btn = document.getElementById('syncDetailedBtn');
            btn.disabled = true;

            try {
                const endpoint = '<?= site_url('dashboard/sync-orcid-doi') ?>';
                bar.style.width = '50%';
                text.textContent = 'กำลังดึงข้อมูลจาก ORCID...';

                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ orcid_id: orcidId })
                });
                const result = await res.json();
                
                // Log full response to console
                console.log('ORCID API Response:', result);
                
                // Show raw JSON
                rawJsonDisplay.textContent = JSON.stringify(result, null, 2);
                
                if (result.success) {
                    bar.style.width = '70%';
                    text.textContent = 'กำลังประมวลผลข้อมูล CV...';
                    
                    // Display parsed data
                    const data = result.data || {};
                    let html = `
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div><strong>Name:</strong> ${data.name || 'N/A'}</div>
                            <div><strong>Affiliation:</strong> ${data.affiliation || 'N/A'}</div>
                            <div><strong>Works Found:</strong> ${data.works_count || 0}</div>
                            <div><strong>Imported:</strong> ${data.publications_imported || 0}</div>
                            <div><strong>Skipped:</strong> ${data.publications_skipped || 0}</div>
                            <div><strong>Errors:</strong> ${data.publications_errors || 0}</div>
                        </div>
                    `;
                    
                    // Show education data
                    const educationList = data.education || [];
                    if (educationList.length > 0) {
                        html += `<div class="mt-3 pt-3 border-t border-gray-200">
                            <strong class="text-green-600">🎓 Education (${educationList.length})</strong>
                            <ul class="mt-1 ml-4 list-disc">`;
                        educationList.forEach(edu => {
                            html += `<li>${edu.role_title || edu.title || 'Degree'} - ${edu.organization || edu.org_name || 'Unknown'} (${edu.start_year || '?'} - ${edu.end_year || 'Present'})</li>`;
                        });
                        html += `</ul></div>`;
                    }
                    
                    // Show employment data
                    const employmentList = data.employments || [];
                    if (employmentList.length > 0) {
                        html += `<div class="mt-3 pt-3 border-t border-gray-200">
                            <strong class="text-blue-600">💼 Employment (${employmentList.length})</strong>
                            <ul class="mt-1 ml-4 list-disc">`;
                        employmentList.forEach(emp => {
                            html += `<li>${emp.role_title || emp.title || 'Position'} - ${emp.organization || emp.org_name || 'Unknown'} (${emp.start_year || '?'} - ${emp.end_year || 'Present'})</li>`;
                        });
                        html += `</ul></div>`;
                    }
                    
                    responseContent.innerHTML = html;
                    responseBox.classList.remove('hidden');
                    
                    // Save education and employment data to CV sections
                    bar.style.width = '85%';
                    text.textContent = 'กำลังบันทึกข้อมูล CV...';
                    
                    if (educationList.length > 0 || employmentList.length > 0) {
                        try {
                            console.log('Saving CV data:', { education: educationList, employment: employmentList });
                            const cvRes = await fetch('<?= site_url('dashboard/save-orcid-cv') ?>', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                body: JSON.stringify({
                                    education: educationList,
                                    employment: employmentList
                                })
                            });
                            const cvResult = await cvRes.json();
                            console.log('CV save result:', cvResult);
                            
                            if (cvResult.success) {
                                html += `<div class="mt-3 pt-3 border-t border-gray-200 text-green-600">
                                    ✅ CV saved: ${cvResult.education_count || 0} education, ${cvResult.employment_count || 0} employment
                                </div>`;
                                responseContent.innerHTML = html;
                            }
                        } catch (cvError) {
                            console.error('CV save error:', cvError);
                        }
                    }
                    
                    bar.style.width = '100%';
                    text.textContent = '✅ Sync สำเร็จ!';
                    
                    setTimeout(() => {
                        progress.classList.add('hidden');
                        Swal.fire({
                            icon: 'success',
                            title: 'Sync Complete',
                            html: `
                                <p>Publications: <strong>${data.publications_imported || 0}</strong> imported, <strong>${data.publications_skipped || 0}</strong> skipped</p>
                                <p>Education: <strong>${educationList.length}</strong> entries</p>
                                <p>Employment: <strong>${employmentList.length}</strong> entries</p>
                            `,
                            confirmButtonText: 'ดู CV',
                            showCancelButton: true,
                            cancelButtonText: 'ปิด'
                        }).then((r) => {
                            if (r.isConfirmed) {
                                window.location.href = '<?= site_url('dashboard/cv-manage') ?>';
                            }
                        });
                    }, 500);
                } else {
                    bar.style.width = '100%';
                    text.textContent = '❌ Failed';
                    responseContent.innerHTML = `<div class="text-red-500">Error: ${result.message}</div>`;
                    responseBox.classList.remove('hidden');
                    setTimeout(() => {
                        progress.classList.add('hidden');
                        Swal.fire({ icon: 'error', title: 'Error', text: result.message });
                    }, 500);
                }
            } catch (e) {
                console.error('Sync error:', e);
                text.textContent = '❌ Connection failed';
                progress.classList.add('hidden');
                Swal.fire({ icon: 'error', title: 'Error', text: 'ไม่สามารถเชื่อมต่อได้: ' + e.message });
            } finally {
                btn.disabled = false;
            }
        }
    </script>
</body>

</html>
