/**
 * Curriculum User Management System
 * Handles drag-and-drop user assignment to curriculums
 */

// Global state
let facultiesData = [];
let users = [];
let draggedUser = null;
let BASE_URL = '';
let searchTimeout = null;
let facultyFilterInitialized = false; // Track if faculty-filter has been populated
let appInitialized = false; // Track if app initialization is complete

// Default configuration
const defaultConfig = {
    app_title: "จัดการหลักสูตรผู้ใช้",
    unassigned_label: "รายชื่อผู้ใช้",
    primary_color: "#3b82f6",
    secondary_color: "#667eea",
    text_color: "#1f2937",
    surface_color: "#ffffff",
    accent_color: "#f59e0b"
};

// Data handler for SDK
const dataHandler = {
    onDataChanged(data) {
        users = data;
        renderUsers();
    }
};

// Element SDK configuration
const elementConfig = {
    defaultConfig,
    onConfigChange: async (config) => {
        const appTitle = document.getElementById('app-title');
        const unassignedLabel = document.getElementById('unassigned-label');

        if (appTitle) {
            appTitle.textContent = config.app_title || defaultConfig.app_title;
            appTitle.style.color = config.text_color || defaultConfig.text_color;
        }

        if (unassignedLabel) {
            const labelText = unassignedLabel.querySelector('span') || unassignedLabel.childNodes[1];
            if (labelText) {
                labelText.textContent = config.unassigned_label || defaultConfig.unassigned_label;
            }
        }

        // Update colors
        document.documentElement.style.setProperty('--primary-color', config.primary_color || defaultConfig.primary_color);
        document.documentElement.style.setProperty('--secondary-color', config.secondary_color || defaultConfig.secondary_color);
        document.documentElement.style.setProperty('--text-color', config.text_color || defaultConfig.text_color);
        document.documentElement.style.setProperty('--surface-color', config.surface_color || defaultConfig.surface_color);
        document.documentElement.style.setProperty('--accent-color', config.accent_color || defaultConfig.accent_color);
    },
    mapToCapabilities: (config) => ({
        recolorables: [
            {
                get: () => config.primary_color || defaultConfig.primary_color,
                set: (value) => {
                    config.primary_color = value;
                    window.elementSdk.setConfig({ primary_color: value });
                }
            },
            {
                get: () => config.secondary_color || defaultConfig.secondary_color,
                set: (value) => {
                    config.secondary_color = value;
                    window.elementSdk.setConfig({ secondary_color: value });
                }
            },
            {
                get: () => config.text_color || defaultConfig.text_color,
                set: (value) => {
                    config.text_color = value;
                    window.elementSdk.setConfig({ text_color: value });
                }
            },
            {
                get: () => config.surface_color || defaultConfig.surface_color,
                set: (value) => {
                    config.surface_color = value;
                    window.elementSdk.setConfig({ surface_color: value });
                }
            },
            {
                get: () => config.accent_color || defaultConfig.accent_color,
                set: (value) => {
                    config.accent_color = value;
                    window.elementSdk.setConfig({ accent_color: value });
                }
            }
        ],
        borderables: [],
        fontEditable: undefined,
        fontSizeable: undefined
    }),
    mapToEditPanelValues: (config) => new Map([
        ["app_title", config.app_title || defaultConfig.app_title],
        ["unassigned_label", config.unassigned_label || defaultConfig.unassigned_label]
    ])
};

/**
 * Initialize the application
 */
async function initializeApp(baseUrl) {
    BASE_URL = baseUrl;

    try {
        if (window.elementSdk) {
            await window.elementSdk.init(elementConfig);
        }

        if (window.dataSdk) {
            const result = await window.dataSdk.init(dataHandler);
            if (!result.isOk) {
                console.error('Failed to initialize data SDK');
            }
        }

        // Load faculties and curriculums via AJAX
        await loadFacultiesAndCurriculums();

        // Load users via AJAX
        await loadUsers();

        renderFaculties();
        setupEventListeners();
        
        // Mark app as initialized
        appInitialized = true;
    } catch (error) {
        console.error('Failed to initialize app:', error);
        showMessage('Failed to initialize application', 'error');
    }
}

/**
 * Load faculties and curriculums from server
 */
async function loadFacultiesAndCurriculums() {
    try {
        const response = await fetch(BASE_URL + '/index.php/faculty-curriculum/getWithCurriculums', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();

        if (result.success) {
            facultiesData = result.data;
        } else {
            showMessage('ไม่สามารถโหลดข้อมูลคณะและหลักสูตร', 'error');
        }
    } catch (error) {
        console.error('Error loading faculties:', error);
        showMessage('เกิดข้อผิดพลาดในการโหลดข้อมูลคณะและหลักสูตร', 'error');
    }
}

/**
 * Load users from server
 * @param {string} searchTerm - Optional search term
 */
async function loadUsers(searchTerm = '', facultyId = '') {
    try {
        let url = BASE_URL + '/index.php/user/getWithCurriculum';
        const params = [];
        if (searchTerm) {
            params.push('search=' + encodeURIComponent(searchTerm));
        }
        if (facultyId) {
            params.push('faculty_id=' + encodeURIComponent(facultyId));
        }
        if (params.length > 0) {
            url += '?' + params.join('&');
        }

        console.log('🔍 Loading users from:', url);

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        console.log('📡 Response status:', response.status);
        const result = await response.json();
        console.log('👥 Users result:', result);
        console.log('📊 Users count:', result.data ? result.data.length : 0);

        if (result.success) {
            // Transform user data to match expected format
            users = result.data.map(user => {
                // Prioritize Thai name, fallback to English name, then email
                let displayName = '';

                if (user.thai_name && user.thai_lastname) {
                    displayName = `${user.thai_name} ${user.thai_lastname}`.trim();
                } else if (user.gf_name && user.gl_name) {
                    displayName = `${user.gf_name} ${user.gl_name}`.trim();
                } else {
                    displayName = user.email;
                }

                // Main faculty from user.faculty_id (required for teachers)
                const userFacultyId = user.user_faculty_id || user.faculty_id || null;
                const userFacultyName = user.user_faculty_name || 'ไม่พบข้อมูลคณะ';
                const userFacultyCode = user.user_faculty_code || '';
                
                // Curriculum from teacher_curriculum table (optional, can be from different faculty)
                const curriculumId = user.curriculum_id || null;
                const curriculumName = user.curriculum_name || null;
                const curriculumFacultyId = user.curriculum_faculty_id || null;
                const curriculumFacultyName = user.curriculum_faculty_name || null;

                const isTeacher = typeof user.is_teacher !== 'undefined'
                    ? (user.is_teacher === true || user.is_teacher === 1 || user.is_teacher === '1')
                    : ((user.user_type || '').toUpperCase() === 'TEACHER');

                return {
                    id: user.uid,
                    name: displayName,
                    email: user.email,
                    curriculum_id: curriculumId,
                    curriculum_name: curriculumName,
                    // Main faculty (from user.faculty_id)
                    faculty_id: userFacultyId,
                    faculty_name: userFacultyName,
                    faculty_code: userFacultyCode,
                    user_faculty_id: userFacultyId,
                    user_faculty_name: userFacultyName,
                    // Curriculum's faculty (may be different from user's main faculty)
                    curriculum_faculty_id: curriculumFacultyId,
                    curriculum_faculty_name: curriculumFacultyName,
                    user_type: user.user_type || null,
                    is_teacher: isTeacher,
                    type: 'user'
                };
            });

            // Show unassigned teachers alert
            showUnassignedTeachersAlert();

            // Update user count badge
            updateUserCountBadge();

            // Re-render faculties to reorder curriculums based on search results
            // Only call renderFaculties() if app is already initialized (not during initial load)
            // This prevents duplicate calls during initialization
            if (appInitialized) {
                renderFaculties();
            }
            renderUsers();
        } else {
            showMessage('ไม่สามารถโหลดข้อมูลผู้ใช้', 'error');
        }
    } catch (error) {
        console.error('Error loading users:', error);
        showMessage('เกิดข้อผิดพลาดในการโหลดข้อมูลผู้ใช้', 'error');
    }
}

/**
 * Show unassigned teachers at the top of the page
 */
function showUnassignedTeachersAlert() {
    // Show teachers without main faculty OR without curriculum
    // Schema: Teachers must have main faculty (user.faculty_id), curriculum is optional
    const unassignedTeachers = users.filter(user => {
        const noFaculty = !user.user_faculty_id;
        const noCurriculum = !user.curriculum_id;
        return (noFaculty || noCurriculum);
    });

    const alertSection = document.getElementById('unassigned-alert');
    const teachersList = document.getElementById('unassigned-teachers-list');

    if (unassignedTeachers.length > 0) {
        alertSection.classList.remove('hidden');

        teachersList.innerHTML = unassignedTeachers.map(teacher => {
            const noFaculty = !teacher.user_faculty_id;
            const noCurriculum = !teacher.curriculum_id;
            let statusBadge = '';
            let statusText = '';

            if (noFaculty && noCurriculum) {
                statusBadge = 'bg-red-100 text-red-800';
                statusText = 'ไม่มีคณะและหลักสูตร';
            } else if (noFaculty) {
                statusBadge = 'bg-orange-100 text-orange-800';
                statusText = 'ไม่มีคณะหลัก (จำเป็น)';
            } else if (noCurriculum) {
                statusBadge = 'bg-yellow-100 text-yellow-800';
                statusText = 'ไม่มีหลักสูตร (ไม่บังคับ)';
            }

            return `
                <div class="bg-white border border-yellow-300 rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">${escapeHtml(teacher.name)}</div>
                            <div class="text-xs text-gray-600 mt-1">${escapeHtml(teacher.email)}</div>
                        </div>
                        <div class="ml-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${statusBadge}">
                                ${statusText}
                            </span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    } else {
        alertSection.classList.add('hidden');
    }
}

/**
 * Update user count badge
 */
function updateUserCountBadge() {
    const badge = document.getElementById('user-count-badge');
    if (badge) {
        const totalUsers = users.length;
        const unassignedUsers = users.filter(u => !u.curriculum_id).length;
        badge.textContent = `(${totalUsers} คน${unassignedUsers > 0 ? ', ยังไม่กำหนด ' + unassignedUsers + ' คน' : ''})`;
    }
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Render faculties and curriculums
 */
function renderFaculties() {
    const container = document.getElementById('faculty-container');
    const facultyFilter = document.getElementById('faculty-filter');
    const selectedFacultyId = facultyFilter ? facultyFilter.value : '';

    container.innerHTML = '';

    // Populate single faculty filter dropdown if not already done
    if (facultyFilter && !facultyFilterInitialized) {
        // Get existing option values to prevent duplicates
        const existingValues = new Set(
            Array.from(facultyFilter.options).map(opt => opt.value)
        );
        
        // Add each faculty option only if it doesn't already exist
        facultiesData.forEach(faculty => {
            const facultyId = String(faculty.id);
            if (!existingValues.has(facultyId)) {
                const option = document.createElement('option');
                option.value = faculty.id;
                option.textContent = faculty.name;
                facultyFilter.appendChild(option);
                existingValues.add(facultyId);
            }
        });
        
        // Mark as initialized to prevent future duplicate population
        facultyFilterInitialized = true;
    }

    // Filter faculties based on selection
    // Note: "null" value means "no faculty" - for curriculums, we show all since curriculums always belong to a faculty
    const facultiesToShow = (selectedFacultyId && selectedFacultyId !== 'null') ?
        facultiesData.filter(faculty => faculty.id == selectedFacultyId) :
        facultiesData;

    // Get count of assigned users per curriculum for sorting
    const assignedUsers = users.filter(user => user.curriculum_id);
    const userCountByCurriculum = {};
    assignedUsers.forEach(user => {
        userCountByCurriculum[user.curriculum_id] = (userCountByCurriculum[user.curriculum_id] || 0) + 1;
    });

    if (selectedFacultyId && selectedFacultyId !== 'null') {
        // Show only curriculums for selected faculty in rows
        const selectedFaculty = facultiesData.find(f => f.id == selectedFacultyId);
        if (selectedFaculty) {
            // Sort curriculums: those with users first
            const sortedCurriculums = [...selectedFaculty.curriculums].sort((a, b) => {
                const countA = userCountByCurriculum[a.id] || 0;
                const countB = userCountByCurriculum[b.id] || 0;
                return countB - countA; // Descending order (most users first)
            });

            const facultySection = document.createElement('div');
            facultySection.innerHTML = `
                <div class="space-y-4">
                    ${sortedCurriculums.map(curriculum => `
                        <div class="curriculum-block border-2 border-dashed border-gray-300 rounded-lg p-4 min-h-32 bg-gray-50 hover:border-blue-400 transition-colors curriculum-container"
                             data-curriculum-id="${curriculum.id}"
                             data-faculty-id="${selectedFaculty.id}">
                            <div class="mb-3">
                                <h4 class="font-medium text-gray-800">${curriculum.name}</h4>
                                <p class="text-xs text-gray-500 mt-1">คณะ: ${selectedFaculty.name}</p>
                            </div>
                            <p class="text-xs text-blue-600 mb-2 italic">หมายเหตุ: อาจารย์จากคณะอื่นสามารถสอนหลักสูตรนี้ได้</p>

                            <div class="space-y-2" id="curriculum-${curriculum.id}">
                                <!-- Users will be populated here -->
                            </div>
                            <div class="text-sm text-gray-500 mt-2" id="count-${curriculum.id}">
                                0 users
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
            container.appendChild(facultySection);
        }
    } else {
        // Show all curriculums - reorder globally by user count (ignore faculty grouping)
        // Collect all curriculums with their faculty info
        const allCurriculums = [];
        facultiesToShow.forEach(faculty => {
            faculty.curriculums.forEach(curriculum => {
                allCurriculums.push({
                    ...curriculum,
                    faculty_name: faculty.name,
                    faculty_id: faculty.id,
                    user_count: userCountByCurriculum[curriculum.id] || 0
                });
            });
        });

        // Sort all curriculums globally by user count (descending)
        allCurriculums.sort((a, b) => b.user_count - a.user_count);

        // Render all curriculums in sorted order
        const allCurriculumsSection = document.createElement('div');
        allCurriculumsSection.className = 'space-y-4';
        allCurriculumsSection.innerHTML = allCurriculums.map(curriculum => `
            <div class="curriculum-block border-2 border-dashed border-gray-300 rounded-lg p-4 min-h-32 bg-gray-50 hover:border-blue-400 transition-colors curriculum-container"
                 data-curriculum-id="${curriculum.id}"
                 data-faculty-id="${curriculum.faculty_id}">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-800">${curriculum.name}</h4>
                        <p class="text-xs text-gray-500 mt-1">คณะ: ${curriculum.faculty_name}</p>
                    </div>
                </div>
                <p class="text-xs text-blue-600 mb-2 italic">หมายเหตุ: อาจารย์จากคณะอื่นสามารถสอนหลักสูตรนี้ได้</p>

                <div class="space-y-2" id="curriculum-${curriculum.id}">
                    <!-- Users will be populated here -->
                </div>
                <div class="text-sm text-gray-500 mt-2" id="count-${curriculum.id}">
                    0 คน
                </div>
            </div>
        `).join('');
        container.appendChild(allCurriculumsSection);
    }

    setupDragAndDrop();
}

/**
 * Render users in their appropriate containers
 */
function renderUsers() {
    console.log('🎨 Rendering users...');
    console.log('Total users:', users.length);

    // Clear all containers
    const usersList = document.getElementById('users-list');
    if (!usersList) {
        console.error('❌ users-list element not found!');
        return;
    }
    usersList.innerHTML = '';

    facultiesData.forEach(faculty => {
        faculty.curriculums.forEach(curriculum => {
            const container = document.getElementById(`curriculum-${curriculum.id}`);
            if (container) container.innerHTML = '';
        });
    });

    // Get the selected faculty filter value
    const facultyFilter = document.getElementById('faculty-filter');
    const selectedFacultyId = facultyFilter ? facultyFilter.value : '';
    
    // Separate unassigned and assigned users
    const unassignedUsers = users.filter(user => !user.curriculum_id);
    const assignedUsers = users.filter(user => user.curriculum_id);

    console.log('Unassigned users:', unassignedUsers.length);
    console.log('Assigned users:', assignedUsers.length);

    // Determine which users to show in the left list
    // Schema: Users have main faculty (user.faculty_id), curriculum is separate
    // If a faculty is selected, show ALL users from that main faculty (both with and without curriculum)
    // Otherwise, show only users without curriculum
    let usersToShowInList = [];
    if (selectedFacultyId && selectedFacultyId !== 'null') {
        // Show all users from the selected main faculty
        usersToShowInList = users.filter(user => {
            const userFacultyId = user.user_faculty_id || user.faculty_id;
            return userFacultyId == selectedFacultyId;
        });
    } else if (selectedFacultyId === 'null') {
        // Show users without main faculty
        usersToShowInList = users.filter(user => {
            const userFacultyId = user.user_faculty_id || user.faculty_id;
            return !userFacultyId;
        });
    } else {
        // Show only users without curriculum when no faculty filter is selected
        usersToShowInList = unassignedUsers;
    }

    // Render users in the left list
    usersToShowInList.forEach(user => {
        const userCard = createUserListCard(user);
        usersList.appendChild(userCard);
    });

    // Render assigned users in their curriculum blocks
    facultiesData.forEach(faculty => {
        faculty.curriculums.forEach(curriculum => {
            const curriculumUsers = assignedUsers.filter(user => user.curriculum_id == curriculum.id);

            // Get the curriculum block element
            const curriculumBlock = document.querySelector(`[data-curriculum-id="${curriculum.id}"]`);

            curriculumUsers.forEach(user => {
                const userCard = createUserBlockCard(user);
                const container = document.getElementById(`curriculum-${curriculum.id}`);
                if (container) {
                    container.appendChild(userCard);
                }
            });

            // Update user counts and highlight curriculum blocks that have users
            const countElement = document.getElementById(`count-${curriculum.id}`);
            if (countElement) {
                const totalCount = curriculumUsers.length;
                countElement.textContent = `${totalCount} คน`;

                // Highlight curriculum blocks with users (especially useful during search)
                if (curriculumBlock) {
                    if (totalCount > 0) {
                        curriculumBlock.classList.add('ring-2', 'ring-blue-400', 'bg-blue-50');
                    } else {
                        curriculumBlock.classList.remove('ring-2', 'ring-blue-400', 'bg-blue-50');
                    }
                }
            }
        });
    });
}

/**
 * Create user card for the users list
 */
function createUserListCard(user) {
    const card = document.createElement('div');
    card.className = 'user-card bg-white rounded-lg p-3 shadow-sm cursor-move border border-gray-200 hover:shadow-md transition-shadow';
    card.draggable = true;
    card.dataset.userId = user.id;
    card.innerHTML = `
                <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3 flex-1">
                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                    ${user.name.charAt(0).toUpperCase()}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-gray-900 text-sm truncate">${user.name}</div>
                    <div class="text-xs text-gray-500 truncate">${user.email}</div>
                    ${user.user_faculty_name ? `<div class="text-xs text-blue-600 truncate">${user.user_faculty_name}</div>` : ''}
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                </svg>
            </div>
        </div>
    `;
    return card;
}

/**
 * Create user card for curriculum blocks
 */
function createUserBlockCard(user) {
    const card = document.createElement('div');
    card.className = 'user-card bg-white rounded-md p-2 shadow-sm cursor-move border border-gray-200 hover:border-gray-300';
    card.draggable = true;
    card.dataset.userId = user.id;
    
    // Show user's main faculty if different from curriculum's faculty
    const showFacultyBadge = user.user_faculty_name && 
                             user.curriculum_faculty_name && 
                             user.user_faculty_name !== user.curriculum_faculty_name;
    
    card.innerHTML = `
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center space-x-2 flex-1 min-w-0">
                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-xs font-medium flex-shrink-0">
                    ${user.name.charAt(0).toUpperCase()}
                </div>
                <div class="text-left flex-1 min-w-0">
                    <div class="font-medium text-gray-900 text-xs truncate">${user.name}</div>
                    <div class="text-xs text-gray-500 truncate">${user.email}</div>
                    ${showFacultyBadge ? `<div class="text-xs text-blue-600 truncate mt-0.5" title="คณะหลักของอาจารย์">${user.user_faculty_name}</div>` : ''}
                </div>
            </div>
            <button class="remove-user text-red-500 hover:text-red-700 p-1 flex-shrink-0" data-user-id="${user.id}" title="นำออกจากหลักสูตร">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    `;
    return card;
}

/**
 * Setup drag and drop event listeners
 */
function setupDragAndDrop() {
    const containers = document.querySelectorAll('.curriculum-container');

    containers.forEach(container => {
        container.addEventListener('dragover', handleDragOver);
        container.addEventListener('drop', handleDrop);
        container.addEventListener('dragleave', handleDragLeave);
    });

    document.addEventListener('dragstart', handleDragStart);
    document.addEventListener('dragend', handleDragEnd);
}

/**
 * Handle drag start
 */
function handleDragStart(e) {
    if (e.target.classList.contains('user-card')) {
        draggedUser = e.target.dataset.userId;
        e.target.classList.add('dragging');
    }
}

/**
 * Handle drag end
 */
function handleDragEnd(e) {
    if (e.target.classList.contains('user-card')) {
        e.target.classList.remove('dragging');
        draggedUser = null;
    }
}

/**
 * Handle drag over
 */
function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('drag-over');
}

/**
 * Handle drag leave
 */
function handleDragLeave(e) {
    e.currentTarget.classList.remove('drag-over');
}

/**
 * Handle drop - Update user curriculum via AJAX
 */
async function handleDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('drag-over');

    if (!draggedUser) return;

    const curriculumId = e.currentTarget.dataset.curriculumId || null;
    const facultyId = e.currentTarget.dataset.facultyId || null;

    // Find the user and update their assignment
    const user = users.find(u => u.id == draggedUser);
    if (user) {
        try {
            // Update user curriculum via AJAX
            const formData = new FormData();
            formData.append('user_id', user.id);
            formData.append('curriculum_id', curriculumId || '');

            const response = await fetch(BASE_URL + '/index.php/user/updateCurriculum', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Update local user data
                // Note: user.faculty_id (main faculty) doesn't change when curriculum changes
                // Curriculum can be from different faculty than user's main faculty
                user.curriculum_id = curriculumId;
                // Don't update user.faculty_id - it's the main faculty and should remain unchanged

                // Re-render to show changes
                renderUsers();
                showMessage("อัปเดตการกำหนดหลักสูตรสำเร็จ!", "success");
            } else {
                showMessage(result.message || "ไม่สามารถอัปเดตการกำหนดหลักสูตร", "error");
            }
        } catch (error) {
            console.error('Error updating user curriculum:', error);
            showMessage("เกิดข้อผิดพลาดในการอัปเดต กรุณาลองใหม่อีกครั้ง", "error");
        }
    }
}

/**
 * Remove user from curriculum (unassign)
 */
async function removeUserFromCurriculum(userId) {
    const user = users.find(u => u.id == userId);
    if (!user) return;

    try {
        // Update user curriculum to null via AJAX
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('curriculum_id', '');

        const response = await fetch(BASE_URL + '/index.php/user/updateCurriculum', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Update local user data
            // Note: Removing curriculum doesn't remove user's main faculty
            user.curriculum_id = null;
            // Don't update user.faculty_id - main faculty should remain

            // Re-render to show changes
            renderUsers();
            showMessage("นำผู้ใช้ออกจากหลักสูตรสำเร็จ!", "success");
        } else {
            showMessage(result.message || "ไม่สามารถนำผู้ใช้ออกจากหลักสูตร", "error");
        }
    } catch (error) {
        console.error('Error removing user from curriculum:', error);
        showMessage("เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง", "error");
    }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Single faculty filter that controls both users and curriculums
    const facultyFilter = document.getElementById('faculty-filter');
    const searchInput = document.getElementById('user-search');

    // Faculty filter change event - updates both users and curriculums
    if (facultyFilter) {
        facultyFilter.addEventListener('change', () => {
            const facultyId = facultyFilter.value;
            const searchTerm = searchInput ? searchInput.value.trim() : '';
            
            // Update users list
            loadUsers(searchTerm, facultyId);
            
            // Update curriculum display
            renderFaculties();
            renderUsers();
        });
    }

    // Search functionality with debouncing (300ms delay)
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.trim();
            const facultyId = facultyFilter ? facultyFilter.value : '';

            // Clear existing timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            // Set new timeout for search
            searchTimeout = setTimeout(() => {
                loadUsers(searchTerm, facultyId);
            }, 300);
        });
    }

    // Remove user button click (using event delegation)
    document.addEventListener('click', (e) => {
        const removeButton = e.target.closest('.remove-user');
        if (removeButton) {
            e.stopPropagation(); // Prevent drag from starting
            const userId = removeButton.dataset.userId;
            if (userId) {
                removeUserFromCurriculum(userId);
            }
        }
    });
}

/**
 * Show message toast
 */
function showMessage(message, type) {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 p-4 rounded-lg text-white z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Initialize the application when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // BASE_URL will be set from the view file
    if (typeof window.BASE_URL !== 'undefined') {
        initializeApp(window.BASE_URL);
    }
});
