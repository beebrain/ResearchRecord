<?php

namespace App\Models;

use App\Libraries\UserIdentity;
use CodeIgniter\Model;

class UserProfileModel extends Model
{
    protected $table            = 'user_profile';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'user_email',
        'bio',
        'expertise',
        'phone',
        'institution',
        'google_scholar',
        'orcid',
        'orcid_id',
        'orcid_data',
        'orcid_synced_at',
        'scopus',
        'researchgate',
        'linkedin',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getByUserEmail(string $email): ?array
    {
        $email = UserIdentity::normalizeEmail($email);

        return $email === '' ? null : $this->where('user_email', $email)->first();
    }

    public function getOrCreate(string $userEmail): ?array
    {
        $userEmail = UserIdentity::normalizeEmail($userEmail);
        if ($userEmail === '') {
            return null;
        }

        $profile = $this->getByUserEmail($userEmail);
        if ($profile) {
            return $profile;
        }

        if (UserIdentity::resolveUserByEmail($userEmail) === null) {
            log_message('warning', 'UserProfile getOrCreate skipped: no user row for {email}', ['email' => $userEmail]);

            return null;
        }

        $this->insert(['user_email' => $userEmail]);

        return $this->getByUserEmail($userEmail);
    }

    public function updateByUserEmail(string $userEmail, array $data): bool
    {
        $userEmail = UserIdentity::normalizeEmail($userEmail);
        if ($userEmail === '') {
            return false;
        }

        $profile = $this->getByUserEmail($userEmail);
        if ($profile) {
            return $this->update($profile['id'], $data);
        }

        if (UserIdentity::resolveUserByEmail($userEmail) === null) {
            log_message('warning', 'UserProfile update skipped: no user row for {email}', ['email' => $userEmail]);

            return false;
        }

        $data['user_email'] = $userEmail;

        return (bool) $this->insert($data);
    }

    /**
     * @deprecated Use updateByUserEmail — $userKey must be a user email.
     */
    public function updateByUserUid($userKey, array $data): bool
    {
        return $this->updateByUserEmail((string) $userKey, $data);
    }
}
