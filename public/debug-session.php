<?php
// Debug session data
session_start();

echo "<h1>Session Debug</h1>";
echo "<pre>";
echo "All Session Data:\n";
print_r($_SESSION);
echo "\n\nUser Data:\n";
print_r($_SESSION['user_data'] ?? 'No user_data found');
echo "</pre>";
