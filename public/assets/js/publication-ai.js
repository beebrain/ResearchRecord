/**
 * Publication AI Assistant
 * Handles AI extraction, previews, and form population.
 */

// Use BASE_URL from window (defined in page) or empty string as fallback
// Don't redeclare if already exists
if (typeof window.BASE_URL === 'undefined') {
    window.BASE_URL = '';
}

let aiExtractedData = null;

// DOM Elements
const aiModal = document.getElementById('aiModal');
const aiAssistBtn = document.getElementById('ai-assist-btn');
const aiToggle = document.getElementById('ai-toggle');
const urlInputSection = document.getElementById('url-input-section');
const aiUrlInput = document.getElementById('ai-url-input');
const aiInfoCards = document.getElementById('ai-info-cards');
const aiFileUploadSection = document.getElementById('ai-file-upload-section');
const aiRawJsonContainer = document.getElementById('aiRawJsonContainer');
const aiRawJson = document.getElementById('aiRawJson');
const applyAIDataBtn = document.getElementById('applyAIData');
const aiWaitingModal = document.getElementById('aiWaitingModal');
const aiAutoFillMessage = document.getElementById('aiAutoFillMessage');

//const AI_API_URL = 'https://beebrain.duckdns.org:5678/webhook/journal';
const AI_API_URL = 'https://sweetmeal-loamless-wendy.ngrok-free.dev/webhook/extract-article';

// Export AI_API_URL for reuse in other files
window.AI_API_URL = AI_API_URL;

/**
 * Validate and normalize URL before sending to AI
 */
function validateUrlForAI(url) {
    if (!url || typeof url !== 'string') {
        return { valid: false, message: 'URL ไม่ถูกต้อง' };
    }

    let trimmedUrl = url.trim();

    // Check for localhost URLs (AI service can't access these)
    if (trimmedUrl.includes('localhost') || trimmedUrl.includes('127.0.0.1') || trimmedUrl.includes('::1')) {
        return {
            valid: false,
            message: 'ไม่สามารถใช้ URL localhost ได้ AI ไม่สามารถเข้าถึง URL นี้ กรุณาใช้ URL ที่เข้าถึงได้จากอินเทอร์เน็ต หรืออัปโหลดไฟล์แทน'
        };
    }

    // Check for HTTPS (recommended for most academic sites)
    if (!trimmedUrl.startsWith('http://') && !trimmedUrl.startsWith('https://')) {
        return { valid: false, message: 'URL ต้องเริ่มต้นด้วย http:// หรือ https://' };
    }

    // Check for valid URL format and normalize
    try {
        const urlObj = new URL(trimmedUrl);
        // Use the normalized URL from URL object (handles encoding properly)
        trimmedUrl = urlObj.href;
    } catch (e) {
        return { valid: false, message: 'รูปแบบ URL ไม่ถูกต้อง: ' + e.message };
    }

    return { valid: true, url: trimmedUrl };
}

/**
 * Main function to process with AI - checks if file or URL is provided
 */
async function processWithAI() {
    // Show blocking modal immediately when button is clicked
    showAIWaitingModal();

    // Disable the AI button immediately
    const aiButton = document.getElementById('ai-assist-btn');
    if (aiButton) {
        aiButton.disabled = true;
        aiButton.style.opacity = '0.5';
        aiButton.style.cursor = 'not-allowed';
    }

    let uploadedFileData = typeof window.getUploadedFileData === 'function' ? window.getUploadedFileData() : null;
    const urlValue = aiUrlInput ? aiUrlInput.value.trim() : '';

    // Check if user has provided a URL 
    if (urlValue) {
        // User provided a URL - process directly
        // Note: Logic allows URL priority if both exist, or we could strict check
        await runAIExtraction();
        return;
    }

    // Check if file is uploaded
    if (uploadedFileData) {
        // User uploaded a file - process directly
        await runAIExtraction();
    } else {
        // Hide modal and show error
        hideAIWaitingModal();

        // Re-enable button
        if (aiButton) {
            aiButton.disabled = false;
            aiButton.style.opacity = '1';
            aiButton.style.cursor = 'pointer';
        }

        // No file or URL provided
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'ไม่พบข้อมูล',
                text: 'กรุณาอัปโหลดไฟล์หรือใส่ URL ของเอกสารก่อนใช้ AI',
                confirmButtonText: 'ตกลง'
            });
        } else if (typeof showNotification === 'function') {
            showNotification('กรุณาอัปโหลดไฟล์หรือใส่ URL ของเอกสารก่อนใช้ AI', 'warning');
        } else {
            alert('กรุณาอัปโหลดไฟล์หรือใส่ URL ของเอกสารก่อนใช้ AI');
        }
    }
}

/**
 * Run AI extraction with blocking modal
 */
async function runAIExtraction() {
    try {
        // Modal already shown by processWithAI(), just call extraction
        await callAIExtraction();

    } catch (error) {
        console.error('AI extraction error:', error);

        hideAIWaitingModal();

        // Re-enable the AI button on error
        const aiButton = document.getElementById('ai-assist-btn');
        if (aiButton) {
            aiButton.disabled = false;
            aiButton.style.opacity = '1';
            aiButton.style.cursor = 'pointer';
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถประมวลผลด้วย AI ได้ กรุณาลองใหม่อีกครั้ง',
                confirmButtonText: 'ตกลง'
            });
        }
    }
}

/**
 * Initialise AI assistant behaviour
 */
function initAIAssistant() {
    if (aiToggle) {
        aiToggle.addEventListener('change', function () {
            const isEnabled = this.checked;
            toggleAISections(isEnabled);

            if (typeof showNotification === 'function') {
                showNotification(isEnabled ? 'เปิดใช้งาน AI สำเร็จ' : 'ปิดใช้งาน AI แล้ว', isEnabled ? 'success' : 'info');
            }
        });

        toggleAISections(aiToggle.checked);
    }

    if (aiAssistBtn) {
        aiAssistBtn.addEventListener('click', function () {
            const uploadedFileData = typeof window.getUploadedFileData === 'function'
                ? window.getUploadedFileData()
                : null;

            const hasFile = uploadedFileData !== null;
            const hasUrl = aiUrlInput && aiUrlInput.value.trim() !== '';

            if (!hasFile && !hasUrl) {
                if (typeof showNotification === 'function') {
                    showNotification('กรุณาอัปโหลดไฟล์หรือใส่ URL ก่อน', 'warning');
                }
                return;
            }

            openAIModal();
        });
    }

    if (applyAIDataBtn) {
        applyAIDataBtn.addEventListener('click', onApplyAIData);
    }

    // NOTE: Removed click-outside-to-close behavior
    // Modal can only be closed via X button or Close button
    // This prevents accidental closing during AI processing

    // Prevent ESC key from closing modal during processing
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            // Only allow ESC to close if not processing
            const isProcessing = aiWaitingModal && !aiWaitingModal.classList.contains('hidden');
            if (isProcessing) {
                event.preventDefault();
                event.stopPropagation();
            }
        }
    });

    console.log('Publication AI assistant initialised');

    // Auto-trigger listener removed (User request: Manual trigger only)
}

function toggleAISections(isEnabled) {
    if (aiAssistBtn) {
        aiAssistBtn.style.display = isEnabled ? 'flex' : 'none';
    }
    if (urlInputSection) {
        urlInputSection.style.display = isEnabled ? 'block' : 'none';
    }
    if (aiInfoCards) {
        aiInfoCards.style.display = isEnabled ? 'grid' : 'none';
    }
    if (aiFileUploadSection) {
        aiFileUploadSection.style.display = isEnabled ? 'block' : 'none';
    }
}

function openAIModal() {
    if (!aiModal) return;

    aiModal.classList.remove('hidden');

    document.getElementById('aiInitial')?.classList.remove('hidden');
    document.getElementById('aiProcessing')?.classList.add('hidden');
    document.getElementById('aiSuccess')?.classList.add('hidden');
    document.getElementById('aiError')?.classList.add('hidden');
    applyAIDataBtn?.classList.add('hidden');

    if (aiRawJson) {
        aiRawJson.textContent = '';
    }
    if (aiRawJsonContainer) {
        aiRawJsonContainer.classList.add('hidden');
    }


    if (aiAutoFillMessage) {
        aiAutoFillMessage.classList.add('hidden');
    }
}

function closeAIModal() {
    if (!aiModal) return;
    aiModal.classList.add('hidden');
    hideAIWaitingModal();
}

function showAIWaitingModal() {
    if (!aiWaitingModal) return;
    aiWaitingModal.classList.remove('hidden');
}

function hideAIWaitingModal() {
    if (!aiWaitingModal) return;
    aiWaitingModal.classList.add('hidden');
}

/**
 * Call AI API to extract data
 */
async function callAIExtraction() {
    document.getElementById('aiInitial')?.classList.add('hidden');
    document.getElementById('aiProcessing')?.classList.remove('hidden');
    document.getElementById('aiSuccess')?.classList.add('hidden');
    document.getElementById('aiError')?.classList.add('hidden');
    applyAIDataBtn?.classList.add('hidden');

    try {
        const uploadedFileData = typeof window.getUploadedFileData === 'function'
            ? window.getUploadedFileData()
            : null;

        let requestData = {};
        let isUserProvidedUrl = false;

        // Check if user provided a URL in the input field
        if (aiUrlInput && aiUrlInput.value.trim() !== '') {
            const url = aiUrlInput.value.trim();

            // Validate URL before sending to AI
            const validation = validateUrlForAI(url);
            if (!validation.valid) {
                throw new Error(validation.message);
            }

            requestData = { url: validation.url };
            isUserProvidedUrl = true;

            const refUrlInput = document.getElementById('ref_url');
            if (refUrlInput && !refUrlInput.value) {
                refUrlInput.value = validation.url;
            }
        } else if (uploadedFileData) {
            // Use uploaded file data (construct local URL)
            requestData = {
                url: uploadedFileData.download_url || window.BASE_URL + '/index.php/utility/downloadFile/' + uploadedFileData.stored_name
            };
        } else {
            throw new Error('กรุณาอัปโหลดไฟล์หรือใส่ URL ก่อน');
        }

        console.log('Sending to AI API:', AI_API_URL);
        console.log('Request Data:', requestData);

        // Single API call - wait for response (no polling)
        const response = await fetch(AI_API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        });

        // Try to get error details from response body
        if (!response.ok) {
            let errorDetails = '';
            try {
                const errorBody = await response.text();
                console.error('AI API Error Response:', errorBody);
                errorDetails = errorBody ? ` - ${errorBody.substring(0, 200)}` : '';
            } catch (e) {
                console.error('Could not read error response body');
            }

            // Provide helpful error messages based on status code
            let userMessage = `AI API error: ${response.status}`;
            if (response.status === 500) {
                userMessage = 'AI ไม่สามารถเข้าถึง URL นี้ได้ (500)\n\n' +
                    '📌 วิธีแก้ไข:\n' +
                    '1. Capture หน้าเว็บไซต์เป็น PDF\n' +
                    '   • กด Ctrl+P (หรือ Cmd+P บน Mac)\n' +
                    '   • เลือก "Save as PDF"\n' +
                    '   • บันทึกไฟล์\n\n' +
                    '2. หรือ Screenshot หน้าเว็บ\n' +
                    '   • กด Print Screen หรือใช้ Snipping Tool\n' +
                    '   • บันทึกเป็นรูปภาพ\n\n' +
                    '3. อัปโหลดไฟล์ที่ได้แทน URL';
            } else if (response.status === 404) {
                userMessage = 'ไม่พบ AI API endpoint (404)';
            } else if (response.status === 403) {
                userMessage = 'ไม่มีสิทธิ์เข้าถึง AI API (403)';
            } else if (response.status === 408 || response.status === 504) {
                userMessage = 'AI API หมดเวลา - URL อาจใช้เวลาโหลดนานเกินไป\n\nลอง Capture หน้าเว็บเป็น PDF แล้วอัปโหลดแทน';
            }

            throw new Error(userMessage + errorDetails);
        }

        const result = await response.json();
        console.log('AI API Response:', result);

        displayAIRawResponse(result);

        const outputPayload = normalizeAIResponsePayload(result);

        if (outputPayload) {
            aiExtractedData = transformAIResponse(outputPayload);

            document.getElementById('aiProcessing')?.classList.add('hidden');
            document.getElementById('aiSuccess')?.classList.remove('hidden');
            applyAIDataBtn?.classList.remove('hidden');

            displayAIPreview(aiExtractedData);

            try {
                // Keep modal visible while filling the form
                await applyAIDataToForm({ closeModal: false, notify: true, auto: true });

                // Hide modal and re-enable button after form is filled
                hideAIWaitingModal();

                const aiButton = document.getElementById('ai-assist-btn');
                if (aiButton) {
                    aiButton.disabled = false;
                    aiButton.style.opacity = '1';
                    aiButton.style.cursor = 'pointer';
                }
            } catch (autoFillError) {
                console.error('Auto apply AI data error:', autoFillError);
                hideAIWaitingModal();

                const aiButton = document.getElementById('ai-assist-btn');
                if (aiButton) {
                    aiButton.disabled = false;
                    aiButton.style.opacity = '1';
                    aiButton.style.cursor = 'pointer';
                }

                if (typeof showNotification === 'function') {
                    showNotification('ไม่สามารถกรอกข้อมูลอัตโนมัติได้ กรุณากดปุ่ม "นำข้อมูลไปใช้"', 'warning');
                }
            }
        } else {
            hideAIWaitingModal();

            const aiButton = document.getElementById('ai-assist-btn');
            if (aiButton) {
                aiButton.disabled = false;
                aiButton.style.opacity = '1';
                aiButton.style.cursor = 'pointer';
            }

            throw new Error('ไม่พบผลลัพธ์จาก AI');
        }
    } catch (error) {
        console.error('AI error:', error);

        hideAIWaitingModal();

        // Re-enable button on error
        const aiButton = document.getElementById('ai-assist-btn');
        if (aiButton) {
            aiButton.disabled = false;
            aiButton.style.opacity = '1';
            aiButton.style.cursor = 'pointer';
        }

        document.getElementById('aiProcessing')?.classList.add('hidden');
        document.getElementById('aiError')?.classList.remove('hidden');

        const errorLabel = document.getElementById('aiErrorMessage');
        if (errorLabel) {
            errorLabel.textContent = error.message;
        }

        displayAIRawResponse({
            error: true,
            message: error.message
        });
    }
}

/**
 * Normalise AI API response to a single payload object.
 * Supports: root-level array [{ ... }], { output: obj } or { output: [obj] }, or direct object { type, title_en, ... }.
 * Uses first item only when response is an array.
 */
function normalizeAIResponsePayload(result) {
    if (!result) return null;
    if (Array.isArray(result) && result.length > 0) {
        return result[0];
    }
    if (result.output !== undefined) {
        const out = result.output;
        return Array.isArray(out) && out.length > 0 ? out[0] : out;
    }
    // Direct object at root (e.g. n8n returns single extraction object)
    if (typeof result === 'object' && (result.type || result.title_en || result.title_th || result.journalname || result.source === 'ai_extraction')) {
        return result;
    }
    return null;
}

/**
 * Split a full name into first and last parts.
 */
function splitNameParts(fullName) {
    if (!fullName || typeof fullName !== 'string') {
        return { firstName: '', lastName: '' };
    }

    const parts = fullName.trim().split(/\s+/).filter(Boolean);

    if (parts.length === 0) {
        return { firstName: '', lastName: '' };
    }

    if (parts.length === 1) {
        return { firstName: parts[0], lastName: '' };
    }

    return {
        firstName: parts[0],
        lastName: parts[parts.length - 1]
    };
}

/**
 * Extract first and last name parts from various input shapes.
 * Accepts string or object carrying different naming conventions.
 */
function extractNameParts(nameInfo, options = {}) {
    const { language = 'en' } = options;

    if (!nameInfo) {
        return { firstName: '', lastName: '' };
    }

    if (typeof nameInfo === 'string') {
        return splitNameParts(nameInfo);
    }

    // Priority order for first name candidates based on language
    let firstCandidates, lastCandidates;

    if (language === 'th') {
        firstCandidates = [
            nameInfo.firstName,
            nameInfo.first_name,
            nameInfo.thai_first_name,
            nameInfo.th_first_name
        ];
        lastCandidates = [
            nameInfo.lastName,
            nameInfo.last_name,
            nameInfo.thai_last_name,
            nameInfo.th_last_name,
            nameInfo.thai_lastname
        ];
    } else {
        // English language
        firstCandidates = [
            nameInfo.firstName,
            nameInfo.first_name,
            nameInfo.gf_name,
            nameInfo.en_first_name,
            nameInfo.given_name,
            nameInfo.givenName
        ];
        lastCandidates = [
            nameInfo.lastName,
            nameInfo.last_name,
            nameInfo.gl_name,
            nameInfo.en_last_name,
            nameInfo.family_name,
            nameInfo.familyName
        ];
    }

    let firstName = firstCandidates.find(val => typeof val === 'string' && val.trim() !== '') || '';
    let lastName = lastCandidates.find(val => typeof val === 'string' && val.trim() !== '') || '';

    // Fallback: try to split full name if first/last not found
    if (!firstName || !lastName) {
        const fallbackFullNames = [
            nameInfo.fullName,
            nameInfo.full_name,
            nameInfo.name,
            language === 'th'
                ? (nameInfo.thai_name || nameInfo.name_th || '')
                : (nameInfo.english_name || nameInfo.name_en || '')
        ];

        for (const candidate of fallbackFullNames) {
            if (candidate && typeof candidate === 'string' && candidate.trim() !== '') {
                const { firstName: ff, lastName: ll } = splitNameParts(candidate);
                if (!firstName && ff) firstName = ff;
                if (!lastName && ll) lastName = ll;
            }
            if (firstName && lastName) {
                break;
            }
        }
    }

    console.log(`extractNameParts (${language}):`, { firstName, lastName, from: nameInfo });

    return {
        firstName: firstName ? firstName.trim() : '',
        lastName: lastName ? lastName.trim() : ''
    };
}

/**
 * Transform AI API response to our internal format
 */
function transformAIResponse(output) {
    const transformAuthors = (authorsEn, authorsTh) => {
        const authors = [];
        const maxLength = Math.max(
            Array.isArray(authorsEn) ? authorsEn.length : 0,
            Array.isArray(authorsTh) ? authorsTh.length : 0
        );

        for (let i = 0; i < maxLength; i++) {
            const englishRaw = Array.isArray(authorsEn) && authorsEn[i] ? authorsEn[i].trim() : '';
            const thaiRaw = Array.isArray(authorsTh) && authorsTh[i] ? authorsTh[i].trim() : '';

            const { firstName: enFirst, lastName: enLast } = splitNameParts(englishRaw);
            const { firstName: thFirst, lastName: thLast } = splitNameParts(thaiRaw);

            authors.push({
                name_en: englishRaw || null,
                name_th: thaiRaw || null,
                english_name: englishRaw || null,
                thai_name: thaiRaw || null,
                gf_name: enFirst || '',
                gl_name: enLast || '',
                thai_first_name: thFirst || '',
                thai_last_name: thLast || '',
                email: null,
                affiliation: null
            });
        }

        return authors;
    };

    return {
        publication_type: output.type || 'journal',
        title_th: output.title_th || null,
        title_en: output.title_en || null,
        title: output.title_th || output.title_en || '',
        authors: transformAuthors(output.authors_en, output.authors_th),
        abstract_th: output.abstract_th || null,
        abstract_en: output.abstract_en || null,
        abstract: output.abstract_th || output.abstract_en || '',
        keywords_th: output.keywords_th || [],
        keywords_en: output.keywords_en || [],
        keywords: output.keywords_th || output.keywords_en || [],
        year: output.year_en || output.year_th || null,
        month: output.month_en || output.month_th || null,
        month_en: output.month_en || null,
        month_th: output.month_th || null,

        // Source field - prioritize based on publication type
        source: output.journalname ||
            output.conference_name_th || output.conference_name_en ||
            output.publisher_th || output.publisher_en ||
            output.book_title_th || output.book_title_en || null,

        // Journal fields
        volume: output.volume || null,
        issue: output.issue || null,
        pages: output.pages || null,
        doi: output.doi || null,

        // Book fields
        isbn: output.isbn || null,
        publisher: output.publisher_th || output.publisher_en || null,
        book_title: output.book_title_th || output.book_title_en || null,
        chapter: output.chapter || null,
        editor: output.editor_th || output.editor_en || null,

        // Conference fields
        conference_name: output.conference_name_th || output.conference_name_en || null,
        conference_location: output.conference_location_th || output.conference_location_en || null,
        conference_date: output.conference_date || null,

        // Additional fields
        url: output.url || null,
        file_link: output.file_link || null
    };
}

/**
 * Display AI extracted data preview
 */
function displayAIPreview(data) {
    const preview = document.getElementById('aiPreview');
    if (!preview) return;

    let html = '<div class="space-y-3">';

    if (data.title) {
        html += `<div><strong class="text-gray-700">ชื่อ:</strong> <span class="text-gray-900">${data.title}</span></div>`;
    }

    if (data.authors && data.authors.length > 0) {
        const authorNames = data.authors
            .map(author => author.thai_name || author.name_th || author.english_name || author.name_en)
            .filter(Boolean);
        if (authorNames.length > 0) {
            html += `<div><strong class="text-gray-700">ผู้แต่ง:</strong> <span class="text-gray-900">${authorNames.join(', ')}</span></div>`;
        }
    }

    if (data.year) {
        html += `<div><strong class="text-gray-700">ปี:</strong> <span class="text-gray-900">${data.year}</span></div>`;
    }

    if (data.journal) {
        html += `<div><strong class="text-gray-700">วารสาร:</strong> <span class="text-gray-900">${data.journal}</span></div>`;
    }

    if (data.volume) {
        html += `<div><strong class="text-gray-700">Volume:</strong> <span class="text-gray-900">${data.volume}</span></div>`;
    }

    if (data.issue) {
        html += `<div><strong class="text-gray-700">Issue:</strong> <span class="text-gray-900">${data.issue}</span></div>`;
    }

    if (data.pages) {
        html += `<div><strong class="text-gray-700">หน้า:</strong> <span class="text-gray-900">${data.pages}</span></div>`;
    }

    if (data.doi) {
        html += `<div><strong class="text-gray-700">DOI:</strong> <span class="text-gray-900">${data.doi}</span></div>`;
    }

    if (data.abstract) {
        const abstractText = data.abstract.substring(0, 200);
        const suffix = data.abstract.length > 200 ? '...' : '';
        html += `<div><strong class="text-gray-700">บทคัดย่อ:</strong> <p class="text-gray-900 mt-1">${abstractText}${suffix}</p></div>`;
    }

    html += '</div>';

    preview.innerHTML = html;
}

/**
 * Display raw AI response JSON for verification
 */
function displayAIRawResponse(payload) {
    if (!aiRawJson) return;

    const serialised = typeof payload === 'string'
        ? payload
        : JSON.stringify(payload, null, 2);

    aiRawJson.textContent = serialised;

    if (aiRawJsonContainer) {
        aiRawJsonContainer.classList.remove('hidden');
    }
}

/**
 * Apply AI extracted data to form
 */
async function onApplyAIData() {
    if (!aiExtractedData) return;

    const applyBtn = applyAIDataBtn;
    const originalText = applyBtn.innerHTML;

    applyBtn.disabled = true;
    applyBtn.innerHTML = '<span class="loading-spinner" style="width: 16px; height: 16px; border-width: 2px; display: inline-block; margin-right: 8px;"></span>กำลังประมวลผล...';

    try {
        await applyAIDataToForm({ closeModal: true, notify: true, auto: false });
    } catch (error) {
        console.error('Error applying AI data:', error);
        if (typeof showNotification === 'function') {
            showNotification('เกิดข้อผิดพลาดในการนำข้อมูลมาใช้: ' + error.message, 'error');
        }
    } finally {
        applyBtn.disabled = false;
        applyBtn.innerHTML = originalText;
    }
}

/**
 * Apply AI extracted data to the main form.
 */
async function applyAIDataToForm({ closeModal = true, notify = false, auto = false } = {}) {
    if (!aiExtractedData) {
        throw new Error('ไม่มีข้อมูลจาก AI ให้กรอก');
    }

    await fillFormWithAIData(aiExtractedData);

    if (closeModal) {
        closeAIModal();
    }

    if (notify && typeof showNotification === 'function') {
        showNotification(auto ? 'AI ได้กรอกข้อมูลลงในฟอร์มให้อัตโนมัติแล้ว' : 'นำข้อมูลจาก AI มาใช้เรียบร้อยแล้ว', 'success');
    }

    if (aiAutoFillMessage) {
        aiAutoFillMessage.classList.remove('hidden');
        aiAutoFillMessage.textContent = auto
            ? 'AI ได้กรอกข้อมูลลงในแบบฟอร์มให้เรียบร้อยแล้ว คุณสามารถตรวจสอบหรือแก้ไขได้ทันที'
            : 'ข้อมูลจาก AI ถูกนำไปใช้กับแบบฟอร์มแล้ว';
    }
}

/**
 * Fill basic publication fields
 */
function fillBasicPublicationFields(data) {
    if (data.title_th || data.title_en || data.title) {
        const title = data.title_th || data.title_en || data.title;
        const titleInput = document.getElementById('title') || document.querySelector('input[name="title"]');
        if (titleInput) titleInput.value = title;
    }

    if (data.year) {
        let year = parseInt(data.year, 10);
        if (!Number.isNaN(year) && year < 2500) {
            year += 543;
        }
        const yearInput = document.getElementById('publication_year') || document.querySelector('input[name="publication_year"]');
        if (yearInput && !Number.isNaN(year)) yearInput.value = year;
    }

    const monthInput = document.getElementById('publication_month');
    if (monthInput) {
        const monthValue = normaliseMonthValue(data.month, data.month_th, data.month_en);
        if (monthValue) {
            monthInput.value = monthValue;
        }
    }

    if (data.source || data.journal) {
        const source = data.source || data.journal;
        const sourceInput = document.getElementById('source') || document.querySelector('input[name="source"]');
        if (sourceInput) sourceInput.value = source;
    }

    if (data.volume) {
        const volumeInput = document.getElementById('volume') || document.querySelector('input[name="volume"]');
        if (volumeInput) volumeInput.value = data.volume;
    }

    if (data.issue) {
        const issueInput = document.getElementById('issue') || document.querySelector('input[name="issue"]');
        if (issueInput) issueInput.value = data.issue;
    }

    if (data.pages) {
        const pagesInput = document.getElementById('pages') || document.querySelector('input[name="pages"]');
        if (pagesInput) pagesInput.value = data.pages;
    }

    if (data.doi) {
        const doiInput = document.getElementById('doi') || document.querySelector('input[name="doi"]');
        if (doiInput) doiInput.value = data.doi;
    }

    if (data.isbn) {
        const isbnInput = document.getElementById('isbn') || document.querySelector('input[name="isbn"]');
        if (isbnInput) isbnInput.value = data.isbn;
    }

    // Book fields
    if (data.publisher) {
        const publisherInput = document.getElementById('publisher') || document.querySelector('input[name="publisher"]');
        if (publisherInput) publisherInput.value = data.publisher;
    }

    if (data.book_title) {
        const bookTitleInput = document.getElementById('book_title') || document.querySelector('input[name="book_title"]');
        if (bookTitleInput) bookTitleInput.value = data.book_title;
    }

    if (data.chapter) {
        const chapterInput = document.getElementById('chapter') || document.querySelector('input[name="chapter"]');
        if (chapterInput) chapterInput.value = data.chapter;
    }

    if (data.editor) {
        const editorInput = document.getElementById('editor') || document.querySelector('input[name="editor"]');
        if (editorInput) editorInput.value = data.editor;
    }

    // Conference fields
    if (data.conference_name) {
        const confNameInput = document.getElementById('conference_name') || document.querySelector('input[name="conference_name"]');
        if (confNameInput) confNameInput.value = data.conference_name;
    }

    if (data.conference_location) {
        const confLocationInput = document.getElementById('conference_location') || document.querySelector('input[name="conference_location"]');
        if (confLocationInput) confLocationInput.value = data.conference_location;
    }

    if (data.conference_date) {
        const confDateInput = document.getElementById('conference_date') || document.querySelector('input[name="conference_date"]');
        if (confDateInput) confDateInput.value = data.conference_date;
    }

    if (data.abstract_th || data.abstract_en || data.abstract) {
        const abstract = data.abstract_th || data.abstract_en || data.abstract;
        const abstractInput = document.getElementById('abstract') || document.querySelector('textarea[name="abstract"]');
        if (abstractInput) abstractInput.value = abstract;
    }

    if (data.keywords && Array.isArray(data.keywords)) {
        const keywordsInput = document.getElementById('keywords') || document.querySelector('input[name="keywords"]');
        if (keywordsInput) {
            keywordsInput.value = data.keywords.join(', ');
        }
    }

    // Fill URL field with user-provided URL or uploaded file link
    const urlInput = document.getElementById('url') || document.querySelector('input[name="url"]');
    const aiUrlInput = document.getElementById('ai-url-input');
    const uploadedFileData = typeof window.getUploadedFileData === 'function' ? window.getUploadedFileData() : null;

    if (urlInput) {
        // Priority 1: User-provided URL from AI URL input
        if (aiUrlInput && aiUrlInput.value.trim() !== '') {
            urlInput.value = aiUrlInput.value.trim();
        }
        // Priority 2: Uploaded file download URL
        else if (uploadedFileData && uploadedFileData.download_url) {
            urlInput.value = uploadedFileData.download_url;
        }
        // Priority 3: URL from AI response
        else if (data.url) {
            urlInput.value = data.url;
        }
    }

    // Also update ref_url hidden field
    const refUrlInput = document.getElementById('ref_url');
    if (refUrlInput && !refUrlInput.value) {
        if (aiUrlInput && aiUrlInput.value.trim() !== '') {
            refUrlInput.value = aiUrlInput.value.trim();
        } else if (uploadedFileData && uploadedFileData.download_url) {
            refUrlInput.value = uploadedFileData.download_url;
        } else if (data.url) {
            refUrlInput.value = data.url;
        }
    }
}

/**
 * Process and fill authors with intelligent matching
 */
async function processAndFillAuthors(authorsData) {
    const authorRows = document.querySelectorAll('.author-row');
    for (let i = authorRows.length - 1; i > 0; i--) {
        authorRows[i].remove();
    }

    for (let i = 0; i < authorsData.length; i++) {
        const authorData = ensureAuthorSplitFields(authorsData[i]);
        authorsData[i] = authorData;

        if (i > 0 && typeof addAuthor === 'function') {
            addAuthor();
            await new Promise(resolve => setTimeout(resolve, 100));
        }

        await matchAndFillAuthor(authorData, i);
    }
}

/**
 * Match and fill individual author data
 */
async function matchAndFillAuthor(authorData, index) {
    let matchedAuthor = null;

    // Ensure we have split name fields before searching
    const enrichedData = ensureAuthorSplitFields(authorData);

    const email = enrichedData.email || null;
    const thaiName = enrichedData.name_th || enrichedData.thai_name || null;
    const engName = enrichedData.name_en || enrichedData.english_name || null;

    console.log(`[Author ${index + 1}] Searching for:`, {
        email,
        thaiName,
        engName,
        thai_first: enrichedData.thai_first_name,
        thai_last: enrichedData.thai_last_name,
        eng_first: enrichedData.gf_name,
        eng_last: enrichedData.gl_name
    });

    if (email) {
        matchedAuthor = await searchAuthorByEmail(email);
        if (matchedAuthor) console.log(`[Author ${index + 1}] Matched by email`);
    }

    if (!matchedAuthor && thaiName) {
        // Split Thai name if not already split
        let thaiFirst = enrichedData.thai_first_name;
        let thaiLast = enrichedData.thai_last_name;

        if (!thaiFirst && thaiName) {
            const parts = splitNameParts(thaiName);
            thaiFirst = parts.firstName;
            thaiLast = parts.lastName;
        }

        console.log(`[Author ${index + 1}] Searching Thai: first="${thaiFirst}", last="${thaiLast}"`);

        matchedAuthor = await searchAuthorByThaiName({
            fullName: thaiName,
            firstName: thaiFirst,
            lastName: thaiLast
        });
        if (matchedAuthor) console.log(`[Author ${index + 1}] Matched by Thai name`);
    }

    if (!matchedAuthor && engName) {
        // Split English name if not already split
        let engFirst = enrichedData.gf_name;
        let engLast = enrichedData.gl_name;

        if (!engFirst && engName) {
            const parts = splitNameParts(engName);
            engFirst = parts.firstName;
            engLast = parts.lastName;
        }

        console.log(`[Author ${index + 1}] Searching English: first="${engFirst}", last="${engLast}"`);

        matchedAuthor = await searchAuthorByEnglishName({
            fullName: engName,
            firstName: engFirst,
            lastName: engLast
        });
        if (matchedAuthor) console.log(`[Author ${index + 1}] Matched by English name`);
    }

    if (!matchedAuthor) {
        console.log(`[Author ${index + 1}] No match found`);
    }

    fillAuthorFields(index, enrichedData, matchedAuthor);
}

async function searchAuthorByEmail(email) {
    try {
        const response = await fetch(window.BASE_URL + '/index.php/user/searchByEmail', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ email })
        });

        const result = await response.json();

        if (result.success && result.author) {
            return result.author;
        }
    } catch (error) {
        console.error('Error searching author by email:', error);
    }

    return null;
}

async function searchAuthorByThaiName(nameInfo) {
    try {
        const { firstName, lastName } = extractNameParts(nameInfo, { language: 'th' });

        if (!firstName) {
            return null;
        }

        const response = await fetch(window.BASE_URL + '/index.php/user/searchByName', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                first_name: firstName,
                last_name: lastName,
                language: 'th'
            })
        });

        const result = await response.json();

        if (result.success && result.author) {
            return result.author;
        }
    } catch (error) {
        console.error('Error searching author by Thai name:', error);
    }

    return null;
}

async function searchAuthorByEnglishName(nameInfo) {
    try {
        const { firstName, lastName } = extractNameParts(nameInfo, { language: 'en' });

        if (!firstName) {
            return null;
        }

        const response = await fetch(window.BASE_URL + '/index.php/user/searchByName', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                first_name: firstName,
                last_name: lastName,
                language: 'en'
            })
        });

        const result = await response.json();

        if (result.success && result.author) {
            return result.author;
        }
    } catch (error) {
        console.error('Error searching author by English name:', error);
    }

    return null;
}

/**
 * Fill author fields in the form
 */
function fillAuthorFields(index, aiAuthorData, matchedAuthor) {
    const enrichedAIData = ensureAuthorSplitFields(aiAuthorData);
    const nameInput = document.querySelector(`input[name="authors[${index}][name]"]`);
    const emailInput = document.querySelector(`input[name="authors[${index}][email]"]`);
    const affiliationInput = document.querySelector(`input[name="authors[${index}][affiliation]"]`);
    const authorRow = nameInput ? nameInput.closest('.author-row') : null;

    const aiThaiName = deriveThaiName(enrichedAIData);
    const aiEnglishName = deriveEnglishName(enrichedAIData);

    if (matchedAuthor) {
        const matchThaiName = deriveThaiName(matchedAuthor);
        const matchEnglishName = deriveEnglishName(matchedAuthor);

        if (nameInput) {
            nameInput.classList.remove('border-yellow-500', 'bg-yellow-50');
            nameInput.classList.add('border-green-500', 'bg-green-50');

            const displayName = matchThaiName.full ||
                matchEnglishName.full ||
                aiThaiName.full ||
                aiEnglishName.full ||
                '';

            if (displayName) {
                nameInput.value = displayName;
            }

            attachMatchBadges(authorRow, matchedAuthor, true, index, enrichedAIData);
        }

        if (emailInput) {
            emailInput.value = matchedAuthor.email || enrichedAIData.email || '';
        }

        if (affiliationInput) {
            affiliationInput.value = matchedAuthor.affiliation ||
                matchedAuthor.organization ||
                enrichedAIData.affiliation ||
                'มหาวิทยาลัยราชภัฏอุตรดิตถ์';
        }

        console.log(`✓ Matched author ${index + 1}:`, matchedAuthor);
    } else {
        if (nameInput) {
            nameInput.classList.remove('border-green-500', 'bg-green-50');
            nameInput.classList.add('border-yellow-500', 'bg-yellow-50');

            const displayName = aiThaiName.full ||
                aiEnglishName.full ||
                enrichedAIData.name ||
                '';
            nameInput.value = displayName;

            attachMatchBadges(authorRow, null, false, index, enrichedAIData);
        }

        if (emailInput) {
            emailInput.value = enrichedAIData.email || '';
        }

        if (affiliationInput) {
            // Don't add organization if name is not found in system
            affiliationInput.value = enrichedAIData.affiliation ||
                enrichedAIData.organization ||
                '';
        }

        console.log(`✗ No match for author ${index + 1}, using AI data:`, enrichedAIData);
    }
}

/**
 * Trigger AI extraction
 */
function triggerAIExtraction() {
    runAIExtraction();
}

function processUploadedFileWithAI() {
    const uploadedFileData = typeof window.getUploadedFileData === 'function'
        ? window.getUploadedFileData()
        : null;

    if (!uploadedFileData) {
        if (typeof showNotification === 'function') {
            showNotification('กรุณาอัปโหลดไฟล์ก่อนเริ่มประมวลผลด้วย AI', 'warning');
        }
        return;
    }

    openAIModal();
    triggerAIExtraction();
}

function processUrlInputWithAI() {
    const url = aiUrlInput ? aiUrlInput.value.trim() : '';

    if (!url) {
        if (typeof showNotification === 'function') {
            showNotification('กรุณาใส่ URL ของเอกสารก่อนเริ่มประมวลผลด้วย AI', 'warning');
        }
        return;
    }

    openAIModal();
    triggerAIExtraction();
}

// Initialise when DOM is ready
document.addEventListener('DOMContentLoaded', initAIAssistant);

// Export functions for global access
window.closeAIModal = closeAIModal;
window.triggerAIExtraction = triggerAIExtraction;
window.processUploadedFileWithAI = processUploadedFileWithAI;
window.processUrlInputWithAI = processUrlInputWithAI;
window.validateUrlForAI = validateUrlForAI;
window.normalizeAIResponsePayload = normalizeAIResponsePayload;
window.transformAIResponse = transformAIResponse;
window.callAIExtraction = callAIExtraction;

// Export author matching functions for use in other pages (e.g., managePublication.php)
window.processAndFillAuthors = processAndFillAuthors;
window.matchAndFillAuthor = matchAndFillAuthor;
window.searchAuthorByEmail = searchAuthorByEmail;
window.searchAuthorByThaiName = searchAuthorByThaiName;
window.searchAuthorByEnglishName = searchAuthorByEnglishName;
window.ensureAuthorSplitFields = ensureAuthorSplitFields;
window.splitNameParts = splitNameParts;
window.extractNameParts = extractNameParts;

/**
 * Shared helper to fill the form using the AI payload.
 */
async function fillFormWithAIData(data) {
    // Auto-select publication type first
    if (data.publication_type) {
        selectPublicationTypeFromAI(data.publication_type);
    }

    fillBasicPublicationFields(data);

    if (data.authors && data.authors.length > 0) {
        const enrichedAuthors = data.authors.map(author => ensureAuthorSplitFields(author));
        await processAndFillAuthors(enrichedAuthors);
    }
}

/**
 * Auto-select publication type based on AI response
 */
function selectPublicationTypeFromAI(type) {
    // Map AI type to form type
    const typeMapping = {
        'journal': 'journal',
        'book': 'book',
        'conference': 'proceedings',
        'proceedings': 'proceedings',
        'thesis': 'thesis',
        'report': 'report',
        'other': 'other'
    };

    const formType = typeMapping[type.toLowerCase()] || type.toLowerCase();

    // Find and click the corresponding publication type card
    const $typeCard = $(`.publication-type-card[data-type="${formType}"]`);

    if ($typeCard.length > 0) {
        // Trigger the selection
        $typeCard.trigger('click');
        console.log('Auto-selected publication type:', formType);
    } else {
        console.warn('Publication type not found:', formType);

        // Fallback: manually set the hidden input and trigger field visibility
        $('#publication_type').val(formType);

        // If selectPublicationType function exists in publication-form.js
        if (typeof selectPublicationType === 'function') {
            selectPublicationType($typeCard);
        } else if (typeof toggleConditionalFields === 'function') {
            toggleConditionalFields(formType);
        }
    }
}

/**
 * Ensure an author object has split name fields for Thai and English.
 */
function ensureAuthorSplitFields(author) {
    if (!author || typeof author !== 'object') {
        return author;
    }

    const updated = { ...author };

    if (!updated.thai_first_name || !updated.thai_last_name) {
        const thaiSource = updated.thai_name || updated.name_th || updated.name;
        const { firstName, lastName } = splitNameParts(thaiSource);
        updated.thai_first_name = (updated.thai_first_name || firstName || '').trim();
        updated.thai_last_name = (updated.thai_last_name || lastName || '').trim();
    } else {
        updated.thai_first_name = updated.thai_first_name.trim();
        updated.thai_last_name = updated.thai_last_name.trim();
    }

    if (!updated.gf_name || !updated.gl_name) {
        const englishSource = updated.english_name || updated.name_en || updated.name;
        const { firstName, lastName } = splitNameParts(englishSource);
        updated.gf_name = (updated.gf_name || firstName || '').trim();
        updated.gl_name = (updated.gl_name || lastName || '').trim();
    } else {
        updated.gf_name = updated.gf_name.trim();
        updated.gl_name = updated.gl_name.trim();
    }

    return updated;
}

/**
 * Convert month representations to the dropdown value (01-12).
 */
function normaliseMonthValue(month, monthTh, monthEn) {
    const mapping = {
        'january': '01',
        'jan': '01',
        'february': '02',
        'feb': '02',
        'march': '03',
        'mar': '03',
        'april': '04',
        'apr': '04',
        'may': '05',
        'june': '06',
        'jun': '06',
        'july': '07',
        'jul': '07',
        'august': '08',
        'aug': '08',
        'september': '09',
        'sep': '09',
        'october': '10',
        'oct': '10',
        'november': '11',
        'nov': '11',
        'december': '12',
        'dec': '12',
        'มกราคม': '01',
        'กุมภาพันธ์': '02',
        'ก.พ.': '02',
        'มีนาคม': '03',
        'มี.ค.': '03',
        'เมษายน': '04',
        'เม.ย.': '04',
        'พฤษภาคม': '05',
        'พ.ค.': '05',
        'มิถุนายน': '06',
        'มิ.ย.': '06',
        'กรกฎาคม': '07',
        'ก.ค.': '07',
        'สิงหาคม': '08',
        'ส.ค.': '08',
        'กันยายน': '09',
        'ก.ย.': '09',
        'ตุลาคม': '10',
        'ต.ค.': '10',
        'พฤศจิกายน': '11',
        'พ.ย.': '11',
        'ธันวาคม': '12',
        'ธ.ค.': '12'
    };

    const candidates = [month, monthTh, monthEn].filter(candidate => candidate !== null && candidate !== undefined);

    for (const candidate of candidates) {
        const raw = String(candidate).trim();
        if (!raw) continue;

        if (/^\d{1,2}$/.test(raw)) {
            const intVal = parseInt(raw, 10);
            if (intVal >= 1 && intVal <= 12) {
                return intVal.toString().padStart(2, '0');
            }
        }

        const lower = raw.toLowerCase();
        if (mapping[lower]) {
            return mapping[lower];
        }
    }

    return '';
}

/**
 * Build a full name string from first and last parts.
 */
function buildFullName(first, last) {
    return [first, last].filter(part => typeof part === 'string' && part.trim() !== '').join(' ').trim();
}

/**
 * Derive Thai name representations from a source object.
 */
function deriveThaiName(source = {}) {
    const { firstName, lastName } = extractNameParts({
        firstName: source.thai_first_name,
        lastName: source.thai_last_name,
        thai_first_name: source.thai_first_name,
        thai_last_name: source.thai_last_name,
        thai_lastname: source.thai_lastname,
        fullName: source.thai_name || source.name_th,
        name: source.name
    }, { language: 'th' });

    const full =
        buildFullName(firstName, lastName) ||
        (typeof source.thai_name === 'string' ? source.thai_name.trim() : '') ||
        (typeof source.name_th === 'string' ? source.name_th.trim() : '');

    return {
        first: firstName || '',
        last: lastName || '',
        full: full || ''
    };
}

/**
 * Derive English name representations from a source object.
 */
function deriveEnglishName(source = {}) {
    const { firstName, lastName } = extractNameParts({
        firstName: source.gf_name,
        lastName: source.gl_name,
        name_en: source.name_en,
        english_name: source.english_name,
        fullName: source.english_name || source.name_en,
        name: source.name
    }, { language: 'en' });

    const full =
        buildFullName(firstName, lastName) ||
        (typeof source.english_name === 'string' ? source.english_name.trim() : '') ||
        (typeof source.name_en === 'string' ? source.name_en.trim() : '');

    return {
        first: firstName || '',
        last: lastName || '',
        full: full || ''
    };
}

/**
 * Resolve the author index from the DOM or provided value.
 */
function resolveAuthorIndex(authorRow, providedIndex) {
    if (typeof providedIndex === 'number' || (typeof providedIndex === 'string' && providedIndex !== '')) {
        return providedIndex;
    }

    const hiddenName = authorRow?.querySelector('input[name$="[name]"]')?.name || '';
    const indexMatch = hiddenName.match(/authors\[(\d+)]\[name]/);
    return indexMatch ? indexMatch[1] : null;
}

/**
 * Ensure hidden input for author metadata exists.
 */
function ensureHiddenAuthorInput(authorRow, index, field) {
    if (!authorRow) return null;

    const selector = `input[name="authors[${index}][${field}]"]`;
    let input = authorRow.querySelector(selector);

    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = `authors[${index}][${field}]`;
        authorRow.appendChild(input);
    }

    return input;
}

/**
 * Set hidden author metadata value.
 */
function setHiddenAuthorValue(authorRow, index, field, value) {
    const resolvedIndex = resolveAuthorIndex(authorRow, index);
    if (resolvedIndex === null || resolvedIndex === undefined) {
        return;
    }

    const input = ensureHiddenAuthorInput(authorRow, resolvedIndex, field);
    if (input) {
        input.value = typeof value === 'string' ? value.trim() : (value ?? '');
    }
}

/**
 * Append badges/hidden fields to author rows after matching.
 */
function attachMatchBadges(authorRow, matchedAuthor, isMatched, index, sourceData = null) {
    if (!authorRow) return;

    authorRow.querySelectorAll('.matched-badge, .new-badge, .matched-id-badge').forEach(badge => badge.remove());

    const authorIndex = resolveAuthorIndex(authorRow, index);
    const nameGroup = authorRow.querySelector('input[name$="[name]"]')?.parentElement || authorRow.querySelector('.input-group');

    if (isMatched && matchedAuthor) {
        const badge = document.createElement('span');
        badge.className = 'matched-badge inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full ml-2';
        badge.innerHTML = '✓ พบข้อมูลในระบบ';

        const idBadge = document.createElement('span');
        idBadge.className = 'matched-id-badge inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-full ml-2';

        const matchedThai = deriveThaiName(matchedAuthor);
        const matchedEnglish = deriveEnglishName(matchedAuthor);
        const userId = matchedAuthor.user_uid ||
            matchedAuthor.user_id ||
            matchedAuthor.uid ||
            matchedAuthor.id ||
            matchedAuthor.author_id ||
            '';

        idBadge.innerHTML = userId ? `รหัสผู้ใช้: ${userId}` : 'รหัสผู้ใช้ไม่ระบุ';

        if (nameGroup) {
            nameGroup.appendChild(badge);
            nameGroup.appendChild(idBadge);
        }

        setHiddenAuthorValue(authorRow, authorIndex, 'user_uid', userId);
        setHiddenAuthorValue(authorRow, authorIndex, 'user_id', userId);
        setHiddenAuthorValue(authorRow, authorIndex, 'gf_name', matchedEnglish.first);
        setHiddenAuthorValue(authorRow, authorIndex, 'gl_name', matchedEnglish.last);
        setHiddenAuthorValue(authorRow, authorIndex, 'thai_first_name', matchedThai.first);
        setHiddenAuthorValue(authorRow, authorIndex, 'thai_last_name', matchedThai.last);
    } else {
        const badge = document.createElement('span');
        badge.className = 'new-badge inline-flex items-center px-2 py-1 text-xs font-medium text-yellow-700 bg-yellow-100 rounded-full ml-2';
        badge.innerHTML = '⚠ ผู้แต่งใหม่';

        if (nameGroup) {
            nameGroup.appendChild(badge);
        }

        const enrichedSource = ensureAuthorSplitFields(sourceData || {});
        const sourceThai = deriveThaiName(enrichedSource);
        const sourceEnglish = deriveEnglishName(enrichedSource);

        setHiddenAuthorValue(authorRow, authorIndex, 'user_uid', '');
        setHiddenAuthorValue(authorRow, authorIndex, 'user_id', '');
        setHiddenAuthorValue(authorRow, authorIndex, 'gf_name', sourceEnglish.first);
        setHiddenAuthorValue(authorRow, authorIndex, 'gl_name', sourceEnglish.last);
        setHiddenAuthorValue(authorRow, authorIndex, 'thai_first_name', sourceThai.first);
        setHiddenAuthorValue(authorRow, authorIndex, 'thai_last_name', sourceThai.last);
    }
}
