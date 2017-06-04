<?php 
error_reporting(E_ALL);
require_once __DIR__ . '/vendor/autoload.php'; // Autoload files using Composer autoload

use Qik\Qik;

Qik::Serve();
exit;
?>