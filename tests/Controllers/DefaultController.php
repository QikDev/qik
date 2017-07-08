<?php 

namespace Tests\Controllers;

use Qik\Core\{APIController};
use Qik\Exceptions\{APIException};
use Qik\Database\{DBQuery, DBResult};
use Qik\Utility\Utility;

use Tests\Objects\{Object, Related, Related2};

class DefaultController extends APIController
{
	public function GET()
	{
		$object = new Object();
		$user = $object->Select()->FetchAll();
		//exit;
		//$result = DBResult::CreateObjects($user, [new Object, new Related]);
		$this->response->AddData($user);//'users', DBResult::CreateObjects($user, [new Object]));
		//exit;
		//throw new APIException('throw a 400!');
		//Utility::Dump($user);
		//exit;
	}
}