<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * n8n automation endpoints (AI article extraction + ORCID sync).
 *
 * Shares the same n8n instance as newScience (the newer deployment). Override
 * in .env if the host or webhook paths change:
 *   N8N_BASE_URL = "https://n8n.kidcbc.work"
 *   N8N_EXTRACT_ARTICLE_PATH = "webhook/extract-article"
 *   N8N_SYNC_ORCID_PATH = "webhook/sync-orcid"
 */
class N8n extends BaseConfig
{
    /** Base URL of the n8n instance (no trailing slash). */
    public string $baseUrl = 'https://n8n.kidcbc.work';

    /** Webhook path for AI article extraction (POST { "url": "..." }). */
    public string $extractArticlePath = 'webhook/extract-article';

    /** Webhook path for ORCID sync (GET ?orcid_id=...). */
    public string $syncOrcidPath = 'webhook/sync-orcid';

    public function __construct()
    {
        parent::__construct();

        $base = (string) env('N8N_BASE_URL', $this->baseUrl);
        if (trim($base) !== '') {
            $this->baseUrl = rtrim(trim($base), '/');
        }
        $this->extractArticlePath = trim((string) env('N8N_EXTRACT_ARTICLE_PATH', $this->extractArticlePath), '/ ');
        $this->syncOrcidPath      = trim((string) env('N8N_SYNC_ORCID_PATH', $this->syncOrcidPath), '/ ');
    }

    public function extractArticleUrl(): string
    {
        return $this->baseUrl . '/' . $this->extractArticlePath;
    }

    public function syncOrcidUrl(string $orcidId): string
    {
        return $this->baseUrl . '/' . $this->syncOrcidPath . '?orcid_id=' . rawurlencode($orcidId);
    }
}
