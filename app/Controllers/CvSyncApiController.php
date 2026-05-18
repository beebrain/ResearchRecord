<?php

namespace App\Controllers;

use App\Controllers\ApiController;
use App\Libraries\ResearchSyncHmac;
use App\Models\CvEntryModel;
use App\Models\CvSectionModel;
use App\Models\UserModel;
use App\Models\UserProfileModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * API sync CV / publications bundle — กลุ่ม api/public + filter apikey
 */
class CvSyncApiController extends ApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * GET /api/public/cv-bundle-by-email?email=&exp=&sig=
     */
    public function getCvBundleByEmail(): ResponseInterface
    {
        $this->setCors();

        try {
            $email = $this->request->getGet('email');
            $exp   = $this->request->getGet('exp');
            $sig   = $this->request->getGet('sig');

            $v = ResearchSyncHmac::verify($email, $exp, $sig);
            if (!$v['ok']) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'error'   => $v['message'] ?? 'SYNC_AUTH_FAILED',
                ]);
            }

            $userModel = new UserModel();
            $user      = $userModel->where('email', $v['email'])->first();

            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'error'   => 'USER_NOT_FOUND',
                ]);
            }

            $uid = (string) ($user['uid'] ?? '');
            $bundle = $this->buildCvBundleForUser($uid, $v['email']);

            return $this->response->setJSON([
                'success' => true,
                'bundle'  => $bundle,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'CvSyncApiController::getCvBundleByEmail ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error'   => 'SERVER_ERROR',
            ]);
        }
    }

    /**
     * POST /api/public/cv-bundle-by-email (JSON body: bundle)
     */
    public function postCvBundleByEmail(): ResponseInterface
    {
        $this->setCors();

        try {
            $email = $this->request->getGet('email') ?? $this->request->getPost('email');
            $exp   = $this->request->getGet('exp') ?? $this->request->getPost('exp');
            $sig   = $this->request->getGet('sig') ?? $this->request->getPost('sig');

            $v = ResearchSyncHmac::verify($email, $exp, $sig);
            if (!$v['ok']) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'error'   => $v['message'] ?? 'SYNC_AUTH_FAILED',
                ]);
            }

            $input = $this->request->getJSON(true);
            if (!is_array($input)) {
                $input = $this->request->getPost();
            }
            $bundle = $input['bundle'] ?? $input;
            if (!is_array($bundle) || empty($bundle['sections']) || !is_array($bundle['sections'])) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error'   => 'INVALID_BUNDLE',
                ]);
            }

            $userModel = new UserModel();
            $user      = $userModel->where('email', $v['email'])->first();
            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'error'   => 'USER_NOT_FOUND',
                ]);
            }

            $uid = (string) ($user['uid'] ?? '');
            $this->replaceCvFromBundle($uid, $bundle);

            if (!empty($bundle['orcid_id'])) {
                $profileModel = new UserProfileModel();
                $profileModel->updateByUserUid($uid, ['orcid_id' => (string) $bundle['orcid_id']]);
            }

            $fresh = $this->buildCvBundleForUser($uid, $v['email']);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'CV replaced from bundle',
                'bundle'  => $fresh,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'CvSyncApiController::postCvBundleByEmail ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error'   => 'SERVER_ERROR',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * GET /api/public/publications-sync-bundle-by-email?email=&exp=&sig=
     */
    public function getPublicationsSyncBundleByEmail(): ResponseInterface
    {
        $this->setCors();

        try {
            $email = $this->request->getGet('email');
            $exp   = $this->request->getGet('exp');
            $sig   = $this->request->getGet('sig');

            $v = ResearchSyncHmac::verify($email, $exp, $sig);
            if (!$v['ok']) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'error'   => $v['message'] ?? 'SYNC_AUTH_FAILED',
                ]);
            }

            $userModel = new UserModel();
            $user      = $userModel->where('email', $v['email'])->first();
            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'error'   => 'USER_NOT_FOUND',
                ]);
            }

            $publications = $this->publicationModel->getPublicationsByAuthor($user['uid']);
            $items        = [];

            foreach ($publications as $pub) {
                $doi = trim((string) ($pub['doi'] ?? ''));
                $id  = (int) ($pub['id'] ?? 0);
                $key = $doi !== '' ? 'pub:h:' . substr(hash('sha256', strtolower($doi)), 0, 40) : 'pub:id:' . $id;

                $items[] = [
                    'external_key'       => $key,
                    'rr_publication_id'  => $id,
                    'title'              => (string) ($pub['title'] ?? ''),
                    'publication_year'   => $pub['publication_year'] ?? null,
                    'publication_type'   => $pub['publication_type'] ?? null,
                    'source'             => $pub['source'] ?? null,
                    'doi'                => $doi !== '' ? $doi : null,
                    'metadata'           => [
                        'rr_publication_id' => $id,
                        'doi'               => $doi !== '' ? $doi : null,
                    ],
                ];
            }

            $hash = hash('sha256', json_encode($items, JSON_UNESCAPED_UNICODE));

            return $this->response->setJSON([
                'success'        => true,
                'email'          => $v['email'],
                'publications'   => $items,
                'content_hash'   => $hash,
                'retrieved_at'   => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'CvSyncApiController::getPublicationsSyncBundleByEmail ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error'   => 'SERVER_ERROR',
            ]);
        }
    }

    private function setCors(): void
    {
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, X-API-KEY');
    }

    /**
     * @return array<string,mixed>
     */
    private function buildCvBundleForUser(string $userUid, string $canonicalEmail): array
    {
        $sectionModel = new CvSectionModel();
        $entryModel   = new CvEntryModel();
        $profileModel = new UserProfileModel();
        $profile      = $profileModel->getByUserUid($userUid);

        $orcidId = $profile['orcid_id'] ?? null;
        if ($orcidId === null && !empty($profile['orcid'])) {
            if (preg_match('/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/i', (string) $profile['orcid'], $m)) {
                $orcidId = strtoupper($m[1]);
            }
        }

        $sections = $sectionModel->where('user_uid', $userUid)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $out = [];
        foreach ($sections as $section) {
            $sid     = (int) $section['id'];
            $entries = $entryModel->where('section_id', $sid)
                ->orderBy('sort_order', 'ASC')
                ->orderBy('id', 'ASC')
                ->findAll();

            $secKey = $this->rrSectionKey($section);
            $rows   = [];
            foreach ($entries as $e) {
                $meta = [];
                if (!empty($e['metadata'])) {
                    $d = json_decode((string) $e['metadata'], true);
                    $meta = is_array($d) ? $d : [];
                }
                $rows[] = [
                    'external_key'      => $this->rrEntryKey($e, $meta),
                    'title'             => mb_substr((string) ($e['title'] ?? ''), 0, 500),
                    'organization'      => $e['organization'] ?? null,
                    'location'          => $e['location'] ?? null,
                    'start_date'        => $e['start_date'] ?? null,
                    'end_date'          => $e['end_date'] ?? null,
                    'is_current'        => (int) ($e['is_current'] ?? 0),
                    'description'       => $e['description'] ?? null,
                    'visible_on_public' => 1,
                    'metadata'          => $meta,
                    'sort_order'        => (int) ($e['sort_order'] ?? 0),
                ];
            }

            $out[] = [
                'external_key'      => $secKey,
                'type'              => (string) ($section['type'] ?? 'custom'),
                'title'             => (string) ($section['title'] ?? ''),
                'description'       => $section['description'] ?? null,
                'sort_order'        => (int) ($section['sort_order'] ?? 0),
                'visible_on_public' => 1,
                'entries'           => $rows,
            ];
        }

        $bundle = [
            'version'  => 1,
            'email'    => ResearchSyncHmac::normEmail($canonicalEmail),
            'orcid_id' => $orcidId ? (string) $orcidId : null,
            'sections' => $out,
            'source'   => 'research_record',
        ];
        $bundle['content_hash'] = hash('sha256', json_encode($bundle, JSON_UNESCAPED_UNICODE));
        $bundle['retrieved_at'] = date('Y-m-d H:i:s');

        return $bundle;
    }

    /**
     * @param array<string,mixed> $section
     */
    private function rrSectionKey(array $section): string
    {
        return 's:' . substr(hash('sha256', implode('|', [
            strtolower(trim(preg_replace('/\s+/', ' ', (string) ($section['type'] ?? '')))),
            strtolower(trim(preg_replace('/\s+/', ' ', (string) ($section['title'] ?? '')))),
            (string) ((int) ($section['sort_order'] ?? 0)),
        ])), 0, 40);
    }

    /**
     * @param array<string,mixed> $e
     * @param array<string,mixed> $meta
     */
    private function rrEntryKey(array $e, array $meta): string
    {
        if (!empty($meta['orcid_put_code'])) {
            return 'p:' . (string) $meta['orcid_put_code'];
        }
        if (!empty($meta['sync_external_key'])) {
            return (string) $meta['sync_external_key'];
        }

        return 'h:' . substr(hash('sha256', implode('|', [
            strtolower(trim(preg_replace('/\s+/', ' ', (string) ($e['title'] ?? '')))),
            strtolower(trim(preg_replace('/\s+/', ' ', (string) ($e['organization'] ?? '')))),
            (string) ($e['start_date'] ?? ''),
        ])), 0, 40);
    }

    /**
     * @param array<string,mixed> $bundle
     */
    private function replaceCvFromBundle(string $userUid, array $bundle): void
    {
        $db            = \Config\Database::connect();
        $sectionModel  = new CvSectionModel();
        $entryModel    = new CvEntryModel();

        $db->transStart();

        $existing = $sectionModel->where('user_uid', $userUid)->findAll();
        foreach ($existing as $ex) {
            $entryModel->where('section_id', (int) $ex['id'])->delete();
            $sectionModel->delete((int) $ex['id']);
        }

        $order = 0;
        foreach ($bundle['sections'] as $sec) {
            if (!is_array($sec)) {
                continue;
            }
            $order++;
            $sectionModel->insert([
                'user_uid'     => $userUid,
                'type'         => (string) ($sec['type'] ?? 'custom'),
                'title'        => mb_substr((string) ($sec['title'] ?? ''), 0, 255),
                'description'  => $sec['description'] ?? null,
                'sort_order'   => (int) ($sec['sort_order'] ?? $order),
                'is_default'   => 0,
            ]);
            $sid = (int) $sectionModel->getInsertID();
            $eOrder = 0;
            foreach ($sec['entries'] ?? [] as $en) {
                if (!is_array($en)) {
                    continue;
                }
                $eOrder++;
                $meta = $en['metadata'] ?? [];
                if (!is_array($meta)) {
                    $meta = [];
                }
                if (!empty($en['external_key'])) {
                    $meta['sync_external_key'] = (string) $en['external_key'];
                }
                $entryModel->insert([
                    'section_id'   => $sid,
                    'title'        => mb_substr((string) ($en['title'] ?? ''), 0, 255),
                    'organization' => isset($en['organization']) ? mb_substr((string) $en['organization'], 0, 255) : null,
                    'location'     => isset($en['location']) ? mb_substr((string) $en['location'], 0, 255) : null,
                    'start_date'   => $en['start_date'] ?? null,
                    'end_date'     => $en['end_date'] ?? null,
                    'is_current'   => (int) ($en['is_current'] ?? 0),
                    'description'  => $en['description'] ?? null,
                    'metadata'     => $meta !== [] ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
                    'sort_order'   => (int) ($en['sort_order'] ?? $eOrder),
                ]);
            }
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new \RuntimeException('Transaction failed');
        }
    }
}
