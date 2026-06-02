<?php
/**
 * โหลด Config จริงของ CI4 บน win-kc
 */
declare(strict_types=1);

chdir('C:/inetpub/ResearchRecord');
require 'vendor/autoload.php';

$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$rr = config(\Config\NewscienceSso::class);
echo 'RR config sharedSecret len=' . strlen($rr->sharedSecret) . ' [' . $rr->sharedSecret . ']' . PHP_EOL;

chdir('C:/inetpub/newscience');
// minimal bootstrap for NS config only
define('ROOTPATH', 'C:/inetpub/newscience/');
define('APPPATH', 'C:/inetpub/newscience/app/');
define('SYSTEMPATH', 'C:/inetpub/newscience/vendor/codeigniter4/framework/system/');
define('WRITEPATH', 'C:/inetpub/newscience/writable/');
define('FCPATH', 'C:/inetpub/newscience/public/');

require ROOTPATH . 'vendor/autoload.php';
(new CodeIgniter\Config\DotEnv(ROOTPATH))->load();

$ns = new \Config\ResearchRecordSso();
echo 'NS config sharedSecret len=' . strlen($ns->sharedSecret) . ' [' . $ns->sharedSecret . ']' . PHP_EOL;

function b64urlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$payloadB64 = b64urlEncode(json_encode(['email' => 't@t.th', 'name' => 'T', 'exp' => time() + 120]));
$sigNs = b64urlEncode(hash_hmac('sha256', $payloadB64, $ns->sharedSecret, true));
$token = $payloadB64 . '.' . $sigNs;

$parts = explode('.', $token, 2);
$expected = hash_hmac('sha256', $parts[0], $rr->sharedSecret, true);
$padding = 4 - (strlen($parts[1]) % 4);
if ($padding !== 4) {
    $parts[1] .= str_repeat('=', $padding);
}
$sig = base64_decode(strtr($parts[1], '-_', '+/'), true);
echo 'CI configs cross_verify=' . ($sig !== false && hash_equals($expected, $sig) ? 'ok' : 'FAIL') . PHP_EOL;
