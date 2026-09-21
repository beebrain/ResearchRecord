/**
 * Curriculum User Management System V2
 * Two-column layout: Users (left) and Curriculums by Faculty (right)
 */

const BASE_URL = window.BASE_URL || '';

let users = [];
let curriculums = [];
let faculties = [];
let searchTimeout = null;

/** Keep expanded curriculum cards open across re-renders after assign/remove. */
const expandedCurriculumIds = new Set();

function isCurriculumExpanded(curriculumId) {
    return expandedCurriculumIds.has(String(curriculumId));
}

function toggleCurriculumExpanded(curriculumId, forceOpen) {
    const key = String(curriculumId);
    const open = forceOpen === true ? true : forceOpen === false ? false : !expandedCurriculumIds.has(key);
    if (open) {
        expandedCurriculumIds.add(key);
    } else {
        expandedCurriculumIds.delete(key);
    }
    const card = document.querySelector(`.curriculum-card[data-curriculum-id="${key}"]`);
    if (card) {
        card.classList.toggle('is-expanded', open);
        const header = card.querySelector('.curriculum-card-header');
        if (header) {
            header.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    }
    return open;
}

function roleLabelTh(role) {
    if (role === 'coordinator') return 'ผู้รับผิดชอบ';
    if (role === 'assistant') return 'ผู้ช่วย';
    return 'อาจารย์ประจำ';
}

/**
 * Normalize member identity: email preferred (PK), legacy uid fallback.
 */
function memberIdentity(member) {
    const email = (member.email || member.teacher_email || '').toString().trim();
    if (email.includes('@')) {
        return email;
    }
    return member.teacher_uid || member.uid || member.user_id || member.id || null;
}

// Make function globally available for onclick
window.removecurrilumn = function (uid, curriculumId, buttonElement) {
    console.log('removecurrilumn called', { uid, curriculumId, buttonElement });

    let actualUid = uid;
    let actualCurriculumId = curriculumId;
    let userName = 'ผู้ใช้';
    let curriculumName = 'หลักสูตร';

    if (buttonElement) {
        const $button = $(buttonElement);
        if (!actualUid || actualUid === 'null' || actualUid === null) {
            actualUid = $button.data('user-id') || $button.attr('data-user-id') || null;
        }
        if (!actualCurriculumId || actualCurriculumId === 'null' || actualCurriculumId === null) {
            actualCurriculumId = $button.data('curriculum-id') || $button.attr('data-curriculum-id') || null;
        }
        userName = $button.data('user-name') || $button.attr('data-user-name') || 'ผู้ใช้';
        curriculumName = $button.data('curriculum-name') || $button.attr('data-curriculum-name') || 'หลักสูตร';
    }

    if (!actualUid || !actualCurriculumId || actualUid === 'null' || actualCurriculumId === 'null') {
        showError('ไม่พบข้อมูลผู้ใช้หรือหลักสูตร');
        return;
    }

    // Email PK: do not parseInt emails. Curriculum id is numeric.
    const uidStr = String(actualUid);
    if (!uidStr.includes('@')) {
        const asNum = parseInt(uidStr, 10);
        if (!isNaN(asNum)) {
            actualUid = asNum;
        }
    } else {
        actualUid = uidStr;
    }
    actualCurriculumId = parseInt(actualCurriculumId, 10);
    if (isNaN(actualCurriculumId)) {
        showError('ข้อมูลหลักสูตรไม่ถูกต้อง');
        return;
    }

    Swal.fire({
        title: 'ยืนยันการลบ',
        html: `คุณต้องการนำ <strong>"${escapeHtml(userName)}"</strong> ออกจากหลักสูตร <strong>"${escapeHtml(curriculumName)}"</strong> ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบออก',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        $.ajax({
            url: appRoute('user/removeFromCurriculum'),
            type: 'POST',
            dataType: 'json',
            data: {
                user_id: actualUid,
                curriculum_id: actualCurriculumId
            },
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: function (result) {
                if (result.success) {
                    loadUsers($('#user-search').val() || '', $('#user-faculty-filter').val() || 'all');
                    loadCurriculums($('#curriculum-faculty-filter').val() || 'all');
                    showToast(`นำ "${userName}" ออกจากหลักสูตรแล้ว`, 'success');
                } else {
                    showError(result.message || 'ไม่สามารถนำผู้ใช้ออกจากหลักสูตรได้');
                }
            },
            error: function () {
                showError('เกิดข้อผิดพลาดในการนำผู้ใช้ออกจากหลักสูตร');
            }
        });
    });
};

// Legacy function name (keep for backward compatibility)
window.handleRemoveMemberDirect = function (userId, curriculumId, userName, curriculumName) {
    console.log('handleRemoveMemberDirect called (global)', { userId, curriculumId, userName, curriculumName });

    if (!userId || !curriculumId) {
        console.error('Missing user_id or curriculum_id', { userId, curriculumId });
        Swal.fire({
            title: 'เกิดข้อผิดพลาด!',
            text: 'ไม่พบข้อมูลผู้ใช้หรือหลักสูตร',
            icon: 'error',
            confirmButtonText: 'ตกลง'
        });
        return;
    }

    // Confirm before removing using SweetAlert2
    Swal.fire({
        title: 'ยืนยันการลบ',
        html: `คุณต้องการนำ <strong>"${userName}"</strong> ออกจากหลักสูตร <strong>"${curriculumName}"</strong> ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบออก',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            console.log('Sending remove request via jQuery AJAX...', { userId, curriculumId });

            // Use jQuery AJAX
            $.ajax({
                url: appRoute('user/removeFromCurriculum'),
                type: 'POST',
                dataType: 'json',
                data: {
                    user_id: userId,
                    curriculum_id: curriculumId
                },
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                },
                success: function (result) {
                    console.log('Response data:', result);

                    if (result.success) {
                        // Reload data to reflect changes
                        loadUsers(
                            $('#user-search').val() || '',
                            $('#user-faculty-filter').val() || 'all'
                        );
                        loadCurriculums($('#curriculum-faculty-filter').val() || 'all');

                        // Show success message using SweetAlert2
                        Swal.fire({
                            title: 'สำเร็จ!',
                            text: `นำ "${userName}" ออกจากหลักสูตร "${curriculumName}" สำเร็จ`,
                            icon: 'success',
                            confirmButtonText: 'ตกลง',
                            timer: 2000,
                            timerProgressBar: true
                        });
                    } else {
                        // Show error message using SweetAlert2
                        Swal.fire({
                            title: 'เกิดข้อผิดพลาด!',
                            text: 'ไม่สามารถนำผู้ใช้ออกจากหลักสูตรได้: ' + (result.message || 'Unknown error'),
                            icon: 'error',
                            confirmButtonText: 'ตกลง'
                        });
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error removing user from curriculum:', { xhr, status, error });
                    // Show error message using SweetAlert2
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด!',
                        text: 'เกิดข้อผิดพลาดในการนำผู้ใช้ออกจากหลักสูตร: ' + (error || 'Unknown error'),
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                }
            });
        }
    });
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    initializeApp();
});

/**
 * Initialize the application
 */
function initializeApp() {
    // Check if jQuery is loaded
    if (typeof $ === 'undefined') {
        console.error('jQuery is not loaded!');
        Swal.fire({
            title: 'เกิดข้อผิดพลาด!',
            text: 'jQuery library is required but not found. Please include jQuery before this script.',
            icon: 'error',
            confirmButtonText: 'ตกลง'
        });
        return;
    }

    // Load faculties for filter dropdown
    loadFaculties();

    // Load initial data
    loadUsers();
    loadCurriculums('all');
}

/**
 * Load faculties for filter dropdown
 */
function loadFaculties() {
    $.ajax({
        url: appRoute('admin/getFaculties'),
        type: 'GET',
        dataType: 'json',
        beforeSend: function (xhr) {
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        },
        success: function (result) {
            if (result.success && result.data) {
                faculties = result.data;
                populateFacultyFilter();
            }
        },
        error: function (xhr, status, error) {
            console.error('Error loading faculties:', { xhr, status, error });
        }
    });
}

/**
 * Populate faculty filter dropdowns
 */
function populateFacultyFilter() {
    // Populate user faculty filter (left column)
    const userFacultyFilter = document.getElementById('user-faculty-filter');
    if (userFacultyFilter) {
        userFacultyFilter.innerHTML = '<option value="all">ทุกคณะ</option><option value="null">ไม่มีสังกัดคณะ</option>';

        faculties.forEach(faculty => {
            const option = document.createElement('option');
            option.value = faculty.id;
            option.textContent = `${faculty.name} (${faculty.code || ''})`;
            userFacultyFilter.appendChild(option);
        });

        // Add event listener for user filter
        userFacultyFilter.addEventListener('change', function () {
            const facultyId = this.value;
            const search = document.getElementById('user-search')?.value || '';
            loadUsers(search, facultyId);
        });
    }

    // Populate curriculum faculty filter (right column)
    const curriculumFacultyFilter = document.getElementById('curriculum-faculty-filter');
    if (curriculumFacultyFilter) {
        curriculumFacultyFilter.innerHTML = '<option value="all">ทุกคณะ</option><option value="null">ไม่มีสังกัดคณะ</option>';

        faculties.forEach(faculty => {
            const option = document.createElement('option');
            option.value = faculty.id;
            option.textContent = `${faculty.name} (${faculty.code || ''})`;
            curriculumFacultyFilter.appendChild(option);
        });

        // Add event listener for curriculum filter
        curriculumFacultyFilter.addEventListener('change', function () {
            const facultyId = this.value;
            loadCurriculums(facultyId);
        });
    }
}

/**
 * Load all users with curriculum info
 */
function loadUsers(search = '', facultyId = 'all') {
    const url = appRoute('admin/getAllUsersForCurriculumManagement'); // POST: IIS drops GET params
    const params = {};

    if (search) {
        params.search = search;
    }
    if (facultyId && facultyId !== 'all') {
        params.faculty_id = facultyId;
    }

    $.ajax({
        url: url,
        type: 'POST',
        dataType: 'json',
        data: params,
        beforeSend: function (xhr) {
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        },
        success: function (result) {
            if (result.success) {
                users = result.data || [];
                renderUsers();
            } else {
                Swal.fire({
                    title: 'เกิดข้อผิดพลาด!',
                    text: 'ไม่สามารถโหลดข้อมูลผู้ใช้ได้: ' + (result.message || 'Unknown error'),
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
            }
        },
        error: function (xhr, status, error) {
            console.error('Error loading users:', { xhr, status, error });
            Swal.fire({
                title: 'เกิดข้อผิดพลาด!',
                text: 'เกิดข้อผิดพลาดในการโหลดข้อมูลผู้ใช้',
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
        }
    });
}

/**
 * Load curriculums by faculty with members
 */
function loadCurriculums(facultyId = 'all') {
    const url = appRoute('admin/getCurriculumsByFacultyWithMembers'); // POST: IIS drops GET params
    const params = {};

    if (facultyId) {
        params.faculty_id = facultyId;
    }

    console.log('[DEBUG] Loading curriculums with params:', params);

    $.ajax({
        url: url,
        type: 'POST',
        dataType: 'json',
        data: params,
        beforeSend: function (xhr) {
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        },
        success: function (result) {
            console.log('[DEBUG] Curriculums loaded:', result);

            if (result.success) {
                curriculums = result.data || [];
                console.log('[DEBUG] Number of curriculums:', curriculums.length);
                if (curriculums.length > 0) {
                    console.log('[DEBUG] First curriculum:', curriculums[0]);
                    console.log('[DEBUG] First curriculum members:', curriculums[0].members);
                }
                renderCurriculums();
            } else {
                console.error('[ERROR] Failed to load curriculums:', result.message);
                Swal.fire({
                    title: 'เกิดข้อผิดพลาด!',
                    text: 'ไม่สามารถโหลดข้อมูลหลักสูตรได้: ' + (result.message || 'Unknown error'),
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
            }
        },
        error: function (xhr, status, error) {
            console.error('[ERROR] Error loading curriculums:', { xhr, status, error });
            console.error('[ERROR] Response text:', xhr.responseText);
            Swal.fire({
                title: 'เกิดข้อผิดพลาด!',
                text: 'เกิดข้อผิดพลาดในการโหลดข้อมูลหลักสูตร',
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
        }
    });
}

/**
 * Render users list
 */
function renderUsers() {
    const container = document.getElementById('users-list');
    const countBadge = document.getElementById('user-count-badge');

    if (!container) return;

    // Update count badge
    if (countBadge) {
        countBadge.textContent = `(${users.length} คน)`;
    }

    if (users.length === 0) {
        container.innerHTML = `
            <div class="text-center text-gray-500 py-8">
                <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <p>ไม่พบข้อมูลผู้ใช้</p>
            </div>
        `;
        return;
    }

    container.innerHTML = users.map(user => {
        const name = (user.thai_name && user.thai_lastname)
            ? `${user.thai_name} ${user.thai_lastname}`
            : `${user.gf_name || ''} ${user.gl_name || ''}`.trim() || 'ไม่มีชื่อ';

        const facultyName = user.user_faculty_name || 'ไม่มีสังกัดคณะ';
        const primaryCurriculum = user.primary_curriculum_name
            ? `${user.primary_curriculum_name} (${user.primary_curriculum_code || ''})`
            : 'ไม่มีหลักสูตร';

        const allCurriculums = user.all_curriculums || '';

        return `
            <div class="user-card draggable bg-gray-50 border border-gray-200 rounded-lg p-4" 
                 draggable="true" 
                 data-user-id="${escapeHtml(user.email || user.uid || '')}"
                 data-user-name="${escapeHtml(name)}">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-800 mb-1">${escapeHtml(name)}</h4>
                        <div class="text-sm text-gray-600 space-y-1">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span>${escapeHtml(user.email || 'ไม่มีอีเมล')}</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span>${escapeHtml(facultyName)}</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <span class="font-medium">หลักสูตรหลัก:</span> ${escapeHtml(primaryCurriculum)}
                            </div>
                            ${allCurriculums && allCurriculums !== primaryCurriculum ? `
                                <div class="text-xs text-gray-500 mt-1">
                                    <span class="font-medium">หลักสูตรอื่น:</span> ${escapeHtml(allCurriculums)}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    // Re-setup drag and drop after rendering
    setTimeout(() => {
        setupDragAndDrop();
    }, 100);
}

/**
 * Render curriculums list
 */
function renderCurriculums() {
    const container = document.getElementById('curriculums-list');
    if (!container) return;

    if (curriculums.length === 0) {
        container.innerHTML = `
            <div class="text-center text-gray-500 py-8">
                <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <p>ไม่พบข้อมูลหลักสูตร</p>
            </div>
        `;
        // Still setup drag and drop even when no curriculums (for user cards)
        setTimeout(() => {
            setupDragAndDrop();
        }, 100);
        return;
    }

    // Group curriculums by faculty
    const groupedByFaculty = {};
    curriculums.forEach(curriculum => {
        const facultyKey = curriculum.faculty_id || 'null';
        const facultyName = curriculum.faculty_name || 'ไม่มีสังกัดคณะ';

        if (!groupedByFaculty[facultyKey]) {
            groupedByFaculty[facultyKey] = {
                faculty_id: curriculum.faculty_id,
                faculty_name: facultyName,
                faculty_code: curriculum.faculty_code || '',
                curriculums: []
            };
        }

        groupedByFaculty[facultyKey].curriculums.push(curriculum);
    });

    container.innerHTML = Object.values(groupedByFaculty).map(facultyGroup => {
        return `
            <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-700 mb-3 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    ${escapeHtml(facultyGroup.faculty_name)} ${facultyGroup.faculty_code ? `(${escapeHtml(facultyGroup.faculty_code)})` : ''}
                </h4>
                <div class="space-y-3">
                    ${facultyGroup.curriculums.map(curriculum => renderCurriculumCard(curriculum)).join('')}
                </div>
            </div>
        `;
    }).join('');

    // Re-setup drag and drop after rendering all curriculums
    setTimeout(() => {
        setupDragAndDrop();
        setupRemoveMemberButtons();
        setupChairButtons();

        // Also setup event delegation as backup
        const curriculumsList = document.getElementById('curriculums-list');
        if (curriculumsList) {
            curriculumsList.removeEventListener('click', handleRemoveMemberClick);
            curriculumsList.addEventListener('click', handleRemoveMemberClick, true);
        }
    }, 100);
}

/**
 * Render a single curriculum card (collapsed by default; click header to expand).
 */
function renderCurriculumCard(curriculum) {
    const degreeLevelMap = {
        'bachelor': 'ปริญญาตรี',
        'master': 'ปริญญาโท',
        'doctoral': 'ปริญญาเอก'
    };

    const degreeLevel = degreeLevelMap[curriculum.degree_level] || curriculum.degree_level;
    const members = curriculum.members || [];
    const coordinators = members.filter(m => (m.role || '') === 'coordinator');
    const instructors = members.filter(m => (m.role || 'instructor') !== 'coordinator');

    const chairInfo = curriculum.chair_info;
    const chairName = chairInfo && chairInfo.name ? chairInfo.name : null;
    const displayName = curriculum.display_name || curriculum.name;
    const expanded = isCurriculumExpanded(curriculum.id);
    const bodyId = `curriculum-body-${curriculum.id}`;

    const renderMemberRow = (member) => {
        const memberName = (member.thai_name && member.thai_lastname)
            ? `${member.thai_name} ${member.thai_lastname}`
            : `${member.gf_name || ''}`.trim() || 'ไม่มีชื่อ';

        const role = member.role || 'instructor';
        const userId = memberIdentity(member);
        const curriculumId = curriculum.id || null;
        const email = member.email || '';

        return `
            <div class="flex items-center justify-between gap-2 text-sm group py-1">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    <select class="member-role-select shrink-0"
                        data-user-id="${escapeHtml(String(userId || ''))}"
                        data-curriculum-id="${curriculumId || ''}"
                        data-user-name="${escapeHtml(memberName)}"
                        data-curriculum-name="${escapeHtml(displayName)}"
                        data-previous-role="${escapeHtml(role)}"
                        aria-label="บทบาทของ ${escapeHtml(memberName)}"
                        title="เปลี่ยนบทบาทโดยไม่ต้องลากใหม่">
                        <option value="instructor" ${role === 'instructor' || role === 'assistant' ? 'selected' : ''}>อาจารย์ประจำ</option>
                        <option value="coordinator" ${role === 'coordinator' ? 'selected' : ''}>ผู้รับผิดชอบ</option>
                    </select>
                    <div class="min-w-0">
                        <span class="text-gray-800 font-medium truncate block">${escapeHtml(memberName)}</span>
                        ${member.teacher_faculty_name ? `<span class="text-xs text-gray-500 truncate block">${escapeHtml(member.teacher_faculty_name)}</span>` : ''}
                    </div>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    ${email ? `<span class="text-xs text-gray-400 hidden xl:inline max-w-[9rem] truncate">${escapeHtml(email)}</span>` : ''}
                    <button
                        type="button"
                        class="remove-member-btn text-red-500 hover:text-red-700 opacity-40 group-hover:opacity-100 transition-opacity p-2 rounded hover:bg-red-50 cursor-pointer min-h-[40px] min-w-[40px] flex items-center justify-center"
                        onclick="removecurrilumn(null, null, this)"
                        data-user-id="${escapeHtml(String(userId || ''))}"
                        data-curriculum-id="${curriculumId || ''}"
                        data-user-name="${escapeHtml(memberName)}"
                        data-curriculum-name="${escapeHtml(displayName)}"
                        title="นำออกจากหลักสูตร"
                        aria-label="นำ ${escapeHtml(memberName)} ออกจากหลักสูตร">
                        <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        `;
    };

    return `
        <div class="curriculum-card drop-zone ${expanded ? 'is-expanded' : ''}"
             data-curriculum-id="${curriculum.id}"
             data-curriculum-name="${escapeHtml(displayName)}"
             data-drop-role="coordinator">
            <div role="button"
                tabindex="0"
                class="curriculum-card-header"
                data-curriculum-toggle="${curriculum.id}"
                aria-expanded="${expanded ? 'true' : 'false'}"
                aria-controls="${bodyId}">
                <svg class="curriculum-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <div class="flex-1 min-w-0">
                    ${chairName ? `
                        <div class="text-xs font-medium text-green-700 truncate mb-0.5">ประธาน: ${escapeHtml(chairName)}</div>
                    ` : ''}
                    <div class="font-semibold text-gray-900 truncate">
                        ${escapeHtml(displayName)}
                        <span class="text-sm font-normal text-gray-500">(${escapeHtml(curriculum.code || '')})</span>
                    </div>
                    <div class="text-xs text-gray-500 mt-0.5">
                        ${escapeHtml(degreeLevel)}
                        · ผู้รับผิดชอบ ${coordinators.length}
                        · ประจำ ${instructors.length}
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button
                        type="button"
                        class="bg-green-600 hover:bg-green-700 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-colors min-h-[40px]"
                        title="เลือกประธานหลักสูตร"
                        data-curriculum-id="${curriculum.id}"
                        data-curriculum-name="${escapeHtml(curriculum.name)}"
                        data-chair-id="${chairInfo ? chairInfo.email : ''}">
                        ประธาน
                    </button>
                    <span class="bg-slate-200 text-slate-700 text-xs font-semibold px-2 py-1 rounded">
                        ${curriculum.member_count || 0}
                    </span>
                </div>
            </div>

            <div id="${bodyId}" class="curriculum-card-body space-y-3 pt-3">
                <div class="role-drop-zone drop-zone"
                     data-role="coordinator"
                     data-curriculum-id="${curriculum.id}"
                     data-curriculum-name="${escapeHtml(displayName)}"
                     data-drop-role="coordinator">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs font-semibold text-violet-800">ผู้รับผิดชอบหลักสูตร</p>
                        <span class="text-xs text-violet-600">${coordinators.length} คน</span>
                    </div>
                    ${coordinators.length > 0
                        ? `<div class="space-y-1">${coordinators.map(renderMemberRow).join('')}</div>`
                        : `<p class="text-xs text-violet-700/80 text-center py-3">ลากอาจารย์มาวางที่นี่เพื่อตั้งเป็นผู้รับผิดชอบ</p>`
                    }
                </div>

                <div class="role-drop-zone drop-zone"
                     data-role="instructor"
                     data-curriculum-id="${curriculum.id}"
                     data-curriculum-name="${escapeHtml(displayName)}"
                     data-drop-role="instructor">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs font-semibold text-blue-800">อาจารย์ประจำหลักสูตร</p>
                        <span class="text-xs text-blue-600">${instructors.length} คน</span>
                    </div>
                    ${instructors.length === 0
                        ? `<p class="text-xs text-amber-700 text-center py-3">ยังไม่มีอาจารย์ประจำ — ลากมาวางที่นี่</p>`
                        : `<div class="space-y-1 max-h-52 overflow-y-auto pr-1">${instructors.map(renderMemberRow).join('')}</div>`
                    }
                </div>
            </div>
        </div>
    `;
}

/**
 * Setup search functionality
 */
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('user-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const search = this.value.trim();

            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            searchTimeout = setTimeout(() => {
                const facultyId = document.getElementById('user-faculty-filter')?.value || 'all';
                loadUsers(search, facultyId);
            }, 300);
        });
    }

    setTimeout(() => {
        setupDragAndDrop();
    }, 500);
});

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Setup drag and drop + expand toggles
 */
function setupDragAndDrop() {
    const userCards = document.querySelectorAll('.user-card.draggable');
    const dropZones = document.querySelectorAll('.drop-zone');

    userCards.forEach(card => {
        card.removeEventListener('dragstart', handleDragStart);
        card.removeEventListener('dragend', handleDragEnd);
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
    });

    dropZones.forEach(zone => {
        zone.removeEventListener('dragover', handleDragOver);
        zone.removeEventListener('dragleave', handleDragLeave);
        zone.removeEventListener('drop', handleDrop);
        zone.addEventListener('dragover', handleDragOver);
        zone.addEventListener('dragleave', handleDragLeave);
        zone.addEventListener('drop', handleDrop);
    });

    bindMemberRoleSelects(document.getElementById('curriculums-list'));
    bindCurriculumExpandToggles();
}

function bindCurriculumExpandToggles() {
    document.querySelectorAll('[data-curriculum-toggle]').forEach((header) => {
        if (header.dataset.toggleBound === '1') return;
        header.dataset.toggleBound = '1';

        const toggle = () => {
            const id = header.getAttribute('data-curriculum-toggle');
            if (id) {
                toggleCurriculumExpanded(id);
            }
        };

        header.addEventListener('click', (e) => {
            // Don't collapse/expand when clicking chair / remove / selects inside header area
            if (e.target.closest('button[data-curriculum-id], .remove-member-btn, .member-role-select, a')) {
                return;
            }
            toggle();
        });

        header.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggle();
            }
        });
    });
}

/**
 * Handle drag start
 */
function handleDragStart(e) {
    const userCard = e.target.closest('.user-card');
    if (userCard) {
        const userId = userCard.dataset.userId;
        const userName = userCard.dataset.userName;

        e.dataTransfer.setData('text/plain', userId);
        e.dataTransfer.setData('application/json', JSON.stringify({
            userId: userId,
            userName: userName
        }));
        e.dataTransfer.effectAllowed = 'move';

        userCard.classList.add('dragging');
    }
}

/**
 * Handle drag end
 */
function handleDragEnd(e) {
    const userCard = e.target.closest('.user-card');
    if (userCard) {
        userCard.classList.remove('dragging');
    }

    document.querySelectorAll('.drop-zone').forEach(zone => {
        zone.classList.remove('drag-over');
    });
}

/**
 * Handle drag over — prefer innermost role zone; auto-expand collapsed card.
 */
function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';

    const roleZone = e.target.closest('.role-drop-zone');
    const card = e.target.closest('.curriculum-card');

    document.querySelectorAll('.drop-zone.drag-over').forEach((z) => {
        if (z !== roleZone && z !== card) {
            z.classList.remove('drag-over');
        }
    });

    if (roleZone) {
        roleZone.classList.add('drag-over');
        if (card) {
            card.classList.remove('drag-over');
            if (!card.classList.contains('is-expanded')) {
                toggleCurriculumExpanded(card.dataset.curriculumId, true);
            }
        }
        return;
    }

    if (card) {
        card.classList.add('drag-over');
    }
}

/**
 * Handle drag leave
 */
function handleDragLeave(e) {
    const dropZone = e.target.closest('.drop-zone');
    if (dropZone && !dropZone.contains(e.relatedTarget)) {
        dropZone.classList.remove('drag-over');
    }
}

/**
 * Handle drop — role from zone (coordinator/instructor). Collapsed card = instructor.
 */
function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();

    const roleZone = e.target.closest('.role-drop-zone');
    const card = e.target.closest('.curriculum-card');
    const dropZone = roleZone || card;
    if (!dropZone) return;

    document.querySelectorAll('.drop-zone').forEach((z) => z.classList.remove('drag-over'));

    const userId = e.dataTransfer.getData('text/plain');
    const curriculumId = dropZone.dataset.curriculumId || card?.dataset.curriculumId;
    const curriculumName = dropZone.dataset.curriculumName || card?.dataset.curriculumName;
    const role = (dropZone.dataset.dropRole || dropZone.dataset.role || 'coordinator') === 'instructor'
        ? 'instructor'
        : 'coordinator';

    if (curriculumId) {
        // Keep open after assign so user sees the new member
        expandedCurriculumIds.add(String(curriculumId));
    }

    if (userId && curriculumId) {
        assignUserToCurriculum(userId, curriculumId, curriculumName, role);
    }
}

/**
 * Assign user to curriculum via API
 */
function assignUserToCurriculum(userId, curriculumId, curriculumName, role = 'coordinator', options = {}) {
    const silent = !!options.silent;
    const userCard = document.querySelector(`[data-user-id="${CSS.escape ? CSS.escape(String(userId)) : String(userId)}"]`);
    if (userCard) {
        userCard.style.opacity = '0.5';
    }

    const roleText = roleLabelTh(role);

    $.ajax({
        url: appRoute('user/updateCurriculum'),
        type: 'POST',
        dataType: 'json',
        data: {
            user_id: userId,
            curriculum_id: curriculumId,
            role: role
        },
        beforeSend: function (xhr) {
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        },
        success: function (result) {
            if (result.success) {
                loadUsers(
                    $('#user-search').val() || '',
                    $('#user-faculty-filter').val() || 'all'
                );
                loadCurriculums($('#curriculum-faculty-filter').val() || 'all');
                if (!silent) {
                    showToast(`บันทึกแล้ว: ${roleText} ใน «${curriculumName}»`, 'success');
                }
            } else {
                showError(result.message || 'ไม่สามารถเพิ่มผู้ใช้เข้ากับหลักสูตรได้');
                if (typeof options.onError === 'function') {
                    options.onError(result);
                }
            }
        },
        error: function () {
            showError('เกิดข้อผิดพลาดในการเพิ่มผู้ใช้เข้ากับหลักสูตร');
            if (typeof options.onError === 'function') {
                options.onError({});
            }
        },
        complete: function () {
            const card = document.querySelector(`[data-user-id="${CSS.escape ? CSS.escape(String(userId)) : String(userId)}"]`);
            if (card) {
                card.style.opacity = '1';
            }
        }
    });
}

/**
 * Change member role from inline select (no re-drag).
 */
function handleMemberRoleChange(selectEl) {
    const userId = selectEl.dataset.userId;
    const curriculumId = selectEl.dataset.curriculumId;
    const curriculumName = selectEl.dataset.curriculumName || 'หลักสูตร';
    const userName = selectEl.dataset.userName || 'อาจารย์';
    const previous = selectEl.dataset.previousRole || 'instructor';
    const nextRole = selectEl.value;

    if (!userId || !curriculumId || nextRole === previous) {
        return;
    }

    selectEl.disabled = true;
    assignUserToCurriculum(userId, curriculumId, curriculumName, nextRole, {
        onError: function () {
            selectEl.value = previous;
            selectEl.disabled = false;
        }
    });
    // Success path reloads lists; toast says "เพิ่มเป็น…" which is ok for role change too
    // Soften message via toast after — assignUserToCurriculum already toasts.
    selectEl.dataset.previousRole = nextRole;
}

function bindMemberRoleSelects(root) {
    const scope = root || document;
    scope.querySelectorAll('.member-role-select').forEach((sel) => {
        if (sel.dataset.bound === '1') return;
        sel.dataset.bound = '1';
        sel.addEventListener('change', function () {
            handleMemberRoleChange(this);
        });
    });
}

/**
 * Lightweight toast — better for bulk dragging many instructors.
 */
function showToast(message, type = 'info') {
    const icon = type === 'error' ? 'error' : type === 'success' ? 'success' : 'info';
    Swal.fire({
        text: message,
        icon: icon,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2200,
        timerProgressBar: true
    });
}

/**
 * Show success message using SweetAlert2
 */
function showSuccess(message) {
    showToast(message, 'success');
}

/**
 * Show error message using SweetAlert2
 */
function showError(message) {
    console.error(message);
    Swal.fire({
        title: 'เกิดข้อผิดพลาด!',
        text: message,
        icon: 'error',
        confirmButtonText: 'ตกลง'
    });
}

/**
 * Setup remove member buttons
 */
function setupRemoveMemberButtons() {
    console.log('Setting up remove member buttons...');

    // Find all remove buttons
    const removeButtons = document.querySelectorAll('.remove-member-btn');
    console.log('Found remove buttons:', removeButtons.length);

    // Remove existing listeners and add new ones
    removeButtons.forEach((button, index) => {
        // Clone button to remove all event listeners
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);

        // Add click event listener directly
        newButton.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const userId = this.dataset.userId || this.getAttribute('data-user-id');
            const curriculumId = this.dataset.curriculumId || this.getAttribute('data-curriculum-id');
            const userName = this.dataset.userName || this.getAttribute('data-user-name');
            const curriculumName = this.dataset.curriculumName || this.getAttribute('data-curriculum-name');

            console.log('Remove button clicked directly', { userId, curriculumId, userName, curriculumName });

            if (userId && curriculumId) {
                handleRemoveMemberDirect(userId, curriculumId, userName, curriculumName);
            } else {
                console.error('Missing data attributes', { userId, curriculumId });
            }
        });

        console.log(`Button ${index + 1} setup completed`);
    });

    console.log('Remove member buttons setup completed');
}

/**
 * Handle click on remove member button (event delegation)
 */
function handleRemoveMemberClick(e) {
    // Check if clicked element is the button or inside it
    const button = e.target.closest('.remove-member-btn');

    if (!button) {
        // Also check if clicked on SVG inside button
        const svg = e.target.closest('svg');
        if (svg && svg.closest('.remove-member-btn')) {
            const btn = svg.closest('.remove-member-btn');
            e.stopPropagation();
            e.preventDefault();
            handleRemoveMember(e, btn);
            return;
        }
        return;
    }

    e.stopPropagation();
    e.preventDefault();
    console.log('Remove button clicked', button.dataset);
    handleRemoveMember(e, button);
}

/**
 * Handle remove member button click
 */
async function handleRemoveMember(e, button) {
    if (e && e.stopPropagation) {
        e.stopPropagation();
    }

    if (!button) {
        button = e.currentTarget || e.target.closest('.remove-member-btn');
    }

    if (!button) return;
    const userId = button.dataset.userId;
    const userName = button.dataset.userName;
    const curriculumId = button.dataset.curriculumId;
    const curriculumName = button.dataset.curriculumName;

    // Confirm before removing using SweetAlert2
    const confirmResult = await Swal.fire({
        title: 'ยืนยันการลบ',
        html: `คุณต้องการนำ <strong>"${userName}"</strong> ออกจากหลักสูตร <strong>"${curriculumName}"</strong> ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบออก',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true
    });

    if (!confirmResult.isConfirmed) {
        return;
    }

    try {
        console.log('Sending remove request...', { userId, curriculumId });

        const response = await fetch(appRoute('user/removeFromCurriculum'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                user_id: userId,
                curriculum_id: curriculumId
            })
        });

        console.log('Response status:', response.status);
        const result = await response.json();
        console.log('Response data:', result);

        if (result.success) {
            // Reload data to reflect changes
            await Promise.all([
                loadUsers(
                    document.getElementById('user-search')?.value || '',
                    document.getElementById('user-faculty-filter')?.value || 'all'
                ),
                loadCurriculums(document.getElementById('curriculum-faculty-filter')?.value || 'all')
            ]);

            // Show success message using SweetAlert2
            Swal.fire({
                title: 'สำเร็จ!',
                text: `นำ "${userName}" ออกจากหลักสูตร "${curriculumName}" สำเร็จ`,
                icon: 'success',
                confirmButtonText: 'ตกลง',
                timer: 2000,
                timerProgressBar: true
            });
        } else {
            // Show error message using SweetAlert2
            Swal.fire({
                title: 'เกิดข้อผิดพลาด!',
                text: 'ไม่สามารถนำผู้ใช้ออกจากหลักสูตรได้: ' + (result.message || 'Unknown error'),
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
        }
    } catch (error) {
        console.error('Error removing user from curriculum:', error);
        // Show error message using SweetAlert2
        Swal.fire({
            title: 'เกิดข้อผิดพลาด!',
            text: 'เกิดข้อผิดพลาดในการนำผู้ใช้ออกจากหลักสูตร: ' + error.message,
            icon: 'error',
            confirmButtonText: 'ตกลง'
        });
    }
}

/**
 * Handle remove member button click (legacy - for event delegation)
 */
async function handleRemoveMember(e, button) {
    if (e && e.stopPropagation) {
        e.stopPropagation();
    }

    if (!button) {
        button = e.currentTarget || e.target.closest('.remove-member-btn');
    }

    if (!button) return;
    const userId = button.dataset.userId || button.getAttribute('data-user-id');
    const userName = button.dataset.userName || button.getAttribute('data-user-name');
    const curriculumId = button.dataset.curriculumId || button.getAttribute('data-curriculum-id');
    const curriculumName = button.dataset.curriculumName || button.getAttribute('data-curriculum-name');

    if (!userId || !curriculumId) {
        console.error('Missing user_id or curriculum_id', { userId, curriculumId });
        showError('ไม่พบข้อมูลผู้ใช้หรือหลักสูตร');
        return;
    }

    // Call direct function
    await handleRemoveMemberDirect(userId, curriculumId, userName, curriculumName);
}

/**
 * Setup chair buttons
 */
function setupChairButtons() {
    // Remove existing event listeners by using event delegation
    const curriculumsList = document.getElementById('curriculums-list');
    if (curriculumsList) {
        // Remove old listeners
        curriculumsList.removeEventListener('click', handleChairButtonClick);
        // Add new listener
        curriculumsList.addEventListener('click', handleChairButtonClick, true);
    }
}

/**
 * Handle chair button click
 */
function handleChairButtonClick(e) {
    const button = e.target.closest('button[data-curriculum-id]');
    if (!button || !button.hasAttribute('data-curriculum-id')) {
        return;
    }
    
    e.preventDefault();
    e.stopPropagation();
    
    const curriculumId = button.getAttribute('data-curriculum-id');
    const curriculumName = button.getAttribute('data-curriculum-name') || '';
    const currentChairId = button.getAttribute('data-chair-id') || null;
    
    openChairModal(curriculumId, curriculumName, currentChairId);
}

/**
 * Open chair selection modal
 */
window.openChairModal = function(curriculumId, curriculumName, currentChairId = null) {
    try {
        $('#chairModalCurriculumId').val(curriculumId);
        $('#chairModalCurriculumName').text(curriculumName);
        hideChairConflictWarning();

        if (!window.chairAutocompleteReady) {
            ChairTeacherAutocomplete.init('#chairTeacherAutocomplete', {
                onSelectionChange: updateChairConflictWarning,
            });
            window.chairAutocompleteReady = true;
        }

        ChairTeacherAutocomplete.setCurriculumId(curriculumId);
        ChairTeacherAutocomplete.clear();

        if (currentChairId) {
            ChairTeacherAutocomplete.loadByEmail(currentChairId);
        } else {
            ChairTeacherAutocomplete.setStatus('พิมพ์ชื่อหรืออีเมลเพื่อค้นหาอาจารย์');
        }

        $('#chairModal').removeClass('hidden');
        setTimeout(function () {
            $('#chairTeacherSearch').trigger('focus');
        }, 100);
    } catch (error) {
        console.error('Error opening chair modal:', error);
        Swal.fire({
            title: 'เกิดข้อผิดพลาด!',
            text: 'ไม่สามารถเปิดหน้าต่างเลือกประธานหลักสูตรได้',
            icon: 'error',
            confirmButtonText: 'ตกลง'
        });
    }
};

function hideChairConflictWarning() {
    $('#chairConflictWarning').addClass('hidden');
    $('#chairConflictList').empty();
}

function updateChairConflictWarning(item) {
    hideChairConflictWarning();
    if (!item || !item.has_conflict) {
        return;
    }

    const list = $('#chairConflictList');
    list.empty();
    (item.warnings || []).forEach(function (warning) {
        list.append($('<li></li>').text(warning));
    });
    $('#chairConflictWarning').removeClass('hidden');
}

/**
 * Clear chair selection in modal
 */
window.clearChairSelection = function() {
    ChairTeacherAutocomplete.clear();
    hideChairConflictWarning();
};

/**
 * Close chair modal
 */
window.closeChairModal = function() {
    $('#chairModal').addClass('hidden');
    ChairTeacherAutocomplete.clear();
    hideChairConflictWarning();
};

/**
 * Save curriculum chair
 */
window.saveCurriculumChair = function() {
    const curriculumId = $('#chairModalCurriculumId').val();
    const chairId = ChairTeacherAutocomplete.getSelectedEmail() || null;
    const selected = ChairTeacherAutocomplete.getSelected();

    if (!curriculumId) {
        Swal.fire({
            title: 'เกิดข้อผิดพลาด!',
            text: 'ไม่พบข้อมูลหลักสูตร',
            icon: 'error',
            confirmButtonText: 'ตกลง'
        });
        return;
    }

    const submit = function(confirmConflict) {
        $('#chairSaveBtn').prop('disabled', true);

        $.ajax({
            url: appRoute('admin/setCurriculumChair'),
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                curriculum_id: curriculumId,
                chair_email: chairId,
                confirm_conflict: !!confirmConflict,
            }),
            dataType: 'json',
            success: function(response) {
                $('#chairSaveBtn').prop('disabled', false);

                if (response.requires_confirmation) {
                    const warningHtml = (response.warnings || [])
                        .map(function (w) { return '<li>' + escapeHtml(w) + '</li>'; })
                        .join('');

                    Swal.fire({
                        title: 'ยืนยันการตั้งประธาน?',
                        html: '<p class="text-sm text-gray-700 mb-2">อาจารย์ท่านนี้มีตำแหน่งในหลักสูตรอื่นแล้ว:</p><ul class="text-left text-sm text-amber-800 list-disc pl-5">' + warningHtml + '</ul>',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'ยืนยันบันทึก',
                        cancelButtonText: 'ยกเลิก',
                        confirmButtonColor: '#059669',
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            submit(true);
                        }
                    });
                    return;
                }

                if (response.success) {
                    Swal.fire({
                        title: 'สำเร็จ!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'ตกลง',
                        timer: 2000,
                        timerProgressBar: true
                    });
                    closeChairModal();
                    loadCurriculums($('#curriculum-faculty-filter').val() || 'all');
                } else {
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด!',
                        text: response.message || 'ไม่สามารถตั้งประธานหลักสูตรได้',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                }
            },
            error: function(xhr, status, error) {
                $('#chairSaveBtn').prop('disabled', false);
                console.error('Error setting curriculum chair:', { xhr, status, error });
                Swal.fire({
                    title: 'เกิดข้อผิดพลาด!',
                    text: 'เกิดข้อผิดพลาดในการตั้งประธานหลักสูตร',
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
            }
        });
    };

    if (selected && selected.has_conflict) {
        const warningHtml = (selected.warnings || [])
            .map(function (w) { return '<li>' + escapeHtml(w) + '</li>'; })
            .join('');

        Swal.fire({
            title: 'ยืนยันการตั้งประธาน?',
            html: '<p class="text-sm text-gray-700 mb-2">อาจารย์ท่านนี้มีตำแหน่งในหลักสูตรอื่นแล้ว:</p><ul class="text-left text-sm text-amber-800 list-disc pl-5">' + warningHtml + '</ul>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ยืนยันบันทึก',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#059669',
        }).then(function(result) {
            if (result.isConfirmed) {
                submit(true);
            }
        });
        return;
    }

    submit(false);
};

