<?php 

namespace Qik\Database;

use Qik\Utility\Utility;

//http://envms.github.io/fluentpdo/
use FluentPDO;

class DBQuery extends FluentPDO
{
	private static $_instance;
	private static $_query;

	public function __construct($pdo = null)
	{
		if (!empty($pdo))
			parent::__construct($pdo);
		
	}

	public static function Connect($pdo = null, $convertTypes = false)
	{
		self::$_instance = new DBQuery($pdo);
		self::$_instance->convertTypes = $convertTypes;
	}

	public static function Get()
	{
		return self::$_instance;
	}

	public static function Build() : DBQuery
	{
		return self::Get();
	}

	public static function Query($table, $primaryKey = null)
	{
	    $me = self::Get();
		$me->_query = parent::from($table, $primaryKey);

		return $me->_query;
	}

	public static function EnableDebug($callback = null)
	{
		self::Build()->debug = (is_callable($callback) ? $callback : true);
	}
}