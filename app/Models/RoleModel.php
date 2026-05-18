<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'name',
        'display_name',
        'description'
    ];

    /**
     * Get role by name
     */
    public function getRoleByName($name)
    {
        return $this->where('name', $name)->first();
    }

    /**
     * Get all active roles
     */
    public function getAllRoles()
    {
        return $this->orderBy('id', 'ASC')->findAll();
    }
}
