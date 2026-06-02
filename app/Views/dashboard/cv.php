<?php
// Get expertise as array
$expertiseList = [];
if (!empty($user_profile['expertise'])) {
    $expertiseList = array_map('trim', explode(',', $user_profile['expertise']));
}

// Calculate stats by year
$publicationsByYear = [];
foreach ($all_publications ?? [] as $pub) {
    $year = $pub['publication_year'] ?? '';
    if ($year) {
        $publicationsByYear[$year] = ($publicationsByYear[$year] ?? 0) + 1;
    }
}
krsort($publicationsByYear);

// Calculate stats by type
$publicationsByType = [];
foreach ($all_publications ?? [] as $pub) {
    $type = $pub['publication_type'] ?? 'Other';
    $publicationsByType[$type] = ($publicationsByType[$type] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Academic CV') ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #f8f9fa; }
        .section-title { font-size: 14px; text-transform: uppercase; letter-spacing: 2px; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 16px; }
        
        @media print {
            .no-print { display: none !important; }
            body { background: white; padding: 0; }
            .print-container { box-shadow: none !important; border: none !important; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>

<body class="min-h-screen py-8">
    <!-- Quick Access Bar (Hidden on print) -->
    <div class="no-print max-w-5xl mx-auto px-4 mb-6">
        <div class="flex items-center justify-between bg-white rounded-xl shadow-sm px-4 py-3 border border-gray-100">
            <div class="flex items-center gap-4">
                <a href="<?= site_url('dashboard') ?>" class="text-gray-500 hover:text-gray-700 text-sm">← กลับ Dashboard</a>
                <span class="text-gray-300">|</span>
                <span class="font-semibold text-gray-700">📄 Academic CV</span>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= site_url('dashboard/cv-manage') ?>" class="px-3 py-1.5 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50">📝 แก้ไข CV</a>
                <a href="<?= site_url('dashboard/settings') ?>" class="px-3 py-1.5 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50">⚙️ ตั้งค่า</a>
                <button onclick="window.print()" class="px-3 py-1.5 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900">🖨️ พิมพ์ CV</button>
            </div>
        </div>
    </div>

    <!-- CV Content -->
    <div class="max-w-5xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden print-container">
            <!-- Header Section -->
            <div class="p-8 border-b border-gray-100">
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-4xl font-light text-gray-800 tracking-wide mb-1"><?= esc(strtoupper($user['name'] ?? 'RESEARCHER')) ?></h1>
                        <p class="text-sm text-gray-500 uppercase tracking-widest"><?= esc($user['position'] ?? $user['major'] ?? 'Faculty Member') ?></p>
                    </div>
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="<?= esc(str_replace('https:', 'http:', $user['profile_picture'])) ?>" class="w-24 h-24 rounded-full object-cover border-4 border-gray-100">
                    <?php else: ?>
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-gray-300 to-gray-400 flex items-center justify-center text-white text-3xl font-light">
                            <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="flex">
                <!-- Left Column -->
                <div class="w-1/3 p-8 bg-gray-50 border-r border-gray-100">
                    <!-- Contact -->
                    <div class="mb-8">
                        <h3 class="section-title">Contact</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-3">
                                <span class="text-gray-400">📧</span>
                                <span class="text-gray-700"><?= esc($user['email'] ?? '-') ?></span>
                            </div>
                            <?php if (!empty($user_profile['phone'])): ?>
                            <div class="flex items-center gap-3">
                                <span class="text-gray-400">📞</span>
                                <span class="text-gray-700"><?= esc($user_profile['phone']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex items-center gap-3">
                                <span class="text-gray-400">📍</span>
                                <span class="text-gray-700"><?= esc($user_profile['institution'] ?? $user['faculty'] ?? 'Uttaradit Rajabhat University') ?></span>
                            </div>
                            <?php if (!empty($user_profile['orcid_id'])): ?>
                            <div class="flex items-center gap-3">
                                <span class="text-gray-400">🔗</span>
                                <span class="text-gray-700">ORCID: <?= esc($user_profile['orcid_id']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="mb-8">
                        <h3 class="section-title">Statistics</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 text-sm">Publications</span>
                                <span class="font-semibold text-gray-800"><?= $cv_stats['total_publications'] ?? 0 ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 text-sm">Collaborators</span>
                                <span class="font-semibold text-gray-800"><?= $cv_stats['unique_authors'] ?? 0 ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 text-sm">Active Years</span>
                                <span class="font-semibold text-gray-800"><?= $cv_stats['active_years'] ?? 1 ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Skills/Expertise -->
                    <?php if (!empty($expertiseList)): ?>
                    <div class="mb-8">
                        <h3 class="section-title">Expertise</h3>
                        <div class="space-y-2">
                            <?php foreach ($expertiseList as $skill): ?>
                                <div class="text-sm text-gray-700"><?= esc($skill) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Publication Types -->
                    <?php if (!empty($publicationsByType)): ?>
                    <div class="mb-8">
                        <h3 class="section-title">By Type</h3>
                        <div class="space-y-2">
                            <?php foreach ($publicationsByType as $type => $count): ?>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600"><?= esc(ucfirst($type)) ?></span>
                                    <span class="text-gray-800 font-medium"><?= $count ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Academic Profiles -->
                    <?php if (!empty($social_links)): ?>
                    <div>
                        <h3 class="section-title">Academic Profiles</h3>
                        <div class="space-y-2 text-sm">
                            <?php foreach ($social_links as $label => $link): ?>
                            <a href="<?= esc($link) ?>" target="_blank" class="block text-gray-600 hover:text-gray-800">🔗 <?= esc($label) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column -->
                <div class="w-2/3 p-8">
                    <!-- Professional Profile -->
                    <?php if (!empty($professional_summary)): ?>
                    <div class="mb-8">
                        <h3 class="section-title">Professional Profile</h3>
                        <p class="text-gray-600 leading-relaxed">
                            <?= esc($professional_summary) ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <!-- CV Sections (Education, Work Experience, etc.) -->
                    <?php if (!empty($cv_sections)): ?>
                        <?php foreach ($cv_sections as $section): ?>
                            <?php if (!empty($section['entries'])): ?>
                            <div class="mb-8">
                                <h3 class="section-title"><?= esc($section['title']) ?></h3>
                                <div class="space-y-4">
                                    <?php foreach ($section['entries'] as $entry): ?>
                                        <div class="border-l-2 border-gray-200 pl-4">
                                            <div class="flex items-start justify-between">
                                                <div>
                                                    <h4 class="font-medium text-gray-800"><?= esc($entry['title']) ?></h4>
                                                    <?php if (!empty($entry['organization'])): ?>
                                                        <p class="text-sm text-gray-600"><?= esc($entry['organization']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($entry['start_date'])): ?>
                                                    <span class="text-xs text-gray-500 shrink-0">
                                                        <?= date('Y', strtotime($entry['start_date'])) ?>
                                                        <?php if (!empty($entry['end_date'])): ?>
                                                            - <?= date('Y', strtotime($entry['end_date'])) ?>
                                                        <?php elseif ($entry['is_current']): ?>
                                                            - Present
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($entry['location'])): ?>
                                                <p class="text-xs text-gray-400 mt-1"><?= esc($entry['location']) ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($entry['description'])): ?>
                                                <p class="text-sm text-gray-500 mt-2"><?= esc($entry['description']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Publications -->
                    <?php if (!empty($all_publications)): ?>
                    <div class="mb-8">
                        <h3 class="section-title">Publications</h3>
                        <div class="space-y-4">
                            <?php foreach ($all_publications as $pub): ?>
                                <div class="border-l-2 border-gray-200 pl-4">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-medium text-gray-500 uppercase"><?= esc($pub['publication_type'] ?? 'Publication') ?></span>
                                        <span class="text-gray-300">|</span>
                                        <span class="text-xs text-gray-500"><?= esc($pub['publication_year'] ?? '') ?></span>
                                    </div>
                                    <h4 class="font-medium text-gray-800 mb-1"><?= esc($pub['title'] ?? 'Untitled') ?></h4>
                                    <?php if (!empty($pub['authors'])): ?>
                                        <p class="text-sm text-gray-500"><?= esc($pub['authors']) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($pub['source'])): ?>
                                        <p class="text-sm text-gray-500 italic"><?= esc($pub['source']) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Publication Timeline -->
                    <?php if (!empty($publicationsByYear)): ?>
                    <div>
                        <h3 class="section-title">Publication Timeline</h3>
                        <div class="space-y-3">
                            <?php 
                            $maxCount = max($publicationsByYear);
                            foreach (array_slice($publicationsByYear, 0, 10, true) as $year => $count): 
                                $barWidth = ($maxCount > 0) ? ($count / $maxCount) * 100 : 0;
                            ?>
                                <div class="flex items-center gap-4">
                                    <span class="w-12 text-sm font-medium text-gray-700"><?= esc($year) ?></span>
                                    <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-gray-400 rounded-full" style="width: <?= $barWidth ?>%;"></div>
                                    </div>
                                    <span class="w-8 text-sm text-gray-600 text-right"><?= esc($count) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-4 bg-gray-50 border-t border-gray-100 text-center text-xs text-gray-400">
                Generated on <?= date('j F Y') ?> • Research Publication Management System
            </div>
        </div>
    </div>

    <!-- Floating Print Button (Mobile) -->
    <div class="no-print fixed bottom-6 right-6 md:hidden">
        <button onclick="window.print()" class="w-14 h-14 bg-gray-800 text-white rounded-full shadow-lg flex items-center justify-center text-2xl hover:bg-gray-700 transition">
            🖨️
        </button>
    </div>
</body>

</html>
