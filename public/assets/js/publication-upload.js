/**
 * Publication File Upload
 * Handles file uploads via input button and exposes uploaded file metadata.
 *
 * Note: AI processing logic now lives in publication-ai.js.
 */

// BASE_URL is already declared in the main page
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
let selectedFile = null;

// DOM Elements
const fileInput = document.getElementById('fileInput');
const uploadProgress = document.getElementById('uploadProgress');
const uploadedFile = document.getElementById('uploadedFile');
const fileName = document.getElementById('fileName');

// Optional elements (may not exist in simplified design)
const uploadPrompt = document.getElementById('uploadPrompt');
const progressBar = document.getElementById('progressBar');
const progressText = document.getElementById('progressText');
const fileSize = document.getElementById('fileSize');

if (fileInput) {
    fileInput.addEventListener('change', function () {
        if (this.files.length > 0) {
            handleFile(this.files[0]);
        }
    });
}


function showSelectedUserInfo(file) {
    if (fileName) fileName.textContent = file.name;
    if (fileSize) fileSize.textContent = formatFileSize(file.size);

    // Show "Prepared" state (distinct from "Uploaded")
    if (uploadPrompt) uploadPrompt.classList.add('hidden');
    if (uploadedFile) {
        uploadedFile.classList.remove('hidden');
        // Update status text to indicate "Selected" vs "Uploaded"
        const statusText = uploadedFile.querySelector('.text-green-800');
        if (statusText) {
            statusText.textContent = '✓ เลือกไฟล์แล้ว (รอการประมวลผล)';
            statusText.classList.remove('text-green-800');
            statusText.classList.add('text-blue-800'); // Change color to blue for "ready"
        }

        // Fix the checkmark icon if possible, but JS might be complex to swap SVG.
        // For now, text change is sufficient.
    }
}

// Expose for AI module
window.uploadSelectedFile = async function () {
    if (!selectedFile) {
        // If no new file selected, check if we already have uploadedFileData
        if (uploadedFileData) return uploadedFileData;
        throw new Error("No file selected");
    }

    // Trigger the actual upload
    return await handleFile(selectedFile);
};

/**
 * Handle file upload
 */
async function handleFile(file) {
    // Validate file
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = ['application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg', 'image/jpg', 'image/png'];

    if (!allowedTypes.includes(file.type)) {
        if (typeof showNotification === 'function') {
            showNotification('ไฟล์ประเภทนี้ไม่รองรับ กรุณาเลือกไฟล์ PDF, DOC, DOCX, JPG หรือ PNG', 'error');
        } else {
            alert('ไฟล์ประเภทนี้ไม่รองรับ กรุณาเลือกไฟล์ PDF, DOC, DOCX, JPG หรือ PNG');
        }
        return;
    }

    if (file.size > maxSize) {
        if (typeof showNotification === 'function') {
            showNotification('ไฟล์ใหญ่เกินไป ขนาดสูงสุด 10MB', 'error');
        } else {
            alert('ไฟล์ใหญ่เกินไป ขนาดสูงสุด 10MB');
        }
        return;
    }

    // Show progress
    if (uploadPrompt) uploadPrompt.classList.add('hidden');
    if (uploadedFile) uploadedFile.classList.add('hidden');
    if (uploadProgress) uploadProgress.classList.remove('hidden');

    // Upload file
    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await fetch(BASE_URL + '/index.php/utility/uploadFile', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();

        if (result.success) {
            // Store uploaded file data
            uploadedFileData = result.file;

            // Show uploaded file
            if (fileName) fileName.textContent = result.file.original_name;
            if (fileSize) fileSize.textContent = formatFileSize(result.file.file_size);

            // Set the ref_url in the form
            const refUrlInput = document.getElementById('ref_url');
            if (refUrlInput) {
                refUrlInput.value = result.file.ref_url;
            }

            if (uploadProgress) uploadProgress.classList.add('hidden');
            if (uploadedFile) uploadedFile.classList.remove('hidden');

            if (typeof showNotification === 'function') {
                showNotification('อัปโหลดไฟล์สำเร็จ', 'success');
            }

            // Dispatch event for AI assistant
            // const event = new CustomEvent('publication-file-uploaded', {
            //     detail: result.file
            // });
            // document.dispatchEvent(event);

            // Update UI to success state (green)
            const statusText = uploadedFile.querySelector('.text-blue-800');
            if (statusText) {
                statusText.textContent = '✓ อัปโหลดสำเร็จ';
                statusText.classList.remove('text-blue-800');
                statusText.classList.add('text-green-800');
            }

            return result.file;
        } else {
            throw new Error(result.message || 'Upload failed');
        }
    } catch (error) {
        console.error('Upload error:', error);

        if (typeof showNotification === 'function') {
            showNotification('เกิดข้อผิดพลาดในการอัปโหลดไฟล์', 'error');
        }

        // Reset to initial state
        if (uploadProgress) uploadProgress.classList.add('hidden');
        if (uploadPrompt) uploadPrompt.classList.remove('hidden');
    }
}

/**
 * Remove uploaded file
 */
async function removeUploadedFile() {
    if (!uploadedFileData) return;

    try {
        // Check if we are just clearing the selection (not yet uploaded)
        if (!uploadedFileData && selectedFile) {
            // Just clear selection
            selectedFile = null;
            if (fileInput) fileInput.value = '';
            if (uploadedFile) uploadedFile.classList.add('hidden');
            if (uploadPrompt) uploadPrompt.classList.remove('hidden');
            // Reset UI color
            const statusText = uploadedFile ? uploadedFile.querySelector('.text-green-800, .text-blue-800') : null;
            if (statusText) {
                statusText.classList.remove('text-blue-800');
                statusText.classList.add('text-green-800'); // Reset to default class for next time (though innerHTML changes)
            }
            return;
        }

        const response = await fetch(BASE_URL + '/index.php/utility/deleteFile', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                file_name: uploadedFileData.stored_name
            })
        });

        const result = await response.json();

        if (result.success) {
            uploadedFileData = null;
            if (uploadedFile) uploadedFile.classList.add('hidden');
            if (uploadPrompt) uploadPrompt.classList.remove('hidden');

            // Clear the ref_url in the form
            const refUrlInput = document.getElementById('ref_url');
            if (refUrlInput) {
                refUrlInput.value = '';
            }

            // Clear file input
            if (fileInput) fileInput.value = '';

            if (typeof showNotification === 'function') {
                showNotification('ลบไฟล์สำเร็จ', 'success');
            }
        }
    } catch (error) {
        console.error('Delete error:', error);
        if (typeof showNotification === 'function') {
            showNotification('เกิดข้อผิดพลาดในการลบไฟล์', 'error');
        }
    }
}

/**
 * Utility Functions
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function showNotification(message, type = 'info') {
    // Use existing notification system if available
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            text: message,
            icon: type === 'error' ? 'error' : type === 'warning' ? 'warning' : type === 'success' ? 'success' : 'info',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    } else {
        alert(message);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    console.log('Publication upload module initialized');
});

// Expose helpers for other modules
window.removeUploadedFile = removeUploadedFile;
window.getUploadedFileData = function () {
    return uploadedFileData;
};
