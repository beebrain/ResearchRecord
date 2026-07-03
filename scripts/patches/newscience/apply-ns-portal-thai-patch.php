#!/usr/bin/env php
<?php
/**
 * Patch newScience UserModel to sync tf_name/tl_name from Portal personInfo on every OAuth login.
 * Run on win-kc:
 *   php C:/inetpub/ResearchRecord/scripts/patches/newscience/apply-ns-portal-thai-patch.php
 */

declare(strict_types=1);

$nsRoot   = 'C:/inetpub/newscience';
$userPath = $nsRoot . '/app/Models/UserModel.php';

if (! is_file($userPath)) {
    fwrite(STDERR, "Not found: {$userPath}\n");
    exit(1);
}

$content = file_get_contents($userPath);
if ($content === false) {
    fwrite(STDERR, "Cannot read UserModel.php\n");
    exit(1);
}

if (! str_contains($content, 'use App\\Libraries\\PortalPersonNames;')) {
    $content = str_replace(
        "use CodeIgniter\\Model;\n",
        "use CodeIgniter\\Model;\nuse App\\Libraries\\PortalPersonNames;\n",
        $content,
        $count
    );
    if ($count === 0) {
        fwrite(STDERR, "Could not add PortalPersonNames import\n");
        exit(1);
    }
}

$oldExisting = <<<'PHP'
        if ($user !== null) {
            $uid = (int) ($user['uid'] ?? $user['id'] ?? 0);
            log_message('info', 'UserModel::findOrCreateFromPortalUser existing user skip portal sync uid=' . $uid . ' portal_email=' . $email);
            return $user;
        }
PHP;

$newExisting = <<<'PHP'
        if ($user !== null) {
            $uid = (int) ($user['uid'] ?? $user['id'] ?? 0);
            $this->syncPortalNamesFromOAuth($uid, $portalUser);
            log_message('info', 'UserModel::findOrCreateFromPortalUser existing user portal sync uid=' . $uid . ' portal_email=' . $email);

            return $this->find($uid) ?: $user;
        }
PHP;

if (str_contains($content, $oldExisting)) {
    $content = str_replace($oldExisting, $newExisting, $content);
} elseif (! str_contains($content, 'syncPortalNamesFromOAuth')) {
    fwrite(STDERR, "Existing-user block not found (already patched?)\n");
    exit(1);
}

$oldTf = <<<'PHP'
            'tf_name' => trim($portalUser['tf_name'] ?? $portalUser['first_name_th'] ?? $portalUser['firstname_th'] ?? $portalUser['thai_name'] ?? $portalUser['th_name'] ?? ''),
            'tl_name' => trim($portalUser['tl_name'] ?? $portalUser['last_name_th'] ?? $portalUser['lastname_th'] ?? $portalUser['thai_lastname'] ?? ''),
PHP;

$newTf = <<<'PHP'
            'tf_name' => PortalPersonNames::thaiFirstLast($portalUser)[0],
            'tl_name' => PortalPersonNames::thaiFirstLast($portalUser)[1],
PHP;

if (str_contains($content, $oldTf)) {
    $content = str_replace($oldTf, $newTf, $content);
}

if (! str_contains($content, 'function syncPortalNamesFromOAuth')) {
    $method = <<<'PHP'

    /**
     * Refresh gf/gl/tf/tl from Portal OAuth payload (incl. personInfo) on each login.
     */
    private function syncPortalNamesFromOAuth(int $uid, array $portalUser): void
    {
        if ($uid <= 0) {
            return;
        }

        [$tfName, $tlName] = PortalPersonNames::thaiFirstLast($portalUser);
        $patch = array_filter([
            'title'   => trim($portalUser['title'] ?? $portalUser['prefix_th'] ?? $portalUser['prefix_en'] ?? ''),
            'gf_name' => trim($portalUser['gf_name'] ?? $portalUser['first_name_en'] ?? $portalUser['firstname_en'] ?? ''),
            'gl_name' => trim($portalUser['gl_name'] ?? $portalUser['last_name_en'] ?? $portalUser['lastname_en'] ?? ''),
            'tf_name' => $tfName,
            'tl_name' => $tlName,
        ], static fn ($v) => $v !== null && $v !== '');

        $existing = $this->find($uid);
        if (! is_array($existing)) {
            return;
        }

        $curTf = trim((string) ($existing['tf_name'] ?? ''));
        $curThai = $curTf . trim((string) ($existing['tl_name'] ?? ''));
        $newThai = $tfName . $tlName;

        if ($tfName === '' || ($curTf !== '' && PortalPersonNames::hasThai($curThai) && ! PortalPersonNames::hasThai($newThai))) {
            unset($patch['tf_name'], $patch['tl_name']);
        }

        if ($patch === []) {
            return;
        }

        $this->update($uid, $patch);
    }

PHP;

    $needle = "    public function findOrCreateFromPortalUser(array \$portalUser): ?array\n    {";
    if (! str_contains($content, $needle)) {
        fwrite(STDERR, "Cannot insert syncPortalNamesFromOAuth method\n");
        exit(1);
    }
    $content = str_replace($needle, $method . "\n" . $needle, $content);
}

if (file_put_contents($userPath, $content) === false) {
    fwrite(STDERR, "Write failed\n");
    exit(1);
}

echo "Patched: {$userPath}\n";
