<?php 

namespace Qik\Database;

use Qik\Utility\{Utility};
use Qik\Debug\{Debugger, Logger};

class DBConnection extends \PDO
{
	private $handler;

	public function __construct(string $host = null, string $user = null, string $password = null, string $db = null, string $type = 'mysql') 
	{
		try {
			parent::__construct($type.':host='.$host.';dbname='.$db, $user, $password);
		} catch (PDOException $ex) {
			throw $ex;
		}
	}

	public function Prepare($sql = null, $options = null) : \PDOStatement
	{
		return parent::Prepare($sql);
	}

	public function Execute(\PDOStatement $statement) : \PDOStatement
	{
		$r = $statement->Execute();

		return $statement;
	}

	function Query($sql = NULL, $cacheKey = null, $cacheLength = 300) : \PDOStatement
	{
		$statement = $this->Prepare($sql);
		$statement = $this->Execute($statement);
		return $statement;
	}

	function Export($sql = null, $filename = null)
	{	
		if (empty($filename))
			$filename = 'query_export_'.time().'.csv';
		else
		{
			$parts = explode('.', $filename);
			$parts = array_reverse($parts);
			if ($parts[0] != 'csv')
				$filename.='.csv';
		}

		$statement = $this->Query($sql);
    
        header("Cache-Control: private, no-cache, must-revalidate");
        header("Content-type: text/csv; charset=utf-8");
        header("Content-disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        $first = true;
        do
        {
            $r = $statement->Fetch(\PDO::FETCH_ASSOC);
            
            //some people have commas in their state names apparently, so we need to take them out and replace them with a space
            $data = '';
            $header = '';
            
            if (!empty($r))
            {
				if (!empty($exclusions))
				{
					foreach ($exclusions as $exclude)
						unset($r[$exclude]);
				}
				
				if (isset($r['Date']) && $makeDateReadable)
					$r['Date'] = gmdate('m/d/Y g:i A', $r['Date']);
					
                $data = '"'.implode(str_replace('"', '\"',str_replace('\\','\\\\',str_replace(array("\r\n","\n"),"<br />",$r))), '","').'"'."\n";
                
                if ($first)
                {
                
                    $header = implode(array_keys($r), ',')."\n";
                    echo $header.$data;
                    $first = false;
                }
                else
                    echo $data;
            }

        } while (!empty($r));
	}
}