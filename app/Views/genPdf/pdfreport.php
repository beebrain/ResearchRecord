<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>PDF Generator</title>
    <script src="<?= base_url(); ?>./pdfmake/build/pdfmake.min.js"></script>
    <script src="<?= base_url(); ?>./pdfmake/build/vfs_fonts.js"></script>
    <script src="<?= base_url(); ?>./pdfmake/scriptgen.js"></script>
    <script src="<?php echo base_url() ?>/assets/js/backend-bundle.min.js"></script>
    <script src="<?php echo base_url() ?>/assets/js/app.js"></script>

    <!-- Add Bootstrap CSS for better styling -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">

    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .btn-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        .pdf-btn {
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pdf-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            max-width: 150px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .option-container {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>รายงานผลการทดสอบ</h1>
            <p>ศูนย์วิทยาศาสตร์และเทคโนโลยี มหาวิทยาลัยราชภัฏอุตรดิตถ์</p>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Generate PDF Report</h4>
            </div>
            <div class="card-body">
                <div class="option-container">
                    <h5>Report Options</h5>

                    <div class="form-group">
                        <label for="analysis-end-date">วันที่สิ้นสุดการวิเคราะห์:</label>
                        <input type="date" id="analysis-end-date" class="form-control">
                        <small class="form-text text-muted">เลือกวันที่สิ้นสุดการวิเคราะห์ (ถ้าไม่เลือก จะใช้วันที่ทวนสอบผล)</small>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="iso-certification" checked>
                        <label class="form-check-label" for="iso-certification">รายการทดสอบได้รับการรับรอง ISO/IEC 17025</label>
                    </div>
                </div>

                <p>Please select the type of report you want to generate:</p>

                <div class="btn-container">
                    <button id="normal-pdf" class="btn btn-primary pdf-btn" onclick="genPDF(1)">
                        <i class="fa fa-file-pdf-o mr-2"></i> ISO Report
                    </button>

                    <button id="detailed-pdf" class="btn btn-success pdf-btn" onclick="genPDF(0)">
                        <i class="fa fa-file-pdf-o mr-2"></i> Standard Report
                    </button>

                    <button id="no-logo-pdf" class="btn btn-secondary pdf-btn" onclick="genPDF(2)">
                        <i class="fa fa-file-pdf-o mr-2"></i> No Logo Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo base_url() ?>/assets/js/science/scientific-input.js"></script>


    <script>
        var sampleData = <?php echo json_encode($Sample[0]); ?>;
        var sampleDetailData = <?php echo json_encode($Sampledetail); ?>;

        $(document).ready(function() {
            // Set default date values
            setDefaultDates();
        });

        function setDefaultDates() {
            // Set the end date input to today's date as a fallback
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            const formattedDate = `${year}-${month}-${day}`;

            document.getElementById('analysis-end-date').value = formattedDate;
        }

        function genPDF(type) {
            // Get values from form inputs
            const analysisEndDate = document.getElementById('analysis-end-date').value;
            const isoCertified = document.getElementById('iso-certification').checked;

            createPDFData(sampleData, sampleDetailData, type, analysisEndDate, isoCertified);
        }

        function convertToThaiBuddhistDateTime(dateTimeString) {
            try {
                const dateObject = new Date(dateTimeString);

                if (isNaN(dateObject.getTime())) {
                    return "วันที่ไม่ได้ระบุ";
                }

                const buddhistYear = dateObject.getFullYear() + 543;
                const thaiMonthNames = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
                    "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
                ];
                const thaiMonth = thaiMonthNames[dateObject.getMonth()];
                const thaiDay = dateObject.getDate();

                // Format hours and minutes
                var hours = dateObject.getHours();
                var minutes = dateObject.getMinutes();

                // Pad single digit numbers with a leading zero
                hours = hours < 10 ? '0' + hours : hours;
                minutes = minutes < 10 ? '0' + minutes : minutes;

                var thaiBuddhistDateTime = thaiDay + ' ' + thaiMonth + ' พ.ศ. ' + buddhistYear + ' เวลา ' + hours + ':' + minutes + ' น.';

                return thaiBuddhistDateTime;
            } catch (error) {
                console.error(error);
                return "";
            }
        }

        function getOperationResult(idTest) {
            return new Promise((resolve, reject) => {
                var dataToSend = {
                    "sampleid": idTest,
                };
                $.ajax({
                    url: "<?php echo base_url() . 'index.php/sample/samplecontrol/getAllResultOperation' ?>",
                    type: 'POST',
                    dataType: 'json',
                    data: dataToSend,
                    success: function(data) {
                        resolve(data);
                    },
                    error: function(xhr, status, error) {
                        reject(error);
                    }
                });
            });
        }

        function formatScientificTextForPDF(text) {
            if (!text) return text;

            // First convert any standard notations with ScientificInput formatter
            let formattedText = ScientificInput.formatScientificNotation(text, 'combined');

            // Custom replacements for Greek letters
            const greekLetterMap = {
                'α': 'α', // alpha
                'β': 'β', // beta
                'γ': 'γ', // gamma
                'δ': 'δ', // delta
                'Δ': 'Δ', // Delta (uppercase)
                'μ': 'μ', // mu
                'σ': 'σ', // sigma
                'Σ': 'Σ', // Sigma (uppercase)
                'ω': 'ω', // omega
                'Ω': 'Ω' // Omega (uppercase)
            };

            // Define special characters set including equilibrium
            const specialChars = new Set([
                '°', '±', '≤', '≥', '×', '⇌', '→', '←', '↔', '⟶', '⟵'
            ]);

            // Greek letter characters
            const greekChars = new Set(Object.values(greekLetterMap));

            // Helper function to check if a character is Thai
            const isThai = (char) => {
                const code = char.charCodeAt(0);
                return (code >= 0x0E00 && code <= 0x0E7F); // Thai Unicode range
            };

            // Helper function to check if a character is special or Greek (but not Thai)
            const isSpecialOrGreek = (char) => {
                return !isThai(char) && (specialChars.has(char) || greekChars.has(char));
            };

            // Helper function to determine appropriate style for character
            const getCharacterStyle = (char) => {
                if (isThai(char)) {
                    return 'small'; // Use standard Thai font for Thai characters
                } else if (isSpecialOrGreek(char)) {
                    return 'scientific'; // Use scientific font for special symbols
                } else {
                    return 'small'; // Use standard font for other characters
                }
            };

            // Replace Greek letter names with actual Greek letters
            formattedText = formattedText.replace(/\b(alpha|beta|gamma|delta|mu|sigma|omega)\b/gi, function(match) {
                const lowerMatch = match.toLowerCase();
                if (lowerMatch === 'alpha') return 'α';
                if (lowerMatch === 'beta') return 'β';
                if (lowerMatch === 'gamma') return 'γ';
                if (lowerMatch === 'delta') return lowerMatch === match ? 'δ' : 'Δ';
                if (lowerMatch === 'mu') return 'μ';
                if (lowerMatch === 'sigma') return lowerMatch === match ? 'σ' : 'Σ';
                if (lowerMatch === 'omega') return lowerMatch === match ? 'ω' : 'Ω';
                return match;
            });

            // Check if any subscript, superscript, LaTeX notation, or HTML tags are in the text
            const hasSpecialCharacters = /[₀₁₂₃₄₅₆₇₈₉⁰¹²³⁴⁵⁶⁷⁸⁹⁺⁻]/.test(formattedText) ||
                formattedText.includes('<sup') ||
                formattedText.includes('<sub') ||
                formattedText.includes('_{') ||
                formattedText.includes('^{');

            if (hasSpecialCharacters) {
                // Convert to rich text array with manual positioning
                let result = [];
                let currentText = '';
                let i = 0;

                // Helper function to flush current text with consistent styling
                const flushCurrentText = () => {
                    if (currentText) {
                        // Group consecutive characters with same style
                        let tempText = '';
                        let tempStyle = null;

                        for (let j = 0; j < currentText.length; j++) {
                            const c = currentText[j];
                            const charStyle = getCharacterStyle(c);

                            if (tempStyle === null) {
                                tempStyle = charStyle;
                                tempText = c;
                            } else if (tempStyle === charStyle) {
                                tempText += c;
                            } else {
                                // Style changed, push current group and start new one
                                result.push({
                                    text: tempText,
                                    style: tempStyle
                                });
                                tempText = c;
                                tempStyle = charStyle;
                            }
                        }

                        // Push remaining text
                        if (tempText) {
                            result.push({
                                text: tempText,
                                style: tempStyle
                            });
                        }

                        currentText = '';
                    }
                };

                while (i < formattedText.length) {
                    // Check for LaTeX subscript _{...}
                    if (i <= formattedText.length - 2 && formattedText.substring(i, i + 2) === '_{') {
                        // Process any pending text
                        flushCurrentText();

                        // Find the closing brace
                        let braceCount = 1;
                        let j = i + 2;
                        while (j < formattedText.length && braceCount > 0) {
                            if (formattedText[j] === '{') braceCount++;
                            else if (formattedText[j] === '}') braceCount--;
                            j++;
                        }

                        if (braceCount === 0) {
                            // Extract content between braces
                            const subscriptContent = formattedText.substring(i + 2, j - 1);

                            // Determine style for subscript content
                            const subStyle = subscriptContent.split('').some(c => isThai(c)) ? 'small' :
                                subscriptContent.split('').some(c => isSpecialOrGreek(c)) ? 'scientific' : 'small';

                            // Add as subscript
                            result.push({
                                text: subscriptContent,
                                style: subStyle,
                                sub: {
                                    offset: '30%',
                                    fontSize: 10
                                }
                            });

                            i = j;
                            continue;
                        } else {
                            // Malformed LaTeX, treat as regular text
                            currentText += formattedText[i];
                            i++;
                        }
                    }
                    // Check for LaTeX superscript ^{...}
                    else if (i <= formattedText.length - 2 && formattedText.substring(i, i + 2) === '^{') {
                        // Process any pending text
                        flushCurrentText();

                        // Find the closing brace
                        let braceCount = 1;
                        let j = i + 2;
                        while (j < formattedText.length && braceCount > 0) {
                            if (formattedText[j] === '{') braceCount++;
                            else if (formattedText[j] === '}') braceCount--;
                            j++;
                        }

                        if (braceCount === 0) {
                            // Extract content between braces
                            const superscriptContent = formattedText.substring(i + 2, j - 1);

                            // Determine style for superscript content
                            const supStyle = superscriptContent.split('').some(c => isThai(c)) ? 'small' :
                                superscriptContent.split('').some(c => isSpecialOrGreek(c)) ? 'scientific' : 'small';

                            // Add as superscript
                            result.push({
                                text: superscriptContent,
                                style: supStyle,
                                sup: {
                                    offset: '30%',
                                    fontSize: 10
                                }
                            });

                            i = j;
                            continue;
                        } else {
                            // Malformed LaTeX, treat as regular text
                            currentText += formattedText[i];
                            i++;
                        }
                    }
                    // Check for <sup> tag
                    else if (i <= formattedText.length - 5 && formattedText.substring(i, i + 5) === '<sup ') {
                        // Process any pending text
                        flushCurrentText();

                        // Find the closing tag
                        const closingTag = formattedText.indexOf('</sup>', i);
                        if (closingTag !== -1) {
                            // Extract the data-letters attribute value
                            const dataAttrStart = formattedText.indexOf('data-letters="', i) + 14;
                            const dataAttrEnd = formattedText.indexOf('"', dataAttrStart);

                            if (dataAttrStart !== -1 && dataAttrEnd !== -1) {
                                const letters = formattedText.substring(dataAttrStart, dataAttrEnd);

                                // Determine style for superscript content
                                const supStyle = letters.split('').some(c => isThai(c)) ? 'small' :
                                    letters.split('').some(c => isSpecialOrGreek(c)) ? 'scientific' : 'small';

                                // Add as superscript
                                result.push({
                                    text: letters,
                                    style: supStyle,
                                    sup: {
                                        offset: '30%',
                                        fontSize: 10
                                    }
                                });
                            }

                            // Move past the closing tag
                            i = closingTag + 6;
                            continue;
                        }
                    }
                    // Check for <sub> tag
                    else if (i <= formattedText.length - 5 && formattedText.substring(i, i + 5) === '<sub ') {
                        // Process any pending text
                        flushCurrentText();

                        // Find the closing tag
                        const closingTag = formattedText.indexOf('</sub>', i);
                        if (closingTag !== -1) {
                            // Extract the data-letters attribute value
                            const dataAttrStart = formattedText.indexOf('data-letters="', i) + 14;
                            const dataAttrEnd = formattedText.indexOf('"', dataAttrStart);

                            if (dataAttrStart !== -1 && dataAttrEnd !== -1) {
                                const letters = formattedText.substring(dataAttrStart, dataAttrEnd);

                                // Determine style for subscript content
                                const subStyle = letters.split('').some(c => isThai(c)) ? 'small' :
                                    letters.split('').some(c => isSpecialOrGreek(c)) ? 'scientific' : 'small';

                                // Add as subscript
                                result.push({
                                    text: letters,
                                    style: subStyle,
                                    sub: {
                                        offset: '30%',
                                        fontSize: 10
                                    }
                                });
                            }

                            // Move past the closing tag
                            i = closingTag + 6;
                            continue;
                        }
                    }
                    // Check if character is a Unicode subscript
                    else if ('₀₁₂₃₄₅₆₇₈₉'.includes(formattedText[i])) {
                        // Add any pending regular text
                        flushCurrentText();

                        // Convert subscript Unicode to regular number
                        const subscriptMap = {
                            '₀': '0',
                            '₁': '1',
                            '₂': '2',
                            '₃': '3',
                            '₄': '4',
                            '₅': '5',
                            '₆': '6',
                            '₇': '7',
                            '₈': '8',
                            '₉': '9'
                        };
                        const regularDigit = subscriptMap[formattedText[i]] || formattedText[i];

                        // Add as subscript with scientific style for numbers
                        result.push({
                            text: regularDigit,
                            style: 'scientific',
                            sub: {
                                offset: '30%',
                                fontSize: 10
                            }
                        });

                        i++;
                    }
                    // Check if character is a Unicode superscript
                    else if ('⁰¹²³⁴⁵⁶⁷⁸⁹⁺⁻'.includes(formattedText[i])) {
                        // Add any pending regular text
                        flushCurrentText();

                        // Convert superscript Unicode to regular character
                        const superscriptMap = {
                            '⁰': '0',
                            '¹': '1',
                            '²': '2',
                            '³': '3',
                            '⁴': '4',
                            '⁵': '5',
                            '⁶': '6',
                            '⁷': '7',
                            '⁸': '8',
                            '⁹': '9',
                            '⁺': '+',
                            '⁻': '-'
                        };
                        const regularChar = superscriptMap[formattedText[i]] || formattedText[i];

                        // Add as superscript with scientific style for numbers and symbols
                        result.push({
                            text: regularChar,
                            style: 'scientific',
                            sup: {
                                offset: '30%',
                                fontSize: 10
                            }
                        });

                        i++;
                    } else {
                        // Regular character, add to current text
                        currentText += formattedText[i];
                        i++;
                    }
                }

                // Process any remaining text
                flushCurrentText();

                return result;
            }

            // For text without special formatting, group consecutive characters by style
            let result = [];
            let currentText = '';
            let currentStyle = null;

            for (let i = 0; i < formattedText.length; i++) {
                const c = formattedText[i];
                const charStyle = getCharacterStyle(c);

                if (currentStyle === null) {
                    currentStyle = charStyle;
                    currentText = c;
                } else if (currentStyle === charStyle) {
                    currentText += c;
                } else {
                    // Style changed, push current group and start new one
                    result.push({
                        text: currentText,
                        style: currentStyle
                    });
                    currentText = c;
                    currentStyle = charStyle;
                }
            }

            // Push remaining text
            if (currentText) {
                result.push({
                    text: currentText,
                    style: currentStyle
                });
            }

            return result;
        }


        async function createPDFData(sampledata, sampleDetail, type, customEndDate, isoCertified) {
            let trackingno = sampledata.trackNo || "";
            let testid = sampledata.testid || "";
            let activeDate = sampledata.activeDate || "";
            let docnumber = sampledata.docnumber || "";
            let createDate = sampledata.createDate || "";
            if (docnumber === "" || docnumber === "0000") {
                docnumber = "xxx";
            }

            // Format the latest test result and confirmation times
            let latestTestResultTime = sampledata.latest_test_result_time ?
                convertToThaiBuddhistDate(sampledata.latest_test_result_time) : 'ไม่ระบุ';
            let latestConfirmationTime = sampledata.latest_confirmation_time ?
                convertToThaiBuddhistDate(sampledata.latest_confirmation_time) : 'ไม่ระบุ';

            let reviewer_confirm = sampledata.reviewer_confirm ?
                convertToThaiBuddhistDate(sampledata.reviewer_confirm) : 'ไม่ระบุ';

            let fistActive = sampledata.activeDate ?
                convertToThaiBuddhistDate(sampledata.activeDate) : 'ไม่ระบุ';

            // Use custom end date if provided
            let analysisEndDate = 'ไม่ระบุ';
            if (customEndDate) {
                analysisEndDate = convertToThaiBuddhistDate(customEndDate);
            } else {
                analysisEndDate = reviewer_confirm; // Default to reviewer confirmation date
            }

            let createDateYear = parseInt(createDate.substring(0, 4)) + 543 - 2500;
            let serviceText = "";

            let content = [];

            let tableData = [
                ['ชื่อตัวอย่าง/รหัสปฏิบัติการ', 'ลักษณะตัวอย่าง', 'รายการทดสอบ', 'ผลการวิเคราะห์', 'วิธีทดสอบ/เครื่องมือ']
            ];

            var indexno = 0;
            var totalpriceall = 0;
            var textOperation;

            for (const item of sampleDetail) {
                textOperation = ++indexno;
                if (item.operationnumber !== null) {
                    textOperation = item.operationnumber;
                }

                try {
                    let detailsampleid = await getOperationResult(item.sampleid);
                    if (detailsampleid && Array.isArray(detailsampleid)) {
                        detailsampleid.forEach((itemsample, index) => {
                            console.log('Processing sample:', itemsample);

                            let namesample = index === 0 ? item.samplename + " " + textOperation : "";
                            let appearance = index === 0 ? item.appearance : "";



                            tableData.push([{
                                    text: namesample,
                                    style: 'small',
                                    alignment: 'left'
                                },
                                {
                                    text: appearance,
                                    style: 'small',
                                    alignment: 'left'
                                },
                                {
                                    text: [{
                                            text: (index + 1) + ' ',
                                            style: 'small'
                                        },
                                        ...formatScientificTextForPDF(itemsample.service || "")
                                    ],
                                    style: 'small',
                                    alignment: 'left'
                                },
                                {
                                    text: formatScientificTextForPDF(itemsample.testvalue || ""),
                                    style: 'small',
                                    alignment: 'left'
                                },
                                {
                                    text: formatScientificTextForPDF(itemsample.methodName || ""),
                                    style: 'small',
                                    alignment: 'left'
                                }
                            ]);
                        });

                        // Add a separator row after each sample detail
                        tableData.push([{
                                text: '',
                                colSpan: 5,
                                style: 'separator'
                            },
                            '', '', '', ''
                        ]);

                    } else {
                        console.log('detailsampleid is not an array or is null');
                    }
                } catch (error) {
                    console.error('An error occurred:', error);
                }

                totalpriceall += parseFloat(item.totalprice || 0);
            }

            // Remove the last separator row
            tableData.pop();

            // Add the main table to the content
            content.push({
                style: 'tableExample',
                table: {
                    headerRows: 1,
                    body: tableData,
                    keepWithHeaderRows: 1,
                    dontBreakRows: true,
                    widths: [80, 60, '*', 60, "*"],
                },
                margin: [0, -10, 0, 0],
                layout: createTableLayout(1)
            });

            // Build remarks section based on ISO certification checkbox
            let remarksBody = [];

            if (isoCertified) {
                remarksBody = [
                    [{
                            text: 'หมายเหตุ:',
                            style: 'small',
                            alignment: 'left',
                            bold: true,
                            lineHeight: 0.7
                        },
                        {
                            text: '* รายการทดสอบที่ได้รับการรับรองความสามารถในการทดสอบตาม ISO/IEC 17025 จากกรมวิทยาศาสตร์บริการ',
                            style: 'small',
                            alignment: 'left',
                            lineHeight: 0.7
                        }
                    ],
                    [{
                            text: '',
                            style: 'small',
                            lineHeight: 0.7
                        },
                        {
                            text: '- รับรองผลเฉพาะตัวอย่างที่นำมาทดสอบเท่านั้น',
                            style: 'small',
                            alignment: 'left',
                            lineHeight: 0.7
                        }
                    ],
                    [{
                            text: '',
                            style: 'small',
                            lineHeight: 0.7
                        },
                        {
                            text: '- รายงานผลการทดสอบ ต้องไม่ถูกสำเนาเฉพาะบางส่วน ยกเว้นทำทั้งฉบับ โดยไม่ได้รับความยินยอมเป็นลายลักษณ์อักษรจากห้องปฏิบัติการ',
                            style: 'small',
                            alignment: 'left',
                            lineHeight: 0.7
                        }
                    ]
                ];
            } else {
                remarksBody = [
                    [{
                            text: 'หมายเหตุ:',
                            style: 'small',
                            alignment: 'left',
                            bold: true,
                            lineHeight: 0.7
                        },
                        {
                            text: 'รับรองผลเฉพาะตัวอย่างที่นำมาทดสอบเท่านั้น',
                            style: 'small',
                            alignment: 'left',
                            lineHeight: 0.7
                        }
                    ],
                    [{
                            text: '',
                            style: 'small',
                            lineHeight: 0.7
                        },
                        {
                            text: '- รายงานผลการทดสอบ ต้องไม่ถูกสำเนาเฉพาะบางส่วน ยกเว้นทำทั้งฉบับ โดยไม่ได้รับความยินยอมเป็นลายลักษณ์อักษรจากห้องปฏิบัติการ',
                            style: 'small',
                            alignment: 'left',
                            lineHeight: 0.7
                        }
                    ]
                ];
            }

            // Add remarks to content
            content.push({
                margin: [0, 5, 0, 0],
                table: {
                    widths: ['auto', '*'],
                    body: remarksBody
                },
                layout: 'noBorders' // No borders for this explanatory text
            });

            // Add signature section
            content.push(createSignatureSection(latestTestResultTime, latestConfirmationTime, reviewer_confirm));

            var docDefinition = {
                background: {
                    image: logoImage,
                    opacity: type === 2 ? 0 : 0.1, // Make background invisible for no-logo option
                    alignment: "center",
                    width: 500,
                },
                pageSize: 'A4',
                pageMargins: [20, 180, 20, 30],
                header: function(currentPage, pageCount, pageSize) {

                    let logoDisplay;
                    if (type === 1) { // If standard report - display both logos
                        logoDisplay = {
                            width: '*',
                            columns: [{
                                    image: mriLogoImage,
                                    width: 100,
                                    alignment: 'left',
                                },
                                {
                                    image: logoImage,
                                    width: 50,
                                    margin: [-15, 0, 0, 0],
                                    alignment: 'left',
                                }
                            ]
                        };
                    } else if (type === 0) { // If ISO report - display one logo
                        logoDisplay = {
                            width: '*',
                            columns: [{
                                image: sciLogoImage,
                                width: 60,
                                alignment: 'left',
                            }]
                        };
                    } else { // No logo option but maintain spacing
                        logoDisplay = {
                            width: '*',
                            columns: [{
                                image: sciLogoImage,
                                width: 60,
                                alignment: 'left',
                                opacity: 0 // Make the logo invisible but maintain spacing
                            }]
                        };
                    }


                    return [{
                            columns: [
                                logoDisplay,
                                // Right side with page number and TR box
                                {
                                    width: 'auto',
                                    stack: [{
                                        columns: [{
                                            table: {
                                                widths: [50],
                                                body: [
                                                    [{
                                                        text: 'TR ' + docnumber.replace(/^0+/, ''), // "123",
                                                        style: 'small',
                                                        alignment: 'center'
                                                    }]
                                                ]
                                            },
                                            layout: {
                                                hLineWidth: function(i, node) {
                                                    return 1;
                                                },
                                                vLineWidth: function(i, node) {
                                                    return 1;
                                                },
                                                hLineColor: function(i, node) {
                                                    return 'black';
                                                },
                                                vLineColor: function(i, node) {
                                                    return 'black';
                                                },
                                                paddingLeft: function(i, node) {
                                                    return 4;
                                                },
                                                paddingRight: function(i, node) {
                                                    return 4;
                                                },
                                                paddingTop: function(i, node) {
                                                    return 2;
                                                },
                                                paddingBottom: function(i, node) {
                                                    return 2;
                                                }
                                            }
                                        }]
                                    }],
                                    margin: [-10, 0, 40, 0]
                                }
                            ],
                            margin: [40, 10, 0, 0]
                        },
                        {
                            columns: [{
                                width: '*', // Changed from 'auto' to '*' to allow right alignment
                                margin: [0, -40, 40, 0],
                                text: [{
                                        text: 'หน้า ' + currentPage + '/' + pageCount + '\n',
                                        alignment: 'right',
                                        style: 'small',
                                    },
                                    {
                                        text: 'ศูนย์วิทยาศาสตร์และเทคโนโลยี มหาวิทยาลัยราชภัฏอุตรดิตถ์\n เลขที่ 27  ถ.อินใจมี ต.ท่าอิฐ อ.เมือง จ.อุตรดิตถ์ \nโทร. (055) 411096 ต่อ 1679, 089-7067288',
                                        fontSize: 12,
                                        bold: true,
                                        alignment: 'right',
                                        style: 'small'
                                    }
                                ],
                                alignment: 'right' // Added alignment at the column level
                            }]

                        },
                        {
                            text: 'รายงานผลการทดสอบ',
                            style: 'header',
                            alignment: 'center',
                            margin: [0, -10, 0, 5],
                        },
                        {
                            columns: [{
                                    width: 250,
                                    text: 'หมายเลขการวิเคราะห์ที่  ศวท.อต. ' + docnumber.replace(/^0+/, ''),
                                    style: 'normal'
                                },
                                {
                                    width: '*',
                                    text: 'ชื่อตัวอย่าง ' + sampledata.sampleName,
                                    style: 'normal'
                                }
                            ],
                            margin: [40, 0, 20, 0]
                        },
                        {
                            columns: [{
                                    width: 250,
                                    text: 'รับตัวอย่างเมื่อ ' + convertToThaiBuddhistDate(sampledata.datetime).toString(),
                                    style: 'normal'
                                },
                                {
                                    width: '*',
                                    // text: 'วิเคราะห์เมื่อ ' + fistActive + " - " + analysisEndDate,
                                    text: 'วิเคราะห์เมื่อ ' + analysisEndDate,
                                    style: 'normal'
                                }
                            ],
                            margin: [40, 0, 20, 0]
                        },
                        {
                            columns: [{
                                    width: 250,
                                    text: 'ชื่อหน่วยงาน/ผู้ขอรับบริการ ' + sampledata.senderAgencyname.toString(),
                                    style: 'normal'
                                },
                                {
                                    width: '*',
                                    text: 'ที่อยู่ ' + sampledata.address.toString(),
                                    style: 'normal'
                                },
                            ],
                            margin: [40, 0, 20, 0]
                        },
                        {
                            canvas: [{
                                type: 'line',
                                x1: 40,
                                y1: 5,
                                x2: pageSize.width - 40,
                                y2: 5,
                                lineWidth: 1
                            }]
                        },

                    ];
                },
                footer: function(currentPage, pageCount) {
                    return {
                        columns: [{
                            width: '*', // Changed from 'auto' to '*' to allow right alignment
                            margin: [40, 0, 0, 0],
                            text: [{
                                text: 'แก้ไขครั้งที่ 8  ออกเมื่อ 7/10/62 ',
                                alignment: 'left',
                                style: 'small',
                            }],
                            alignment: 'left' // Added alignment at the column level
                        }, {
                            width: '*', // Changed from 'auto' to '*' to allow right alignment
                            margin: [0, 0, 40, 0],
                            text: [{
                                text: 'ศวทF-5.10(1)-1/1',
                                alignment: 'right',
                                style: 'small',
                            }],
                            alignment: 'right' // Added alignment at the column level
                        }]
                    };
                },
                content: content,
                styles: {
                    header: {
                        fontSize: 16,
                        bold: true,
                        font: "Sarabun"
                    },
                    subheader: {
                        fontSize: 14,
                        bold: true,
                        font: "Sarabun"
                    },
                    quote: {
                        italics: true,
                        font: "Sarabun"
                    },
                    normal: {
                        fontSize: 14,
                        font: "Sarabun",
                        lineHeight: 0.9
                    },
                    small: {
                        fontSize: 12,
                        font: "Sarabun",
                        lineHeight: 0.9
                    },
                    abnormal: {
                        fontSize: 10,
                        font: "Roboto"
                    },
                    footer: {
                        fontSize: 8,
                        italics: true,
                    },
                    tableExample: {
                        margin: [0, 0, 0, 0]
                    },
                    separator: {
                        fontSize: 0.5,
                        font: "Roboto",
                        fillColor: '#000000'
                    },
                    scientific: {
                        fontSize: 12,
                        font: "Dejavu",
                        bold: false,
                        lineHeight: 0.9
                    },
                },
                defaultStyle: {
                    fontSize: 14,
                    font: "Sarabun",
                    alignment: "justify",
                    columnGap: 20
                },
            };

            pdfMake.fonts = {
                Sarabun: {
                    normal: "THSarabunNew.ttf",
                    bold: "THSarabunNew Bold.ttf",
                    italics: "THSarabunNew Italic.ttf",
                    bolditalics: "THSarabunNew BoldItalic.ttf",
                },
                Roboto: {
                    normal: "Roboto-Regular.ttf",
                    bold: "Roboto-Medium.ttf",
                    italics: "Roboto-Italic.ttf",
                    bolditalics: "Roboto-MediumItalic.ttf",
                },
                Roboto2: {
                    normal: "Roboto-Light.ttf",
                    bold: "Roboto-Light.ttf",
                    italics: "Roboto-Light.ttf",
                    bolditalics: "Roboto-Light.ttf",
                },
                Dejavu: {
                    normal: "DejaVuSans.ttf",
                    bold: "DejaVuSans.ttf",
                    italics: "DejaVuSans.ttf",
                    bolditalics: "DejaVuSans.ttf",
                }
            };

            pdfMake.createPdf(docDefinition).open({}, window);
        }

        function createTableLayout(headerRows) {
            return {
                hLineWidth: function(i, node) {
                    if (i === 0 || i === node.table.body.length) return 1;
                    return (i === headerRows) ? 1 : 0;
                },
                vLineWidth: function(i) {
                    return 1;
                },
                hLineColor: function(i, node) {
                    return (i === 0 || i === 1 || i === node.table.body.length) ? 'black' : 'gray';
                },
                vLineColor: function(i) {
                    return 'black';
                },
                paddingLeft: function(i) {
                    return 4;
                },
                paddingRight: function(i) {
                    return 4;
                },
                paddingTop: function(i, node) {
                    return (i === 0) ? 2 : 1;
                },
                paddingBottom: function(i, node) {
                    return (i === node.table.body.length - 1) ? 2 : 1;
                },
                // fillColor: function(rowIndex, node, columnIndex) {
                //     return (rowIndex % 2 === 0) ? '#FFFFFF' : '#F8F8F8';
                // }
            };
        }

        function createSignatureSection(latestTestResultTime, latestConfirmationTime, reviewer_confirm) {
            return [{
                columns: [{
                        width: '50%',
                        stack: [{
                                text: 'ลงชื่อ .......................................................',
                            }, {
                                text: '( ' + sampleData.scientist_name.toString() + ' )',
                                style: 'normal',
                                alignment: 'center'
                            }, {
                                text: 'นักวิเคราะห์',
                                style: 'normal',
                                alignment: 'center',
                                style: 'normal',
                            },
                            {
                                text: 'วันที่ ' + "....................", //latestConfirmationTime,
                                style: 'normal',
                                alignment: 'center'
                            },
                        ],
                        alignment: 'center'
                    },
                    {
                        width: '50%',
                        stack: [{
                                text: 'ลงชื่อ .......................................................',

                            },
                            {
                                text: '( ' + sampleData.reviewer_name.toString() + ')',
                                style: 'normal',
                                alignment: 'center'
                            },
                            {
                                text: 'ทวนสอบผล',
                                style: 'normal',
                                alignment: 'center'
                            },
                            {
                                text: 'วันที่ ' + "....................", //+ reviewer_confirm,
                                style: 'normal',
                                alignment: 'center'
                            },
                        ],
                        alignment: 'center'
                    }
                ],
                margin: [0, 50, 0, 0]
            }, {
                columns: [{
                    width: '100%',
                    stack: [{
                            text: 'ลงชื่อ .......................................................',

                        }, {
                            text: '(ผู้ช่วยศาสตราจารย์ ดร. พรทิพพา พิญญาพงษ์)',
                            style: 'normal',
                            alignment: 'center'
                        },
                        {
                            text: 'ผู้บริหารสูงสุดของห้องปฏิบัติการ',
                            style: 'normal',
                            alignment: 'center'
                        },

                        {
                            text: 'วันที่ ' + "....................", //+ reviewer_confirm,
                            style: 'normal',
                            alignment: 'center'
                        },
                        {
                            text: '(ผู้อนุมัติ)',
                            style: 'normal',
                            alignment: 'center'
                        },
                        {
                            text: '******************* End of data *******************',
                            style: 'normal',
                            bold: true,
                            alignment: 'center'
                        },
                    ],
                    alignment: 'center'
                }],
                margin: [0, 50, 0, 0],
            }];
        }


        function convertToThaiBuddhistDate(dateString) {
            try {
                const dateObject = new Date(dateString);

                if (isNaN(dateObject.getTime())) {
                    if (dateString === "0000-00-00") {
                        return "วันที่ไม่ได้ระบุ";
                    } else {
                        throw new Error("Invalid date string: " + dateString);
                    }
                }

                const buddhistYear = dateObject.getFullYear() + 543;
                const thaiMonthNames = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
                    "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
                ];
                const thaiMonth = thaiMonthNames[dateObject.getMonth()];
                const thaiDay = dateObject.getDate();

                const thaiBuddhistDate = `${thaiDay} ${thaiMonth} พ.ศ. ${buddhistYear}`;

                return thaiBuddhistDate;
            } catch (error) {
                console.error(error);
                return "";
            }
        }
    </script>


    <script>
        var logoImage = "<?php echo $logoBase64; ?>";
        var mriLogoImage = "<?php echo $mriLogoBase64; ?>";
        var sciLogoImage = "<?php echo $sci_centerBase64; ?>";
    </script>

</body>

</html>