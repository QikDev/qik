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
					->FetchAll();
		
		$this->response->AddData('results', DBResult::CreateObjects($results, [new Object]));
	}
}