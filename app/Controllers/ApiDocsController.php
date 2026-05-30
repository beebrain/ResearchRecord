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
     * Swagger UI — GET /api/docs
     */
    public function index()
    {
        return view('api_docs/swagger', [
            'title'   => 'Research Record API',
            'specUrl' => site_url('api/openapi.json'),
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
