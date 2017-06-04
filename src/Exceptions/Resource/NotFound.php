<?php 

namespace Qik\Exceptions\Resource;

use Qik\Exceptions\APIException;

class NotFound extends APIException {
	protected $responseCode = 404;
	protected $errorCode = 0;
	protected $message = 'API Resource not found';
}