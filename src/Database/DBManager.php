<?php 

namespace Qik\Database;

use Qik\Utility\Utility;

class DBManager
{
	private static $tablePrefix;
	private static $connections = [];

	public static function CreateConnection(string $host = null, string $user = null, string $password = null, string $db = null, string $type = 'mysql') 
	{
		try {
			$connection = new DBConnection($host, $user, $password, $db, $type);
			self::$connections[$db] = $connection;
		} catch (DBConnectException $ex) {
			throw $ex;
		}
	}

	public static function GetConnection($db = null)
	{
		if (!$db && count(self::$connections) == 1)
			return array_pop(self::$connections);

		return self::$connections[$db];
	}

	public static function SetDefaultTablePrefix($prefix = null)
	{
		return self::$tablePrefix = $prefix;
	}

	public static function GetDefaultTablePrefix()
	{
		return self::$tablePrefix;
	}
}