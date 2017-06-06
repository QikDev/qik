<?php 

namespace Qik\Exceptions;

use Qik\Exceptions\APIException;

class APIInternalException extends APIException
{
	public function __construct($internalMessage = null, $errorCode = null, $responseCode = 500, $externalMessage = null)
	{
		$iMessage = (!empty($internalMessage) ? $this->internalMessage : 'An unknown error occurred');
		$eMessage = (!empty($externalMessage) ? $this->externalMessage : 'An unknown error occurred');

		return parent::__construct($eMessage, $errorCode, $responseCode, $iMessage);
	}
}