<?php 

namespace App\Controllers;

use Qik\Core\{APIController, APIServer};
use Qik\Utility\{Utility};

class DefaultController extends APIController
{
	public function DefaultGET()
	{
		$this->response->AddData('testing', 'test1');
	}
}