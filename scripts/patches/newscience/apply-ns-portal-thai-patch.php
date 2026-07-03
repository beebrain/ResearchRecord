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

$normalized = str_replace("\r\n", "\n", $content);

if (! str_contains($normalized, 'use App\\Libraries\\PortalPersonNames;')) {
    $normalized = str_replace(
        "use CodeIgniter\\Model;\n",
        "use CodeIgniter\\Model;\nuse App\\Libraries\\PortalPersonNames;\n",
        $normalized,
        $count
    );
    if ($count === 0) {
        fwrite(STDERR, "Could not add PortalPersonNames import\n");
        exit(1);
    }
}

$oldExisting = "        if (\$user !== null) {\n            \$uid = (int) (\$user['uid'] ?? \$user['id'] ?? 0);\n            log_message('info', 'UserModel::findOrCreateFromPortalUser existing user skip portal sync uid=' . \$uid . ' portal_email=' . \$email);\n            return \$user;\n        }";

$newExisting = "        if (\$user !== null) {\n            \$uid = (int) (\$user['uid'] ?? \$user['id'] ?? 0);\n            \$this->syncPortalNamesFromOAuth(\$uid, \$portalUser);\n            log_message('info', 'UserModel::findOrCreateFromPortalUser existing user portal sync uid=' . \$uid . ' portal_email=' . \$email);\n\n            return \$this->find(\$uid) ?: \$user;\n        }";

if (str_contains($normalized, $oldExisting)) {
    $normalized = str_replace($oldExisting, $newExisting, $normalized);
} elseif (! str_contains($normalized, 'syncPortalNamesFromOAuth')) {
    fwrite(STDERR, "Existing-user block not found (already patched?)\n");
    exit(1);
}

$oldTf = "            'tf_name' => trim(\$portalUser['tf_name'] ?? \$portalUser['first_name_th'] ?? \$portalUser['firstname_th'] ?? \$portalUser['thai_name'] ?? \$portalUser['th_name'] ?? ''),\n            'tl_name' => trim(\$portalUser['tl_name'] ?? \$portalUser['last_name_th'] ?? \$portalUser['lastname_th'] ?? \$portalUser['thai_lastname'] ?? ''),";

$newTf = "            'tf_name' => PortalPersonNames::thaiFirstLast(\$portalUser)[0],\n            'tl_name' => PortalPersonNames::thaiFirstLast(\$portalUser)[1],";

if (str_contains($normalized, $oldTf)) {
    $normalized = str_replace($oldTf, $newTf, $normalized);
}

if (! str_contains($normalized, 'function syncPortalNamesFromOAuth')) {
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
    if (! str_contains($normalized, $needle)) {
        fwrite(STDERR, "Cannot insert syncPortalNamesFromOAuth method\n");
        exit(1);
    }
    $normalized = str_replace($needle, $method . "\n" . $needle, $normalized);
}

$content = str_replace("\n", "\r\n", $normalized);

if (file_put_contents($userPath, $content) === false) {
    fwrite(STDERR, "Write failed\n");
    exit(1);
}

echo "Patched: {$userPath}\n";
