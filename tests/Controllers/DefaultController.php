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
		$results = DBQuery::Build()
						->from('object')
						->select(array('object.id, r__home.rol1 as related__home_rol1, r__away.rol1 as related__away_rol1'))
						->leftJoin('related as r__home ON r__home.id = object.related_id')
						->leftJoin('related as r__away ON r__away.id = object.related2_id')
					->GetQuery();

		Utility::Dump($results);
		exit;

		$obj = new Object();
		$obj->col1 = 10;
		$obj->col2 = 20;
		//$obj->col3 = 30;
		$obj->Insert();

		$this->response->AddData($obj);
		$this->response->Send();

		exit;


		
		$this->response->AddData('results', DBResult::CreateObjects($results, [new Object, new Related, new Related2]));
	}
}