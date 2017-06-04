<?php 

namespace Qik\Exceptions\Resource;

use Qik\Exceptions\APIException;

class Invalid extends APIException {
	protected $responseCode = 404;
	protected $errorCode = 0;
	protected $message = 'Requested API Resource is invalid';
}