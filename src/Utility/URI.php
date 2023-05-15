<?php 

namespace Qik\Utility;

class URI
{
	public static function GetRequest()
	{	
		$uri = $_SERVER['REQUEST_URI'];	
		$base = str_replace('?'.$_SERVER['QUERY_STRING'], '', $uri);
		$query = http_build_query($_GET);
		$uri = $base.(strlen($query ?? '') > 0 ? '?'.$query : '');
		
		return $uri;
	}

	//array('scheme', 'host', 'path')
	public static function GetParts($parts = array())
	{
		if (empty($parts))
			return self::GetRequest();
			
		if (!is_array($parts))
			$parts = array($parts);
		
		$uri = self::GetFull();
		$uriParts = parse_url($uri);
		
		$reconstructedURI = '';
		foreach ($parts as $each=>$part)
		{
			if (isset($uriParts[$part]))
				$reconstructedURI .= ($part == 'scheme' ? $uriParts[$part].'://' : $uriParts[$part]);
		}
				
		return $reconstructedURI;
	}
	
	public static function GetFull()
	{
		$s = (empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on")) ? "s" : "";
		$sp = (isset($_SERVER['SERVER_PROTOCOL']) ? strtolower($_SERVER["SERVER_PROTOCOL"] ?? '') : '');
		$protocol = substr($sp, 0, strpos($sp, "/")) . $s;
		$port = isset($_SERVER['SERVER_PORT']) && ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".(isset($_SERVER["SERVER_PORT"]) ? $_SERVER['SERVER_PORT'] : ''));
		$host = (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST']))? $_SERVER['HTTP_HOST']:$_SERVER['SERVER_NAME'];
		
		return $protocol . "://" . $host . $port . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
	}

	public static function GetControllerName($uri = null) 
	{
		$uri = URI::GetParts('path');
		$uri = trim($uri ?? '', '/');
		$parts = explode('/', $uri ?? '');

		$controllerParts = explode('\\', $parts[1] ?? '');
		if (count($controllerParts) <= 0)
			return false;

		return ucfirst($controllerParts[0]).'Controller';
	}
}