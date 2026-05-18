<?php

namespace App\Models;

use CodeIgniter\Model;

class CvEntryModel extends Model
{
    protected $table            = 'cv_entries';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'section_id',
        'title',
        'organization',
        'location',
        'start_date',
        'end_date',
        'is_current',
        'metadata',
        'description',
        'sort_order',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Don't use cast for metadata - handle manually to avoid type issues
    // protected array $casts = [
    //     'metadata' => 'json'
    // ];
}
