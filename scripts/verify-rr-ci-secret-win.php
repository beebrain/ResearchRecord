<?php
declare(strict_types=1);

chdir('C:/inetpub/ResearchRecord');
require 'vendor/autoload.php';

$dotenv = new CodeIgniter\Config\DotEnv('C:/inetpub/ResearchRecord');
$dotenv->load();

$cfg = new \Config\NewscienceSso();
echo 'RR CI secret=[' . $cfg->sharedSecret . '] len=' . strlen($cfg->sharedSecret) . PHP_EOL;
echo 'hex=' . bin2hex($cfg->sharedSecret) . PHP_EOL;
