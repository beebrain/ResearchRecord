/**
 * Admin Dashboard JavaScript
 * Handles AJAX requests and data visualization for the admin dashboard
 * Supports role-based data filtering (Super Admin / Faculty Admin)
 */

// Chart instances
let yearChart = null;
let typeChart = null;
let facultyChart = null;
let admissionChart = null;
let curriculumYearlyChart = null;

// Global state
let dashboardState = {
    isSuperAdmin: false,
    statistics: null,
    summary: null
};

// Chart colors
const chartColors = {
    primary: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#f97316', '#ec4899'],
    background: ['rgba(59, 130, 246, 0.8)', 'rgba(16, 185, 129, 0.8)', 'rgba(245, 158, 11, 0.8)', 
                 'rgba(239, 68, 68, 0.8)', 'rgba(139, 92, 246, 0.8)', 'rgba(6, 182, 212, 0.8)',
                 'rgba(249, 115, 22, 0.8)', 'rgba(236, 72, 153, 0.8)']
};

// Initialize dashboard on page load
$(document).ready(function() {
    initializeDashboard();
});

/**
 * Initialize the dashboard
 */
function initializeDashboard() {
    initializeCharts();
    loadAllData();
}

/**
 * Load all dashboard data
 */
async function loadAllData() {
    try {
        // Load main statistics first
        await loadStatistics();
        
        // Load summary data (faculty table, publication types, recent publications)
        await loadSummaryData();
        
        // Load curriculum readiness data
        loadCurriculumReadiness();
        
        // Load curriculum publications data
        loadCurriculumPublications();
        
        // Load chart data
        loadYearChartData();
        loadFacultyChartData();
        
        // Setup event listeners
        setupReadinessYearFilter();
        
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}

/**
 * Load main statistics
 */
async function loadStatistics() {
    try {
        const response = await $.ajax({
            url: API_ENDPOINTS.statistics,
            method: 'GET',
            dataType: 'json'
        });

        if (response.success) {
            dashboardState.statistics = response.data;
            dashboardState.isSuperAdmin = response.data.isSuperAdmin;
            updateStatisticsDisplay(response.data);
            updateUIForRole(response.data.isSuperAdmin);
        } else {
            console.error('Failed to load statistics:', response.message);
            showDefaultStatistics();
        }
    } catch (error) {
        console.error('Error loading statistics:', error);
        showDefaultStatistics();
    }
}

/**
 * Update statistics display
 */
function updateStatisticsDisplay(data) {
    // Main stats
    $('#total-publications').text(formatNumber(data.totalPublications || 0));
    $('#total-authors').text(formatNumber(data.totalAuthors || 0));
    $('#active-faculties').text(formatNumber(data.activeFaculties || 0));
    
    // Publications this year
    $('#pub-this-year').text(formatNumber(data.publicationsThisYear || 0));
    $('#pub-year-count').text(formatNumber(data.publicationsThisYear || 0));
    
    // Curricula stats
    if (data.curricula) {
        $('#total-curricula').text(formatNumber(data.curricula.total || 0));
        $('#cur-bachelor').text(data.curricula.bachelor || 0);
        $('#cur-master').text(data.curricula.master || 0);
        $('#cur-doctoral').text(data.curricula.doctoral || 0);
    }
    
    // Admission stats
    if (data.admissionForms) {
        $('#total-admission').text(formatNumber(data.admissionForms.total || 0));
        $('#adm-draft').text(data.admissionForms.draft || 0);
        $('#adm-submitted').text(data.admissionForms.submitted || 0);
        $('#adm-approved').text(data.admissionForms.approved || 0);
        
        // Update admission chart
        updateAdmissionChart(data.admissionForms);
    }
    
    // Education stats
    if (data.education) {
        const pct = data.education.percentage || 0;
        $('#edu-completion-pct').text(pct + '%');
        $('#edu-percentage').text(pct + '%');
        $('#edu-progress-bar').css('width', pct + '%');
        $('#edu-with').text(data.education.users_with_education || 0);
        $('#edu-total').text(data.education.total_users || 0);
    }
}

/**
 * Load summary data (tables and additional charts)
 */
async function loadSummaryData() {
    try {
        const response = await $.ajax({
            url: API_ENDPOINTS.summary,
            method: 'GET',
            dataType: 'json'
        });

        if (response.success) {
            dashboardState.summary = response.data;
            
            // Update faculty summary table
            if (response.data.facultySummary) {
                updateFacultySummaryTable(response.data.facultySummary);
            }
            
            // Update publication types
            if (response.data.publicationTypes) {
                updatePublicationTypes(response.data.publicationTypes);
                updateTypeChart(response.data.publicationTypes);
            }
            
            // Update recent publications
            if (response.data.recentPublications) {
                updateRecentPublications(response.data.recentPublications);
            }
        }
    } catch (error) {
        console.error('Error loading summary data:', error);
    }
}

/**
 * Update UI based on user role
 */
function updateUIForRole(isSuperAdmin) {
    if (!isSuperAdmin) {
        // Hide faculties card for non-super admin
        $('#faculties-card').addClass('hidden');
        // Adjust grid to 3 columns
        $('#main-stats-row').removeClass('lg:grid-cols-4').addClass('lg:grid-cols-3');
    }
}

/**
 * Update faculty summary table
 */
function updateFacultySummaryTable(data) {
    const tbody = $('#faculty-summary-body');
    tbody.empty();
    
    if (!data || data.length === 0) {
        tbody.html('<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">ไม่พบข้อมูล</td></tr>');
        return;
    }
    
    data.forEach(faculty => {
        const row = `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <div class="text-sm font-medium text-gray-900">${escapeHtml(faculty.name)}</div>
                    <div class="text-xs text-gray-500">${escapeHtml(faculty.code || '')}</div>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        ${faculty.curricula_count || 0}
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        ${faculty.teachers_count || 0}
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        ${faculty.publications_count || 0}
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                        ${faculty.admission_forms_count || 0}
                    </span>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

/**
 * Update publication types display
 */
function updatePublicationTypes(data) {
    const typeNames = {
        'journal': 'Journal',
        'book': 'Book',
        'proceedings': 'Proceedings',
        'thesis': 'Thesis',
        'report': 'Report',
        'other': 'Other'
    };
    
    // Count unique types
    const typeCount = data.raw ? data.raw.length : 0;
    $('#pub-type-count').text(typeCount + ' ประเภท');
    
    // Build breakdown text
    if (data.raw && data.raw.length > 0) {
        const breakdownParts = data.raw.slice(0, 3).map(item => {
            const name = typeNames[item.type] || item.type || 'Other';
            return `${name}: ${item.count}`;
        });
        $('#pub-types-breakdown').text(breakdownParts.join(' | '));
    } else {
        $('#pub-types-breakdown').text('ไม่มีข้อมูล');
    }
}

/**
 * Update recent publications table
 */
function updateRecentPublications(data) {
    const tbody = $('#recent-publications-body');
    tbody.empty();
    
    if (!data || data.length === 0) {
        tbody.html('<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">ไม่พบผลงานวิจัย</td></tr>');
        return;
    }
    
    const typeColors = {
        'journal': 'bg-blue-100 text-blue-800',
        'book': 'bg-green-100 text-green-800',
        'proceedings': 'bg-purple-100 text-purple-800',
        'thesis': 'bg-amber-100 text-amber-800',
        'report': 'bg-cyan-100 text-cyan-800',
        'other': 'bg-gray-100 text-gray-800'
    };
    
    data.forEach(pub => {
        const typeClass = typeColors[pub.publication_type] || typeColors['other'];
        const title = pub.title ? (pub.title.length > 60 ? pub.title.substring(0, 60) + '...' : pub.title) : 'N/A';
        
        const row = `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <div class="text-sm font-medium text-gray-900" title="${escapeHtml(pub.title || '')}">${escapeHtml(title)}</div>
                </td>
                <td class="px-4 py-3">
                    <div class="text-sm text-gray-600">${escapeHtml(pub.created_by_name || 'N/A')}</div>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${typeClass}">
                        ${escapeHtml(pub.publication_type || 'other')}
                    </span>
                </td>
                <td class="px-4 py-3 text-center text-sm text-gray-600">
                    ${pub.publication_year || 'N/A'}
                </td>
                <td class="px-4 py-3">
                    <div class="text-sm text-gray-600">${escapeHtml(pub.faculty_name || 'N/A')}</div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

/**
 * Load year chart data
 */
function loadYearChartData() {
    $.ajax({
        url: API_ENDPOINTS.yearData,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && yearChart) {
                const labels = response.data.map(item => item.year);
                const values = response.data.map(item => item.count);
                
                yearChart.data.labels = labels;
                yearChart.data.datasets[0].data = values;
                yearChart.update();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading year data:', error);
        }
    });
}

/**
 * Load faculty chart data
 */
function loadFacultyChartData() {
    $.ajax({
        url: API_ENDPOINTS.facultyData,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && facultyChart) {
                const labels = response.data.map(item => item.faculty);
                const values = response.data.map(item => item.count);
                
                facultyChart.data.labels = labels;
                facultyChart.data.datasets[0].data = values;
                facultyChart.update();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading faculty data:', error);
        }
    });
}

/**
 * Update type chart
 */
function updateTypeChart(data) {
    if (!typeChart || !data.labels) return;
    
    typeChart.data.labels = data.labels;
    typeChart.data.datasets[0].data = data.data;
    typeChart.update();
}

/**
 * Update admission chart
 */
function updateAdmissionChart(data) {
    if (!admissionChart) return;
    
    const labels = ['Draft', 'Submitted', 'Approved', 'Rejected'];
    const values = [
        data.draft || 0,
        data.submitted || 0,
        data.approved || 0,
        data.rejected || 0
    ];
    
    admissionChart.data.labels = labels;
    admissionChart.data.datasets[0].data = values;
    admissionChart.update();
}

/**
 * Initialize Chart.js charts
 */
function initializeCharts() {
    // Year Chart (Bar)
    const yearCtx = document.getElementById('year-chart');
    if (yearCtx) {
        yearChart = new Chart(yearCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'ผลงานวิจัย',
                    data: [],
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: '#3b82f6',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    // Type Chart (Doughnut)
    const typeCtx = document.getElementById('type-chart');
    if (typeCtx) {
        typeChart = new Chart(typeCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: chartColors.background
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { font: { size: 11 }, padding: 15 }
                    }
                }
            }
        });
    }

    // Faculty Chart (Horizontal Bar)
    const facultyCtx = document.getElementById('faculty-chart');
    if (facultyCtx) {
        facultyChart = new Chart(facultyCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'ผลงาน',
                    data: [],
                    backgroundColor: chartColors.background,
                    borderWidth: 0,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    // Admission Chart (Pie)
    const admissionCtx = document.getElementById('admission-chart');
    if (admissionCtx) {
        admissionChart = new Chart(admissionCtx.getContext('2d'), {
            type: 'pie',
            data: {
                labels: ['Draft', 'Submitted', 'Approved', 'Rejected'],
                datasets: [{
                    data: [0, 0, 0, 0],
                    backgroundColor: [
                        'rgba(156, 163, 175, 0.8)', // gray
                        'rgba(59, 130, 246, 0.8)',  // blue
                        'rgba(16, 185, 129, 0.8)',  // green
                        'rgba(239, 68, 68, 0.8)'    // red
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { font: { size: 11 }, padding: 15 }
                    }
                }
            }
        });
    }
}

/**
 * Show default statistics on error
 */
function showDefaultStatistics() {
    $('#total-publications').text('0');
    $('#total-authors').text('0');
    $('#active-faculties').text('0');
    $('#total-curricula').text('0');
    $('#total-admission').text('0');
    $('#pub-year-count').text('0');
    $('#edu-completion-pct').text('0%');
}

/**
 * Format number with commas
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

// ============================================================
// CURRICULUM READINESS FUNCTIONS
// ============================================================

/**
 * Setup year filter event listener
 */
function setupReadinessYearFilter() {
    $('#readiness-year-filter').on('change', function() {
        loadCurriculumReadiness();
    });
}

/**
 * Load curriculum readiness data
 */
function loadCurriculumReadiness() {
    const academicYear = $('#readiness-year-filter').val() || (new Date().getFullYear() + 543);
    $('#readiness-year').text(academicYear);
    
    $.ajax({
        url: API_ENDPOINTS.curriculumReadiness,
        method: 'GET',
        data: { academic_year: academicYear },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateReadinessSummary(response.data.summary);
                updateReadinessTable(response.data.curricula);
            } else {
                console.error('Failed to load curriculum readiness:', response.message);
                showEmptyReadiness();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading curriculum readiness:', error);
            showEmptyReadiness();
        }
    });
}

/**
 * Update readiness summary cards
 */
function updateReadinessSummary(summary) {
    if (!summary) return;
    
    $('#readiness-ready').text(summary.ready || 0);
    $('#readiness-partial').text(summary.partial || 0);
    $('#readiness-not-ready').text(summary.not_ready || 0);
    $('#readiness-avg-score').text((summary.average_score || 0) + '%');
}

/**
 * Update curriculum readiness table
 */
function updateReadinessTable(data) {
    const tbody = $('#curriculum-readiness-body');
    tbody.empty();
    
    if (!data || data.length === 0) {
        tbody.html('<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">ไม่พบข้อมูลหลักสูตรในปีการศึกษานี้</td></tr>');
        return;
    }
    
    const degreeBadge = {
        'bachelor': '<span class="px-2 py-0.5 text-xs rounded bg-blue-100 text-blue-800">ป.ตรี</span>',
        'master': '<span class="px-2 py-0.5 text-xs rounded bg-green-100 text-green-800">ป.โท</span>',
        'doctoral': '<span class="px-2 py-0.5 text-xs rounded bg-amber-100 text-amber-800">ป.เอก</span>'
    };
    
    const statusBadge = {
        'draft': '<span class="px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-700">ร่าง</span>',
        'submitted': '<span class="px-2 py-0.5 text-xs rounded bg-blue-100 text-blue-700">รอพิจารณา</span>',
        'approved': '<span class="px-2 py-0.5 text-xs rounded bg-green-100 text-green-700">อนุมัติ</span>',
        'rejected': '<span class="px-2 py-0.5 text-xs rounded bg-red-100 text-red-700">ปฏิเสธ</span>'
    };
    
    const teachersBadge = {
        'complete': '<span class="px-2 py-0.5 text-xs rounded bg-green-100 text-green-700">ครบ</span>',
        'incomplete': '<span class="px-2 py-0.5 text-xs rounded bg-amber-100 text-amber-700">ไม่ครบ</span>'
    };
    
    const readinessStatusBadge = {
        'ready': '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">พร้อม</span>',
        'partial': '<span class="px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800">บางส่วน</span>',
        'not_ready': '<span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">ไม่พร้อม</span>'
    };
    
    data.forEach(item => {
        const curriculumName = item.curriculum_name || item.curriculum_official_name || 'N/A';
        const facultyName = item.faculty_name || 'N/A';
        const degree = degreeBadge[item.degree_level] || '<span class="text-gray-400">-</span>';
        const teachers = teachersBadge[item.teachers_status] || '<span class="text-gray-400">-</span>';
        const formStatus = statusBadge[item.status] || '<span class="text-gray-400">-</span>';
        const plan = item.admission_plan_count ? `<span class="font-medium">${item.admission_plan_count}</span> คน` : '<span class="text-gray-400">-</span>';
        const score = item.readiness_score || 0;
        const readinessStatus = readinessStatusBadge[item.readiness_status] || readinessStatusBadge['not_ready'];
        
        // Progress bar color
        let progressColor = 'bg-red-500';
        if (score >= 80) progressColor = 'bg-green-500';
        else if (score >= 50) progressColor = 'bg-amber-500';
        
        const row = `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <div class="text-sm font-medium text-gray-900">${escapeHtml(curriculumName)}</div>
                    <div class="text-xs text-gray-500">${escapeHtml(facultyName)}</div>
                </td>
                <td class="px-4 py-3 text-center">${degree}</td>
                <td class="px-4 py-3 text-center">${teachers}</td>
                <td class="px-4 py-3 text-center">${formStatus}</td>
                <td class="px-4 py-3 text-center">${plan}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div class="${progressColor} h-2 rounded-full" style="width: ${score}%"></div>
                        </div>
                        <span class="text-xs font-medium text-gray-600 w-8">${score}%</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-center">${readinessStatus}</td>
            </tr>
        `;
        tbody.append(row);
    });
}

/**
 * Show empty readiness state
 */
function showEmptyReadiness() {
    $('#readiness-ready').text('0');
    $('#readiness-partial').text('0');
    $('#readiness-not-ready').text('0');
    $('#readiness-avg-score').text('0%');
    $('#curriculum-readiness-body').html('<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">ไม่พบข้อมูล</td></tr>');
}

// ============================================================
// CURRICULUM PUBLICATIONS FUNCTIONS
// ============================================================

/**
 * Load curriculum publications data (5 years)
 */
function loadCurriculumPublications() {
    $.ajax({
        url: API_ENDPOINTS.curriculumPublications,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateCurriculumPublicationsSummary(response.data.summary);
                updateCurriculumPublicationsTable(response.data.curricula, response.data.years);
                updateCurriculumYearlyChart(response.data.summary.yearly_totals, response.data.years);
            } else {
                console.error('Failed to load curriculum publications:', response.message);
                showEmptyCurriculumPublications();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading curriculum publications:', error);
            showEmptyCurriculumPublications();
        }
    });
}

/**
 * Update curriculum publications summary cards
 */
function updateCurriculumPublicationsSummary(summary) {
    if (!summary) return;
    
    $('#cur-pub-total-curricula').text(summary.total_curricula || 0);
    $('#cur-pub-with-pubs').text(summary.curricula_with_publications || 0);
    $('#cur-pub-without-pubs').text(summary.curricula_without_publications || 0);
    $('#cur-pub-total').text(summary.total_publications || 0);
    $('#cur-pub-coverage').text((summary.coverage_percentage || 0) + '%');
}

/**
 * Update curriculum publications table
 */
function updateCurriculumPublicationsTable(data, years) {
    const tbody = $('#curriculum-publications-body');
    tbody.empty();
    
    if (!data || data.length === 0) {
        const colCount = 4 + years.length + 2; // curriculum + level + teachers + years + total + trend
        tbody.html(`<tr><td colspan="${colCount}" class="px-4 py-8 text-center text-gray-500">ไม่พบข้อมูลหลักสูตร</td></tr>`);
        return;
    }
    
    const degreeBadge = {
        'bachelor': '<span class="px-2 py-0.5 text-xs rounded bg-blue-100 text-blue-800">ป.ตรี</span>',
        'master': '<span class="px-2 py-0.5 text-xs rounded bg-green-100 text-green-800">ป.โท</span>',
        'doctoral': '<span class="px-2 py-0.5 text-xs rounded bg-amber-100 text-amber-800">ป.เอก</span>'
    };
    
    data.forEach(item => {
        const curriculumName = item.curriculum_name || 'N/A';
        const facultyName = item.faculty_name || 'N/A';
        const degree = degreeBadge[item.degree_level] || '<span class="text-gray-400">-</span>';
        const teacherCount = item.teacher_count || 0;
        const totalPubs = item.total_publications || 0;
        const trend = item.trend || 0;
        
        // Build year columns
        let yearCols = '';
        years.forEach(year => {
            const count = item.publications[year] || 0;
            const bgClass = count > 0 ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-400';
            yearCols += `<td class="px-4 py-3 text-center text-sm ${bgClass}">${count}</td>`;
        });
        
        // Trend indicator
        let trendHtml = '';
        if (trend > 0) {
            trendHtml = `<span class="inline-flex items-center text-green-600 text-sm font-medium">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                </svg>+${trend}
            </span>`;
        } else if (trend < 0) {
            trendHtml = `<span class="inline-flex items-center text-red-600 text-sm font-medium">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>${trend}
            </span>`;
        } else {
            trendHtml = '<span class="text-gray-400 text-sm">-</span>';
        }
        
        // Total cell with highlight if > 0
        const totalClass = totalPubs > 0 ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-400';
        
        const row = `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <div class="text-sm font-medium text-gray-900">${escapeHtml(curriculumName)}</div>
                    <div class="text-xs text-gray-500">${escapeHtml(facultyName)}</div>
                </td>
                <td class="px-4 py-3 text-center">${degree}</td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                        ${teacherCount} คน
                    </span>
                </td>
                ${yearCols}
                <td class="px-4 py-3 text-center ${totalClass}">${totalPubs}</td>
                <td class="px-4 py-3 text-center">${trendHtml}</td>
            </tr>
        `;
        tbody.append(row);
    });
}

/**
 * Update curriculum yearly chart
 */
function updateCurriculumYearlyChart(yearlyTotals, years) {
    const canvas = document.getElementById('curriculum-yearly-chart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Destroy existing chart
    if (curriculumYearlyChart) {
        curriculumYearlyChart.destroy();
    }
    
    const labels = years.map(y => y.toString());
    const data = years.map(y => yearlyTotals[y] || 0);
    
    curriculumYearlyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'ผลงานวิจัย',
                data: data,
                backgroundColor: 'rgba(99, 102, 241, 0.8)',
                borderColor: '#6366f1',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
}

/**
 * Show empty curriculum publications state
 */
function showEmptyCurriculumPublications() {
    $('#cur-pub-total-curricula').text('0');
    $('#cur-pub-with-pubs').text('0');
    $('#cur-pub-without-pubs').text('0');
    $('#cur-pub-total').text('0');
    $('#cur-pub-coverage').text('0%');
    $('#curriculum-publications-body').html('<tr><td colspan="10" class="px-4 py-8 text-center text-gray-500">ไม่พบข้อมูล</td></tr>');
}
