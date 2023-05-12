<?php 

namespace Qik\Exceptions;

use Qik\Utility;

class APIException extends \Exception
{
	protected $message;
	protected $errorCode;
	protected $responseCode;
	protected $tag;
	protected $internalMessage;

	public function __construct(string $message = null, $tag = 'global', int $errorCode = null, int $responseCode = null, string $internalMessage = null, Throwable $previous = null) 
	{
		$iMessage = (!empty($message) ? $message : (!empty($this->message) ? $this->message : 'An unknown exception occurred'));
		$iErrorCode = (!empty($errorCode) ? $errorCode : (!empty($this->errorCode) ? $this->errorCode : 0));
		
		$this->tag = (!empty($tag) ? $tag : (!empty($this->tag) ? $this->tag : 'global'));
		$this->responseCode = (!empty($responseCode) ? $responseCode : (!empty($this->responseCode) ? $this->responseCode : 400));

		$this->internalMessage = $internalMessage;

		return parent::__construct($iMessage, $iErrorCode, $previous);
	}

	public function getResponseCode()
	{
		return $this->responseCode;
	}

	public function getTag()
	{
		return $this->tag;
	}

	public function getInternalMessage()
	{
		return $this->internalMessage;
	}
}