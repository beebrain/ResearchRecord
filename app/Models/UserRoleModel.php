<?php

namespace App\Models;

use CodeIgniter\Model;

class UserRoleModel extends Model
{
    protected $table = 'user_roles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id',
        'role_id',
        'faculty_id',
        'assigned_at',
        'assigned_by'
    ];

    /**
     * Get all roles for a user
     */
    public function getUserRoles($userId)
    {
        return $this->select('user_roles.*, roles.name, roles.display_name, faculties.name as faculty_name, faculties.code as faculty_code')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->join('faculties', 'faculties.id = user_roles.faculty_id', 'left')
            ->where('user_roles.user_id', $userId)
            ->findAll();
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($userId, $roleName)
    {
        $result = $this->select('user_roles.*')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->where('roles.name', $roleName)
            ->first();

        return !empty($result);
    }

    /**
     * Check if user has role with specific faculty
     */
    public function hasRoleInFaculty($userId, $roleName, $facultyId)
    {
        $result = $this->select('user_roles.*')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->where('roles.name', $roleName)
            ->where('user_roles.faculty_id', $facultyId)
            ->first();

        return !empty($result);
    }

    /**
     * Get all faculties a user can manage (for faculty_admin role)
     */
    public function getManagedFaculties($userId)
    {
        $results = $this->select('user_roles.faculty_id, faculties.name, faculties.code')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->join('faculties', 'faculties.id = user_roles.faculty_id')
            ->where('user_roles.user_id', $userId)
            ->where('roles.name', 'faculty_admin')
            ->whereNotNull('user_roles.faculty_id')
            ->findAll();

        return $results;
    }

    /**
     * Assign role to user
     */
    public function assignRole($userId, $roleId, $facultyId = null, $assignedBy = null)
    {
        $data = [
            'user_id' => $userId,
            'role_id' => $roleId,
            'faculty_id' => $facultyId,
            'assigned_at' => date('Y-m-d H:i:s'),
            'assigned_by' => $assignedBy
        ];

        return $this->insert($data);
    }

    /**
     * Remove role from user
     */
    public function removeRole($userId, $roleId, $facultyId = null)
    {
        $builder = $this->where('user_id', $userId)
                       ->where('role_id', $roleId);

        if ($facultyId !== null) {
            $builder->where('faculty_id', $facultyId);
        }

        return $builder->delete();
    }

    /**
     * Remove all roles from user
     */
    public function removeAllUserRoles($userId)
    {
        return $this->where('user_id', $userId)->delete();
    }

    /**
     * Get users by role
     */
    public function getUsersByRole($roleName)
    {
        return $this->select('user_roles.*, user.uid, user.email, user.gf_name, user.gl_name, user.thai_name, user.thai_lastname')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->join('user', 'user.uid = user_roles.user_id')
            ->where('roles.name', $roleName)
            ->where('user.active', 1)
            ->findAll();
    }

    /**
     * Update user roles (replace all existing with new set)
     */
    public function updateUserRoles($userId, $roles, $assignedBy = null)
    {
        // Remove all existing roles
        $this->removeAllUserRoles($userId);

        // Insert new roles
        if (!empty($roles)) {
            $batch = [];
            foreach ($roles as $role) {
                $batch[] = [
                    'user_id' => $userId,
                    'role_id' => $role['role_id'],
                    'faculty_id' => $role['faculty_id'] ?? null,
                    'assigned_at' => date('Y-m-d H:i:s'),
                    'assigned_by' => $assignedBy
                ];
            }
            return $this->insertBatch($batch);
        }

        return true;
    }
}
