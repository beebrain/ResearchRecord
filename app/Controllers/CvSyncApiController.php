<?php

namespace App\Controllers;

use App\Controllers\ApiController;
use App\Libraries\ResearchSyncHmac;
use App\Libraries\UserIdentity;
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

            $user = UserIdentity::resolveUserByEmail((string) $v['email']);

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

            $user = UserIdentity::resolveUserByEmail((string) $v['email']);
            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'error'   => 'USER_NOT_FOUND',
                ]);
            }

            $uid = (string) ($user['uid'] ?? '');
            $this->replaceCvFromBundle($uid, (string) $v['email'], $bundle);

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

            $user = UserIdentity::resolveUserByEmail((string) $v['email']);
            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'error'   => 'USER_NOT_FOUND',
                ]);
            }

            $publications = $this->publicationModel->getPublicationsByCanonicalEmail((string) $v['email']);
            $publicationIds = array_values(array_filter(array_map(
                static fn (array $pub): int => (int) ($pub['id'] ?? 0),
                $publications
            )));
            $contributorsByPublication = $this->buildContributorsByPublicationIds($publicationIds);
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
                    'abstract'           => $pub['abstract'] ?? null,
                    'publication_month'  => $pub['publication_month'] ?? null,
                    'volume'             => $pub['volume'] ?? null,
                    'pages'              => $pub['pages'] ?? null,
                    'isbn'               => $pub['isbn'] ?? null,
                    'keywords'           => $pub['keywords'] ?? null,
                    'notes'              => $pub['notes'] ?? null,
                    'ref_url'            => $pub['ref_url'] ?? null,
                    'contributors'       => $contributorsByPublication[$id] ?? [],
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

    /**
     * POST /api/public/publications-sync-bundle-by-email?email=&exp=&sig=
     *
     * Upsert publications sent from newScience. This is intentionally an upsert,
     * not a replace, so repeated automatic syncs do not duplicate publications or
     * delete authors entered directly in Research Record.
     */
    public function postPublicationsSyncBundleByEmail(): ResponseInterface
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

            $user = (new UserModel())->where('email', $v['email'])->first();
            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'error'   => 'USER_NOT_FOUND',
                ]);
            }

            $input = $this->request->getJSON(true);
            if (!is_array($input)) {
                $input = $this->request->getPost();
            }
            $publications = $input['publications'] ?? [];
            if (!is_array($publications)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error'   => 'INVALID_PUBLICATIONS',
                ]);
            }

            $stats = ['inserted' => 0, 'updated' => 0, 'skipped_unchanged' => 0, 'authors_synced' => 0];
            $ids   = [];
            $this->db->transStart();
            foreach ($publications as $pub) {
                if (!is_array($pub)) {
                    continue;
                }
                $saved = $this->upsertPublicationFromSyncPayload($pub, $user, $stats);
                if ($saved > 0) {
                    $ids[] = $saved;
                }
            }
            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Publication sync transaction failed');
            }

            $items = $this->buildPublicationSyncItemsByIds(array_values(array_unique($ids)));

            return $this->response->setJSON([
                'success'      => true,
                'message'      => 'Publications upserted from newScience',
                'stats'        => $stats,
                'publications' => $items,
                'content_hash' => hash('sha256', json_encode($items, JSON_UNESCAPED_UNICODE)),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'CvSyncApiController::postPublicationsSyncBundleByEmail ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error'   => 'SERVER_ERROR',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build contributor tags for newScience sync from publication_authors.
     *
     * Email remains the canonical identity when present. Rows without email are
     * still exported by display name so newScience can show unresolved authors
     * without creating placeholder accounts.
     *
     * @param list<int> $publicationIds
     *
     * @return array<int, list<array<string, mixed>>>
     */
    private function buildContributorsByPublicationIds(array $publicationIds): array
    {
        $publicationIds = array_values(array_unique(array_filter(
            array_map(static fn ($id): int => (int) $id, $publicationIds),
            static fn (int $id): bool => $id > 0
        )));
        if ($publicationIds === []) {
            return [];
        }

        $rows = $this->db->table('publication_authors pa')
            ->select('pa.publication_id, pa.author_name, pa.author_email, pa.author_affiliation, pa.author_order, pa.corresponding, pa.uid, u.email AS user_email, u.faculty_id AS rr_faculty_id')
            ->join('user u', 'u.uid = pa.uid', 'left')
            ->whereIn('pa.publication_id', $publicationIds)
            ->orderBy('pa.publication_id', 'ASC')
            ->orderBy('pa.author_order', 'ASC')
            ->orderBy('pa.id', 'ASC')
            ->get()
            ->getResultArray();

        $byPublication = [];
        foreach ($rows as $row) {
            $publicationId = (int) ($row['publication_id'] ?? 0);
            if ($publicationId <= 0) {
                continue;
            }

            $email = strtolower(trim((string) ($row['author_email'] ?? '')));
            if ($email === '') {
                $email = strtolower(trim((string) ($row['user_email'] ?? '')));
            }

            $name = trim((string) ($row['author_name'] ?? ''));
            if ($name === '' && $email === '') {
                continue;
            }

            $facultyId = $row['rr_faculty_id'] ?? null;
            $byPublication[$publicationId][] = [
                'email'         => $email !== '' ? $email : null,
                'name'          => $name !== '' ? $name : null,
                'order'         => (int) ($row['author_order'] ?? 0),
                'corresponding' => (int) ($row['corresponding'] ?? 0),
                'affiliation'   => trim((string) ($row['author_affiliation'] ?? '')) ?: null,
                'rr_user_uid'   => trim((string) ($row['uid'] ?? '')) ?: null,
                'rr_faculty_id' => $facultyId !== null && $facultyId !== '' ? (int) $facultyId : null,
            ];
        }

        return $byPublication;
    }

    /**
     * @param array<string,mixed> $pub
     * @param array<string,mixed> $user
     * @param array<string,int>   $stats
     */
    private function upsertPublicationFromSyncPayload(array $pub, array $user, array &$stats): int
    {
        $title = trim((string) ($pub['title'] ?? ''));
        if ($title === '') {
            return 0;
        }

        $existing = $this->findExistingPublicationForSync($pub);
        $hash = $this->publicationSyncContentHash($pub);
        if ($existing !== null && ($existing['content_hash'] ?? '') === $hash) {
            $stats['skipped_unchanged']++;
            $publicationId = (int) $existing['id'];
            $stats['authors_synced'] += $this->syncPublicationAuthorsFromPayload($publicationId, $pub['contributors'] ?? []);

            return $publicationId;
        }

        $data = [
            'title'            => mb_substr($title, 0, 500),
            'publication_type' => trim((string) ($pub['publication_type'] ?? '')) ?: null,
            'source'           => trim((string) ($pub['source'] ?? '')) ?: null,
            'publication_year' => isset($pub['publication_year']) && $pub['publication_year'] !== '' ? (int) $pub['publication_year'] : null,
            'doi'              => trim((string) ($pub['doi'] ?? '')) ?: null,
            'abstract'         => trim((string) ($pub['abstract'] ?? '')) ?: null,
            'publication_month'=> isset($pub['publication_month']) && $pub['publication_month'] !== '' ? (int) $pub['publication_month'] : null,
            'volume'           => trim((string) ($pub['volume'] ?? '')) ?: null,
            'pages'            => trim((string) ($pub['pages'] ?? '')) ?: null,
            'isbn'             => trim((string) ($pub['isbn'] ?? '')) ?: null,
            'keywords'         => trim((string) ($pub['keywords'] ?? '')) ?: null,
            'notes'            => trim((string) ($pub['notes'] ?? '')) ?: null,
            'ref_url'          => trim((string) ($pub['ref_url'] ?? '')) ?: null,
            'created_by'       => $user['uid'] ?? null,
            'approve'          => 0,
        ];
        $syncFields = $this->publicationSyncFields($pub, $hash);
        $data = array_merge($data, $syncFields);

        if ($existing === null) {
            $this->publicationModel->insert($data);
            $publicationId = (int) $this->publicationModel->getInsertID();
            $stats['inserted']++;
        } else {
            $publicationId = (int) $existing['id'];
            $this->publicationModel->update($publicationId, $data);
            $stats['updated']++;
        }

        $stats['authors_synced'] += $this->syncPublicationAuthorsFromPayload($publicationId, $pub['contributors'] ?? []);

        return $publicationId;
    }

    /**
     * @param array<string,mixed> $pub
     */
    private function findExistingPublicationForSync(array $pub): ?array
    {
        $rrId = (int) ($pub['rr_publication_id'] ?? 0);
        if ($rrId > 0) {
            $row = $this->publicationModel->find($rrId);
            if (is_array($row)) {
                return $row;
            }
        }

        if ($this->db->fieldExists('sync_external_key', 'publications')) {
            $key = trim((string) ($pub['external_key'] ?? ''));
            if ($key !== '') {
                $row = $this->publicationModel->where('sync_external_key', $key)->first();
                if (is_array($row)) {
                    return $row;
                }
            }
        }

        $doi = strtolower(trim((string) ($pub['doi'] ?? '')));
        if ($doi !== '') {
            $row = $this->publicationModel->where('LOWER(TRIM(doi))', $doi)->first();
            if (is_array($row)) {
                return $row;
            }
        }

        $title = trim((string) ($pub['title'] ?? ''));
        $year  = $pub['publication_year'] ?? null;
        if ($title !== '' && $year !== null && $year !== '') {
            $row = $this->publicationModel
                ->where('LOWER(TRIM(title))', mb_strtolower($title))
                ->where('publication_year', (int) $year)
                ->first();
            if (is_array($row)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param array<string,mixed> $pub
     *
     * @return array<string,mixed>
     */
    private function publicationSyncFields(array $pub, string $hash): array
    {
        $data = [];
        if ($this->db->fieldExists('sync_external_key', 'publications')) {
            $data['sync_external_key'] = trim((string) ($pub['external_key'] ?? '')) ?: null;
        }
        if ($this->db->fieldExists('ns_publication_id', 'publications')) {
            $nsId = (int) ($pub['ns_publication_id'] ?? 0);
            $data['ns_publication_id'] = $nsId > 0 ? $nsId : null;
        }
        if ($this->db->fieldExists('sync_origin', 'publications')) {
            $data['sync_origin'] = trim((string) ($pub['sync_origin'] ?? '')) ?: 'newscience';
        }
        if ($this->db->fieldExists('last_synced_from', 'publications')) {
            $data['last_synced_from'] = 'newscience';
        }
        if ($this->db->fieldExists('content_hash', 'publications')) {
            $data['content_hash'] = $hash;
        }

        return $data;
    }

    /**
     * @param mixed $contributors
     */
    private function syncPublicationAuthorsFromPayload(int $publicationId, mixed $contributors): int
    {
        if (!is_array($contributors)) {
            return 0;
        }

        $count = 0;
        foreach ($contributors as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $name  = trim((string) ($row['name'] ?? ''));
            if ($email === '' && $name === '') {
                continue;
            }

            $existing = null;
            if ($email !== '') {
                $existing = $this->db->table('publication_authors')
                    ->where('publication_id', $publicationId)
                    ->where('LOWER(TRIM(author_email))', $email)
                    ->get()
                    ->getRowArray();
            }
            if ($existing === null && $email === '' && $name !== '') {
                $existing = $this->db->table('publication_authors')
                    ->where('publication_id', $publicationId)
                    ->where('LOWER(TRIM(author_name))', mb_strtolower($name))
                    ->get()
                    ->getRowArray();
            }

            $user = $email !== ''
                ? $this->db->table('user')->select('uid')->where('email', $email)->get()->getRowArray()
                : null;
            $data = [
                'publication_id'      => $publicationId,
                'author_name'         => $name !== '' ? mb_substr($name, 0, 255) : ($email !== '' ? $email : ''),
                'author_email'        => $email !== '' ? $email : null,
                'author_affiliation'  => trim((string) ($row['affiliation'] ?? '')) ?: null,
                'uid'                 => $user['uid'] ?? ($row['rr_user_uid'] ?? null),
                'author_order'        => (int) ($row['order'] ?? ($i + 1)),
                'corresponding'       => (int) ($row['corresponding'] ?? 0),
            ];

            if ($existing === null) {
                $this->db->table('publication_authors')->insert($data);
            } else {
                $this->db->table('publication_authors')->where('id', (int) $existing['id'])->update($data);
            }
            $count++;
        }

        return $count;
    }

    /**
     * @param list<int> $publicationIds
     *
     * @return list<array<string,mixed>>
     */
    private function buildPublicationSyncItemsByIds(array $publicationIds): array
    {
        $publicationIds = array_values(array_unique(array_filter(array_map('intval', $publicationIds))));
        if ($publicationIds === []) {
            return [];
        }

        $rows = $this->db->table('publications')
            ->whereIn('id', $publicationIds)
            ->orderBy('publication_year', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();
        $contributorsByPublication = $this->buildContributorsByPublicationIds($publicationIds);
        $items = [];
        foreach ($rows as $pub) {
            $doi = trim((string) ($pub['doi'] ?? ''));
            $id  = (int) ($pub['id'] ?? 0);
            $key = $doi !== '' ? 'pub:h:' . substr(hash('sha256', strtolower($doi)), 0, 40) : 'pub:id:' . $id;
            if (!empty($pub['sync_external_key'])) {
                $key = (string) $pub['sync_external_key'];
            }
            $items[] = [
                'external_key'       => $key,
                'rr_publication_id'  => $id,
                'title'              => (string) ($pub['title'] ?? ''),
                'publication_year'   => $pub['publication_year'] ?? null,
                'publication_type'   => $pub['publication_type'] ?? null,
                'source'             => $pub['source'] ?? null,
                'doi'                => $doi !== '' ? $doi : null,
                'abstract'           => $pub['abstract'] ?? null,
                'publication_month'  => $pub['publication_month'] ?? null,
                'volume'             => $pub['volume'] ?? null,
                'pages'              => $pub['pages'] ?? null,
                'isbn'               => $pub['isbn'] ?? null,
                'keywords'           => $pub['keywords'] ?? null,
                'notes'              => $pub['notes'] ?? null,
                'ref_url'            => $pub['ref_url'] ?? null,
                'contributors'       => $contributorsByPublication[$id] ?? [],
                'metadata'           => [
                    'rr_publication_id' => $id,
                    'doi'               => $doi !== '' ? $doi : null,
                    'ns_publication_id' => $pub['ns_publication_id'] ?? null,
                ],
            ];
        }

        return $items;
    }

    /**
     * @param array<string,mixed> $pub
     */
    private function publicationSyncContentHash(array $pub): string
    {
        $biblio = [];
        foreach (['abstract', 'publication_month', 'volume', 'pages', 'isbn', 'keywords', 'notes', 'ref_url'] as $key) {
            if (! array_key_exists($key, $pub)) {
                continue;
            }
            $val = $pub[$key];
            if ($val === null || $val === '') {
                continue;
            }
            $biblio[$key] = is_string($val) ? mb_strtolower(trim($val)) : $val;
        }
        if (isset($pub['publication_month']) && $pub['publication_month'] !== '' && $pub['publication_month'] !== null) {
            $biblio['publication_month'] = (int) $pub['publication_month'];
        }

        return hash('sha256', json_encode([
            'title'        => mb_strtolower(trim((string) ($pub['title'] ?? ''))),
            'year'         => $pub['publication_year'] ?? null,
            'type'         => $pub['publication_type'] ?? null,
            'source'       => mb_strtolower(trim((string) ($pub['source'] ?? ''))),
            'doi'          => strtolower(trim((string) ($pub['doi'] ?? ''))),
            'contributors' => $pub['contributors'] ?? [],
            'biblio'       => $biblio,
        ], JSON_UNESCAPED_UNICODE));
    }

    private function setCors(): void
    {
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, X-API-KEY');
    }

    private function applyCvOwnerFilter($model, string $userUid, string $email)
    {
        $db = \Config\Database::connect();
        if ($email !== '' && $db->fieldExists('owner_email_norm', 'cv_sections')) {
            return $model->groupStart()
                ->where('owner_email_norm', $email)
                ->orWhere('user_uid', $userUid)
                ->groupEnd();
        }

        return $model->where('user_uid', $userUid);
    }

    private function withCvOwnerEmail(array $data, string $email): array
    {
        $db = \Config\Database::connect();
        if ($email !== '' && $db->fieldExists('owner_email_norm', 'cv_sections')) {
            $data['owner_email_norm'] = $email;
        }

        return $data;
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
        $ownerEmail   = UserIdentity::normalizeEmail($canonicalEmail);

        $orcidId = $profile['orcid_id'] ?? null;
        if ($orcidId === null && !empty($profile['orcid'])) {
            if (preg_match('/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/i', (string) $profile['orcid'], $m)) {
                $orcidId = strtoupper($m[1]);
            }
        }

        $sections = $this->applyCvOwnerFilter($sectionModel, $userUid, $ownerEmail)
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
    private function replaceCvFromBundle(string $userUid, string $canonicalEmail, array $bundle): void
    {
        $db            = \Config\Database::connect();
        $sectionModel  = new CvSectionModel();
        $entryModel    = new CvEntryModel();
        $ownerEmail    = UserIdentity::normalizeEmail($canonicalEmail);

        $db->transStart();

        $existing = $this->applyCvOwnerFilter($sectionModel, $userUid, $ownerEmail)->findAll();
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
            $sectionModel->insert($this->withCvOwnerEmail([
                'user_uid'     => $userUid,
                'type'         => (string) ($sec['type'] ?? 'custom'),
                'title'        => mb_substr((string) ($sec['title'] ?? ''), 0, 255),
                'description'  => $sec['description'] ?? null,
                'sort_order'   => (int) ($sec['sort_order'] ?? $order),
                'is_default'   => 0,
            ], $ownerEmail));
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
