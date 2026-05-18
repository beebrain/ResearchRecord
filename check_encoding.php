<?php

$file = 'c:\xampp\htdocs\researchRecord\user.csv';
$content = file_get_contents($file);

// Get first 500 bytes
$sample = substr($content, 0, 500);

echo "Sample hex: " . bin2hex($sample) . "\n\n";

// Try different encodings
$encodings = ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'CP874', 'TIS-620'];

echo "Testing encodings:\n";
foreach ($encodings as $enc) {
    if (@mb_check_encoding($content, $enc)) {
        echo "- $enc: Valid\n";
    } else {
        echo "- $enc: Invalid\n";
    }
}

// Read second line
$lines = explode("\n", $content);
echo "\n\nFirst data line raw: " . $lines[1] . "\n";
echo "Hex: " . bin2hex(substr($lines[1], 0, 100)) . "\n";

// Try converting
$converted = iconv('Windows-874', 'UTF-8', $lines[1]);
echo "\nConverted from Windows-874: " . $converted . "\n";
