<?php 

namespace Qik\Database;

//http://envms.github.io/fluentpdo/
use FluentPDO;

class DBQuery extends FluentPDO
{
	private static $_instance;

	public function __construct($pdo = null)
	{
		parent::__construct($pdo);
	}

	public static function Connect($pdo = null)
	{
		self::$_instance = new DBQuery($pdo);
	}

	public static function Get()
	{
		return self::$_instance;
	}

	public static function Build()
	{
		return self::Get();
	}

	public static function EnableDebug($callback = null)
	{
		self::Build()->debug = (is_callable($callback) ? $callback : true);
	}
}