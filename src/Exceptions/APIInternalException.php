<?php 

namespace Qik\Exceptions;

use Qik\Exceptions\APIException;

class APIInternalException extends APIException
{
	public function __construct($internalMessage = null, $errorCode = null, $responseCode = 500, $externalMessage = null)
	{
		$this->internalMessage = $internalMessage = $internalMessage ?? $this->internalMessage ?? 'An unknown error occurred';
		$this->externalMessage = $externalMessage = $externalMessage ?? $this->externalMessage ?? 'An unknown error occurred';

		return parent::__construct($externalMessage, $errorCode, $responseCode, $internalMessage);
	}
}