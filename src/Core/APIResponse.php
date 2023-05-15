<?php 

namespace Qik\Core;

use Qik\Qik;
use Qik\Core\APIServer;
use Qik\Exceptions\{APIException, APIInternalException};
use Qik\Utility\{qArray,Utility};
use Qik\Debug\{Debugger, Logger};

class APIResponse
{
	public $data = array();
	public $headers = array();
	public $errors = array();
	public $success = true;
	public $metadata = null;
	public $body = null;
	public $cacheLength = 10;

	public function __construct()
	{
	}

	public function SetError($thrown = null)
	{
		if (!is_object($thrown))
			throw new APIInternalException('$thrown passed to SetError is not an object');

		if (get_class($thrown) == 'Error') //these should be internal-only dev errors, send a bullshit "unknown error" response out
		{	
			$message = 'A critical error occurred with the request';
			$responseCode = 500;
		}
		
		if (method_exists($thrown, 'getResponseCode'))
			$responseCode = $thrown->getResponseCode();

		if (method_exists($thrown, 'getExternalMessage'))
			$message = $thrown->getExternalMessage();
		elseif (empty($message))
			$message = $thrown->getMessage();

		$tag = 'global';
		if (method_exists($thrown, 'getTag'))
			$tag = $thrown->getTag();

		$this->AddHeader('X-PHP-Response-Code', $responseCode ?? 400, $responseCode ?? 400);

		$this->success = false;
		$error = array(
					'code' => $thrown->getCode(),
					'message' => $message,
					'tag' => $tag
				);

		if (APIServer::IsClientDeveloper())
		{
			$error['trace'] = $thrown->getTrace();
			$error['internalMessage'] = method_exists($thrown, 'getInternalMessage') ? $thrown->getInternalMessage() : $thrown->getMessage();
		}

		if (!isset($this->errors[$error['tag'] ?? $error['code']]))
			$this->errors[$error['tag'] ?? $error['code']] = array();

		$this->errors[$error['tag'] ?? $error['code']][] = $error;
	}

	public function DisableCache()
	{
		return $this->SetCacheLength(0);
	}

	public function SetCacheLength($duration = null)
	{
		$this->cacheLength = $duration;

		return true;
	}

	public function GetErrors()
	{
		return $this->errors;
	}
	
	public function HasError()
	{
		if (count($this->errors) > 0)
			return true;
		else
			return false;
	}

	public function FilterModel($object, $stuff = false)
	{
		$data = [];
		if (is_object($object) && method_exists($object, 'GetPublicModel'))
		{
			$model = $object->GetPublicModel();
			foreach ($object as $a=>$piece)
			{
				if (is_object($piece))
					$data[$a] = $this->FilterModel($piece, true);
				elseif (isset($model[$a]))
					$data[$a] = $piece;
			}
		}
		else
			$data = $object;

		return $data;
	}

	public function addMetadata($key, $value)
	{
		$this->metadata[$key] = $value;
	}

    // TODO: probably refactor this to support full recursion
    public function AddData($key = null, $value = null, $convertSingleArrayToObject = false)
    {
        if ((is_object($value) && strtolower(Utility::GetBaseClassNameFromNamespace($value) ?? '') == 'dbresult') || (is_object($key) && strtolower(Utility::GetBaseClassNameFromNamespace($key) ?? '') == 'dbresult'))
        {
            if (is_object($key))
                $objects = $key->GetObjects();
            else
                $objects = $value->GetObjects();

            $add = [];
            foreach ($objects as $i=>$row)
            {
                $new = [];
                foreach ($row as $k=>$val)
                {
                    $data = $this->FilterModel($val);
                    $new[$k] = $data;
                }

                if (is_object($value) && !empty($key))
                    $add[$i] = $new;
                else
                    $this->AddData($i, $new);
            }

            if (is_object($value) && !empty($key)) {

                if (count($add) == 1 && $convertSingleArrayToObject)
                    $this->AddData($key, $add[0]);
                else
                    $this->AddData($key, $add);
            }

        }
        elseif (is_object($key))
        {
            if (strtolower(get_class($key) ?? '') !== 'stdclass') {
				if (!method_exists($key, 'GetData')) {
					throw new APIInternalException('Trying to add '.get_class($key).' to API response without defining proper model definition.');
				}
				
				$data = $key->GetData();
			} else {
				$data = $key;
			}

			$class = strtolower(Utility::GetBaseClassNameFromNamespace($key) ?? '');

            return $this->AddData($class, $data);
        }
        elseif (is_array($value))
        {
            $valArray = array();

            // is it an associative array?
            $isAssoc = qArray::IsAssociative($value);

            foreach ($value as $k=>$val)
            {
                if (is_object($val) && get_class($val) == 'stdClass') {
                    if ($isAssoc)
                        $valArray[trim($k ?? '')] = $val;
                    else
                        $valArray[] = $val;
                }
                else if (is_object($val)) {
					if (strtolower(get_class($val) ?? '') !== 'stdclass') {
						if (!method_exists($val, 'GetData')) {
							throw new APIInternalException('Trying to add '.get_class($val).' to API response without defining proper model definition.');
						}
						
						$data = $val->GetData();
					} else {
						$data = $val;
					}
		
					$class = strtolower(Utility::GetBaseClassNameFromNamespace($val) ?? '');

                    $valArray[trim($k ?? '')] = $data;
				}
                elseif (is_array($val) || !is_string($val))
                    $valArray[trim($k ?? '')] = $val;
                else
                    $valArray[trim($k ?? '')] = trim($val ?? '');
            }

            $this->data[trim($key ?? '')] = $valArray;
        }
        elseif (!is_null($value))
        {
            if (is_bool($value) || !is_string($value))
                return $this->data[trim($key ?? '')] = $value;
            else
                return $this->data[trim($key ?? '')] = trim($value ?? '');
        }
        elseif (!is_array($key) && is_null($value) && !is_object($key))
        {
            return $this->data[trim($key ?? '')] = $value;
        }
        elseif (!empty($key))
        {
            //xDeveloper::Log('Adding curl data '.$key);
            return $this->data = $key;
        }
    }

	public function AddHeader($key = null, $value = null, $code = null)
	{
		if (empty($key) || is_null($value))
			return false;

		$key = trim($key ?? '');
		if (substr($key, -1, 1) != ':')
			$key .= ': ';
		else
			$key .= ' ';

		$this->headers[$key] = array('value'=>(!is_object($value) && !is_array($value) ? trim($value ?? '') : $value), 'code'=>$code);
	}

	public function SendUnauthorized()
	{
		throw new APIException('Unauthorized Request', null, 0, 401);
	}

	public function SendError(\Throwable $thrown)
	{
		$this->SetError($thrown);
		return $this->Send();
	}

	public function SetCachePrefix($prefix = 'auth')
	{

	}

	public function GetDebugInformation()
	{
		if (!APIServer::IsClientDeveloper() && !APIServer::IsDevelopment())
			return;

		$timings = Debugger::GetTimestamps();

		return array(
			'timings' => $timings/*,
			'cache' => array(
				'status' => $_SESSION['_overrideQueryCache'] ? 'disabled' : 'enabled',
				'hits' => $_SESSION['_cacheHits'],
				'misses' => $_SESSION['_cacheMisses'],
				'uncached' => isset($_SESSION['_cacheFails']) ? $_SESSION['_cacheFails'] : 0
			)*/
		);
	}
	
	public function Send($dataOnly = false, $stripChars = true, $escapeChars = true, $sendContentLength = true, $cacheLength = null, $isCachedResponse = false)
	{
		//$this->AddHeader('Access-Control-Allow-Origin', '*');
		if (is_null($cacheLength))
			$cacheLength = $this->cacheLength;

		$disableCache = false;
			
		//if (APIServer::IsClientDeveloper() || APIServer::IsDevelopment())
		//	$disableCache = xGet::Get('_disableCache', false);
		
		//if ($disableCache)
		//	$cacheLength = 0;

		$this->AddHeader('Content-Type', 'application/json; charset=utf-8');
		
		if ($isCachedResponse)
			$this->AddHeader('X-Cached-Response', true);
		else
			$this->AddHeader('X-Cached-Response', false);

		//exit;

		foreach($this->headers as $key=>$val)
			header($key.$val['value'], true, $val['code'] ?? 0);

		Debugger::SetTimestamp('return_send'); //need to do this here so it gets encoded in the response

		//if (X::IsDevelopment())
		//	$cacheLength = 0;

		if (!$dataOnly)
		{
			$data['success'] = $this->success;
			$data['errors'] = $this->errors;

			if (!empty($this->metadata))
				foreach($this->metadata as $key => $value) {
					if (!isset($data[$key]))
						$data[$key] = $value;
				}

			$data['data'] = $this->data;

			if (APIServer::IsClientDeveloper() || APIServer::IsDevelopment())
				$data['debug'] = $this->GetDebugInformation();
		}
		else
			$data = $this->data;
		
		//OVERRIDE, ALWAYS SEND CONTENT LENGTH NOW
		//$sendContentLength = true;
		
		//ob_clean(); //DO NOT REMOVE THIS.  THE RESPONSE MAY/WILL BE CUT OFF BECAUSE THERE ARE EXTRA BITS IN THE BUFFER AND CONTENT-LENGTH WILL BE SHORT

		$encoded = json_encode($data);//xJson::Encode($data, false, $escapeChars, $stripChars); //DO NOT USE json_encode

		//echo 'cache length : '.$cacheLength.'<br />';
		if ($cacheLength && $cacheLength > 0 && strtoupper($_SERVER['REQUEST_METHOD'] ?? '') == 'GET')
		{
			//$key = xApi::GetCacheKey();
			//xDeveloper::Log('Setting cache for '.$key.' : '.$encoded, 'api:cache');
			//xCache::Set($key, $encoded, $cacheLength);
			//xUtility::Dump($variables);
			//exit;
		}
				
		
		$strlen = strlen($encoded ?? '');
		$mbStrlen = mb_strlen($encoded ?? '', 'UTF-8');
		$this->AddHeader('Content-Length', ($strlen > $mbStrlen ? $strlen : $mbStrlen));
		
		echo trim($encoded ?? '');
		exit;
	}	
}