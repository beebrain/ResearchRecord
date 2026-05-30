<?php

namespace App\Helpers;

use App\Libraries\UserIdentity;

/**
 * RoleHelper
 *
 * Provides role-based permission checking for the application
 *
 * Role hierarchy:
 * - god_mode (backdoor): Bypass all permission checks
 * - super_admin: Full access to all resources
 * - faculty_admin: Access to assigned faculties and their curricula/users
 * - user: Access to own publications only
 */
class RoleHelper
{
    /**
     * Check if god mode is enabled (backdoor access)
     * God mode grants all permissions and bypasses all checks
     */
    public static function isGodMode(): bool
    {
        $session = session();
        return $session->get('god_mode') === true;
    }

    /**
     * Check if user is super admin or in god mode
     */
    public static function isSuperAdmin($user): bool
    {
        // God mode bypasses everything
        if (self::isGodMode()) {
            return true;
        }

        if (is_array($user)) {
            return isset($user['role']) && $user['role'] === 'super_admin';
        }
        return false;
    }

    /**
     * Check if user is faculty admin or in god mode
     */
    public static function isFacultyAdmin($user): bool
    {
        // God mode bypasses everything
        if (self::isGodMode()) {
            return true;
        }

        if (is_array($user)) {
            return isset($user['role']) && $user['role'] === 'faculty_admin';
        }
        return false;
    }

    /**
     * Check if user is regular user
     */
    public static function isRegularUser($user): bool
    {
        if (is_array($user)) {
            return !isset($user['role']) || $user['role'] === 'user';
        }
        return true;
    }

    /**
     * Check if user is a dean of any faculty
     */
    public static function isDean($user): bool
    {
        // God mode bypasses everything
        if (self::isGodMode()) {
            return true;
        }

        if (is_array($user) && isset($user['uid'])) {
            $db = \Config\Database::connect();
            $builder = $db->table('faculties');
            $result = $builder->where('dean_id', $user['uid'])->countAllResults();
            return $result > 0;
        }
        return false;
    }

    /**
     * Check if user is a chair of any curriculum
     */
    public static function isChair($user): bool
    {
        // God mode bypasses everything
        if (self::isGodMode()) {
            return true;
        }

        if (is_array($user) && isset($user['uid'])) {
            $db = \Config\Database::connect();
            $builder = $db->table('curriculum');
            $result = $builder->where('chair_id', $user['uid'])->countAllResults();
            return $result > 0;
        }
        return false;
    }

    /**
     * Get faculty IDs where user is dean
     */
    public static function getDeanFaculties($user): array
    {
        if (!is_array($user) || !isset($user['uid'])) {
            return [];
        }

        $db = \Config\Database::connect();
        $builder = $db->table('faculties');
        $faculties = $builder->select('id')->where('dean_id', $user['uid'])->get()->getResultArray();

        return array_column($faculties, 'id');
    }

    /**
     * Get curriculum IDs where user is chair
     */
    public static function getChairCurricula($user): array
    {
        if (!is_array($user) || !isset($user['uid'])) {
            return [];
        }

        $db = \Config\Database::connect();
        $builder = $db->table('curriculum');
        $curricula = $builder->select('id')->where('chair_id', $user['uid'])->get()->getResultArray();

        return array_column($curricula, 'id');
    }

    /**
     * Get faculty IDs accessible by chair (from their curricula)
     */
    public static function getChairFaculties($user): array
    {
        if (!is_array($user) || !isset($user['uid'])) {
            return [];
        }

        $db = \Config\Database::connect();
        $builder = $db->table('curriculum');
        $curricula = $builder->select('faculty_id')->where('chair_id', $user['uid'])->get()->getResultArray();

        return array_unique(array_column($curricula, 'faculty_id'));
    }

    /**
     * Check if user can manage all faculties
     */
    public static function canManageAllFaculties($user): bool
    {
        return self::isSuperAdmin($user);
    }

    /**
     * Get managed faculty IDs for faculty admin
     * Returns array of faculty IDs or empty array
     */
    public static function getManagedFaculties($user): array
    {
        if (!is_array($user)) {
            return [];
        }

        if (self::isSuperAdmin($user)) {
            // Super admin manages all faculties - return empty to indicate "all"
            return [];
        }

        if (self::isFacultyAdmin($user) && !empty($user['managed_faculties'])) {
            $faculties = json_decode($user['managed_faculties'], true);
            return is_array($faculties) ? $faculties : [];
        }

        return [];
    }

    /**
     * Check if user can access a specific faculty
     */
    public static function canAccessFaculty($user, int $facultyId): bool
    {
        if (self::isSuperAdmin($user)) {
            return true;
        }

        if (self::isFacultyAdmin($user)) {
            $managedFaculties = self::getManagedFaculties($user);
            return in_array($facultyId, $managedFaculties);
        }

        return false;
    }

    /**
     * Check if user can access a specific curriculum
     */
    public static function canAccessCurriculum($user, int $curriculumId, $curriculumModel): bool
    {
        if (self::isSuperAdmin($user)) {
            return true;
        }

        if (self::isFacultyAdmin($user)) {
            $curriculum = $curriculumModel->find($curriculumId);
            if ($curriculum) {
                return self::canAccessFaculty($user, $curriculum['faculty_id']);
            }
        }

        return false;
    }

    /**
     * Check if user can access a specific user record
     *
     * Faculty admin: target is in scope if their user.faculty_id is managed, or their
     * primary curriculum belongs to a managed faculty.
     */
    public static function canAccessUser($user, int $targetUserId, $userModel): bool
    {
        if (self::isSuperAdmin($user)) {
            return true;
        }

        if (self::isFacultyAdmin($user)) {
            $targetUser = $userModel->find($targetUserId);
            if (!$targetUser) {
                return false;
            }
            // Primary: user's home faculty (ผู้แต่งสังกัดคณะ)
            if (!empty($targetUser['faculty_id']) && self::canAccessFaculty($user, (int) $targetUser['faculty_id'])) {
                return true;
            }
            // Secondary: primary curriculum under a managed faculty
            if (!empty($targetUser['curriculum_id'])) {
                $curriculumModel = new \App\Models\CurriculumModel();

                return self::canAccessCurriculum($user, (int) $targetUser['curriculum_id'], $curriculumModel);
            }

            return false;
        }

        // Regular users can only access themselves
        if (is_array($user) && isset($user['uid'])) {
            return $user['uid'] == $targetUserId;
        }

        return false;
    }

    /**
     * Check if user can access a specific publication
     *
     * Faculty admin: may edit when the record creator is in scope, or when any
     * publication author (uid / authors.user_uid / resolvable author_email) is in scope.
     * This keeps edit rights after listing stopped treating created_by-only as "their work".
     */
    public static function canAccessPublication($user, int $publicationId, $publicationModel): bool
    {
        if (self::isSuperAdmin($user)) {
            return true;
        }

        $publication = $publicationModel->find($publicationId);
        if (!$publication) {
            return false;
        }

        if (self::isFacultyAdmin($user)) {
            $userModel = new \App\Models\UserModel();
            if (!empty($publication['created_by']) && self::canAccessUser($user, (int) $publication['created_by'], $userModel)) {
                return true;
            }

            return self::publicationHasAuthorInManagedScope($user, $publicationId, $userModel);
        }

        // Regular users can only access their own publications
        if (is_array($user) && isset($user['uid'])) {
            return $publication['created_by'] == $user['uid'];
        }

        return false;
    }

    /**
     * True if any author row on this publication maps to a user the faculty admin may manage.
     */
    private static function publicationHasAuthorInManagedScope($user, int $publicationId, \App\Models\UserModel $userModel): bool
    {
        $db = \Config\Database::connect();
        $rows = $db->table('publication_authors pa')
            ->select('pa.uid AS pa_uid, a.user_uid AS author_user_uid, pa.author_email')
            ->join('authors a', 'a.id = pa.author_id', 'left')
            ->where('pa.publication_id', $publicationId)
            ->get()
            ->getResultArray();

        $checkedUids = [];
        foreach ($rows as $row) {
            $uid = (int) ($row['pa_uid'] ?? 0);
            if ($uid <= 0) {
                $uid = (int) ($row['author_user_uid'] ?? 0);
            }

            if ($uid > 0) {
                if (isset($checkedUids[$uid])) {
                    continue;
                }
                $checkedUids[$uid] = true;
                if (self::canAccessUser($user, $uid, $userModel)) {
                    return true;
                }

                continue;
            }

            $email = UserIdentity::normalizeEmail((string) ($row['author_email'] ?? ''));
            if ($email === '') {
                continue;
            }

            $target = $userModel->getUserByEmail($email);
            if (!is_array($target) || empty($target['uid'])) {
                continue;
            }
            $tuid = (int) $target['uid'];
            if (isset($checkedUids[$tuid])) {
                continue;
            }
            $checkedUids[$tuid] = true;
            if (self::canAccessUser($user, $tuid, $userModel)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get faculty filter SQL for faculty admin
     * Returns WHERE clause condition or empty string for super admin
     */
    public static function getFacultyFilterSQL($user, string $facultyColumn = 'faculty_id'): string
    {
        if (self::isSuperAdmin($user)) {
            return ''; // No filter - access all
        }

        if (self::isFacultyAdmin($user)) {
            $managedFaculties = self::getManagedFaculties($user);
            if (!empty($managedFaculties)) {
                $ids = implode(',', array_map('intval', $managedFaculties));
                return "$facultyColumn IN ($ids)";
            }
        }

        return '1=0'; // No access
    }

    /**
     * Get user filter SQL for faculty admin
     * Returns WHERE clause condition for filtering users by managed faculties
     */
    public static function getUserFilterSQL($user, $curriculumModel): string
    {
        if (self::isSuperAdmin($user)) {
            return ''; // No filter - access all
        }

        if (self::isFacultyAdmin($user)) {
            $managedFaculties = self::getManagedFaculties($user);
            if (!empty($managedFaculties)) {
                // Get all curricula from managed faculties
                $curricula = $curriculumModel
                    ->whereIn('faculty_id', $managedFaculties)
                    ->findAll();

                if (!empty($curricula)) {
                    $curriculumIds = array_column($curricula, 'id');
                    $ids = implode(',', array_map('intval', $curriculumIds));
                    return "curriculum_id IN ($ids)";
                }
            }
        }

        // Regular users - return condition that matches only themselves
        if (is_array($user) && isset($user['uid'])) {
            return "uid = " . intval($user['uid']);
        }

        return '1=0'; // No access
    }

    /**
     * Check if user can manage roles and permissions
     */
    public static function canManageRoles($user): bool
    {
        return self::isSuperAdmin($user);
    }

    /**
     * Get role display name
     */
    public static function getRoleDisplayName(string $role): string
    {
        $roles = [
            'super_admin' => 'Super Administrator',
            'faculty_admin' => 'Faculty Administrator',
            'user' => 'User'
        ];

        return $roles[$role] ?? 'Unknown';
    }

    /**
     * Get role badge HTML
     */
    public static function getRoleBadge(string $role): string
    {
        $badges = [
            'super_admin' => '<span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs font-medium rounded">Super Admin</span>',
            'faculty_admin' => '<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">Faculty Admin</span>',
            'user' => '<span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded">User</span>'
        ];

        return $badges[$role] ?? '<span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded">Unknown</span>';
    }
}
