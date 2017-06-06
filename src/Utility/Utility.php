<?php 

namespace Qik\Utility;

use Qik\Qik;
use Qik\Core\APIServer;

class Utility
{
	public static function Dump($array = null, $return = false, $bypass = false)
	{		
		if (APIServer::GetEnv() == 'production' && !$bypass)
			return;
			
		if (empty($array))
		{
			var_dump($array);
			return;
		}
		
		if ($return)
			return '<pre>'.print_r($array, true).'</pre>';
		else
		{
			echo '<pre>';
			print_r($array);
			echo '</pre>';
		}
	}

	public static function IsJson($string = null) 
	{
 		json_decode($string);

 		return (json_last_error() == JSON_ERROR_NONE);
	}

	public static function ConvertObjectToArray($object = null, $conditional = null)
	{
		if (is_object($object)) {
			// Gets the properties of the given object
			// with get_object_vars function
			$object = get_object_vars($object);
			if (!is_null($conditional))
			{
				$newObject = array();
				foreach ($object as $key=>$val)
				{
					if ($val === $conditional)
						array_unshift($newObject, $key);
					
					unset($object[$key]);
				}

				$object = $newObject;
			}
		}
 
		if (is_array($object)) {
			return array_map('Utility::ConvertObjectToArray', $object);
		}
		else {
			return $object;
		}
		
		return $object;
	}

	public static function GetBaseClassNameFromNamespace($namespace = null) 
	{
		if (is_object($namespace))
			$namespace = get_class($namespace);
		
		$parts = explode('\\', $namespace);
		$parts = array_reverse($parts);

		return $parts[0];
	}
}