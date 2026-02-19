<?php
echo "exec: " . (function_exists('exec') ? "yes" : "no") . "\n";
echo "disabled: " . ini_get('disable_functions') . "\n";
echo "whoami: " . trim(shell_exec('whoami 2>&1')) . "\n";
echo "php: " . trim(shell_exec('php -v 2>&1')) . "\n";
echo "soffice: " . trim(shell_exec('command -v soffice 2>&1')) . "\n";
echo "pdftoppm: " . trim(shell_exec('command -v pdftoppm 2>&1')) . "\n";
