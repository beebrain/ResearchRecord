/**
 * Faculty & Curriculum Management
 * Handles CRUD operations for faculties and curricula
 */

let facultyTable, curriculumTable;
let faculties = [];

// Initialize on page load
$(document).ready(function() {
    init();
});

function init() {
    loadFaculties();
    loadCurricula();
    initFacultyForm();
    initCurriculumForm();
}

// ==================== Faculty Management ====================

function loadFaculties() {
    $.ajax({
        url: appRoute('admin/getFaculties'),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                faculties = response.data;
                initFacultyTable(response.data);
                populateFacultySelects(response.data);
            } else {
                showToast('เกิดข้อผิดพลาดในการโหลดข้อมูลคณะ', 'error');
            }
        },
        error: function() {
            showToast('ไม่สามารถโหลดข้อมูลคณะได้', 'error');
        }
    });
}

function initFacultyTable(data) {
    if ($.fn.DataTable.isDataTable('#facultyTable')) {
        $('#facultyTable').DataTable().destroy();
    }

    facultyTable = $('#facultyTable').DataTable({
        data: data,
        columns: [
            { data: 'code' },
            { data: 'name' },
            {
                data: 'dean_info',
                render: function(data) {
                    if (data && data.name && data.name !== '-') {
                        return '<div class="text-sm">' + data.name + '</div>';
                    }
                    return '<span class="text-gray-400 text-sm">-</span>';
                }
            },
            {
                data: 'status',
                render: function(data) {
                    if (data === 'active' || data == 1) {
                        return '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded">ใช้งาน</span>';
                    } else {
                        return '<span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded">ไม่ใช้งาน</span>';
                    }
                }
            },
            { data: 'curriculum_count' },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    return `
                        <div class="flex space-x-2">
                            <button onclick="editFaculty(${data.id})" class="text-blue-600 hover:text-blue-800" title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button onclick="toggleFacultyStatus(${data.id})" class="text-yellow-600 hover:text-yellow-800" title="Toggle Status">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                            </button>
                            <button onclick="deleteFaculty(${data.id})" class="text-red-600 hover:text-red-800" title="Delete">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        pageLength: 10,
        ordering: true,
        searching: true,
        language: {
            search: 'ค้นหา:',
            lengthMenu: 'แสดง _MENU_ รายการ',
            info: 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
            emptyTable: 'ไม่มีข้อมูล',
            zeroRecords: 'ไม่พบข้อมูลที่ค้นหา',
            paginate: {
                first: 'หน้าแรก',
                last: 'หน้าสุดท้าย',
                next: 'ถัดไป',
                previous: 'ก่อนหน้า'
            }
        }
    });
}

function initFacultyForm() {
    $('#facultyForm').on('submit', function(e) {
        e.preventDefault();

        const id = $('#facultyId').val();
        const data = {
            code: $('#facultyCode').val(),
            name: $('#facultyName').val(),
            status: $('#facultyStatus').is(':checked'),
            dean_email: $('#facultyDean').val() || null
        };

        if (id) {
            data.id = id;
            updateFaculty(data);
        } else {
            createFaculty(data);
        }
    });
}

function openFacultyModal(id = null) {
    // Load users for dean dropdown
    loadUsersForDean(id ? faculties.find(f => f.id == id)?.id : null);
    
    if (id) {
        // Edit mode
        const faculty = faculties.find(f => f.id == id);
        if (faculty) {
            $('#facultyModalTitle').text('แก้ไขคณะ');
            $('#facultyId').val(faculty.id);
            $('#facultyCode').val(faculty.code);
            $('#facultyName').val(faculty.name);
            // Handle both old numeric (1/0) and new string ('active'/'inactive') status values
            $('#facultyStatus').prop('checked', faculty.status === 'active' || faculty.status == 1);
            // Set dean if exists (matched by email)
            if (faculty.dean_info && faculty.dean_info.email) {
                $('#facultyDean').val(faculty.dean_info.email);
            } else {
                $('#facultyDean').val('');
            }
        }
    } else {
        // Add mode
        $('#facultyModalTitle').text('เพิ่มคณะ');
        $('#facultyForm')[0].reset();
        $('#facultyId').val('');
        $('#facultyStatus').prop('checked', true);
        $('#facultyDean').val('');
    }
    $('#facultyModal').removeClass('hidden');
}

function closeFacultyModal() {
    $('#facultyModal').addClass('hidden');
    $('#facultyForm')[0].reset();
    $('#facultyDean').empty().append('<option value="">-- ไม่ระบุ --</option>');
}

function loadUsersForDean(facultyId = null) {
    // Note: faculty_id (used only to prioritise ordering) is intentionally not sent —
    // GET params do not survive the IIS path-rewrite on the index.php?/route form.
    // The full active-user list is returned regardless of faculty.
    const url = appRoute('admin/getUsersForDeanSelection');
    
    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const deanSelect = $('#facultyDean');
                deanSelect.empty().append('<option value="">-- ไม่ระบุ --</option>');
                
                response.data.forEach(function(user) {
                    const option = $('<option></option>')
                        .attr('value', user.email)
                        .text(user.name + (user.email ? ' (' + user.email + ')' : ''));
                    deanSelect.append(option);
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load users for dean selection:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
            showToast('ไม่สามารถโหลดรายชื่อผู้ใช้ได้', 'error');
        }
    });
}

function createFaculty(data) {
    $.ajax({
        url: appRoute('admin/createFaculty'),
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast(response.message, 'success');
                closeFacultyModal();
                loadFaculties();
            } else {
                showToast(response.message, 'error');
            }
        },
        error: function() {
            showToast('ไม่สามารถสร้างคณะได้', 'error');
        }
    });
}

function updateFaculty(data) {
    $.ajax({
        url: appRoute('admin/updateFaculty'),
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast(response.message, 'success');
                closeFacultyModal();
                loadFaculties();
            } else {
                showToast(response.message, 'error');
            }
        },
        error: function() {
            showToast('ไม่สามารถอัปเดตข้อมูลคณะได้', 'error');
        }
    });
}

function editFaculty(id) {
    openFacultyModal(id);
}

function deleteFaculty(id) {
    Swal.fire({
        title: 'คุณแน่ใจหรือไม่?',
        text: 'คณะนี้จะถูกลบอย่างถาวร!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: appRoute('admin/deleteFaculty'),
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id: id }),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast(response.message, 'success');
                        loadFaculties();
                        loadCurricula();
                    } else {
                        showToast(response.message, 'error');
                    }
                },
                error: function() {
                    showToast('ไม่สามารถลบคณะได้', 'error');
                }
            });
        }
    });
}

function toggleFacultyStatus(id) {
    $.ajax({
        url: appRoute('admin/toggleFacultyStatus'),
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ id: id }),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast(response.message, 'success');
                loadFaculties();
            } else {
                showToast(response.message, 'error');
            }
        },
        error: function() {
            showToast('ไม่สามารถเปลี่ยนสถานะคณะได้', 'error');
        }
    });
}

// ==================== Curriculum Management ====================

function loadCurricula(facultyId = null) {
    // Pass faculty_id as a route segment, not a query param: IIS (path rewrite) drops
    // GET params on the index.php?/route form, but a path segment routes correctly on
    // both IIS and the Docker query-string setup.
    const path = facultyId ? ('admin/getCurricula/' + encodeURIComponent(facultyId)) : 'admin/getCurricula';
    const url = appRoute(path);

    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                initCurriculumTable(response.data);
            } else {
                showToast('เกิดข้อผิดพลาดในการโหลดข้อมูลหลักสูตร', 'error');
            }
        },
        error: function() {
            showToast('ไม่สามารถโหลดข้อมูลหลักสูตรได้', 'error');
        }
    });
}

function initCurriculumTable(data) {
    if ($.fn.DataTable.isDataTable('#curriculumTable')) {
        $('#curriculumTable').DataTable().destroy();
    }

    curriculumTable = $('#curriculumTable').DataTable({
        data: data,
        columns: [
            { data: 'code' },
            { data: 'name' },
            { data: 'faculty_name' },
            {
                data: 'degree_level',
                render: function(data) {
                    const degrees = {
                        'bachelor': 'ปริญญาตรี',
                        'master': 'ปริญญาโท',
                        'doctoral': 'ปริญญาเอก'
                    };
                    return degrees[data] || data;
                }
            },
            {
                data: 'status',
                render: function(data) {
                    if (data === 'active' || data == 1) {
                        return '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded">ใช้งาน</span>';
                    } else {
                        return '<span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded">ไม่ใช้งาน</span>';
                    }
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    return `
                        <div class="flex space-x-2">
                            <button onclick="editCurriculum(${data.id})" class="text-blue-600 hover:text-blue-800" title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button onclick="toggleCurriculumStatus(${data.id})" class="text-yellow-600 hover:text-yellow-800" title="Toggle Status">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                            </button>
                            <button onclick="deleteCurriculum(${data.id})" class="text-red-600 hover:text-red-800" title="Delete">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        pageLength: 10,
        ordering: true,
        searching: true,
        language: {
            search: 'ค้นหา:',
            lengthMenu: 'แสดง _MENU_ รายการ',
            info: 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
            emptyTable: 'ไม่มีข้อมูล',
            zeroRecords: 'ไม่พบข้อมูลที่ค้นหา',
            paginate: {
                first: 'หน้าแรก',
                last: 'หน้าสุดท้าย',
                next: 'ถัดไป',
                previous: 'ก่อนหน้า'
            }
        }
    });
}

function initCurriculumForm() {
    $('#curriculumForm').on('submit', function(e) {
        e.preventDefault();

        const id = $('#curriculumId').val();
        const data = {
            faculty_id: $('#curriculumFaculty').val(),
            code: $('#curriculumCode').val(),
            name: $('#curriculumName').val(),
            degree_level: $('#curriculumDegree').val(),
            status: $('#curriculumStatus').is(':checked')
        };

        if (id) {
            data.id = id;
            updateCurriculum(data);
        } else {
            createCurriculum(data);
        }
    });

    // Faculty filter change
    $('#facultyFilter').on('change', function() {
        const facultyId = $(this).val();
        loadCurricula(facultyId || null);
    });
}

function openCurriculumModal(data = null) {
    if (data) {
        // Edit mode
        $('#curriculumModalTitle').text('แก้ไขหลักสูตร');
        $('#curriculumId').val(data.id);
        $('#curriculumFaculty').val(data.faculty_id);
        $('#curriculumCode').val(data.code);
        $('#curriculumName').val(data.name);
        $('#curriculumDegree').val(data.degree_level);
        $('#curriculumStatus').prop('checked', data.status == 1);
    } else {
        // Add mode
        $('#curriculumModalTitle').text('เพิ่มหลักสูตร');
        $('#curriculumForm')[0].reset();
        $('#curriculumId').val('');
        $('#curriculumStatus').prop('checked', true);
    }
    $('#curriculumModal').removeClass('hidden');
}

function closeCurriculumModal() {
    $('#curriculumModal').addClass('hidden');
    $('#curriculumForm')[0].reset();
}

function createCurriculum(data) {
    $.ajax({
        url: appRoute('admin/createCurriculum'),
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast(response.message, 'success');
                closeCurriculumModal();
                loadCurricula();
                loadFaculties(); // Refresh to update curriculum count
            } else {
                showToast(response.message, 'error');
            }
        },
        error: function() {
            showToast('ไม่สามารถสร้างหลักสูตรได้', 'error');
        }
    });
}

function updateCurriculum(data) {
    $.ajax({
        url: appRoute('admin/updateCurriculum'),
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast(response.message, 'success');
                closeCurriculumModal();
                loadCurricula();
            } else {
                showToast(response.message, 'error');
            }
        },
        error: function() {
            showToast('ไม่สามารถอัปเดตข้อมูลหลักสูตรได้', 'error');
        }
    });
}

function editCurriculum(id) {
    // Get curriculum data
    $.ajax({
        url: appRoute('admin/getCurricula'),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const curriculum = response.data.find(c => c.id == id);
                if (curriculum) {
                    openCurriculumModal(curriculum);
                }
            }
        }
    });
}

function deleteCurriculum(id) {
    Swal.fire({
        title: 'คุณแน่ใจหรือไม่?',
        text: 'หลักสูตรนี้จะถูกลบอย่างถาวร!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: appRoute('admin/deleteCurriculum'),
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id: id }),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast(response.message, 'success');
                        loadCurricula();
                        loadFaculties(); // Refresh to update curriculum count
                    } else {
                        showToast(response.message, 'error');
                    }
                },
                error: function() {
                    showToast('ไม่สามารถลบหลักสูตรได้', 'error');
                }
            });
        }
    });
}

function toggleCurriculumStatus(id) {
    $.ajax({
        url: appRoute('admin/toggleCurriculumStatus'),
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ id: id }),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast(response.message, 'success');
                loadCurricula();
            } else {
                showToast(response.message, 'error');
            }
        },
        error: function() {
            showToast('ไม่สามารถเปลี่ยนสถานะหลักสูตรได้', 'error');
        }
    });
}

// ==================== Helper Functions ====================

function populateFacultySelects(faculties) {
    const facultySelect = $('#curriculumFaculty');
    const facultyFilter = $('#facultyFilter');

    facultySelect.empty().append('<option value="">Select Faculty</option>');
    facultyFilter.empty().append('<option value="">All Faculties</option>');

    faculties.forEach(faculty => {
        if (faculty.status == 1) {
            facultySelect.append(`<option value="${faculty.id}">${faculty.name}</option>`);
            facultyFilter.append(`<option value="${faculty.id}">${faculty.name}</option>`);
        }
    });
}

function showToast(message, type = 'info') {
    const icon = type === 'error' ? 'error' : type === 'success' ? 'success' : 'info';

    Swal.fire({
        text: message,
        icon: icon,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}

// Expose functions to global scope
window.openFacultyModal = openFacultyModal;
window.closeFacultyModal = closeFacultyModal;
window.editFaculty = editFaculty;
window.deleteFaculty = deleteFaculty;
window.toggleFacultyStatus = toggleFacultyStatus;

window.openCurriculumModal = openCurriculumModal;
window.closeCurriculumModal = closeCurriculumModal;
window.editCurriculum = editCurriculum;
window.deleteCurriculum = deleteCurriculum;
window.toggleCurriculumStatus = toggleCurriculumStatus;
