<?php 
error_reporting(E_ALL);
require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use Qik\Core\APIServer;
use Qik\Debug\Debugger;
use App\Controllers\DefaultController;

Debugger::SetTimestamp('init');

$server = new APIServer();
$server->Configure();
$server->RegisterDeveloper('127.0.0.1', 'Mike Stevens');
$server->RegisterController(new App\Controllers\DefaultController);
$server->Serve();

Debugger::SetTimestamp('denit');
?>