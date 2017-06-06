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
		$iMessage = (!empty($message) ? $message : (!empty($this->message) ? $this->message : 'An unknown exception occurred'));
		$iErrorCode = (!empty($errorCode) ? $errorCode : (!empty($this->errorCode) ? $this->errorCode : 0));
		$iResponseCode = (!empty($responseCode) ? $responseCode : (!empty($this->responseCode) ? $this->responseCode : 500));

		$this->internalMessage = $internalMessage;

		return parent::__construct($iMessage, $errorCode, $previous);
	}

	public function getResponseCode()
	{
		return $this->responseCode;
	}

	public function getInternalMessage()
	{
		return $this->internalMessage;
	}
}