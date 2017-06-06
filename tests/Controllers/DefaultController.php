<?php 

namespace Tests\Controllers;

use Qik\Core\{APIController};
use Qik\Utility\Utility;

class DefaultController extends APIController
{
	public function DefaultGET()
	{
		Utility::Dump(self::$server->GetVariable('income'));
		exit;
		$this->response->AddData('testing', 'test1');
	}
}