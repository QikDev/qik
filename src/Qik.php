<?php 

namespace Qik;

use Qik\Core\APIConfig;
use Qik\Utility;
use Qik\Exceptions\{APIException, APIError};
use Qik\Exceptions\Request\{NotFound};

class Qik
{
	public static function Init() {
		//echo 'Serving qik!!!';
	}

	public static function GetEnv()
	{
		return APIConfig::ENV;
	}

	public static function Throw()
	{
		//throw new APIException();
	}

	public static function IsDeveloper()
	{
		return true;
	}

	public static function IsLocal()
	{
		return APIConfig::ENV === 'local';
	}

	public static function IsDevelopment()
	{
		return self::IsLocal() || APIConfig::ENV === 'development';
	}

	public static function IsProduction()
	{
		return APIConfig::ENV === 'production';
	}
}