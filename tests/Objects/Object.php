<?php 

namespace Tests\Objects;

use Qik\Database\DBObject;
use Qik\Core\APIObject;

class Object extends DBObject
{
	public function GetPublicModel() : array
	{
		return array(
				'testing1' => [],
				'testing6' => [],
				'testing5' => [],
				'testing4' => [],
				'testing3' => [],
				'testing2' => []
			);
	}
}