<?php 

namespace Qik\Core;

interface APIControllerInterface
{
	public static function Configure(APIServer $server);
	
	public function Init();
}