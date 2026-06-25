<?php
/**
 * FTP Upload — Replace OLD server index.php with redirect to NEW server
 *
 * OLD: 202.29.52.124  (ResearchRecord เก่า — กำลังยกเลิก)
 * NEW: sci.uru.ac.th/recordresearch
 * FTP: port 990 (implicit FTPS / FileZilla)
 *
 * Usage: php scripts/ftp_redirect_upload.php
 */

// ─── Config ────────────────────────────────────────────────────────
define('FTP_HOST',    '202.29.52.124');
define('FTP_USER',    'rac');
define('FTP_PASS',    'rac@URU@2025');
define('FTP_PORT',    990);
define('FTP_TIMEOUT', 30);
define('NEW_URL',     'https://sci.uru.ac.th/recordresearch/');

// ─── Redirect page content ──────────────────────────────────────────
$redirectHTML = <<<'HTML'
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="5;url=https://sci.uru.ac.th/recordresearch/">
  <meta name="robots" content="noindex">
  <title>ย้ายระบบแล้ว — กำลังพาไปยังระบบใหม่</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Sarabun', 'Helvetica Neue', sans-serif;
      background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 50%, #1a5276 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
    }
    .card {
      background: rgba(255,255,255,0.10);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255,255,255,0.2);
      border-radius: 20px;
      padding: 50px 60px;
      text-align: center;
      max-width: 560px;
      width: 90%;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    .icon { font-size: 64px; margin-bottom: 20px; }
    h1 { font-size: 1.6rem; margin-bottom: 12px; font-weight: 700; }
    p  { font-size: 1rem; opacity: 0.85; line-height: 1.7; margin-bottom: 8px; }
    .new-url {
      display: inline-block;
      margin-top: 24px;
      background: rgba(255,255,255,0.2);
      border: 1px solid rgba(255,255,255,0.4);
      border-radius: 10px;
      padding: 12px 24px;
      font-size: 1.05rem;
      font-weight: 600;
      letter-spacing: 0.3px;
      word-break: break-all;
    }
    .btn {
      display: inline-block;
      margin-top: 28px;
      background: #f39c12;
      color: #1e3a5f;
      text-decoration: none;
      padding: 14px 36px;
      border-radius: 50px;
      font-size: 1rem;
      font-weight: 700;
      transition: all 0.3s;
      box-shadow: 0 4px 15px rgba(243,156,18,0.4);
    }
    .btn:hover { background: #e67e22; transform: translateY(-2px); }
    .countdown {
      margin-top: 20px;
      font-size: 0.9rem;
      opacity: 0.7;
    }
    .progress {
      margin-top: 16px;
      height: 4px;
      background: rgba(255,255,255,0.2);
      border-radius: 2px;
      overflow: hidden;
    }
    .progress-bar {
      height: 100%;
      background: #f39c12;
      border-radius: 2px;
      animation: progress 5s linear forwards;
    }
    @keyframes progress { from { width: 0% } to { width: 100% } }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon">🏛️</div>
    <h1>ระบบได้ย้ายไปยังที่อยู่ใหม่แล้ว</h1>
    <p>ระบบบันทึกข้อมูลวิจัย มหาวิทยาลัยราชภัฏอุตรดิตถ์<br>
       ได้ย้ายไปอยู่ที่ URL ใหม่แล้ว กรุณาอัปเดต Bookmark ของท่าน</p>
    <div class="new-url">sci.uru.ac.th/recordresearch</div>
    <br>
    <a href="https://sci.uru.ac.th/recordresearch/" class="btn">
      ไปยังระบบใหม่ →
    </a>
    <div class="countdown" id="countdown">กำลังพาไปอัตโนมัติใน 5 วินาที...</div>
    <div class="progress"><div class="progress-bar"></div></div>
  </div>

  <script>
    let t = 5;
    const el = document.getElementById('countdown');
    const iv = setInterval(() => {
      t--;
      el.textContent = `กำลังพาไปอัตโนมัติใน ${t} วินาที...`;
      if (t <= 0) {
        clearInterval(iv);
        window.location.href = 'https://sci.uru.ac.th/recordresearch/';
      }
    }, 1000);
  </script>
</body>
</html>
HTML;

// ─── PHP redirect wrapper (index.php) ──────────────────────────────
$redirectPHP = '<?php' . "\n" .
'// ResearchRecord — ย้ายไปยัง sci.uru.ac.th/recordresearch แล้ว' . "\n" .
'// Generated: ' . date('Y-m-d H:i:s') . "\n" .
'header("Location: https://sci.uru.ac.th/recordresearch/", true, 301);' . "\n" .
'exit;' . "\n";

// ─── Helper ─────────────────────────────────────────────────────────
function ok(string $msg): void   { echo "  ✅  $msg\n"; }
function fail(string $msg): void { echo "  ❌  $msg\n"; }
function info(string $msg): void { echo "  ℹ️   $msg\n"; }

function ftpUploadString(
    $ftp,
    string $content,
    string $remotePath
): bool {
    $tmp = tmpfile();
    fwrite($tmp, $content);
    rewind($tmp);
    $result = ftp_fput($ftp, $remotePath, $tmp, FTP_ASCII);
    fclose($tmp);
    return $result;
}

// ─── Main ───────────────────────────────────────────────────────────
echo "\n";
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  FTP Upload — Redirect OLD → NEW ResearchRecord     ║\n";
echo "║  OLD: " . FTP_HOST . "                             ║\n";
echo "║  NEW: sci.uru.ac.th/recordresearch                  ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

if (!function_exists('ftp_connect')) {
    fail('PHP FTP extension ไม่ได้ติดตั้ง');
    exit(1);
}

function ftpConnect(string $host, int $port, int $timeout)
{
    if ($port === 990) {
        if (!function_exists('ftp_ssl_connect')) {
            fail('port 990 ต้องใช้ FTPS — ติดตั้ง/เปิด PHP openssl (ftp_ssl_connect)');
            exit(1);
        }
        return @ftp_ssl_connect($host, $port, $timeout);
    }

    return @ftp_connect($host, $port, $timeout);
}

// ─── Connect ────────────────────────────────────────────────────────
$ftp = ftpConnect(FTP_HOST, FTP_PORT, FTP_TIMEOUT);
if (!$ftp) {
    fail('ไม่สามารถเชื่อมต่อ FTP ที่ ' . FTP_HOST . ':' . FTP_PORT);
    exit(1);
}
ok('เชื่อมต่อ FTP สำเร็จ → ' . FTP_HOST . ':' . FTP_PORT);

if (!@ftp_login($ftp, FTP_USER, FTP_PASS)) {
    fail('FTP Login ล้มเหลว');
    ftp_close($ftp);
    exit(1);
}
ok('Login สำเร็จ — user: ' . FTP_USER);

ftp_pasv($ftp, true);
info('Passive mode: เปิดแล้ว');

// ─── List root to find web root ──────────────────────────────────────
$currentDir = ftp_pwd($ftp);
info("Current dir: $currentDir");

$files = @ftp_nlist($ftp, '.') ?: [];
info('ไฟล์/โฟลเดอร์ที่เห็น:');
foreach ($files as $f) {
    echo "       $f\n";
}

// ─── Find index.php location ─────────────────────────────────────────
// ลอง path ที่น่าจะเป็น: /, /public/, /public_html/, /www/
$candidatePaths = [
    './research_academic',
    './research_academic/public',
    '.',
    './public',
    './public_html',
    './www',
    './Research',
    './recordresearch',
];

$webRoot = null;
foreach ($candidatePaths as $path) {
    $list = @ftp_nlist($ftp, $path) ?: [];
    $hasIndex = false;
    foreach ($list as $f) {
        if (basename($f) === 'index.php') {
            $hasIndex = true;
            $webRoot = $path;
            break;
        }
    }
    if ($hasIndex) {
        ok("พบ index.php ใน: $webRoot");
        break;
    }
}

if (!$webRoot) {
    info('ไม่พบ index.php อัตโนมัติ — ใช้ research_academic/');
    $webRoot = './research_academic';
}

// อัปโหลดทั้ง root CI4 และ public/ (entry point หลักของ IIS)
$uploadTargets = array_values(array_unique([
    $webRoot,
    './research_academic',
    './research_academic/public',
]));

foreach ($uploadTargets as $target) {
    $list = @ftp_nlist($ftp, $target) ?: [];
    $hasIndex = false;
    foreach ($list as $f) {
        if (basename($f) === 'index.php') {
            $hasIndex = true;
            break;
        }
    }
    if (!$hasIndex) {
        info("ข้าม $target — ไม่มี index.php");
        continue;
    }

    $remoteIndex  = "$target/index.php";
    $remoteBackup = "$target/index.php.bak_" . date('Ymd_His');

    $tmpBackup = tempnam(sys_get_temp_dir(), 'rr_index_');
    if (@ftp_get($ftp, $tmpBackup, $remoteIndex, FTP_ASCII)) {
        ok("Backup เดิม → $remoteBackup");
        @ftp_put($ftp, $remoteBackup, $tmpBackup, FTP_ASCII);
    } else {
        info("ไม่มี index.php เดิมที่ $target — ข้าม backup");
    }
    @unlink($tmpBackup);

    if (ftpUploadString($ftp, $redirectPHP, $remoteIndex)) {
        ok("Upload redirect index.php สำเร็จ → $remoteIndex");
    } else {
        fail("Upload index.php ล้มเหลว → $remoteIndex");
    }

    $remoteHTML = "$target/index.html";
    if (ftpUploadString($ftp, $redirectHTML, $remoteHTML)) {
        ok("Upload redirect index.html สำเร็จ → $remoteHTML");
    }

    $size = @ftp_size($ftp, $remoteIndex);
    if ($size > 0) {
        ok("ตรวจสอบ: $remoteIndex ขนาด $size bytes");
    }
}

ftp_close($ftp);
echo "\n✅ เสร็จสิ้น — หน้าแรกของ OLD server จะ redirect ไปที่:\n";
echo "   " . NEW_URL . "\n\n";
