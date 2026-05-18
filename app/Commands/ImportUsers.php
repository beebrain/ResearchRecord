<?php

namespace App\Commands;

use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ImportUsers extends BaseCommand
{
    protected $group = 'custom';
    protected $name = 'import:users';
    protected $description = 'Import users from a CSV or TSV file (EMAIL, NAME, SURNAME, POSITION_NAME, DEPARTMENT_BRANCH, FACULTY).';
    protected $usage = 'import:users [path_to_csv_or_tsv]';

    public function run(array $params)
    {
        $filePath = $params[0] ?? ROOTPATH . 'database/seed-data/faculty_users.csv';

        if (!is_file($filePath)) {
            CLI::error("Data file not found: {$filePath}");
            return;
        }

        // Read file content and fix encoding
        $content = file_get_contents($filePath);

        // Remove BOM if present
        $content = str_replace("\xEF\xBB\xBF", '', $content);

        // Convert from Windows-874 (Thai) to UTF-8
        if (!mb_check_encoding($content, 'UTF-8')) {
            // Try Windows-874 first (Thai encoding)
            $converted = @iconv('Windows-874', 'UTF-8//IGNORE', $content);
            if ($converted !== false) {
                $content = $converted;
            } else {
                // Fallback to Windows-1252
                $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
            }
        }

        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'import_');
        file_put_contents($tempFile, $content);

        $file = new \SplFileObject($tempFile);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);

        // Detect delimiter: CSV uses comma, TSV uses tab
        $delimiter = (pathinfo($filePath, PATHINFO_EXTENSION) === 'tsv') ? "\t" : ",";
        $file->setCsvControl($delimiter);

        $userModel = new UserModel();
        $inserted = 0;
        $skipped = 0;
        $line = 0;

        foreach ($file as $row) {
            if (!is_array($row) || count(array_filter($row, fn($value) => $value !== null && $value !== '')) === 0) {
                continue;
            }

            // Skip header
            if ($line === 0 && (stripos($row[0] ?? '', 'email') !== false || stripos($row[0] ?? '', 'c') === 0)) {
                $line++;
                continue;
            }

            $line++;

            $email = strtolower(trim($row[0] ?? ''));
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            if ($userModel->where('email', $email)->first()) {
                $skipped++;
                continue;
            }

            $thaiName   = trim($row[1] ?? '');
            $thaiLast   = trim($row[2] ?? '');

            // Only import if we have at least email and name
            if (empty($thaiName)) {
                $skipped++;
                continue;
            }

            $data = [
                'email'        => $email,
                'thai_name'    => $thaiName,
                'thai_lastname'=> $thaiLast,
                'gf_name'      => $thaiName,
                'gl_name'      => $thaiLast,
                'role'         => 'user',
                'active'       => 1,
                'created_at'   => date('Y-m-d H:i:s'),
            ];

            try {
                $userModel->insert($data);
                $inserted++;
                CLI::write("Imported: {$email} - {$thaiName} {$thaiLast}", 'yellow');
            } catch (\Throwable $e) {
                log_message('error', 'ImportUsers insert failed for ' . $email . ': ' . $e->getMessage());
                $skipped++;
            }
        }

        // Clean up temp file
        @unlink($tempFile);

        CLI::write(sprintf('Import complete. Inserted: %d, Skipped: %d', $inserted, $skipped), 'green');
    }

    private function guessRole(string $position): string
    {
        $position = mb_strtolower($position);

        if (str_contains($position, 'อธิการ') || str_contains($position, 'กรรมการ')) {
            return 'executive';
        }

        if (
            str_contains($position, 'ผู้ทรง') ||
            str_contains($position, 'ผู้ช่วยศาสตราจารย์') ||
            str_contains($position, 'รองศาสตราจารย์')
        ) {
            return 'faculty_admin';
        }

        return 'faculty_member';
    }
}
