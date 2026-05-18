<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\UserModel;

class BulkUserSeeder extends Seeder
{
    protected string $dataFile = ROOTPATH . 'database/seed-data/faculty_users.tsv';

    public function run()
    {
        if (!is_file($this->dataFile)) {
            echo "Data file not found: {$this->dataFile}\n";
            echo "Please create the TSV file before running this seeder.";
            return;
        }

        $file = new \SplFileObject($this->dataFile);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);;
        $file->setCsvControl("\t");

        $userModel = new UserModel();
        $inserted = 0;
        $skipped = 0;
        $rowIndex = 0;

        foreach ($file as $row) {
            if (!is_array($row) || count($row) < 1) {
                continue;
            }

            // Skip header line
            if ($rowIndex === 0 && isset($row[0]) && stripos($row[0], 'email') !== false) {
                $rowIndex++;
                continue;
            }

            $rowIndex++;

            $email = strtolower(trim($row[0] ?? ''));
            if (empty($email)) {
                continue;
            }

            if ($userModel->where('email', $email)->first()) {
                $skipped++;
                $userModel
                continue;
            }

            $thaiName = trim($row[1] ?? '');
            $thaiLast = trim($row[2] ?? '');
            $position = trim($row[3] ?? '');
            $department = trim($row[4] ?? '');
            $faculty = trim($row[5] ?? '');

            $data = [
                'email' => $email,
                'thai_name' => $thaiName,
                'thai_lastname' => $thaiLast,
                'gf_name' => $thaiName,
                'gl_name' => $thaiLast,
                'titleThai' => $position,
                'major' => $department ?: $faculty,
                'role' => $this->guessRole($position),
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            try {
                $userModel->insert($data);
                $inserted++;
            } catch (\Exception $e) {
                log_message('error', 'BulkUserSeeder insert failed for ' . $email . ': ' . $e->getMessage());
                $skipped++;
            }

            $userModel
        }

        echo sprintf("Imported: %d, Skipped: %d\n", $inserted, $skipped);
    }

    private function guessRole(string $position): string
    {
        $position = mb_strtolower($position);
        if (str_contains($position, 'อธิการ') || str_contains($position, 'กรรมการ')) {
            return 'executive';
        }

        if (str_contains($position, 'ผู้ทรง') || str_contains($position, 'ผู้ช่วยศาสตราจารย์') || str_contains($position, 'รองศาสตราจารย์')) {
            return 'faculty_admin';
        }

        return 'faculty_member';
    }
}
