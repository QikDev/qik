<?php 

namespace Qik\Exceptions;

use Qik\Utility;

class APIException extends \Exception
{
	protected $message;
	protected $errorCode;
	protected $responseCode;
	protected $internalMessage;

	public function __construct($message = null, $errorCode = null, $responseCode = null, $internalMessage = '', Throwable $previous = null) 
	{
		$message = $this->message = $message ?? $this->message ?? 'An unknown exception occurred';
		$errorCode = $this->errorCode = $errorCode ?? $this->errorCode ?? 0;
		$responseCode = $this->responseCode = $responseCode ?? $this->responseCode ?? 500;

		$this->internalMessage = $internalMessage;

		return parent::__construct($message, $errorCode, $previous);
	}

	public function getResponseCode()
	{
		return $this->responseCode;
	}
}