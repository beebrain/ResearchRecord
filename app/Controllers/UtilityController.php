<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class UtilityController extends Controller
{
    protected $session;

    public function __construct()
    {
        $this->session = session();
        helper(['filesystem', 'form']);
    }

    /**
     * Upload file (generic file upload handler)
     * Supports: PDF, DOC, DOCX, images, etc.
     */
    public function uploadFile()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
        }

        try {
            $validationRule = [
                'file' => [
                    'label' => 'File',
                    'rules' => [
                        'uploaded[file]',
                        'max_size[file,10240]', // 10MB max
                        'ext_in[file,pdf,doc,docx,jpg,jpeg,png,gif,txt,csv,xlsx,xls,zip,rar]'
                    ],
                ],
            ];

            if (!$this->validate($validationRule)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $this->validator->getErrors()
                ]);
            }

            $file = $this->request->getFile('file');

            if (!$file->isValid()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid file upload: ' . $file->getErrorString()
                ]);
            }

            // Generate unique filename
            $newName = $file->getRandomName();

            // Create upload directory if it doesn't exist (writable/publication/)
            $uploadPath = WRITEPATH . 'publication/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Move file to upload directory
            $file->move($uploadPath, $newName);

            // Store file info in session for later use
            $fileInfo = [
                'original_name' => $file->getClientName(),
                'stored_name' => $newName,
                'file_path' => $uploadPath . $newName,
                'file_size' => $file->getSize(),
                'file_type' => $file->getClientMimeType(),
                'download_url' => base_url('index.php/utility/downloadFile/' . $newName),
                'ref_url' => 'local:' . $newName, // Store as local reference
                'uploaded_at' => date('Y-m-d H:i:s')
            ];

            return $this->response->setJSON([
                'success' => true,
                'message' => 'File uploaded successfully',
                'file' => $fileInfo
            ]);

        } catch (\Exception $e) {
            log_message('error', 'File upload error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error uploading file: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete uploaded file
     */
    public function deleteFile()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
        }

        try {
            $fileName = $this->request->getPost('file_name');

            if (!$fileName) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'File name is required'
                ]);
            }

            $filePath = WRITEPATH . 'publication/' . $fileName;

            if (file_exists($filePath)) {
                unlink($filePath);
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'File deleted successfully'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'File not found'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'File deletion error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error deleting file: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Download file
     */
    public function downloadFile($fileName = null)
    {
        if (!$fileName) {
            return redirect()->back()->with('error', 'File name is required');
        }

        $filePath = WRITEPATH . 'publication/' . $fileName;

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File not found');
        }

        return $this->response->download($filePath, null);
    }

    /**
     * Get allowed file types
     */
    public function getAllowedFileTypes()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'allowed_types' => [
                'documents' => ['pdf', 'doc', 'docx', 'txt'],
                'images' => ['jpg', 'jpeg', 'png', 'gif'],
                'spreadsheets' => ['csv', 'xlsx', 'xls'],
                'archives' => ['zip', 'rar']
            ],
            'max_size' => '10MB'
        ]);
    }

    /**
     * Extract text from PDF for AI processing
     * This is a placeholder - you'll need a library like smalot/pdfparser
     */
    public function extractTextFromFile()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
        }

        try {
            $fileName = $this->request->getPost('file_name');

            if (!$fileName) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'File name is required'
                ]);
            }

            $filePath = WRITEPATH . 'publication/' . $fileName;

            if (!file_exists($filePath)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'File not found'
                ]);
            }

            // TODO: Implement actual text extraction based on file type
            // For now, return a placeholder
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Text extraction ready',
                'text' => 'Extracted text will be here',
                'file_type' => mime_content_type($filePath)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Text extraction error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error extracting text: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Process AI response and format for publication form
     * This endpoint will receive the AI API response
     * Supports both file upload and URL input
     */
    public function processAIResponse()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
        }

        try {
            $requestData = $this->request->getJSON(true);

            // Check the type of request (file or url)
            $type = $requestData['type'] ?? 'file';

            // TODO: Here you would integrate with your actual AI API
            // For now, this is a placeholder that returns mock data

            if ($type === 'file') {
                // Process uploaded file
                $filePath = $requestData['file_path'] ?? '';
                $fileName = $requestData['file_name'] ?? '';

                // TODO: Send file to AI API for processing
                // Example: Call your AI service with the file path or download URL
                log_message('info', 'Processing file with AI: ' . $fileName);

            } elseif ($type === 'url') {
                // Process URL
                $url = $requestData['url'] ?? '';

                // TODO: Send URL to AI API for processing
                // Example: Call your AI service with the URL
                log_message('info', 'Processing URL with AI: ' . $url);
            }

            // TODO: Replace this mock response with actual AI API response
            // This is placeholder data - your AI API should return similar structure
            $aiData = [
                'title' => 'Sample Publication Title',
                'authors' => ['Author 1', 'Author 2'],
                'year' => '2024',
                'publication_type' => 'journal',
                'journal' => 'Sample Journal Name',
                'volume' => '10',
                'issue' => '2',
                'pages' => '123-145',
                'doi' => '10.1234/sample.doi',
                'abstract' => 'This is a sample abstract extracted by AI...',
                'keywords' => ['keyword1', 'keyword2']
            ];

            // Validate and format AI response
            $formattedData = [
                'title' => $aiData['title'] ?? '',
                'authors' => $aiData['authors'] ?? [],
                'year' => $aiData['year'] ?? '',
                'publication_type' => $aiData['publication_type'] ?? '',
                'journal' => $aiData['journal'] ?? '',
                'volume' => $aiData['volume'] ?? '',
                'issue' => $aiData['issue'] ?? '',
                'pages' => $aiData['pages'] ?? '',
                'doi' => $aiData['doi'] ?? '',
                'abstract' => $aiData['abstract'] ?? '',
                'keywords' => $aiData['keywords'] ?? []
            ];

            return $this->response->setJSON([
                'success' => true,
                'message' => 'AI data processed successfully',
                'data' => $formattedData
            ]);

        } catch (\Exception $e) {
            log_message('error', 'AI processing error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error processing AI response: ' . $e->getMessage()
            ]);
        }
    }
}
