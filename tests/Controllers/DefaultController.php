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
		$results = DBQuery::Build()
						->from('object')
						->select(array('object.id, r__home.rol1 as related__home_rol1, r__away.rol1 as related__away_rol1'))
						->leftJoin('related as r__home ON r__home.id = object.related_id')
						->leftJoin('related as r__away ON r__away.id = object.related2_id')
					->FetchAll();//->GetQuery();
		
		$this->response->AddData(DBResult::CreateObjects($results, [new Object, new Related, new Related2]));
	}
}