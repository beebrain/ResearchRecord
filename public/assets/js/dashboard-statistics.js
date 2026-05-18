/**
 * Dashboard Statistics JavaScript
 * Handles AJAX requests and data visualization for the admin dashboard
 */

let facultyChart = null;
let yearChart = null;
let publicationsTable = null;

// Initialize dashboard on page load
$(document).ready(function() {
    initializeDashboard();
});

/**
 * Initialize the dashboard - load all data
 */
function initializeDashboard() {
    initializeCharts();
    loadStatistics();
    loadPublications();
    loadFacultyData();
    loadYearData();
    loadCurriculumData();

    // Setup event listeners
    $('#faculty-filter').on('change', loadCurriculumData);
}

/**
 * Load dashboard statistics (total counts)
 */
function loadStatistics() {
    console.log('Loading statistics from:', API_ENDPOINTS.statistics);

    $.ajax({
        url: API_ENDPOINTS.statistics,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Statistics response:', response);
            if (response.success) {
                updateStatistics(response.data);
            } else {
                console.error('Failed to load statistics:', response.message);
                updateStatistics({
                    totalPublications: 0,
                    totalAuthors: 0,
                    activeFaculties: 0
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading statistics:', error);
            console.error('XHR:', xhr);
            console.error('Status:', status);
            console.error('Response Text:', xhr.responseText);
            // Show default values on error
            updateStatistics({
                totalPublications: 0,
                totalAuthors: 0,
                activeFaculties: 0
            });
        }
    });
}

/**
 * Update statistics display
 */
function updateStatistics(data) {
    $('#total-publications').text(data.totalPublications || 0);
    $('#total-authors').text(data.totalAuthors || 0);
    $('#active-faculties').text(data.activeFaculties || 0);
}

/**
 * Load all publications for the DataTable
 */
function loadPublications() {
    console.log('Loading publications from:', API_ENDPOINTS.publications);

    // If DataTable already exists, destroy it
    if (publicationsTable) {
        publicationsTable.destroy();
    }

    // Initialize DataTable with AJAX source
    publicationsTable = $('#publications-table').DataTable({
        ajax: {
            url: API_ENDPOINTS.publications,
            type: 'GET',
            data: { limit: 1000 }, // Load all publications
            dataSrc: function(response) {
                console.log('Publications response:', response);
                if (response.success) {
                    $('#empty-state').addClass('hidden');
                    return response.data;
                } else {
                    console.error('Failed to load publications:', response.message);
                    showEmptyState();
                    return [];
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading publications:', error);
                console.error('XHR:', xhr);
                console.error('Response Text:', xhr.responseText);
                showEmptyState();
            }
        },
        columns: [
            {
                data: null,
                render: function(data, type, row) {
                    const title = escapeHtml(row.title || '');
                    const source = escapeHtml(row.source || '');
                    return `
                        <div class="text-sm font-medium text-gray-900">${title}</div>
                        ${source ? `<div class="text-sm text-gray-500">${source}</div>` : ''}
                    `;
                }
            },
            {
                data: 'authors_names_thai',
                render: function(data, type, row) {
                    return `<div class="text-sm text-gray-900">${escapeHtml(data || 'N/A')}</div>`;
                }
            },
            {
                data: 'author_faculties',
                render: function(data, type, row) {
                    return generateBadges(data, 'blue');
                }
            },
            {
                data: 'author_curriculum',
                render: function(data, type, row) {
                    return generateBadges(data, 'green');
                }
            },
            {
                data: 'publication_year',
                render: function(data, type, row) {
                    return `<span class="text-sm text-gray-900">${data || 'N/A'}</span>`;
                }
            },
            {
                data: 'publication_type',
                render: function(data, type, row) {
                    return `<span class="text-sm text-gray-900">${escapeHtml(data || 'N/A')}</span>`;
                }
            }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[4, 'desc']], // Sort by year descending
        language: {
            search: "Search publications:",
            lengthMenu: "Show _MENU_ publications per page",
            info: "Showing _START_ to _END_ of _TOTAL_ publications",
            infoEmpty: "No publications available",
            infoFiltered: "(filtered from _MAX_ total publications)",
            zeroRecords: "No matching publications found",
            emptyTable: "No publications available"
        },
        responsive: true,
        autoWidth: false
    });
}

/**
 * Generate badges for faculties or curricula
 */
function generateBadges(data, color) {
    if (!data || !data.trim()) {
        return '<span class="text-xs text-gray-400">N/A</span>';
    }

    const items = data.split(', ').filter(item => item.trim());
    if (items.length === 0) {
        return '<span class="text-xs text-gray-400">N/A</span>';
    }

    const colorClasses = {
        blue: 'bg-blue-100 text-blue-800',
        green: 'bg-green-100 text-green-800',
        purple: 'bg-purple-100 text-purple-800',
        orange: 'bg-orange-100 text-orange-800'
    };

    const badges = items.map(item =>
        `<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${colorClasses[color] || colorClasses.blue}">
            ${escapeHtml(item.trim())}
        </span>`
    ).join(' ');

    return `<div class="flex flex-wrap gap-1">${badges}</div>`;
}

/**
 * Show empty state
 */
function showEmptyState() {
    $('#publications-table-body').empty();
    $('#empty-state').removeClass('hidden');
}

/**
 * Load faculty distribution data for chart
 */
function loadFacultyData() {
    $.ajax({
        url: API_ENDPOINTS.facultyData,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateFacultyChart(response.data);
                updateFacultyFilter(response.data);
            } else {
                console.error('Failed to load faculty data:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading faculty data:', error);
        }
    });
}

/**
 * Update faculty chart
 */
function updateFacultyChart(data) {
    if (!facultyChart) return;

    const labels = data.map(item => item.faculty);
    const values = data.map(item => item.count);

    facultyChart.data.labels = labels;
    facultyChart.data.datasets[0].data = values;
    facultyChart.update();
}

/**
 * Update faculty filter dropdown
 */
function updateFacultyFilter(data) {
    const facultyFilter = $('#faculty-filter');
    const currentValue = facultyFilter.val();

    // Clear existing options except "All Faculties"
    facultyFilter.find('option:not(:first)').remove();

    data.forEach(item => {
        const option = `<option value="${item.id}">${escapeHtml(item.faculty)} (${escapeHtml(item.code)})</option>`;
        facultyFilter.append(option);
    });

    // Restore previous selection if it still exists
    facultyFilter.val(currentValue);
}

/**
 * Load year distribution data for chart
 */
function loadYearData() {
    $.ajax({
        url: API_ENDPOINTS.yearData,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateYearChart(response.data);
            } else {
                console.error('Failed to load year data:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading year data:', error);
        }
    });
}

/**
 * Update year chart
 */
function updateYearChart(data) {
    if (!yearChart) return;

    const labels = data.map(item => item.year);
    const values = data.map(item => item.count);

    yearChart.data.labels = labels;
    yearChart.data.datasets[0].data = values;
    yearChart.update();
}

/**
 * Load curriculum summary data
 */
function loadCurriculumData() {
    const facultyFilter = $('#faculty-filter').val();

    $.ajax({
        url: API_ENDPOINTS.curriculumData,
        method: 'GET',
        dataType: 'json',
        data: { faculty_id: facultyFilter },
        success: function(response) {
            if (response.success) {
                updateCurriculumSummary(response.data);
            } else {
                console.error('Failed to load curriculum data:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading curriculum data:', error);
            console.error('Response:', xhr.responseText);
        }
    });
}

/**
 * Update curriculum summary cards
 */
function updateCurriculumSummary(data) {
    const summaryContainer = $('#curriculum-summary');
    summaryContainer.empty();

    if (!data || data.length === 0) {
        summaryContainer.html('<p class="text-gray-500 text-center py-8 col-span-full">No curriculum found. Add some curriculum to see the summary.</p>');
        return;
    }

    data.forEach(item => {
        const card = `
            <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="mb-3">
                    <h4 class="text-sm font-semibold text-gray-900 mb-1">${escapeHtml(item.curriculum_name || item.name)}</h4>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs text-gray-500">${escapeHtml(item.faculty_name || 'N/A')}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">${escapeHtml(item.code || '')}</span>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-600">Publications</span>
                        <span class="text-sm font-semibold text-blue-600">${item.publication_count || 0}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-600">Authors</span>
                        <span class="text-sm font-semibold text-green-600">${item.author_count || 0}</span>
                    </div>
                </div>
            </div>
        `;
        summaryContainer.append(card);
    });
}

/**
 * Initialize Chart.js charts
 */
function initializeCharts() {
    // Faculty Chart (Doughnut)
    const facultyCtx = document.getElementById('faculty-chart').getContext('2d');
    facultyChart = new Chart(facultyCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#3b82f6', // blue
                    '#10b981', // green
                    '#f59e0b', // orange
                    '#ef4444', // red
                    '#8b5cf6', // purple
                    '#06b6d4', // cyan
                    '#f97316', // orange-500
                    '#ec4899'  // pink
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 10,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Year Chart (Line)
    const yearCtx = document.getElementById('year-chart').getContext('2d');
    yearChart = new Chart(yearCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Publications',
                data: [],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Publications: ${context.parsed.y}`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Utility function to escape HTML to prevent XSS
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

/**
 * Show notification message
 */
function showMessage(message, type = 'info') {
    const bgColor = type === 'success' ? 'bg-green-500' :
                    type === 'error' ? 'bg-red-500' :
                    'bg-blue-500';

    const messageDiv = $(`
        <div class="fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in">
            ${escapeHtml(message)}
        </div>
    `);

    $('body').append(messageDiv);

    setTimeout(() => {
        messageDiv.fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}
