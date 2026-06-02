/**
 * User Roles Manager
 * Handles role assignment and faculty management for users
 */

let usersTable;
let users = [];
let faculties = [];
let currentUserId = null;

// Initialize on page load
$(document).ready(function() {
    loadFaculties();
    loadUsers();
    setupEventListeners();
});

/**
 * Load all faculties for faculty admin assignment
 */
function loadFaculties() {
    $.ajax({
        url: appRoute('admin/getFaculties'),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                faculties = response.data;
                populateUserFacultyDropdown();
            }
        },
        error: function(xhr) {
            console.error('Load faculties error:', xhr);
        }
    });
}

/**
 * Populate user faculty dropdown (single select)
 */
function populateUserFacultyDropdown() {
    const html = faculties.map(faculty => {
        return `<option value="${faculty.id}">${faculty.code} - ${faculty.name}</option>`;
    }).join('');
    $('#userFaculty').append(html);
}

/**
 * Load all users with role information
 */
function loadUsers() {
    $.ajax({
        url: appRoute('admin/getUsersWithRole'),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                users = response.data;
                initUsersTable(response.data);
            } else {
                showNotification(response.message || 'Failed to load users', 'error');
            }
        },
        error: function(xhr) {
            console.error('Load users error:', xhr);
            showNotification('Failed to load users', 'error');
        }
    });
}

/**
 * Initialize DataTable
 */
function initUsersTable(data) {
    if (usersTable) {
        usersTable.destroy();
    }

    usersTable = $('#usersTable').DataTable({
        data: data,
        columns: [
            {
                data: null,
                render: function(data) {
                    const thaiName = (data.thai_name || '') + ' ' + (data.thai_lastname || '');
                    const engName = (data.gf_name || '') + ' ' + (data.gl_name || '');
                    const userType = data.user_type || '';
                    const userTypeBadge = userType ?
                        (userType === 'TEACHER' ? '<span class="text-xs px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded">Teacher</span>' :
                         userType === 'STAFF' ? '<span class="text-xs px-1.5 py-0.5 bg-green-100 text-green-700 rounded">Staff</span>' :
                         userType === 'STUDENT' ? '<span class="text-xs px-1.5 py-0.5 bg-yellow-100 text-yellow-700 rounded">Student</span>' : '') : '';
                    return `<div>
                        <div class="font-medium text-gray-900">${thaiName} ${userTypeBadge}</div>
                        <div class="text-sm text-gray-500">${engName}</div>
                    </div>`;
                }
            },
            { data: 'email' },
            {
                data: null,
                render: function(data) {
                    // Main Faculty Column - from user.faculty_id
                    const facultyName = data.user_faculty_name || '';
                    const facultyCode = data.user_faculty_code || '';
                    
                    if (facultyName) {
                        return `<div>
                            <div class="text-sm font-medium">${facultyName}</div>
                            ${facultyCode ? `<div class="text-xs text-gray-500">${facultyCode}</div>` : ''}
                        </div>`;
                    }
                    return '<span class="text-gray-400">Not assigned</span>';
                }
            },
            {
                data: null,
                render: function(data) {
                    // Curriculum Column - from teacher_curriculum table (primary curriculum only)
                    const curriculumName = data.curriculum_name || '';
                    const curriculumCode = data.curriculum_code || '';
                    const curriculumFacultyName = data.curriculum_faculty_name || '';
                    
                    if (curriculumName) {
                        let html = `<div>
                            <div class="text-sm font-medium">${curriculumName}</div>`;
                        if (curriculumCode) {
                            html += `<div class="text-xs text-gray-500">${curriculumCode}</div>`;
                        }
                        // Show curriculum's faculty if different from user's faculty
                        if (curriculumFacultyName && curriculumFacultyName !== data.user_faculty_name) {
                            html += `<div class="text-xs text-blue-600 mt-1">(${curriculumFacultyName})</div>`;
                        }
                        html += `</div>`;
                        return html;
                    }
                    return '<span class="text-gray-400">No curriculum</span>';
                }
            },
            {
                data: 'role',
                render: function(data) {
                    const role = data || 'user';
                    const badges = {
                        'super_admin': '<span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs font-medium rounded">Super Admin</span>',
                        'faculty_admin': '<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">Faculty Admin</span>',
                        'user': '<span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded">User</span>'
                    };
                    return badges[role] || badges['user'];
                }
            },
            {
                data: 'managed_faculties',
                render: function(data, type, row) {
                    if (row.role === 'faculty_admin' && data) {
                        try {
                            const facultyIds = JSON.parse(data);
                            if (facultyIds.length > 0) {
                                const facultyNames = facultyIds.map(id => {
                                    const faculty = faculties.find(f => f.id == id);
                                    return faculty ? faculty.code : id;
                                });
                                return `<div class="text-xs">${facultyNames.join(', ')}</div>`;
                            }
                        } catch (e) {
                            console.error('Parse error:', e);
                        }
                    }
                    return '<span class="text-gray-400">-</span>';
                }
            },
            {
                data: null,
                render: function(data) {
                    const email = data.email || '';
                    return `
                        <button onclick="editUserRole('${email.replace(/'/g, "\\'")}')"
                            class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                            Edit Role
                        </button>
                    `;
                }
            }
        ],
        order: [[3, 'desc'], [0, 'asc']],
        pageLength: 25,
        language: {
            search: 'Search users:',
            lengthMenu: 'Show _MENU_ users',
            info: 'Showing _START_ to _END_ of _TOTAL_ users',
            paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
            }
        }
    });
}

/**
 * Edit user role
 * Uses email address as the main identifier for searching/editing
 */
function editUserRole(userEmail) {
    // Find user by email address
    const user = users.find(u => u.email === userEmail);
    if (!user) {
        showNotification('User not found with email: ' + userEmail, 'error');
        return;
    }

    currentUserId = user.uid;

    // Populate form
    $('#userId').val(user.uid);
    const userName = (user.thai_name || '') + ' ' + (user.thai_lastname || '') + ' (' + user.email + ')';
    $('#userName').val(userName);
    $('#userEmail').val(user.email || '');

    // Set user type
    $('#userType').val(user.user_type || 'TEACHER');

    // Set faculty
    $('#userFaculty').val(user.faculty_id || user.user_faculty_id || '');

    // Set system role
    $('#userRole').val(user.role || 'user');

    // Populate faculty checkboxes for admin role
    populateFacultyCheckboxes(user.managed_faculties);

    // Show/hide fields based on selections
    toggleUserTypeFaculty();
    toggleFacultySelection();

    // Show modal
    openRoleModal();
}

/**
 * Populate faculty checkboxes
 */
function populateFacultyCheckboxes(managedFaculties) {
    let selectedFaculties = [];
    if (managedFaculties) {
        try {
            selectedFaculties = JSON.parse(managedFaculties);
        } catch (e) {
            console.error('Parse error:', e);
        }
    }

    const html = faculties.map(faculty => {
        const checked = selectedFaculties.includes(faculty.id) ? 'checked' : '';
        return `
            <label class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer">
                <input type="checkbox" name="managed_faculties[]" value="${faculty.id}" ${checked}
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-700">
                    <span class="font-medium">${faculty.code}</span> - ${faculty.name}
                </span>
            </label>
        `;
    }).join('');

    $('#facultyCheckboxes').html(html);
}

/**
 * Toggle faculty field based on user type
 * TEACHER: Faculty required
 * STAFF: Faculty disabled (staff don't have faculty)
 * STUDENT: Faculty optional
 */
function toggleUserTypeFaculty() {
    const userType = $('#userType').val();
    const facultySelect = $('#userFaculty');
    const requiredIndicator = $('#facultyRequiredIndicator');
    const helpText = $('#facultyHelpText');

    if (userType === 'TEACHER') {
        // Teachers MUST have faculty
        facultySelect.prop('disabled', false);
        facultySelect.prop('required', true);
        requiredIndicator.removeClass('hidden');
        helpText.text('Required: Teachers must belong to a faculty');
        helpText.removeClass('text-gray-500').addClass('text-blue-600');
    } else if (userType === 'STAFF') {
        // Staff don't have faculty
        facultySelect.prop('disabled', true);
        facultySelect.prop('required', false);
        facultySelect.val('');
        requiredIndicator.addClass('hidden');
        helpText.text('Staff members do not have faculty affiliation');
        helpText.removeClass('text-blue-600').addClass('text-gray-500');
    } else if (userType === 'STUDENT') {
        // Students may have faculty (optional)
        facultySelect.prop('disabled', false);
        facultySelect.prop('required', false);
        requiredIndicator.addClass('hidden');
        helpText.text('Optional: Select faculty if applicable');
        helpText.removeClass('text-blue-600').addClass('text-gray-500');
    } else {
        // Default: optional
        facultySelect.prop('disabled', false);
        facultySelect.prop('required', false);
        requiredIndicator.addClass('hidden');
        helpText.text('Select the faculty this user belongs to');
        helpText.removeClass('text-blue-600').addClass('text-gray-500');
    }
}

/**
 * Toggle faculty selection visibility (for faculty admin role)
 */
function toggleFacultySelection() {
    const role = $('#userRole').val();
    if (role === 'faculty_admin') {
        $('#facultySelectionDiv').removeClass('hidden');
    } else {
        $('#facultySelectionDiv').addClass('hidden');
    }
}

/**
 * Close role modal
 */
function closeRoleModal() {
    const modal = document.getElementById('roleModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
    document.body.style.overflow = '';
    $('#roleForm')[0].reset();
    currentUserId = null;
}

function openRoleModal() {
    const modal = document.getElementById('roleModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    }
    document.body.style.overflow = 'hidden';
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Role form submission
    $('#roleForm').on('submit', function(e) {
        e.preventDefault();

        const userId = $('#userId').val();
        const userType = $('#userType').val();
        const facultyId = $('#userFaculty').val();
        const role = $('#userRole').val();

        // Validation: Teacher must have faculty
        if (userType === 'TEACHER' && !facultyId) {
            showNotification('Teachers must have a faculty affiliation', 'warning');
            return;
        }

        let managedFaculties = [];
        if (role === 'faculty_admin') {
            managedFaculties = $('input[name="managed_faculties[]"]:checked').map(function() {
                return parseInt($(this).val());
            }).get();

            if (managedFaculties.length === 0) {
                showNotification('Please select at least one faculty for Faculty Admin', 'warning');
                return;
            }
        }

        // Submit role update
        const requestData = {
            user_id: userId,
            user_type: userType,
            faculty_id: facultyId || null,
            role: role,
            managed_faculties: managedFaculties
        };

        console.log('=== updateUserRole REQUEST ===');
        console.log('Request data:', requestData);
        console.log('URL:', appRoute('admin/updateUserRole'));

        $.ajax({
            url: appRoute('admin/updateUserRole'),
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(requestData),
            success: function(response) {
                console.log('=== updateUserRole RESPONSE ===');
                console.log('Response:', response);

                if (response.success) {
                    showNotification(response.message || 'User information updated successfully', 'success');
                    closeRoleModal();
                    loadUsers();
                } else {
                    showNotification(response.message || 'Failed to update user', 'error');
                }
            },
            error: function(xhr) {
                console.error('=== updateUserRole ERROR ===');
                console.error('Status:', xhr.status);
                console.error('Status Text:', xhr.statusText);
                console.error('Response:', xhr.responseText);
                console.error('Full XHR:', xhr);
                showNotification('Failed to update user', 'error');
            }
        });
    });
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    Swal.fire({
        text: message,
        icon: type === 'error' ? 'error' : type === 'warning' ? 'warning' : type === 'success' ? 'success' : 'info',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}
