<?php 

namespace Qik\Core;

class APIRequest
{
	public $url;
	public $type;
	public $return;
	public $errors = array();
	public $raw = null;
	public $timeout;
	public $returnTransfer;

	protected $data = array();
	protected $headers = array();
	protected $handle;
	
	protected $_contentType = 'application/x-www-form-urlencoded; charset=utf-8';
	protected $_contentLength = 0;

	private $_builtData = '';
	
	public function __construct($url = null, $type = 'POST', $contentType = 'application/x-www-form-urlencoded; charset=utf-8', $returnTransfer = true, $timeout = 1000)
	{
		$this->return = new xApiReturn();
		
		$this->url = $url;
		$this->type = $type;
		$this->_contentType = $contentType;
		$this->returnTransfer = $returnTransfer;
		$this->timeout = $timeout;

		$this->SetLocation();
	}

	public function SetLocation($url = null)
	{
		if ($url)
			$this->url = $url;

		if ($this->handle)
			@curl_close($this->handle);

		$this->handle = curl_init($this->url);

		$this->SetType($this->type);
		$this->SetOption(CURLOPT_RETURNTRANSFER, $this->returnTransfer);
		$this->SetOption(CURLOPT_CONNECTTIMEOUT, $this->timeout);
		$this->SetOption(CURLOPT_TIMEOUT, $this->timeout);
		//$this->SetOption(CURLOPT_VERBOSE, true);
		$this->SetOption(CURLOPT_HEADER, true);
		
		$this->AddHeader('Content-Type', $this->_contentType);
	}
	
	public function SetType($type = 'POST')
	{
		switch ($type)
		{
			case 'POST':
				$this->SetOption(CURLOPT_POST, 1);
				$this->SetOption(CURLOPT_CUSTOMREQUEST, 'POST');
				$this->type = 'POST';
				break;
			case 'DELETE':
				$this->SetOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
				$this->SetOption(CURLOPT_POST, 0);
				$this->type = 'DELETE';
				break;
			case 'PUT':
				$this->SetOption(CURLOPT_POST, 0);
				$this->SetOption(CURLOPT_CUSTOMREQUEST, 'PUT');
				$this->type = 'PUT';
				break;
			case 'GET':
				$this->SetOption(CURLOPT_POST, 0);
				$this->SetOption(CURLOPT_CUSTOMREQUEST, 'GET');
				$this->type = 'GET';
				break;
			default:
				break;
		}
		
		return true;
	}
	
	public function DisableSSL()
	{
		$this->SetOption(CURLOPT_SSL_VERIFYPEER, false);
		return $this->SetOption(CURLOPT_SSL_VERIFYHOST, false);
	}
	
	public function SetCertPath($path = null)
	{
		return $this->SetOption(CURLOPT_CAINFO, $path);
	}
	
	public function SetOption($option = null, $value = null)
	{
		if (empty($option) || empty($value) || !$this->handle)
			return false;
		
		return curl_setopt($this->handle, $option, $value);
	}

	public function Base64EncodeData()
	{
		switch ($this->type)
		{
			case 'POST':
			case 'PUT':
			case 'DELETE':
				if (stristr($this->_contentType, 'application/json') && is_array($this->data))
				{
					$this->_builtData = json_encode($this->data);
					return $this->SetOption(CURLOPT_POSTFIELDS, base64_encode($this->_builtData));
				}
				else
				{
					if (is_array($this->data))
						$this->_builtData = http_build_query($this->data);
					else
						$this->_builtData = $this->data;

					return $this->SetOption(CURLOPT_POSTFIELDS, base64_encode($this->_builtData));
				}
				break;
			case 'GET':
				$this->_builtData = $this->url.'?'.http_build_query($this->data);
				return $this->SetOption(CURLOPT_URL, base64_encode($this->_builtData));
				break;
			case 'PUT':
				break;
			default:
				break;
		}
	}
	
	public function AddData($key = null, $value = null)
	{
		if (empty($key) || !$this->handle)
			return false;
		
		if (is_array($value))
		{
			$valArray = array();
			
			foreach ($value as $k=>$val)
			{
				if (is_array($val) || is_object($val))
					$valArray[trim($k)] = $val;
				else
					$valArray[trim($k)] = trim($val);
			}
				
			$this->data[trim($key)] = $valArray;
		}
		elseif (!is_null($value))
		{
			if (is_bool($value))
				$this->data[trim($key)] = $value;
			else
			{
				$key = trim($key);
				$value = trim($value);
				
				$this->data[$key] = $value;
			}
		}
		elseif (!empty($key))
		{
			//xDeveloper::Log('Adding curl data '.$key);
			$this->data = $key;
		}
		
		switch ($this->type)
		{
			case 'POST':
			case 'PUT':
			case 'DELETE':
				if (stristr($this->_contentType, 'application/json') && is_array($this->data))
				{
					$this->_builtData = json_encode($this->data);
					return $this->SetOption(CURLOPT_POSTFIELDS, $this->_builtData);
				}
				else
				{
					if (is_array($this->data))
						$this->_builtData = http_build_query($this->data);
					else
						$this->_builtData = $this->data;

					return $this->SetOption(CURLOPT_POSTFIELDS, $this->_builtData);
				}
				break;
			case 'GET':
				$this->_builtData = $this->url.'?'.http_build_query($this->data);
				return $this->SetOption(CURLOPT_URL, $this->_builtData);
				break;
			case 'PUT':
				break;
			default:
				break;
		}		
	
		return true;
	}

	public function AddHeader($key = null, $value = null, $separate = true)
	{
		if (empty($key))
			return false;

		$key = $key;
		if (substr($key, -1, 1) != ':' && $separate)
			$key .= ': ';
		else
			$key .= ' ';

		$this->headers[$key] = $value;
	}

	public function GetBuiltData()
	{
		return $this->_builtData;	
	}
	
	public function GetBuiltURL()
	{
		if ($this->type != 'GET')
			return $this->url; 
		else
			return $this->url.'?'.$this->_builtData;
	}
	
	public function Decode($data = null)
	{
		//the preg_replace is replacing utf8 BOM invalid json characters
		return json_decode(xApi::AddSlashes(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $data)));
	}
	
	public function Send($followRedirect = true)
	{
		//if (empty($this->data) || count($this->data) < 1)
		//	$this->AddData('dummy', true);
		
		//$this->SetOption(CURLOPT_HTTPHEADER, array('Content-Type: '.$this->_contentType, 'Content-Length: '.$this->_contentLength));

		if (!$this->handle)
		{
			$this->SetError('Handle not established to '.$this->url);
			return false;
		}
		
		$this->_contentLength = strlen($this->_builtData);

		if ($this->type != 'GET')
			$this->AddHeader('Content-Length', $this->_contentLength); //content length must always be sent

		foreach ($this->headers as $key=>$val)
			$sendHeaders[] = $key.$val;

		$this->SetOption(CURLOPT_HTTPHEADER, $sendHeaders);

		$success = curl_exec($this->handle);
		
		if ($success)
		{
			$headerSize = curl_getinfo($this->handle, CURLINFO_HEADER_SIZE);
			$headers = substr($success, 0, $headerSize);
			$body = substr($success, $headerSize);

			$metaData = new stdClass();
			$metaData->headers = addcslashes($headers, xApi::$slashChars);
			$metaData->status = curl_getinfo($this->handle, CURLINFO_HTTP_CODE);
			$metaData->statusText = xUtility::GetHTTPStatusText($metaData->status);
			$metaData->contentType = curl_getinfo($this->handle, CURLINFO_CONTENT_TYPE);
			
			$this->SetRaw(xApi::AddSlashes(htmlentities($success, ENT_QUOTES, 'UTF-8')));
			
			if (stristr($metaData->contentType, 'application/json'))
			{
				$data = $this->Decode($body);
				
				if (isset($data->data))
					$this->return = new xApiReturn($data);
				else
					$this->return->data = $data;
			}
			else
				$this->return->data = xApi::AddSlashes($body);
				
			$this->return->metaData = $metaData;
			$this->return->body = $body;

			$headerArray = array();

			if ($metaData->headers)
			{
				$lines = explode('\r\n', $metaData->headers);
				array_shift($lines);

				foreach ($lines as $line)
				{
					if (empty($line))
						continue;

					$parts = explode(':', $line);

					if (count($parts) > 2)
					{
						$key = $parts[0];
						array_shift($parts);
						$value = implode(':', $parts);

						$headerArray[$key] = trim($value);
					}
					else
					{
						if (isset($parts[1]))
							$headerArray[$parts[0]] = trim($parts[1]);
					}
				}
			}

			$this->return->headers = $headerArray;
			
			if ($this->return === false || $this->return === null)
			{
				$this->SetError('JSON Decode Error #: '.json_last_error());
				$this->SetError('JSON Decode Error Message: '.xJson::GetLastErrorMsg());
				
				//$info = curl_getinfo($this->handle); 
				//$this->SetError('Request to '.$info['url'].' failed to send');
				//$this->SetError('cURL Error: '.@curl_errno());
				//$this->SetError('cURL Info: '.print_r($info, true));
				//$this->SetError('cURL Message: '.@curl_error());
			}
		}
		else
		{
			$info = curl_getinfo($this->handle);

			$this->SetError('Request to '.$info['url'].' failed to send');
			$this->SetError('cURL Error: '.@curl_errno($this->handle));
			$this->SetError($info);
			$this->SetError('cURL Message: '.@curl_error($this->handle));
		}
		
		return ($success ? true : false);
	}
	
	public function SetRaw($raw = null)
	{
		$this->raw = $raw;
		return true;
	}

	public function DisplayErrors()
	{
		foreach ($this->errors as $key=>$error)
		{
			$notice = new ErrorNotice('Error', $error);
			$notice->Send();
		}

		return true;
	}
	
	public function SetError($message = null)
	{
		if (empty($message))
			return false;
	
		array_push($this->errors, $message);
	}
	
	public function HasError()
	{
		if (count($this->errors) > 0)
			return true;
		else
			return false;
	}
	
	public function GetErrors()
	{
		return $this->errors;
	}
	
	public function IsSuccess()
	{
		if (isset($this->return) && isset($this->return->success) && $this->return->success == true)
			return true;
		else
			return false;
	}

	public function GetStatusCode()
	{
		return $this->GetReturnMetaData('status');
	}

	public function GetReturnHeader($key = null)
	{
		if (isset($this->return) && isset($this->return->headers) && isset($this->return->headers[$key]))
			return $this->return->headers[$key];
		elseif (isset($this->return) && isset($this->return->headers))
			return $this->return->headers;
		else
			return false;
	}
	
	public function GetReturnMetaData($key = null)
	{
		if (empty($key))
			return;
		
		if (isset($this->return->metaData->$key))
			return stripcslashes($this->return->metaData->$key);
		else
			return;
	}

	public function GetReturnData($key = null)
	{
		return $this->GetData($key);
	}
	
	public function GetData($key = null)
	{
		if (empty($key))
		{
			if (isset($this->return) && isset($this->return->data))
				return $this->return->data;
		}
		
		if (isset($this->return) && isset($this->return->data) && isset($this->return->data->{$key}))
			return $this->return->data->{$key};
		else
			return;
	}	
}