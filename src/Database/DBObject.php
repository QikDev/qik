<?php 

namespace Qik\Database;

use Qik\Core\APIObject;
use Qik\Utility\Utility;
use Qik\Database\DBManager;
use Qik\Exceptions\Internal\{DBObjectPrimaryKeyNotFound, DbObjectColumnNotFound, DBObjectInsertError};

class DBObject implements APIObject
{
	private static $columns = [];
	private static $connection;
	private $fields = [];

	protected $table;
	protected $primaryKeyColumn;
	protected $primaryKeyValue;
	protected $prefix;

	public function __construct($pk = null, $connection = null)
	{
		if (!empty($connection))
			self::$connection = $connection;

		if (empty($this->prefix))
			$this->prefix = DBManager::GetDefaultTablePrefix();
		
		if (empty($this->table))
			$this->DetermineTable();

		$this->primaryKeyValue = $pk;

		if (!empty($pk))
			$this->Get();

		//$this->{$this->primaryKeyColumn} = $pk;
	}

	public function DeterminePrimaryKey() : bool
	{
		//$this->Query('SELECT * FROM '.$this->table);
		$sql = 'SHOW KEYS FROM '.$this->table.' WHERE Key_name = \'primary\'';
		$columns = $this->Query($sql)->FetchAll();
		foreach ($columns as $col)
		{
			if (strtolower($col['Key_name']) == 'primary')
			{
				$this->primaryKeyColumn = $col['Column_name'];
				$this->{$this->primaryKeyColumn} = $this->primaryKeyValue;
				return true;
			}
		}

		throw new DBObjectPrimaryKeyNotFound('DB Primary key not found for '.$this->table.' :: '.$sql);
		
		return false;
	}

	public function DetermineTable()
	{
		$this->table = strtolower($this->prefix.Utility::GetBaseClassNameFromNamespace($this));
	}

	public function GetTable() : string
	{
		if (empty($this->table))
			$this->DetermineTable();
		
		return $this->table;
	}

	public function LoadColumns($refresh = false) : array
	{
		$sql = 'DESCRIBE '.$this->table;
		$columns = $this->Query($sql)->FetchAll(\PDO::FETCH_ASSOC);

		if (!isset(self::$columns[$this->table]))
			self::$columns[$this->table] = [];
		elseif (!$refresh)
			return self::$columns;

		foreach ($columns as $column)
			self::$columns[$this->table][$column['Field']] = $column;
			
		return self::$columns;
	}

	public function GetModel() : array
	{
		if (count(self::$columns) <= 0)
			$this->LoadColumns();

		Utility::Dump(self::$columns);
		exit;
	}

	public function GetPublicModel() : array
	{
		if (count(self::$columns) <= 0)
			$this->LoadColumns();

		Utility::Dump(self::$columns);
		exit;
	}

	public function GetColumns($table = null, $load = true)
	{
		if (empty($table))
			$table = $this->table;

		if (isset(self::$columns[$table]))
			return self::$columns[$table];

		if ($load)
		{
			$this->LoadColumns();
			return $this->GetColumns($table, false);
		}

		return array();
	}

	public function SetField($column = null, $value = null)
	{
		$this->LoadColumns();

		if (!isset(self::$columns[$this->table][$column]))
			throw new DbObjectColumnNotFound('Could not find column '.$column.' to set value '.$value.' in table '.$this->table);

		return $this->fields[$column] = $value;
	}

	private function RequireConnection()
	{
		if (empty(self::$connection))
			return self::$connection = DBManager::GetConnection();

		return false;
	}

	protected function Query($sql)
	{
		$this->RequireConnection();

		$statement = self::$connection->Query($sql);
		return $statement;
	}

	public function Export()
	{
		$this->RequireConnection();

		return self::$connection->Export('SELECT * FROM '.$this->table, ucwords(str_replace('_', ' ', $this->table)));
	}

	public function GetRecords()
	{

	}

	public function GetAll()
	{
		return $this->Query('SELECT * FROM '.$this->table)->FetchAll(\PDO::FETCH_ASSOC);
	}

	public function Insert()
	{
		$this->RequireConnection();

		$this->LoadColumns();

		$columns = array_keys($this->fields);

		$keys = [];
		foreach ($columns as $i=>$key)
			array_push($keys, ':'.$key);

		$values = [];
		foreach ($this->fields as $key=>$value)
			$values[':'.$key] = $value;

		$statement = self::$connection->Prepare('INSERT INTO '.$this->table.' ('.implode(',', $columns).') VALUES ('.implode(',', $keys).')');
		
		if (!$statement->Execute($values))
		{
			$errorCode = $statement->errorCode();
			$errorInfo = $statement->errorInfo();
			throw new DBObjectInsertError('DBObject insertion error '.$errorInfo[0].' '.$errorInfo[1].': '.$errorInfo[2]);
		}

		return true;
	}

	protected function Get()
	{
		$this->DeterminePrimaryKey();

		return $this->Query('SELECT * FROM '.$this->table.' WHERE '.$this->primaryKeyColumn.' = '.$this->{$this->primaryKeyColumn} = $this->primaryKeyValue)->FetchObject();
	}
}