<?php
declare(strict_types=1);

chdir('C:/inetpub/newscience');
require 'vendor/autoload.php';
require 'app/Config/ResearchRecordSso.php';

(new CodeIgniter\Config\DotEnv('C:/inetpub/newscience'))->load();

$c = new \Config\ResearchRecordSso();
echo 'sharedSecret=[' . $c->sharedSecret . ']' . PHP_EOL;
echo 'ok=' . ($c->sharedSecret === 'pisit_secret' ? 'yes' : 'NO') . PHP_EOL;
