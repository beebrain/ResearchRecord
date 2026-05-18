/**
 * Publication Summary Management
 * Tracks publication status for curriculums
 */

let allData = [];
let filteredData = [];
let selectedFacultyId = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    loadSummaryData();
    setupEventListeners();
});

/**
 * Load summary data from API
 */
async function loadSummaryData() {
    try {
        const response = await fetch(`${BASE_URL}/index.php/admin/publications/summary-data`);
        const result = await response.json();

        console.log('Summary data:', result);

        if (result.success) {
            allData = result.data;
            filteredData = [...allData];

            renderStats();
            renderFacultyList();
            renderCurriculumCards();
        } else {
            showError('ไม่สามารถโหลดข้อมูลได้: ' + result.message);
        }
    } catch (error) {
        console.error('Load error:', error);
        showError('เกิดข้อผิดพลาดในการโหลดข้อมูล');
    }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Search
    let searchTimeout;
    document.getElementById('search-input').addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applySearchFilter();
        }, 300);
    });

    // ESC key to close modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const pubModal = document.getElementById('publication-detail-modal');
            if (pubModal && !pubModal.classList.contains('hidden')) {
                closePublicationDetailModal();
            } else {
                const modal = document.getElementById('detail-modal');
                if (modal && !modal.classList.contains('hidden')) {
                    closeDetailModal();
                }
            }
        }
    });
}

/**
 * Apply search filter
 */
function applySearchFilter() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();

    if (!searchTerm) {
        // Reset to selected faculty or all
        filterByFaculty(selectedFacultyId);
        return;
    }

    // Get base data (selected faculty or all)
    const baseData = selectedFacultyId === null
        ? [...allData]
        : allData.filter(f => f.id === selectedFacultyId);

    // Apply search
    filteredData = baseData.map(faculty => {
        const filteredCurriculums = faculty.curriculums.filter(curriculum => {
            return curriculum.name.toLowerCase().includes(searchTerm);
        });

        return {
            ...faculty,
            curriculums: filteredCurriculums
        };
    }).filter(faculty => faculty.curriculums.length > 0);

    renderCurriculumCards();
}

/**
 * Render summary stats
 */
function renderStats() {
    let safeCount = 0;
    let warningCount = 0;
    let dangerCount = 0;

    allData.forEach(faculty => {
        faculty.curriculums.forEach(curr => {
            if (curr.status === 'safe') safeCount++;
            else if (curr.status === 'warning') warningCount++;
            else if (curr.status === 'danger') dangerCount++;
        });
    });

    document.getElementById('safe-curriculums').textContent = safeCount;
    document.getElementById('warning-curriculums').textContent = warningCount;
    document.getElementById('danger-curriculums').textContent = dangerCount;
}

/**
 * Render faculty list with status counts
 */
function renderFacultyList() {
    const container = document.getElementById('faculty-list');
    container.innerHTML = '';

    // Add "All Faculties" option
    const allItem = createFacultyListItem({
        id: null,
        name: 'ทุกคณะ',
        curriculums: allData.flatMap(f => f.curriculums)
    });
    container.appendChild(allItem);

    // Add individual faculties
    allData.forEach(faculty => {
        const item = createFacultyListItem(faculty);
        container.appendChild(item);
    });
}

function createFacultyListItem(faculty) {
    const item = document.createElement('div');
    const isSelected = selectedFacultyId === faculty.id;

    item.className = `p-3 rounded-lg cursor-pointer transition-all ${
        isSelected
            ? 'bg-blue-50 border-2 border-blue-500'
            : 'bg-gray-50 border border-gray-200 hover:bg-gray-100'
    }`;

    // Count statuses
    let dangerCount = 0;
    let warningCount = 0;
    let safeCount = 0;

    faculty.curriculums.forEach(curr => {
        if (curr.status === 'danger') dangerCount++;
        else if (curr.status === 'warning') warningCount++;
        else if (curr.status === 'safe') safeCount++;
    });

    const totalCount = dangerCount + warningCount + safeCount;

    item.innerHTML = `
        <div class="text-sm">
            <div class="font-medium text-gray-900 mb-2 truncate" title="${escapeHtml(faculty.name)}">
                ${escapeHtml(faculty.name)}
            </div>
            <div class="space-y-1 text-xs">
                ${dangerCount > 0 ? `<div class="text-red-600">ไม่ผ่านเงื่อนไข <span class="font-semibold">(${dangerCount})</span></div>` : ''}
                ${warningCount > 0 ? `<div class="text-yellow-600">ใกล้หมดอายุ <span class="font-semibold">(${warningCount})</span></div>` : ''}
                ${safeCount > 0 ? `<div class="text-green-600">ปลอดภัย <span class="font-semibold">(${safeCount})</span></div>` : ''}
            </div>
        </div>
    `;

    item.onclick = () => {
        selectedFacultyId = faculty.id;
        filterByFaculty(faculty.id);
        renderFacultyList(); // Re-render to update selection
    };

    return item;
}

function filterByFaculty(facultyId) {
    // Clear search
    document.getElementById('search-input').value = '';

    if (facultyId === null) {
        filteredData = [...allData];
    } else {
        filteredData = allData.filter(f => f.id === facultyId);
    }
    renderCurriculumCards();
}

/**
 * Render curriculum cards
 */
function renderCurriculumCards() {
    const grid = document.getElementById('curriculum-grid');
    grid.innerHTML = '';

    let hasCards = false;

    filteredData.forEach(faculty => {
        faculty.curriculums.forEach(curriculum => {
            const card = createCurriculumCard(curriculum, faculty.name);
            grid.appendChild(card);
            hasCards = true;
        });
    });

    if (!hasCards) {
        grid.innerHTML = '<div class="w-full text-center py-12 text-gray-500">ไม่พบหลักสูตรที่ตรงกับเงื่อนไข</div>';
    }
}

/**
 * Create curriculum card element
 */
function createCurriculumCard(curriculum, facultyName) {
    const card = document.createElement('div');
    card.className = 'curriculum-card bg-white rounded-lg shadow-md p-4 cursor-pointer';
    card.onclick = () => showCurriculumDetail(curriculum, facultyName);

    // Count users by status
    let usersAtRisk = 0;
    let usersMeetRequirement = 0;
    let usersNotMeet = 0;

    if (curriculum.users_with_publications) {
        curriculum.users_with_publications.forEach(user => {
            if (user.meets_requirement) {
                usersMeetRequirement++;
                if (user.will_expire_soon) {
                    usersAtRisk++;
                }
            } else {
                usersNotMeet++;
            }
        });
    }

    // Generate dynamic status label
    let statusLabel = '';
    let statusIcon = '';
    let bgClass = '';
    let textClass = '';

    if (curriculum.user_count === 0) {
        statusIcon = '✕';
        statusLabel = 'ต้องมีอาจารย์ผู้รับผิดชอบหลักสูตร';
        bgClass = 'bg-red-100';
        textClass = 'text-red-800';
    } else if (curriculum.status === 'safe') {
        statusIcon = '✓';
        statusLabel = `อาจารย์ผู้รับผิดชอบมีคุณสมบัติครบ`;
        bgClass = 'bg-green-100';
        textClass = 'text-green-800';
    } else if (curriculum.status === 'warning') {
        statusIcon = '⚠';
        statusLabel = `อาจารย์ ${usersAtRisk} คน กำลังหมดอายุปีหน้า`;
        bgClass = 'bg-yellow-100';
        textClass = 'text-yellow-800';
    } else { // danger
        statusIcon = '✕';
        if (usersNotMeet > 0) {
            statusLabel = `อาจารย์ ${usersNotMeet} คน ไม่ผ่านเงื่อนไข`;
        } else {
            statusLabel = 'ไม่มีผลงาน';
        }
        bgClass = 'bg-red-100';
        textClass = 'text-red-800';
    }

    const degreeLevelText = {
        'bachelor': 'ป.ตรี',
        'master': 'ป.โท',
        'doctoral': 'ป.เอก'
    };
    const degreeText = degreeLevelText[curriculum.degree_level] || curriculum.degree_level;

    card.innerHTML = `
        <div class="flex justify-between items-start mb-3">
            <div class="flex-1 min-w-0 pr-2">
                <h3 class="text-base font-semibold text-gray-900 mb-0.5 truncate" title="${escapeHtml(curriculum.name)}">${escapeHtml(curriculum.name)}</h3>
                <p class="text-xs text-gray-600">${escapeHtml(facultyName)} • ${degreeText}</p>
            </div>
            <span class="status-badge ${bgClass} ${textClass} px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap flex-shrink-0 ${curriculum.status === 'warning' ? 'warning-pulse' : ''}">
                ${statusIcon} ${statusLabel}
            </span>
        </div>
        <div class="space-y-1.5 text-xs text-gray-600">
            <div class="flex items-center">
                <svg class="w-3.5 h-3.5 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>เงื่อนไข: อาจารย์ทุกคนต้องมี ${curriculum.required_publications} ผลงาน</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-3.5 h-3.5 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span>ผู้รับผิดชอบ: <strong class="font-semibold">${curriculum.user_count}</strong> คน</span>
                </div>
                <div class="flex items-center">
                    <svg class="w-3.5 h-3.5 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <span>ผลงาน: <strong class="font-semibold">${curriculum.publication_count}</strong></span>
                </div>
            </div>
            ${curriculum.oldest_publication_year ? `
                <div class="flex items-center text-xs">
                    <svg class="w-3.5 h-3.5 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span>เก่าสุด: พ.ศ. ${parseInt(curriculum.oldest_publication_year) + 543}</span>
                </div>
            ` : ''}
        </div>
        <div class="mt-3 pt-3 border-t border-gray-200">
            <button class="text-blue-600 hover:text-blue-800 text-xs font-medium flex items-center justify-center w-full">
                <span>ดูรายละเอียด</span>
                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    `;

    return card;
}

/**
 * Show curriculum detail modal
 */
function showCurriculumDetail(curriculum, facultyName) {
    const modal = document.getElementById('detail-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalContent = document.getElementById('modal-content');

    modalTitle.textContent = curriculum.name;

    const currentYear = new Date().getFullYear();
    const nextYear = currentYear + 1;

    const degreeLevelText = {
        'bachelor': 'ปริญญาตรี (ป.ตรี)',
        'master': 'ปริญญาโท (ป.โท)',
        'doctoral': 'ปริญญาเอก (ป.เอก)'
    };
    const degreeText = degreeLevelText[curriculum.degree_level] || curriculum.degree_level;

    let contentHTML = `
        <div class="mb-6">
            <h4 class="text-lg font-semibold text-gray-900 mb-2">ข้อมูลหลักสูตร</h4>
            <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                <p><span class="font-medium">คณะ:</span> ${escapeHtml(facultyName)}</p>
                <p><span class="font-medium">ระดับการศึกษา:</span> ${degreeText}</p>
                <p><span class="font-medium">เงื่อนไขผลงาน:</span> อาจารย์ทุกคนต้องมีอย่างน้อย <strong class="text-blue-600">${curriculum.required_publications}</strong> ผลงานในรอบ 5 ปี</p>
                <p><span class="font-medium">จำนวนผู้รับผิดชอบ:</span> ${curriculum.user_count} คน</p>
                <p><span class="font-medium">จำนวนผลงานรวม:</span> ${curriculum.publication_count} รายการ</p>
            </div>
        </div>
    `;

    if (curriculum.users_with_publications && curriculum.users_with_publications.length > 0) {
        contentHTML += '<div class="space-y-6">';

        curriculum.users_with_publications.forEach(user => {
            // User status badge
            let userStatusBadge = '';
            if (user.meets_requirement) {
                if (user.will_expire_soon) {
                    userStatusBadge = '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full font-semibold warning-pulse">⚠ ใกล้หมดอายุ</span>';
                } else {
                    userStatusBadge = '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full font-semibold">✓ ผ่านเงื่อนไข</span>';
                }
            } else {
                userStatusBadge = '<span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full font-semibold">✕ ไม่ผ่านเงื่อนไข</span>';
            }

            contentHTML += `
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                            ${user.user_name ? user.user_name.charAt(0).toUpperCase() : 'U'}
                        </div>
                        <div class="flex-1">
                            <h5 class="font-semibold text-gray-900">${user.titleThai ? escapeHtml(user.titleThai) + ' ' : ''}${escapeHtml(user.user_name || 'ไม่ระบุชื่อ')}</h5>
                            <p class="text-sm text-gray-600">${escapeHtml(user.user_email)}</p>
                        </div>
                        ${userStatusBadge}
                    </div>
                    <div class="ml-13">
                        <div class="mb-3 p-2 bg-gray-100 rounded-lg">
                            <p class="text-sm font-medium text-gray-700">
                                ผลงานทั้งหมด: <span class="font-bold text-gray-900">${user.publication_count}</span> รายการ
                                ${user.publications.length > 0 ? '<span class="text-gray-500 text-xs">(แสดง 3 อันล่าสุด)</span>' : ''}
                            </p>
                            <p class="text-sm font-medium mt-1">
                                <span class="text-green-700">ผ่านเกณฑ์ กพอ.:</span> 
                                <span class="font-bold ${user.meets_requirement ? 'text-green-600' : 'text-red-600'}">${user.approved_count || 0}</span> / ${curriculum.required_publications} รายการ
                                ${!user.meets_requirement ? '<span class="text-red-600 text-xs ml-1">(ไม่ถึงเกณฑ์)</span>' : '<span class="text-green-600 text-xs ml-1">(ผ่านเกณฑ์)</span>'}
                            </p>
                        </div>
                        ${user.publications.length > 0 ? '<div class="space-y-2">' : '<p class="text-sm text-gray-500 italic">ไม่มีผลงานในรอบ 5 ปี</p>'}
            `;

            if (user.publications.length > 0) {
                user.publications.forEach(pub => {
                const pubYear = parseInt(pub.publication_year);
                const expiryYear = pubYear + 5;
                const yearsUntilExpiry = expiryYear - currentYear;

                // Approval status badge (กพอ criteria)
                let approveBadge = '';
                const approveStatus = parseInt(pub.approve);
                if (approveStatus === 1) {
                    approveBadge = '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded font-medium">✓ ผ่าน</span>';
                } else if (approveStatus === 0) {
                    approveBadge = '<span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded font-medium">✗ ไม่ผ่าน</span>';
                } else {
                    approveBadge = '<span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded font-medium">⏳ ยังไม่ได้ตรวจสอบ</span>';
                }

                let expiryBadge = '';
                if (yearsUntilExpiry <= 0) {
                    expiryBadge = '<span class="ml-2 px-2 py-1 bg-red-100 text-red-800 text-xs rounded">หมดอายุแล้ว</span>';
                } else if (yearsUntilExpiry === 1) {
                    expiryBadge = '<span class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">หมดอายุปีหน้า</span>';
                }

                // Border color based on approval status
                const borderClass = approveStatus === 1 
                    ? 'border-l-4 border-green-500' 
                    : approveStatus === 0 
                        ? 'border-l-4 border-red-300'
                        : 'border-l-4 border-yellow-400';

                contentHTML += `
                    <div class="bg-gray-50 p-3 rounded cursor-pointer hover:bg-gray-100 transition-colors ${borderClass}" onclick="showPublicationDetail(${pub.id}, event)">
                        <div class="flex justify-between items-start">
                            <h6 class="text-sm font-medium text-gray-900 flex-1">${escapeHtml(pub.title)}</h6>
                            <svg class="w-4 h-4 text-blue-600 ml-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 mt-2 text-xs text-gray-500">
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">${escapeHtml(pub.publication_type || 'N/A')}</span>
                            ${approveBadge}
                            <span>ปี พ.ศ. ${pubYear + 543}</span>
                            ${expiryBadge}
                        </div>
                        <p class="text-xs text-blue-600 mt-2 font-medium">คลิกเพื่อดูรายละเอียด →</p>
                    </div>
                `;
                });

                contentHTML += `
                        </div>
                `;
            }

            contentHTML += `
                    </div>
                </div>
            `;
        });

        contentHTML += '</div>';
    } else {
        contentHTML += `
            <div class="text-center py-8 text-gray-500">
                <p>ไม่มีผู้รับผิดชอบหลักสูตร</p>
            </div>
        `;
    }

    modalContent.innerHTML = contentHTML;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Prevent body scrolling when modal is open
    document.body.classList.add('modal-active');
}

/**
 * Close detail modal
 */
function closeDetailModal() {
    const modal = document.getElementById('detail-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        // Clear modal content to free memory
        const modalContent = document.getElementById('modal-content');
        if (modalContent) {
            modalContent.innerHTML = '';
        }
        // Re-enable body scrolling when modal is closed
        document.body.classList.remove('modal-active');
    }
}

/**
 * Handle modal backdrop click (close when clicking outside)
 */
function handleModalBackdropClick(event) {
    // Only close if clicking directly on the backdrop (not on modal content)
    if (event.target.id === 'detail-modal') {
        closeDetailModal();
    }
}

/**
 * Show publication detail modal (nested modal)
 */
async function showPublicationDetail(publicationId, event) {
    // Prevent event bubbling to parent elements
    if (event) {
        event.stopPropagation();
    }

    const modal = document.getElementById('publication-detail-modal');
    const modalTitle = document.getElementById('publication-modal-title');
    const modalContent = document.getElementById('publication-modal-content');

    // Show loading state
    modalTitle.textContent = 'กำลังโหลด...';
    modalContent.innerHTML = '<div class="flex justify-center items-center py-12"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div></div>';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('modal-active');

    try {
        // Fetch publication details from API (use admin route)
        const response = await fetch(`${BASE_URL}/index.php/admin/publications/get/${publicationId}`);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'ไม่สามารถโหลดข้อมูลได้');
        }

        const pub = result.data;
        modalTitle.textContent = escapeHtml(pub.title || 'รายละเอียดผลงานวิจัย');

        // Format publication details
        const pubYear = pub.publication_year ? parseInt(pub.publication_year) : null;
        const beYear = pubYear ? pubYear + 543 : null;
        const expiryYear = pubYear ? pubYear + 5 : null;
        const beExpiryYear = expiryYear ? expiryYear + 543 : null;

        const monthNames = {
            1: 'มกราคม', 2: 'กุมภาพันธ์', 3: 'มีนาคม', 4: 'เมษายน',
            5: 'พฤษภาคม', 6: 'มิถุนายน', 7: 'กรกฎาคม', 8: 'สิงหาคม',
            9: 'กันยายน', 10: 'ตุลาคม', 11: 'พฤศจิกายน', 12: 'ธันวาคม'
        };
        const monthName = pub.publication_month ? monthNames[parseInt(pub.publication_month)] || '' : '';

        const typeMap = {
            'journal': 'วารสาร',
            'proceedings': 'ประชุมวิชาการ',
            'book': 'หนังสือ',
            'thesis': 'วิทยานิพนธ์',
            'report': 'รายงาน',
            'other': 'อื่นๆ'
        };
        const typeName = typeMap[pub.publication_type] || pub.publication_type || 'ไม่ระบุ';

        // Format authors
        let authorsHTML = '<p class="text-gray-500 italic">ไม่ระบุผู้แต่ง</p>';
        if (pub.authors && pub.authors.length > 0) {
            authorsHTML = '<ul class="list-disc list-inside space-y-1">';
            pub.authors.forEach(author => {
                const authorName = author.author_name || author.name || 'ไม่ระบุชื่อ';
                const authorEmail = author.author_email ? ` (${escapeHtml(author.author_email)})` : '';
                const corresponding = author.corresponding ? ' <span class="text-blue-600 font-semibold">[ผู้แต่งที่ติดต่อได้]</span>' : '';
                authorsHTML += `<li>${escapeHtml(authorName)}${authorEmail}${corresponding}</li>`;
            });
            authorsHTML += '</ul>';
        } else if (pub.authors_names_thai || pub.authors_names_en) {
            authorsHTML = `<p>${escapeHtml(pub.authors_names_thai || pub.authors_names_en)}</p>`;
        }

        // Format publication date string
        let publicationDateStr = '';
        if (beYear) {
            const monthPart = monthName ? monthName + ' ' : '';
            const expiryPart = beExpiryYear ? `<span class="text-gray-500 text-sm ml-2">(หมดอายุ พ.ศ. ${beExpiryYear})</span>` : '';
            publicationDateStr = monthPart + 'พ.ศ. ' + beYear + ' (ค.ศ. ' + pubYear + ')' + expiryPart;
        }

        let contentHTML = `
            <div class="space-y-6">
                <!-- Title -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">ชื่อผลงาน</h4>
                    <p class="text-gray-900 text-lg">${escapeHtml(pub.title || 'ไม่ระบุ')}</p>
                </div>

                <!-- Authors -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">ผู้แต่ง/ผู้วิจัย</h4>
                    <div class="text-gray-900">${authorsHTML}</div>
                </div>

                <!-- Publication Type & Approval Status -->
                <div class="flex flex-wrap gap-4">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">ประเภทผลงาน</h4>
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">${escapeHtml(typeName)}</span>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">สถานะ กพอ.</h4>
                        ${pub.approve == 1 
                            ? '<span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">✓ ผ่าน</span>'
                            : pub.approve == 0
                                ? '<span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">✗ ไม่ผ่าน</span>'
                                : '<span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">⏳ ยังไม่ได้ตรวจสอบ</span>'
                        }
                    </div>
                </div>

                <!-- Source/Journal -->
                ${pub.source ? `
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">แหล่งตีพิมพ์/ชื่อวารสาร</h4>
                    <p class="text-gray-900">${escapeHtml(pub.source)}</p>
                </div>
                ` : ''}

                <!-- Publication Date -->
                ${beYear ? `
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">ปีที่ตีพิมพ์</h4>
                    <p class="text-gray-900">${publicationDateStr}</p>
                </div>
                ` : ''}

                <!-- Volume, Issue, Pages -->
                ${(pub.volume || pub.issue || pub.pages) ? `
                <div class="grid grid-cols-3 gap-4">
                    ${pub.volume ? `
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-1">ปีที่/ฉบับที่</h4>
                        <p class="text-gray-900">${escapeHtml(pub.volume)}</p>
                    </div>
                    ` : ''}
                    ${pub.issue ? `
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-1">ครั้งที่</h4>
                        <p class="text-gray-900">${escapeHtml(pub.issue)}</p>
                    </div>
                    ` : ''}
                    ${pub.pages ? `
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-1">หน้า</h4>
                        <p class="text-gray-900">${escapeHtml(pub.pages)}</p>
                    </div>
                    ` : ''}
                </div>
                ` : ''}

                <!-- DOI and ISBN -->
                ${(pub.doi || pub.isbn) ? `
                <div class="grid grid-cols-2 gap-4">
                    ${pub.doi ? `
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-1">DOI</h4>
                        <p class="text-gray-900">${escapeHtml(pub.doi)}</p>
                    </div>
                    ` : ''}
                    ${pub.isbn ? `
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-1">ISBN</h4>
                        <p class="text-gray-900">${escapeHtml(pub.isbn)}</p>
                    </div>
                    ` : ''}
                </div>
                ` : ''}

                <!-- Abstract -->
                ${pub.abstract ? `
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">บทคัดย่อ</h4>
                    <p class="text-gray-900 whitespace-pre-wrap">${escapeHtml(pub.abstract)}</p>
                </div>
                ` : ''}

                <!-- Keywords -->
                ${pub.keywords ? `
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">คำสำคัญ</h4>
                    <p class="text-gray-900">${escapeHtml(pub.keywords)}</p>
                </div>
                ` : ''}

                <!-- Conference fields -->
                ${pub.conference_name || pub.conference_location || pub.conference_date ? `
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-purple-900 mb-3">ข้อมูลการประชุม</h4>
                    ${pub.conference_name ? `<p class="mb-2"><span class="font-medium">ชื่อการประชุม:</span> ${escapeHtml(pub.conference_name)}</p>` : ''}
                    ${pub.conference_location ? `<p class="mb-2"><span class="font-medium">สถานที่:</span> ${escapeHtml(pub.conference_location)}</p>` : ''}
                    ${pub.conference_date ? `<p><span class="font-medium">วันที่จัด:</span> ${escapeHtml(pub.conference_date)}</p>` : ''}
                </div>
                ` : ''}

                <!-- Book fields -->
                ${pub.book_title || pub.chapter || pub.editor || pub.publisher ? `
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-green-900 mb-3">ข้อมูลหนังสือ</h4>
                    ${pub.book_title ? `<p class="mb-2"><span class="font-medium">ชื่อหนังสือ:</span> ${escapeHtml(pub.book_title)}</p>` : ''}
                    ${pub.chapter ? `<p class="mb-2"><span class="font-medium">บทที่:</span> ${escapeHtml(pub.chapter)}</p>` : ''}
                    ${pub.editor ? `<p class="mb-2"><span class="font-medium">บรรณาธิการ:</span> ${escapeHtml(pub.editor)}</p>` : ''}
                    ${pub.publisher ? `<p><span class="font-medium">สำนักพิมพ์:</span> ${escapeHtml(pub.publisher)}</p>` : ''}
                </div>
                ` : ''}

                <!-- URLs and Files -->
                ${(pub.url || pub.ref_url) ? `
                <div class="space-y-4">
                    ${pub.ref_url ? `
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">URL อ้างอิง / ไฟล์อ้างอิง</h4>
                        ${isLocalFileReference(pub.ref_url) ? `
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900">${escapeHtml(extractLocalFileName(pub.ref_url) || 'ไฟล์')}</span>
                                </div>
                                <a href="${getLocalFileDownloadUrl(extractLocalFileName(pub.ref_url))}" 
                                   class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    ดาวน์โหลด
                                </a>
                            </div>
                        </div>
                        ` : isValidUrl(pub.ref_url) ? `
                        <a href="${pub.ref_url}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 break-all underline inline-flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            ${escapeHtml(pub.ref_url)}
                        </a>
                        ` : `
                        <p class="text-gray-600 break-all text-sm">${escapeHtml(pub.ref_url)}</p>
                        <p class="text-red-500 text-xs mt-1">⚠ URL ไม่ถูกต้อง</p>
                        `}
                    </div>
                    ` : ''}
                    ${pub.url ? `
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">URL เพิ่มเติม / ไฟล์เพิ่มเติม</h4>
                        ${isLocalFileReference(pub.url) ? `
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900">${escapeHtml(extractLocalFileName(pub.url) || 'ไฟล์')}</span>
                                </div>
                                <a href="${getLocalFileDownloadUrl(extractLocalFileName(pub.url))}" 
                                   class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    ดาวน์โหลด
                                </a>
                            </div>
                        </div>
                        ` : isValidUrl(pub.url) ? `
                        <a href="${pub.url}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 break-all underline inline-flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            ${escapeHtml(pub.url)}
                        </a>
                        ` : `
                        <p class="text-gray-600 break-all text-sm">${escapeHtml(pub.url)}</p>
                        <p class="text-red-500 text-xs mt-1">⚠ URL ไม่ถูกต้อง</p>
                        `}
                    </div>
                    ` : ''}
                </div>
                ` : ''}

                <!-- Notes -->
                ${pub.notes ? `
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">หมายเหตุ</h4>
                    <p class="text-gray-900 whitespace-pre-wrap">${escapeHtml(pub.notes)}</p>
                </div>
                ` : ''}
            </div>
        `;

        modalContent.innerHTML = contentHTML;

    } catch (error) {
        console.error('Error loading publication details:', error);
        modalTitle.textContent = 'เกิดข้อผิดพลาด';
        modalContent.innerHTML = `
            <div class="text-center py-12">
                <p class="text-red-600 mb-4">ไม่สามารถโหลดข้อมูลได้</p>
                <p class="text-gray-500 text-sm">${escapeHtml(error.message || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ')}</p>
            </div>
        `;
    }
}

/**
 * Close publication detail modal
 */
function closePublicationDetailModal() {
    const modal = document.getElementById('publication-detail-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        // Clear modal content
        const modalContent = document.getElementById('publication-modal-content');
        if (modalContent) {
            modalContent.innerHTML = '';
        }
        // Re-enable body scrolling if no other modal is open
        const curriculumModal = document.getElementById('detail-modal');
        if (!curriculumModal || curriculumModal.classList.contains('hidden')) {
            document.body.classList.remove('modal-active');
        }
    }
}

/**
 * Handle publication modal backdrop click
 */
function handlePublicationModalBackdropClick(event) {
    if (event.target.id === 'publication-detail-modal') {
        closePublicationDetailModal();
    }
}

/**
 * Show error message
 */
function showError(message) {
    alert(message);
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Validate URL
 */
function isValidUrl(urlString) {
    if (!urlString || typeof urlString !== 'string') {
        return false;
    }
    
    try {
        // Try to create a URL object
        const url = new URL(urlString);
        // Check if it's http or https
        return url.protocol === 'http:' || url.protocol === 'https:';
    } catch (e) {
        // If URL constructor throws, it's not a valid URL
        return false;
    }
}

/**
 * Check if URL is a local file reference
 */
function isLocalFileReference(urlString) {
    if (!urlString || typeof urlString !== 'string') {
        return false;
    }
    
    // Check if it's a local file reference pattern
    // Pattern: "local:filename" or contains "downloadFile" or starts with "/utility/downloadFile"
    return urlString.startsWith('local:') || 
           urlString.includes('downloadFile') || 
           urlString.includes('/utility/downloadFile/');
}

/**
 * Extract filename from local file reference
 */
function extractLocalFileName(urlString) {
    if (!urlString) return null;
    
    // Handle "local:filename" pattern
    if (urlString.startsWith('local:')) {
        return urlString.substring(6); // Remove "local:" prefix
    }
    
    // Handle "/utility/downloadFile/filename" pattern
    const match = urlString.match(/downloadFile\/([^\/\?]+)/);
    if (match) {
        return match[1];
    }
    
    return null;
}

/**
 * Get download URL for local file
 */
function getLocalFileDownloadUrl(fileName) {
    if (!fileName) return null;
    return `${BASE_URL}/index.php/utility/downloadFile/${fileName}`;
}

// Export functions to global scope for onclick handlers
window.showPublicationDetail = showPublicationDetail;
window.closePublicationDetailModal = closePublicationDetailModal;
window.handlePublicationModalBackdropClick = handlePublicationModalBackdropClick;
