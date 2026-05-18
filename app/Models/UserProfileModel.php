<?php

namespace App\Models;

use CodeIgniter\Model;

class UserProfileModel extends Model
{
    protected $table            = 'user_profile';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'user_uid',
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

    /**
     * Get profile by user UID
     */
    public function getByUserUid($userUid)
    {
        return $this->where('user_uid', $userUid)->first();
    }

    /**
     * Get or create profile for user
     */
    public function getOrCreate($userUid)
    {
        $profile = $this->getByUserUid($userUid);

        if (!$profile) {
            // Create empty profile
            $this->insert([
                'user_uid' => $userUid,
            ]);
            return $this->getByUserUid($userUid);
        }

        return $profile;
    }

    /**
     * Update profile by user UID
     */
    public function updateByUserUid($userUid, $data)
    {
        $profile = $this->getByUserUid($userUid);

        if ($profile) {
            return $this->update($profile['id'], $data);
        } else {
            // Create new profile if doesn't exist
            $data['user_uid'] = $userUid;
            return $this->insert($data);
        }
    }
}
