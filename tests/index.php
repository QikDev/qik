<?php 
error_reporting(E_ALL);
require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use Qik\Core\APIServer;
use Qik\Debug\Debugger;
use Tests\Controllers;

$server = new APIServer();
$server->RegisterDeveloper('172.19.0.1', 'Mike Stevens');
$server->RegisterController(new Controllers\DefaultController);

Debugger::SetTimestamp('init');

$server->RegisterPostCache(function() {
	echo 'post cache callback';
	exit;
});

$server->Configure();
$server->Serve();

Debugger::SetTimestamp('denit');
?>