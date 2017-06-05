<?php 

namespace Tests\Controllers;

use Qik\Core\{APIController};

class DefaultController extends APIController
{
	public function DefaultGET()
	{
		$this->response->AddData('testing', 'test1');
	}
}