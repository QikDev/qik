<?php 

namespace Qik\Exceptions\Resource;

use Qik\Exceptions\APIException;

class MethodNotAllowed extends APIException {
	protected $responseCode = 405;
	protected $errorCode = 0;
	protected $message = 'Requested API method is not allowed';
}