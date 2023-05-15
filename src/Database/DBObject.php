<?php 

namespace Qik\Database;

use Qik\Core\APIObject;
use Qik\Utility\Utility;
use Qik\Database\{DBManager, DBConnection, DBObjectIterator, DBQuery};
use Qik\Exceptions\Internal\{DBObjectPrimaryKeyNotFound, DbObjectColumnNotFound, DBObjectInsertError};

class DBObject implements APIObject, \IteratorAggregate
{
	private static $columns = [];
	private static $connection;
	protected $fields = [];
	protected $changed = [];

	protected $table;
	protected $primaryKeyColumn;
	protected $primaryKeyValue;
	protected $prefix;

	public function __construct($pk = null, DBConnection $connection = null)
	{
		if (!empty($connection))
			self::$connection = $connection;

		if (empty($this->prefix))
			$this->prefix = DBManager::GetDefaultTablePrefix();
		
		if (empty($this->table))
			$this->DetermineTable();

		if (!is_array($pk) && !is_object($pk)) {
			$this->primaryKeyValue = $pk;
			$this->Get($pk);
		}
		elseif (is_array($pk)) {
			$this->SetFields($pk);
		}
		elseif (is_object($pk))  {

		} elseif (!empty($pk)) {
			
		}
	}


    public function getIterator() : DBObjectIterator
    {
        // start with magic fields
        $fields = $this->fields;

        // now add manually added public scope fields
        $refObjs = (new \ReflectionObject($this))->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($refObjs as $obj)
        {
            $key = $obj->name;
            $fields[$key] = $this->$key;
        }

        return new DBObjectIterator($fields);
    }

	public function __isset($key)
	{
		return isset($this->fields[$key]);
	}

    public function __unset($key)
    {
        unset($this->fields[$key]);
    }

	public function __set($key, $val)
	{
		$this->fields[$key] = $val;
		$this->changed[$key] = $val;
	}

	public function __get($key)
	{
		return $this->fields[$key] ?? null;
	}
 
	public function SetFields($fields) : bool
	{
		if (!is_array($fields) || count($fields) <= 0)
			return false;

		foreach ($fields as $key=>$val)
		{
			if ($key == $this->primaryKeyColumn)
				$this->primaryKeyValue = $val;
			
			$this->fields[$key] = $val;
		}

		return true;
	}

	public function GetPrimaryKeyColumn()
	{
		$this->DeterminePrimaryKey();

		return $this->primaryKeyColumn;
	}

	public function DeterminePrimaryKey() : bool
	{
		if (!empty($this->primaryKeyColumn))
			return true;

		//$this->Query('SELECT * FROM '.$this->table);
        $sql = 'SHOW KEYS FROM ';
        if (strpos($this->table,'`') !== false)
            $sql .= $this->table;
        else
            $sql .= '`'.$this->table.'`';

        $sql .= ' WHERE Key_name = \'primary\'';
		$columns = $this->Query($sql)->FetchAll();
		foreach ($columns as $col)
		{
			if (strtolower($col['Key_name'] ?? '') == 'primary')
			{
				$this->primaryKeyColumn = $col['Column_name'];
				$this->{$this->primaryKeyColumn} ?? $this->primaryKeyValue;
				return true;
			}
		}

		throw new DBObjectPrimaryKeyNotFound('DB Primary key not found for '.$this->table.' :: '.$sql);
		
		return false;
	}

	public function DetermineTable()
	{
		$this->table = strtolower($this->prefix.Utility::GetBaseClassNameFromNamespace($this) ?? '');
	}

	public function GetTable() : string
	{
		if (empty($this->table))
			$this->DetermineTable();
		
		return $this->table;
	}

	public function LoadColumns($refresh = false) : array
	{
		$sql = 'SHOW FULL COLUMNS FROM '.$this->GetTable();
		$columns = $this->Query($sql)->FetchAll(\PDO::FETCH_ASSOC);

		if (!isset(self::$columns[$this->GetTable()]))
			self::$columns[$this->GetTable()] = [];
		elseif (!$refresh)
			return self::$columns[$this->GetTable()];

		foreach ($columns as $column)
		{
			$attributes = explode('||', $column['Comment']);
			$column['Attributes'] = array();

			foreach ($attributes as $attribute)
			{
				if (empty($attribute))
					continue;

				$parts = explode('=', $attribute);
				$column['Attributes'][$parts[0]] = $parts[1];
			}

			self::$columns[$this->GetTable()][$column['Field']] = $column;
		}
			
		return self::$columns[$this->GetTable()];
	}

	public function GetColumns(string $table = null, bool $load = true) : array
	{
		if (empty($table))
			$table = $this->GetTable();

		if (isset(self::$columns[$table]))
			return self::$columns[$table];

		if ($load)
		{
			$this->LoadColumns();
			return $this->GetColumns($table, false);
		}

		return array();
	}

	public function GetForeignKeys(string $table = null)
	{
		if (empty($table))
			$table = $this->table;

		$sql = 'use INFORMATION_SCHEMA;';
		$this->Query($sql);

		$sql = 'SELECT 
					TABLE_NAME,
					COLUMN_NAME,
					CONSTRAINT_NAME,
					REFERENCED_TABLE_NAME,
					REFERENCED_COLUMN_NAME 
				FROM 
					KEY_COLUMN_USAGE
				WHERE 
					TABLE_SCHEMA = "local" AND 
					TABLE_NAME = "'.$this->table.'" AND 
					REFERENCED_COLUMN_NAME IS NOT NULL';

		$keys = $this->Query($sql)->FetchAll(\PDO::FETCH_ASSOC);

		return $keys;
	}

	public function GetModel() : array
	{
		return $this->GetColumns();
	}

    public function GetPublicModel() : array
    {
        $columns = $this->GetColumns();
        $model = array();

        foreach ($columns as $key=>$column)
        {
            if (isset($column['Attributes']['accessibility']) && $column['Attributes']['accessibility'] == 'public')
                $model[$key] = $column;
        }

        // include vars defined on the object
        $refObjs = (new \ReflectionObject($this))->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($refObjs as $obj)
        {
            $key = $obj->name;
            $column = [];
            $model[$key] = $column;
        }

        return $model;
	}
	
	public function GetData() : array 
	{
		$model = $this->GetPublicModel();
		$obj = array();

		foreach ($this as $k=>$v)
		{
			if (isset($model[$k]))
				$obj[$k] = $v;
		}

		return $obj;
	}

	public function SetField(string $column = null, $value = null)
	{
		$this->LoadColumns();

		if (!isset(self::$columns[$this->table][$column]))
			throw new DbObjectColumnNotFound('Could not find column '.$column.' to set value '.$value.' in table '.$this->table);

		return $this->fields[$column] = $value;
	}

	protected function RequireConnection()
	{
		if (empty(self::$connection))
			return self::$connection = DBManager::GetConnection();

		return false;
	}

	protected function Query(string $sql) : \PDOStatement
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

	public function Select($select = '*')
	{
		return DBQuery::Build()->from($this->GetTable())->select($select);
	}

	public function GetAll() : array
	{
		return $this->Query('SELECT * FROM '.$this->table)->FetchAll(\PDO::FETCH_ASSOC);
	}

	public function IsFieldUnique(string $field, $value = null)
	{
		if (empty($value))
			$value = $this->{$field};
		
		$query = DBQuery::Build()->from($this->GetTable())->where($field.' = ?', $value);

		if ($this->{$this->GetPrimaryKeyColumn()})
			$query->where($this->GetPrimaryKeyColumn().' != ?', $this->{$this->GetPrimaryKeyColumn()});

		$result = $query->limit(1)->Fetch();
			
		return !$result;
	}

	public function Insert() : bool
	{
		$this->RequireConnection();

		$pk = DBQuery::Build()->insertInto($this->GetTable())->values($this->fields)->Execute();

		if (intval($pk) == $pk)
		    $pk = (int)$pk;

		$this->{$this->GetPrimaryKeyColumn()} = $pk;
		$this->primaryKeyValue = $pk;

		return $pk;
	}

	public function Update() : bool
	{
		$this->RequireConnection();

		$result = DBQuery::Build()->update($this->GetTable())->set($this->changed)->where($this->GetPrimaryKeyColumn(), $this->{$this->GetPrimaryKeyColumn()})->Execute();

		return $result;
	}

	public function Delete() : bool
	{
		$this->RequireConnection();

		if (empty($this->{$this->GetPrimaryKeyColumn()})) {
		    return false;
        }

		$result = DBQuery::Build()->deleteFrom($this->GetTable(), $this->{$this->GetPrimaryKeyColumn()})->Execute();

		return $result;
	}

	public function Get($pk)
	{
		$results = DBQuery::Build()->from($this->GetTable())->where($this->GetPrimaryKeyColumn().' = ?', $pk)->Fetch();

		if ($results)
			$this->SetFields($results);

		return $this;
	}

    public function ToSimpleObject()
    {
        $obj = new \stdClass();
        $model = $this->GetPublicModel();

        foreach ($model as $key => $col)
        {
            $obj->$key = $this->$key ?? null;
        }

        return $obj;
    }

    public function ToJson()
    {
        return json_encode($this->ToSimpleObject());
    }

    /**
     * @param array $values
     * @return bool
     */
    protected function InsertOrUpdate(array $values) : bool
    {
        $this->RequireConnection();

        $pk = DBQuery::Build()
            ->insertInto($this->GetTable())
            ->onDuplicateKeyUpdate($values)
            ->values($this->fields)
            ->Execute();

        $this->{$this->GetPrimaryKeyColumn()} = $pk;
        $this->primaryKeyValue = $pk;

        return $pk;
    }
}