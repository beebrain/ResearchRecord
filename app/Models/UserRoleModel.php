<?php

namespace App\Models;

use App\Libraries\UserIdentity;
use CodeIgniter\Model;

class UserRoleModel extends Model
{
    protected $table      = 'user_roles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_email',
        'role_id',
        'faculty_id',
        'assigned_at',
    ];

    public function getUserRoles(string $userEmail)
    {
        $userEmail = UserIdentity::normalizeEmail($userEmail);

        return $this->select('user_roles.*, roles.name, roles.display_name, faculties.name as faculty_name, faculties.code as faculty_code')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->join('faculties', 'faculties.id = user_roles.faculty_id', 'left')
            ->where('user_roles.user_email', $userEmail)
            ->findAll();
    }

    public function hasRole(string $userEmail, string $roleName)
    {
        $userEmail = UserIdentity::normalizeEmail($userEmail);

        $result = $this->select('user_roles.*')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('user_roles.user_email', $userEmail)
            ->where('roles.name', $roleName)
            ->first();

        return ! empty($result);
    }

    public function hasRoleInFaculty(string $userEmail, string $roleName, int $facultyId)
    {
        $userEmail = UserIdentity::normalizeEmail($userEmail);

        $result = $this->select('user_roles.*')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('user_roles.user_email', $userEmail)
            ->where('roles.name', $roleName)
            ->where('user_roles.faculty_id', $facultyId)
            ->first();

        return ! empty($result);
    }

    public function getManagedFaculties(string $userEmail)
    {
        $userEmail = UserIdentity::normalizeEmail($userEmail);

        return $this->select('user_roles.faculty_id, faculties.name, faculties.code')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->join('faculties', 'faculties.id = user_roles.faculty_id')
            ->where('user_roles.user_email', $userEmail)
            ->where('roles.name', 'faculty_admin')
            ->whereNotNull('user_roles.faculty_id')
            ->findAll();
    }

    public function assignRole(string $userEmail, int $roleId, ?int $facultyId = null)
    {
        return $this->insert([
            'user_email'  => UserIdentity::normalizeEmail($userEmail),
            'role_id'     => $roleId,
            'faculty_id'  => $facultyId,
            'assigned_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function removeRole(string $userEmail, int $roleId, ?int $facultyId = null)
    {
        $userEmail = UserIdentity::normalizeEmail($userEmail);
        $builder   = $this->where('user_email', $userEmail)->where('role_id', $roleId);

        if ($facultyId !== null) {
            $builder->where('faculty_id', $facultyId);
        }

        return $builder->delete();
    }

    public function removeAllUserRoles(string $userEmail)
    {
        return $this->where('user_email', UserIdentity::normalizeEmail($userEmail))->delete();
    }

    public function getUsersByRole(string $roleName)
    {
        return $this->select('user_roles.*, user.email, user.gf_name, user.gl_name, user.thai_name, user.thai_lastname')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->join('user', 'user.email = user_roles.user_email')
            ->where('roles.name', $roleName)
            ->where('user.active', 1)
            ->findAll();
    }

    public function updateUserRoles(string $userEmail, array $roles)
    {
        $userEmail = UserIdentity::normalizeEmail($userEmail);
        $this->removeAllUserRoles($userEmail);

        if ($roles === []) {
            return true;
        }

        $batch = [];
        foreach ($roles as $role) {
            $batch[] = [
                'user_email'  => $userEmail,
                'role_id'     => $role['role_id'],
                'faculty_id'  => $role['faculty_id'] ?? null,
                'assigned_at' => date('Y-m-d H:i:s'),
            ];
        }

        return $this->insertBatch($batch);
    }
}
