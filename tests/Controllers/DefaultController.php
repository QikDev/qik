<?php 

namespace Tests\Controllers;

use Qik\Core\{APIController};
use Qik\Utility\Utility;

use Tests\Objects\{Object};

class DefaultController extends APIController
{
	public function GET()
	{
		$obj = new Object();
		$obj->id = 27;
		$obj->col1 = 'testing22222';
		$obj->col2 = 'testing22222';

		$this->response->AddData($obj);
	}
}