/**
 * PDFMake Thai Fonts Loader
 * 
 * This script loads Thai fonts (Sarabun) and registers them with PDFMake
 * for proper Thai text rendering in PDF documents.
 */

(function() {
    'use strict';

    // Font configuration - will be set dynamically
    let FONT_CONFIG = null;

    /**
     * Initialize font configuration with base URL
     * Call this function after the page loads to set the correct base URL
     */
    function initFontConfig(baseUrl) {
        // Remove trailing slash if present
        const cleanBaseUrl = baseUrl.replace(/\/+$/, '');
        FONT_CONFIG = {
            THSarabunNew: {
                normal: cleanBaseUrl + '/assets/fonts/sarabun-400.ttf',
                bold: cleanBaseUrl + '/assets/fonts/sarabun-700.ttf',
                italics: cleanBaseUrl + '/assets/fonts/sarabun-400.ttf',
                bolditalics: cleanBaseUrl + '/assets/fonts/sarabun-700.ttf'
            }
        };
        console.log('Font config initialized with base URL:', cleanBaseUrl);
    }

    // Auto-detect base URL from current page
    function detectBaseUrl() {
        const scripts = document.getElementsByTagName('script');
        for (let script of scripts) {
            if (script.src && script.src.includes('pdfmake-thai-fonts.js')) {
                const url = new URL(script.src);
                return url.origin + url.pathname.replace(/\/assets\/js\/pdfmake-thai-fonts\.js$/, '');
            }
        }
        // Fallback: use current page base
        return window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
    }

    // Cache for loaded fonts
    const fontCache = {};

    /**
     * Convert ArrayBuffer to base64 string (handles large files)
     */
    function arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        const chunkSize = 8192; // Process in chunks to avoid call stack issues
        let binary = '';
        
        for (let i = 0; i < bytes.length; i += chunkSize) {
            const chunk = bytes.subarray(i, Math.min(i + chunkSize, bytes.length));
            binary += String.fromCharCode.apply(null, chunk);
        }
        
        return btoa(binary);
    }

    /**
     * Load a single font file
     */
    async function loadFontFile(url) {
        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            const arrayBuffer = await response.arrayBuffer();
            return arrayBufferToBase64(arrayBuffer);
        } catch (error) {
            console.error(`Failed to load font from ${url}:`, error);
            throw error;
        }
    }

    /**
     * Load all Thai fonts and register with PDFMake
     */
    async function loadThaiFonts() {
        // Check if PDFMake is available
        if (typeof pdfMake === 'undefined') {
            console.error('PDFMake is not loaded. Please include pdfmake.min.js first.');
            return false;
        }

        // Check if fonts are already loaded in VFS
        if (pdfMake.vfs && pdfMake.vfs['Sarabun-Regular.ttf']) {
            console.log('Thai fonts already loaded in VFS.');
            // Ensure font family is registered
            pdfMake.fonts = pdfMake.fonts || {};
            if (!pdfMake.fonts.THSarabunNew) {
                pdfMake.fonts.THSarabunNew = {
                    normal: 'Sarabun-Regular.ttf',
                    bold: 'Sarabun-Bold.ttf',
                    italics: 'Sarabun-Italic.ttf',
                    bolditalics: 'Sarabun-BoldItalic.ttf'
                };
            }
            return true;
        }

        // Initialize font config if not set
        if (!FONT_CONFIG) {
            const baseUrl = detectBaseUrl();
            initFontConfig(baseUrl);
        }

        try {
            console.log('Loading Thai fonts for PDFMake from:', FONT_CONFIG.THSarabunNew.normal);

            // Load all font variants
            const fontPromises = {
                normal: loadFontFile(FONT_CONFIG.THSarabunNew.normal),
                bold: loadFontFile(FONT_CONFIG.THSarabunNew.bold),
                italics: loadFontFile(FONT_CONFIG.THSarabunNew.italics),
                bolditalics: loadFontFile(FONT_CONFIG.THSarabunNew.bolditalics)
            };

            const fonts = await Promise.all([
                fontPromises.normal,
                fontPromises.bold,
                fontPromises.italics,
                fontPromises.bolditalics
            ]);

            console.log('Font files loaded, registering with VFS...');

            // Ensure VFS exists (should already exist from vfs_fonts.min.js)
            if (!pdfMake.vfs) {
                console.warn('pdfMake.vfs not found, creating new VFS');
                pdfMake.vfs = {};
            }

            // Add Thai fonts to VFS
            pdfMake.vfs['Sarabun-Regular.ttf'] = fonts[0];
            pdfMake.vfs['Sarabun-Bold.ttf'] = fonts[1];
            pdfMake.vfs['Sarabun-Italic.ttf'] = fonts[2];
            pdfMake.vfs['Sarabun-BoldItalic.ttf'] = fonts[3];

            // Ensure fonts registry exists
            if (!pdfMake.fonts) {
                pdfMake.fonts = {};
            }

            // Register Thai font family
            pdfMake.fonts.THSarabunNew = {
                normal: 'Sarabun-Regular.ttf',
                bold: 'Sarabun-Bold.ttf',
                italics: 'Sarabun-Italic.ttf',
                bolditalics: 'Sarabun-BoldItalic.ttf'
            };

            // Ensure default Roboto fonts are registered (from vfs_fonts.min.js)
            if (!pdfMake.fonts.Roboto) {
                pdfMake.fonts.Roboto = {
                    normal: 'Roboto-Regular.ttf',
                    bold: 'Roboto-Medium.ttf',
                    italics: 'Roboto-Italic.ttf',
                    bolditalics: 'Roboto-MediumItalic.ttf'
                };
            }

            console.log('Thai fonts loaded successfully!');
            console.log('Available fonts:', Object.keys(pdfMake.fonts));
            console.log('VFS has Sarabun-Regular:', !!pdfMake.vfs['Sarabun-Regular.ttf']);
            return true;

        } catch (error) {
            console.error('Failed to load Thai fonts:', error);
            console.warn('PDF generation will use default Roboto font (Thai text may not render correctly).');
            return false;
        }
    }

    /**
     * Initialize fonts when DOM is ready
     */
    function init() {
        // Wait for PDFMake to be available
        if (typeof pdfMake !== 'undefined') {
            // Pre-load fonts (optional - can be loaded on-demand)
            // loadThaiFonts();
        } else {
            // Wait for PDFMake to load
            const checkPDFMake = setInterval(() => {
                if (typeof pdfMake !== 'undefined') {
                    clearInterval(checkPDFMake);
                    // Pre-load fonts (optional)
                    // loadThaiFonts();
                }
            }, 100);

            // Timeout after 10 seconds
            setTimeout(() => {
                clearInterval(checkPDFMake);
            }, 10000);
        }
    }

    // Export functions to global scope
    window.PDFMakeThaiFonts = {
        load: loadThaiFonts,
        isLoaded: function() {
            return pdfMake && pdfMake.fonts && pdfMake.fonts.THSarabunNew ? true : false;
        },
        init: function(baseUrl) {
            initFontConfig(baseUrl);
        }
    };

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

