<?php

/**
 * cURL Extension Checker
 * 
 * This file checks if the cURL extension is enabled on the server.
 * Access it via: http://your-domain.com/check_curl.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>cURL Extension Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }

        .status {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            font-weight: bold;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }

        .detail {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #007bff;
        }

        .detail strong {
            color: #007bff;
        }

        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background: #f8f9fa;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔍 cURL Extension Check</h1>

        <?php
        // Check if cURL functions exist
        $curlInitExists = function_exists('curl_init');
        $curlExecExists = function_exists('curl_exec');
        $curlSetoptExists = function_exists('curl_setopt');
        $curlGetinfoExists = function_exists('curl_getinfo');
        $curlErrorExists = function_exists('curl_error');
        $curlCloseExists = function_exists('curl_close');

        $allFunctionsExist = $curlInitExists && $curlExecExists && $curlSetoptExists &&
            $curlGetinfoExists && $curlErrorExists && $curlCloseExists;

        // Get PHP version
        $phpVersion = phpversion();

        // Get loaded extensions
        $loadedExtensions = get_loaded_extensions();
        $curlExtensionLoaded = extension_loaded('curl');

        // Try to get cURL version if available
        $curlVersion = null;
        if ($curlInitExists) {
            $ch = curl_init();
            $curlVersion = curl_version();
            curl_close($ch);
        }
        ?>

        <div class="<?php echo $allFunctionsExist ? 'success' : 'error'; ?> status">
            <?php if ($allFunctionsExist): ?>
                ✅ <strong>cURL Extension is ENABLED</strong>
            <?php else: ?>
                ❌ <strong>cURL Extension is NOT ENABLED</strong>
            <?php endif; ?>
        </div>

        <div class="info">
            <strong>PHP Version:</strong> <?php echo htmlspecialchars($phpVersion); ?><br>
            <strong>Server:</strong> <?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?><br>
            <strong>Date:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </div>

        <h2>Function Availability</h2>
        <table>
            <thead>
                <tr>
                    <th>Function</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>curl_init()</code></td>
                    <td><?php echo $curlInitExists ? '✅ Available' : '❌ Not Available'; ?></td>
                </tr>
                <tr>
                    <td><code>curl_exec()</code></td>
                    <td><?php echo $curlExecExists ? '✅ Available' : '❌ Not Available'; ?></td>
                </tr>
                <tr>
                    <td><code>curl_setopt()</code></td>
                    <td><?php echo $curlSetoptExists ? '✅ Available' : '❌ Not Available'; ?></td>
                </tr>
                <tr>
                    <td><code>curl_getinfo()</code></td>
                    <td><?php echo $curlGetinfoExists ? '✅ Available' : '❌ Not Available'; ?></td>
                </tr>
                <tr>
                    <td><code>curl_error()</code></td>
                    <td><?php echo $curlErrorExists ? '✅ Available' : '❌ Not Available'; ?></td>
                </tr>
                <tr>
                    <td><code>curl_close()</code></td>
                    <td><?php echo $curlCloseExists ? '✅ Available' : '❌ Not Available'; ?></td>
                </tr>
            </tbody>
        </table>

        <h2>Extension Information</h2>
        <div class="detail">
            <strong>Extension Loaded:</strong> <?php echo $curlExtensionLoaded ? '✅ Yes' : '❌ No'; ?><br>
            <?php if ($curlVersion): ?>
                <strong>cURL Version:</strong> <?php echo htmlspecialchars($curlVersion['version'] ?? 'Unknown'); ?><br>
                <strong>SSL Version:</strong> <?php echo htmlspecialchars($curlVersion['ssl_version'] ?? 'Unknown'); ?><br>
                <strong>Libz Version:</strong> <?php echo htmlspecialchars($curlVersion['libz_version'] ?? 'Unknown'); ?><br>
                <strong>Protocols:</strong> <?php echo htmlspecialchars(implode(', ', $curlVersion['protocols'] ?? [])); ?>
            <?php else: ?>
                <strong>cURL Version:</strong> Not available (extension not loaded)
            <?php endif; ?>
        </div>

        <?php if ($allFunctionsExist): ?>
            <h2>Test cURL Connection</h2>
            <?php
            // Test cURL with a simple request
            $testUrl = 'https://www.google.com';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $testUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only

            $startTime = microtime(true);
            $result = curl_exec($ch);
            $endTime = microtime(true);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $totalTime = round(($endTime - $startTime) * 1000, 2);
            curl_close($ch);

            if ($result !== false && $httpCode >= 200 && $httpCode < 400):
            ?>
                <div class="success status">
                    ✅ <strong>Test Connection Successful</strong><br>
                    HTTP Code: <?php echo $httpCode; ?><br>
                    Response Time: <?php echo $totalTime; ?> ms
                </div>
            <?php else: ?>
                <div class="error status">
                    ❌ <strong>Test Connection Failed</strong><br>
                    HTTP Code: <?php echo $httpCode ?: 'N/A'; ?><br>
                    Error: <?php echo htmlspecialchars($curlError ?: 'Unknown error'); ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <h2>How to Enable cURL</h2>
            <div class="info">
                <h3>For Linux Servers:</h3>
                <p>
                    <strong>Ubuntu/Debian:</strong><br>
                    <code>sudo apt-get install php-curl</code><br>
                    <code>sudo systemctl restart apache2</code> (or <code>nginx</code>)
                </p>
                <p>
                    <strong>CentOS/RHEL:</strong><br>
                    <code>sudo yum install php-curl</code><br>
                    <code>sudo systemctl restart httpd</code>
                </p>

                <h3>For Windows Servers:</h3>
                <ol>
                    <li>Open <code>php.ini</code> file</li>
                    <li>Find the line: <code>;extension=curl</code></li>
                    <li>Remove the semicolon: <code>extension=curl</code></li>
                    <li>Save the file and restart your web server</li>
                </ol>

                <h3>For XAMPP:</h3>
                <ol>
                    <li>Open <code>C:\xampp\php\php.ini</code></li>
                    <li>Find: <code>;extension=curl</code></li>
                    <li>Change to: <code>extension=curl</code></li>
                    <li>Restart Apache from XAMPP Control Panel</li>
                </ol>
            </div>
        <?php endif; ?>

        <h2>Loaded Extensions</h2>
        <div class="detail">
            <strong>Total Extensions:</strong> <?php echo count($loadedExtensions); ?><br>
            <strong>cURL in List:</strong> <?php echo $curlExtensionLoaded ? '✅ Yes' : '❌ No'; ?><br>
            <details>
                <summary style="cursor: pointer; color: #007bff; margin-top: 10px;">
                    <strong>View All Loaded Extensions (<?php echo count($loadedExtensions); ?>)</strong>
                </summary>
                <div style="margin-top: 10px; max-height: 300px; overflow-y: auto;">
                    <?php
                    sort($loadedExtensions);
                    foreach ($loadedExtensions as $ext):
                        $highlight = ($ext === 'curl') ? 'background: #d4edda; font-weight: bold;' : '';
                    ?>
                        <span style="display: inline-block; padding: 3px 8px; margin: 2px; border-radius: 3px; <?php echo $highlight; ?>">
                            <?php echo htmlspecialchars($ext); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </details>
        </div>

        <div class="footer">
            <p><strong>Note:</strong> This file is for diagnostic purposes. Consider removing it from production after checking.</p>
            <p><strong>File Location:</strong> <code><?php echo __FILE__; ?></code></p>
        </div>
    </div>
</body>

</html>