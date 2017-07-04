<?php 

namespace Qik\Database;

use Qik\Utility\{Utility};

class DBResult
{
	public static function CreateObjects($results, array $objects)
	{
		if (empty($results) || empty($objects))
			return array();

		if (empty($results))
			$results = $statement->FetchAll();

		$baseObjects = [];
		$columns = [];
		foreach ($objects as $object)
		{
			$baseObjects[Utility::GetBaseClassNameFromNamespace($object)] = $object;
			$cols = $object->GetColumns();
			foreach ($cols as $key=>$val)
			{
				if (!isset($columns[$key]))
					$columns[$key] = Utility::GetBaseClassNameFromNamespace($object);
			}
		}


		$return = [];
		foreach ($results as $result)
		{
			$objectified = $baseObjects;
			foreach ($result as $key=>$val)
			{
				if (strpos($key, '_') > -1)
				{
					$reversed = strrev($key);

					$parts = explode('_', $reversed);
					$field = strrev(array_shift($parts));
					$table = strrev(implode('_', $parts));

					$objectified[ucfirst($table)]->{$field} = $val;
				}
				elseif (isset($columns[$key]) && !isset($objectified[$columns[$key]]->{$key}))
					$objectified[ucfirst($columns[$key])]->{$key} = $val;
			}

			array_push($return, $objectified);
		}

		return $return;
	}

	public static function Objectify()
	{

	}

	private function Map($query, $result)
	{

	}


}