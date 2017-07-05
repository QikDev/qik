<?php 

namespace Qik\Database;

use Qik\Utility\{Utility};

class DBResult
{
	private $objects = [];

	public function __construct($objects = null)
	{
		$this->objects = $objects;
	}

	public function GetObjects()
	{
		return $this->objects;
	}

	public static function CreateObjects($results, array $objects)
	{
		if (empty($results) || empty($objects))
			return array();

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
					$class = strrev(implode('_', $parts));
					$subclass = '';

					//we're using __ to denote aliases of the same object so we need to handle them specially
					if (strpos($class, '__') > -1)
					{
						$parts = explode('__', $class);
						$class = $parts[0];
						$subclass = $parts[1];
					}

					if (!empty($subclass))
					{
						if (!isset($objectified[ucfirst($class)]->{$subclass}))
						{
							$name = get_class($objectified[ucfirst($class)]);
							$objectified[ucfirst($class)]->{$subclass} = new $name;
						}

						$objectified[ucfirst($class)]->{$subclass}->{$field} = $val;
					}
					elseif (isset($objectified[ucfirst($class)]))
						$objectified[ucfirst($class)]->{$field} = $val;
				}
				elseif (isset($columns[$key]) && !isset($objectified[$columns[$key]]->{$key}))
					$objectified[ucfirst($columns[$key])]->{$key} = $val;
			}

			array_push($return, $objectified);
		}

		return new DBResult($return);
	}

	public static function Objectify()
	{

	}

	private function Map($query, $result)
	{

	}
}