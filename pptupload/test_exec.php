<?php
header('Content-Type: text/plain; charset=utf-8');

echo "exec exists: " . (function_exists('exec') ? "yes" : "no") . "\n";
echo "shell_exec exists: " . (function_exists('shell_exec') ? "yes" : "no") . "\n";
echo "disable_functions: " . (ini_get('disable_functions') ?: '(none)') . "\n";
echo "PHP_BINARY: " . (PHP_BINARY ?: '(empty)') . "\n";
echo "whoami: " . trim((string)shell_exec('whoami 2>&1')) . "\n";
echo "php -v: " . trim((string)shell_exec('php -v 2>&1')) . "\n";
