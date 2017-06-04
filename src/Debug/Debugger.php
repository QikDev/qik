<?php 

namespace Qik\Debug;

use Qik\Qik;

class Debugger
{
	private static $timestamps = [];

	public static function SetTimestamp($tag = null)
	{
		if (!Qik::IsDeveloper())
			return false;

		array_push(self::$timestamps, array(
										'tag'=>$tag,
										'stamp'=>microtime(true), 
										'duration'=>(count(self::$timestamps) > 0 ? (microtime(true)-self::$timestamps[count(self::$timestamps)-2]['stamp']) : 0)
									));

		return self::$timestamps['total'] = array(
												'tag'=>'total',
												'stamp'=>microtime(true), 
												'duration'=>(count(self::$timestamps) > 0 ? (microtime(true)-self::$timestamps[0]['stamp']) : 0)
											);
	}

	public static function GetTimestamps()
	{
		return self::$timestamps;
	}

	public static function ResetTimestamps()
	{
		return self::$timestamps = [];
	}
}