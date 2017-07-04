<?php 

namespace Tests\Controllers;

use Qik\Core\{APIController};
use Qik\Database\{DBQuery, DBResult};
use Qik\Utility\Utility;

use Tests\Objects\{Object, Related, Related2};

class DefaultController extends APIController
{
	public function GET()
	{
		$obj = new Object();
		$results = DBQuery::Build()->from($obj->GetTable())->select('object.id')->leftJoin('related')->leftJoin('related2')->select('related.rol1')->select('related2.roll3')->FetchAll();
		$this->response->AddData(DBResult::CreateObjects($results, [new Object, new Related, new Related2]));
	}
}