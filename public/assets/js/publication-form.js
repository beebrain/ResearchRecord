/**
 * FIXED Publication Form JavaScript
 * Problem: Publication type selection not working properly
 * Solution: Improved conditional field toggling
 */

// ==========================================
// Configuration & Initialization
// ==========================================

$(document).ready(function() {
    initializeForm();
    
    // Debug: Check if elements exist
    console.log('Publication type cards:', $('.publication-type-card').length);
    console.log('Conditional fields:', $('.conditional-field').length);
});

function initializeForm() {
    setupPublicationTypes();
    setupQuickFill();
    initializeAuthors();
    setupFormValidation();
}

// ==========================================
// FIXED Publication Type Selection
// ==========================================

function setupPublicationTypes() {
    $('.publication-type-card').on('click', function() {
        selectPublicationType($(this));
    });
}

function selectPublicationType($card) {
    // Remove selection from all cards
    $('.publication-type-card').removeClass('selected');
    
    // Add selection to clicked card
    $card.addClass('selected');
    
    // Set hidden input value
    const type = $card.data('type');
    $('#publication_type').val(type);
    
    console.log('Selected publication type:', type); // Debug
    
    // Update quick fill placeholder
    updateQuickFillPlaceholder(type);
    
    // Show/hide conditional fields - FIXED VERSION
    toggleConditionalFields(type);
}

// FIXED VERSION - Reliable conditional field toggling
function toggleConditionalFields(type) {
    console.log('Toggling fields for type:', type);
    
    // Method 1: Try data-types attribute approach first
    $('.conditional-field').hide();
    
    if (type) {
        const $fieldsToShow = $(`.conditional-field[data-types*="${type}"]`);
        console.log('Fields found with data-types:', $fieldsToShow.length);
        
        if ($fieldsToShow.length > 0) {
            $fieldsToShow.show();
        } else {
            // Method 2: Fallback to explicit field mapping
            showFieldsByType(type);
        }
    }
}

// Explicit field mapping for each publication type
function showFieldsByType(type) {
    console.log('Using explicit field mapping for:', type);

    // Hide all conditional fields first
    const conditionalFieldIds = [
        'volume', 'issue', 'pages', 'doi', 'isbn',
        'publisher', 'book_title', 'chapter', 'editor',
        'conference_name', 'conference_location', 'conference_date'
    ];
    conditionalFieldIds.forEach(id => {
        const $field = $(`#${id}`);
        if ($field.length) {
            $field.closest('div').hide(); // Hide the parent div
        }
    });

    // Show fields based on publication type
    let fieldsToShow = [];

    switch(type) {
        case 'journal':
            fieldsToShow = ['volume', 'issue', 'pages', 'doi'];
            break;
        case 'book':
            fieldsToShow = ['pages', 'isbn', 'publisher', 'book_title', 'chapter', 'editor'];
            break;
        case 'proceedings':
            fieldsToShow = ['volume', 'issue', 'pages', 'doi', 'conference_name', 'conference_location', 'conference_date'];
            break;
        case 'thesis':
        case 'report':
        case 'other':
        default:
            fieldsToShow = ['pages'];
            break;
    }

    // Show the selected fields
    fieldsToShow.forEach(id => {
        const $field = $(`#${id}`);
        if ($field.length) {
            $field.closest('div').show(); // Show the parent div
            console.log(`Showing field: ${id}`);
        }
    });
}

// Alternative approach using CSS classes
function toggleConditionalFieldsCSS(type) {
    // Remove all type-specific classes
    $('.conditional-field').removeClass('show-journal show-book show-proceedings show-thesis show-report show-other');
    
    // Add the appropriate class
    if (type) {
        $(`.conditional-field`).addClass(`show-${type}`);
    }
}

function updateQuickFillPlaceholder(type) {
    const $input = $('#quickFillInput');
    
    switch(type) {
        case 'book':
            $input.attr('placeholder', 'Enter ISBN for auto-fill...');
            break;
        case 'journal':
        case 'proceedings':
            $input.attr('placeholder', 'Enter DOI for auto-fill...');
            break;
        default:
            $input.attr('placeholder', 'Enter DOI or ISBN for auto-fill...');
    }
}

// ==========================================
// Quick Fill Functionality (unchanged)
// ==========================================

function setupQuickFill() {
    $('#quickFillBtn').on('click', quickFillData);
    $('#quickFillInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            quickFillData();
        }
    });
}

function quickFillData() {
    const identifier = $('#quickFillInput').val().trim();
    
    if (!identifier) {
        showMessage('Please enter a DOI or ISBN', 'error');
        return;
    }

    const $button = $('#quickFillBtn');
    const originalText = $button.text();
    
    $button.prop('disabled', true).text('Looking up...');

    setTimeout(() => {
        const mockData = getMockData(identifier);
        fillFormData(mockData);
        
        $button.prop('disabled', false).text(originalText);
        showMessage('Data retrieved successfully!', 'success');
    }, 1500);
}

function getMockData(identifier) {
    return {
        title: `Sample Publication Title from ${identifier}`,
        source: 'Sample Journal Name',
        publication_year: 2023,
        abstract: 'This is a sample abstract retrieved from the lookup service.',
        keywords: 'artificial intelligence, machine learning, data science'
    };
}

function fillFormData(data) {
    Object.keys(data).forEach(key => {
        const $input = $(`#${key}`);
        if ($input.length && data[key]) {
            $input.val(data[key]);
        }
    });
}

// ==========================================
// Author Management (unchanged)
// ==========================================

function initializeAuthors() {
    // Check if there are any existing author rows
    const existingAuthors = $('.author-row').length;

    if (existingAuthors === 0) {
        // Add initial author row if none exists
        addAuthor();
    } else {
        // Update count based on existing rows
        authorCount = existingAuthors;
    }

    updateAuthorStatus();
}

function addAuthor() {
    const container = $('#authorsContainer');
    const newAuthorHtml = createAuthorRowHtml(authorCount);
    
    const $newRow = $(newAuthorHtml);
    $newRow.addClass('author-row-enter');
    
    container.append($newRow);
    
    scrollToElement($newRow[0]);
    
    setTimeout(() => {
        $newRow.find('input[name*="[name]"]').focus();
    }, 300);
    
    authorCount++;
    updateAuthorStatus();
}

function createAuthorRowHtml(index) {
    return `
        <div class="author-row">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                <div class="input-group">
                    <input type="text"
                           name="authors[${index}][name]"
                           placeholder="ชื่อผู้แต่ง"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="input-group">
                    <input type="email"
                           name="authors[${index}][email]"
                           placeholder="อีเมล (ไม่บังคับ)"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="input-group flex">
                    <input type="text"
                           name="authors[${index}][affiliation]"
                           placeholder="หน่วยงาน (ไม่บังคับ)"
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <button type="button"
                            onclick="removeAuthor(this)"
                            class="ml-2 px-2 py-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="flex items-center ml-1">
                <input type="checkbox"
                       name="authors[${index}][corresponding]"
                       value="1"
                       class="corresponding-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                       onchange="handleCorrespondingChange(this)">
                <label class="ml-2 text-sm font-medium text-gray-700">
                    ผู้แต่งที่ติดต่อได้ (Corresponding Author)
                </label>
            </div>
        </div>
    `;
}

function removeAuthor(button) {
    const $row = $(button).closest('.author-row');
    const totalRows = $('.author-row').length;
    
    if (totalRows <= 1) {
        showMessage('At least one author is required', 'error');
        return;
    }
    
    $row.fadeOut(300, function() {
        $(this).remove();
        updateAuthorNumbers();
        updateAuthorStatus();
    });
}

function updateAuthorNumbers() {
    $('.author-row').each(function(index) {
        $(this).find('input').each(function() {
            const name = $(this).attr('name');
            if (name) {
                const newName = name.replace(/\[\d+\]/, `[${index}]`);
                $(this).attr('name', newName);
            }
        });
    });
    
    authorCount = $('.author-row').length;
}

function updateAuthorStatus() {
    const count = $('.author-row').length;
    const text = count === 1 ? '1 author added' : `${count} authors added`;
    $('#authorStatus').text(text);
}

// ==========================================
// Form Validation & Submission (unchanged)
// ==========================================

function setupFormValidation() {
    $('#publicationForm').on('submit', function(e) {
        e.preventDefault();
        submitForm();
    });
}

function validateForm() {
    let isValid = true;
    const errors = [];
    
    const requiredFields = [
        { id: 'title', name: 'Title' },
        { id: 'publication_type', name: 'Publication Type' },
        { id: 'source', name: 'Source' }
    ];
    
    requiredFields.forEach(field => {
        const $input = $(`#${field.id}`);
        if (!$input.val().trim()) {
            errors.push(`${field.name} is required`);
            $input.addClass('border-red-500');
            isValid = false;
        } else {
            $input.removeClass('border-red-500');
        }
    });
    
    const authorNames = $('input[name*="[name]"]').filter(function() {
        return $(this).val().trim() !== '';
    });
    
    if (authorNames.length === 0) {
        errors.push('At least one author is required');
        isValid = false;
    }
    
    if (!isValid) {
        showMessage('Please fix the following errors: ' + errors.join(', '), 'error');
    }
    
    return isValid;
}

function submitForm() {
    if (!validateForm()) {
        return;
    }
    
    // Show loading
    Swal.fire({
        title: 'Saving...',
        text: 'Please wait while we save your publication',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    const formData = collectFormData();
    
    $.ajax({
        url: $('#publicationForm').attr('action'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            Swal.close();
            handleSubmitSuccess(response);
        },
        error: function(xhr, status, error) {
            Swal.close();
            handleSubmitError(xhr, status, error);
        }
    });
}

function handleSubmitSuccess(response) {
    if (response.success) {
        const redirectUrl = response.redirect || '';
        if (redirectUrl) {
            Swal.fire({
                title: 'Success! 🎉',
                text: response.message || 'Publication added successfully!',
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: '#28a745',
                timer: 2000,
                timerProgressBar: true,
            }).then(function () {
                window.location.href = redirectUrl;
            });
            setTimeout(function () {
                window.location.href = redirectUrl;
            }, 2200);

            return;
        }

        clearPublicationForm();
        Swal.fire({
            title: 'Success! 🎉',
            text: 'Publication added successfully!',
            icon: 'success',
            confirmButtonText: 'OK',
            confirmButtonColor: '#28a745',
            timer: 3000,
            timerProgressBar: true,
        });
    } else {
        Swal.fire('Error!', response.message || 'Failed to add publication', 'error');
    }
}

function handleSubmitError(xhr, status, error) {
    let errorMessage = 'An error occurred while adding the publication';
    
    if (xhr.responseJSON && xhr.responseJSON.message) {
        errorMessage = xhr.responseJSON.message;
    }
    
    Swal.fire('Error!', errorMessage, 'error');
}

function clearPublicationForm() {
    document.getElementById('publicationForm').reset();

    // Clear publication type selection
    $('.publication-type-card').removeClass('selected');
    $('#publication_type').val('');

    // Clear and reset authors
    clearAuthors();
}

function clearAuthors() {
    // Remove all author rows
    $('#authorsContainer').empty();

    // Reset author count
    authorCount = 0;

    // Add one initial author row
    addAuthor();
}

function collectFormData() {
    const formData = new FormData($('#publicationForm')[0]);
    const authors = getAuthorsData();
    formData.append('authors', JSON.stringify(authors));
    return formData;
}

function getAuthorsData() {
    const authors = [];
    
    $('.author-row').each(function(index) {
        const $row = $(this);
        const name = $row.find('input[name*="[name]"]').val().trim();
        
        if (name) {
            authors.push({
                name: name,
                email: $row.find('input[name*="[email]"]').val().trim(),
                affiliation: $row.find('input[name*="[affiliation]"]').val().trim(),
                author_id: $row.find('input[name*="[email]"]').data('author-id') || null,
                user_uid: $row.find('input[name*="[email]"]').data('user-uid') || null,
                order: index + 1
            });
        }
    });
    
    return authors;
}


function showSubmitLoading(show) {
    const $submitText = $('#submitText');
    const $submitLoading = $('#submitLoading');
    const $submitBtn = $('button[type="submit"]');
    
    if (show) {
        $submitText.addClass('hidden');
        $submitLoading.removeClass('hidden');
        $submitBtn.prop('disabled', true);
    } else {
        $submitText.removeClass('hidden');
        $submitLoading.addClass('hidden');
        $submitBtn.prop('disabled', false);
    }
}

// ==========================================
// Utility Functions
// ==========================================

function scrollToElement(element) {
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    }
}

function showMessage(message, type) {
    const alertClass = type === 'success' 
        ? 'bg-green-100 border-green-400 text-green-700' 
        : 'bg-red-100 border-red-400 text-red-700';
    
    const icon = type === 'success'
        ? '<svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
        : '<svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';

    const alertHtml = `
        <div class="mb-6 ${alertClass} px-4 py-3 rounded border flex items-center">
            ${icon}
            ${message}
        </div>
    `;

    $('.mb-6.bg-green-100, .mb-6.bg-red-100').remove();
    $('main').prepend(alertHtml);
    
    if (type === 'success') {
        setTimeout(() => {
            $('.mb-6.bg-green-100').fadeOut();
        }, 5000);
    }
}

// ==========================================
// Debug Functions (remove in production)
// ==========================================

function debugPublicationTypes() {
    console.log('=== Publication Type Debug ===');
    console.log('Cards found:', $('.publication-type-card').length);
    console.log('Current type:', $('#publication_type').val());
    console.log('Conditional fields:', $('.conditional-field').length);
    
    $('.conditional-field').each(function(i) {
        const $field = $(this);
        console.log(`Field ${i}:`, {
            html: $field.html().substring(0, 50),
            visible: $field.is(':visible'),
            dataTypes: $field.attr('data-types')
        });
    });
}

// Call debug function (remove in production)
$(document).ready(function() {
    setTimeout(debugPublicationTypes, 1000);
});

// ==========================================
// Corresponding Author Handling
// ==========================================

/**
 * Handle corresponding author checkbox change
 * Ensures only one author can be marked as corresponding at a time
 */
function handleCorrespondingChange(checkbox) {
    if (checkbox.checked) {
        // Uncheck all other corresponding checkboxes
        document.querySelectorAll('.corresponding-checkbox').forEach(function(cb) {
            if (cb !== checkbox) {
                cb.checked = false;
            }
        });

        // Add visual indicator
        const authorRow = checkbox.closest('.author-row');
        authorRow.classList.add('corresponding-author');

        // Show notification
        showMessage('ผู้แต่งที่ติดต่อได้ถูกกำหนดแล้ว', 'success');
    } else {
        // Remove visual indicator
        const authorRow = checkbox.closest('.author-row');
        authorRow.classList.remove('corresponding-author');
    }
}