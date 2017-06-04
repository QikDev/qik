<?php 

namespace Qik\Exceptions;

class APIError extends APIException
{
	public function __construct($externalMessage = null, $errorCode = null, $responseCode = 500, $internalMessage = null)
	{
		$this->internalMessage = $internalMessage = $internalMessage ?? $this->internalMessage ?? $externalMessage ?? $this->externalMessage ?? 'An unknown API error occurred';

		return parent::__construct($externalMessage, $errorCode, $responseCode, $internalMessage);
	}
}