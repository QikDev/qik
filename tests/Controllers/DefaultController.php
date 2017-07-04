<?php 

namespace Tests\Controllers;

use Qik\Core\{APIController};
use Qik\Utility\Utility;

use Tests\Objects\{Object};

class DefaultController extends APIController
{
	public function GET()
	{
		$object = new Object();
		$object->testing1 = 'testing';
		$object->testing20 = 'testing3';
		$object->testing2 = 'testing3';

		$this->response->AddData($object);
	}
}