<?php

namespace App\Controllers;

use App\Libraries\RrOpenApiSpec;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Interactive API docs (Swagger UI) — คล้าย FastAPI /docs
 */
class ApiDocsController extends Controller
{
    /**
     * Swagger UI — GET /docs
     */
    public function index()
    {
        return view('api_docs/swagger', [
            'title'   => 'Research Record API',
            'specUrl' => site_url('api/openapi.json'),
        ]);
    }

    /**
     * Browser test page for curriculum-detail-by-name — GET /docs/curriculum-test
     * Encodes Thai query params via URLSearchParams (avoids raw-UTF-8 curl pitfalls).
     */
    public function curriculumTest()
    {
        return view('api_docs/curriculum_test', [
            'title'  => 'ทดสอบ Curriculum API',
            'apiUrl' => site_url('api/curriculum-detail-by-name'),
        ]);
    }

    /**
     * OpenAPI JSON — GET /api/openapi.json
     */
    public function openapi(): ResponseInterface
    {
        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON(RrOpenApiSpec::build());
    }
}
