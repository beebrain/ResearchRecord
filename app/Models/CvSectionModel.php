<?php

namespace App\Models;

use CodeIgniter\Model;

class CvSectionModel extends Model
{
    protected $table            = 'cv_sections';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'user_uid',
        'owner_email_norm',
        'type',
        'title',
        'description',
        'sort_order',
        'is_default',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}