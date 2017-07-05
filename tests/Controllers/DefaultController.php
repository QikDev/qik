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
		$results = DBQuery::Build()->from($obj->GetTable())->leftJoin('related')->leftJoin('related2')->select(array('related.rol1', 'related2.roll3', 'related2.id'))->FetchAll();
		
		$this->response->AddData(DBResult::CreateObjects($results, [new Object, new Related, new Related2]));
	}
}