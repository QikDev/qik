<?php 

namespace Qik\Core;

use Qik\Qik;
use Qik\Core\{APIConfig, APIController, APIResponse, APIRequest};
use Qik\Debug\{Debugger, Logger};
use Qik\Utility\{Utility, URI, qString};
use Qik\Exceptions\{Resource, Internal};

class APIServer
{
	private static $clientIp;
	private static $clientIpSource;
	private static $developers = array();

	private		$_vars, $version, $controller, $command, $method, $requestType;
	private 	$controllers = array();
	private 	$disableCache;
	private 	$postCacheCallback;


	protected 	$response;

	public function __construct()
	{
		$this->response = new APIResponse();
	}

	public function Configure()
	{
		APIController::Configure($this);
	}

	public function GetResponse()
	{
		return $this->response;
	}

	public function RegisterCache($cache)
	{
		
	}

	public function RegisterController($controller) 
	{
		$this->controllers[strtolower(Utility::GetBaseClassNameFromNamespace(get_class($controller)))] = $controller; //store them so we can use O(1) lookups
	}

	public function RegisterDeveloper($ip = null, $name = null)
	{
		return self::$developers[$ip] = new class ($ip, $name) {
			public $name;
			public $ip;

			public function __construct($ip, $name)  {
				$this->name = $name;
				$this->ip = $ip;
			}
		};
	}

	public static function GetEnv()
	{
		return APIConfig::ENV;
	}

	public static function IsLocal()
	{
		return self::GetEnv() === 'local';
	}

	public static function IsDevelopment()
	{
		return self::IsLocal() || self::GetEnv() === 'development';
	}

	public static function IsProduction()
	{
		return self::GetEnv() === 'production';
	}

	public static function IsClientDeveloper($ip = null) 
	{
		if (empty($ip))
			$ip = self::GetClientIP();

		return isset(self::$developers[$ip]) ? self::$developers[$ip] : false;
	}

	public function GetRequestHeaderData($key = null)
	{
		$key = strtoupper('http_'.str_replace('-','_', $key));
		
		if (isset($_SERVER[$key]))
			return $_SERVER[$key];
		else
			return '';
	}

	public function GetVariables()
	{
		$variables = array();

		if (count($this->_vars) > 0)
			$variables = array_merge($variables, $this->_vars);

		$contentType = $this->GetRequestHeaderData('Content-Type');
		if (stristr($contentType, 'application/json'))
		{
			if (!isset($this->_requestData))
			{
				$json = file_get_contents('php://input');
				$this->_requestData = json_decode($json);
			}
			$data = Utility::ConvertObjectToArray($this->_requestData);
			if (count($data) > 0)
				$variables = array_merge($variables, $data);
		}
		else
		{
			parse_str(file_get_contents("php://input"), $_PUTDELETE);
			if (count($_PUTDELETE) > 0)
				$variables = array_merge($variables, $_PUTDELETE);
		}
		
		if (count($_POST) > 0)
			$variables = array_merge($variables, $_POST);
		
		if (count($_GET) > 0)
			$variables = array_merge($variables, $_GET);
		
		return $variables;
	}

	public function GetVariable($index, $default = null, $decodeJSON = true)
	{
		if (is_numeric($index))
			return $this->_vars[$index] ?? $default;
		else
		{
			$contentType = $this->GetRequestHeaderData('Content-Type');

			if (stristr($contentType, 'application/json'))
			{
				if (!isset($this->_requestData))
				{
					$json = file_get_contents('php://input');
					$this->_requestData = json_decode($json);
				}

				return $this->_requestData->{$index} ?? $default;
			}
			else
			{
				if (isset($_POST[$index]))
					$var = $_POST[$index];
				elseif (isset($_GET[$index]))
					$var = $_GET[$index];
				else
				{
					parse_str(file_get_contents("php://input"), $_PUTDELETE);
					if (isset($_PUTDELETE[$index]))
						$var = $_PUTDELETE[$index];
				}

				if (empty($var))
					return $default;

				if ($decodeJSON && $obj = Utility::IsJSON($var)) //IsJSON() will return a decoded json string as an object or false
					return $obj;

				return $var;
			}
		}
	}

	public function GetCacheKey($url = null)
	{
		if (empty($url))
		{
			$path = URI::GetParts(array('scheme', 'host', 'path'));
			$variables = $this->GetVariables();
			ksort($variables);

			$key = $path.'-'.http_build_query($variables);
		}
		else
			$key = $url;

		return md5($key);
	}

	public function PostCache($callback) 
	{
		$this->postCacheCallback = $callback;
	}

	public function Serve()
	{
		try {
			if (APIServer::IsDevelopment())
				$this->disableCache = true;
			
			if (!$this->ParseURI())
				throw new Resource\Invalid();

			if (strtoupper($this->requestType) == 'GET')
			{
				$key = $this->GetCacheKey();
				//$this->SendCachedResponse();
			}

			if (is_callable($this->postCacheCallback))
				$this->postCacheCallback();

			if (!isset($this->controllers[strtolower($this->controller)]))
				throw new Resource\NotFound();
				
			$this->controller = $this->controllers[strtolower($this->controller)];

			if (method_exists($this->controller, 'Init'))
			{
				$this->controller->Init();
				Debugger::SetTimestamp(get_class($this->controller).'_init');
			}

			$method = ucfirst($this->command).$this->requestType;
			if (!method_exists($this->controller, $method))
				throw new Resource\MethodNotAllowed();
			
			$return = call_user_func_array(array($this->controller, $method), array());
			Debugger::SetTimestamp($method.'_call');

			$this->response->Send();
		} catch (\Throwable $thrown) {
			$this->response->SendError($thrown);
		}
	}

	public static function GetClientIP()
	{
		if (isset(self::$clientIp))
			return self::$clientIp;
		else
		{
			if (isset($_SERVER['HTTP_INCAP_CLIENT_IP']) && !empty($_SERVER['HTTP_INCAP_CLIENT_IP']))
			{
				// incapsula IP
				$ip = $_SERVER['HTTP_INCAP_CLIENT_IP'];
				$source = 'HTTP_INCAP_CLIENT_IP';
			}
			else if (isset($_SERVER["HTTP_X_CLUSTER_CLIENT_IP"]) && !empty($_SERVER["HTTP_X_CLUSTER_CLIENT_IP"]))
			{
				// if behind a load balancer
				$ip = $_SERVER["HTTP_X_CLUSTER_CLIENT_IP"];
				$source = "HTTP_X_CLUSTER_CLIENT_IP";
			}
			else if (!empty($_SERVER['HTTP_CLIENT_IP']))   
			{
				//check ip from share internet
				$ip = $_SERVER['HTTP_CLIENT_IP'];
				$source = 'HTTP_CLIENT_IP';
			}
			else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))  
			{
				//to check ip is pass from proxy
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
				$source = 'HTTP_X_FORWARDED_FOR';
			}
			else
			{
				$ip = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
				if (isset($_SERVER['REMOTE_ADDR']))
					$source = 'REMOTE_ADDR';
				else
					$source = 'none';
			}
			
			self::$clientIpSource = $source;
			self::$clientIp = $ip;
		}

		return $ip;
	}

	public function SendCachedResponse()
	{
		return true;
	}

	public function ParseURI()
	{
		$uri = URI::GetParts('path');
		$uri = trim($uri, '/');
		$parts = explode('/', $uri);
				
		if (count($parts) < 2)
			throw new Resource\Invalid();
	
		$this->version = $parts[0];
		
		if (!$this->controller = URI::GetControllerName($uri))
			throw new Resource\Invalid();

		$this->command = isset($parts[2]) && !is_numeric($parts[2]) && !stristr($parts[2], '?') && !stristr($parts[2], '&') ? $parts[2] : '';

		if (empty($this->command))
			$this->_vars = array_slice($parts, 2);
		else
			$this->_vars = array_slice($parts, 3);

		$this->command = qString::LowerDashedToCamelCase($this->command);

		$this->requestType = $_SERVER['REQUEST_METHOD'];
		
		return true;
	}
}