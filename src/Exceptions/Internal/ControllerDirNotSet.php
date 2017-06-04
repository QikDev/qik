<?php 

namespace Qik\Exceptions\Internal;

use Qik\Exceptions\APIError;

class ControllerDirNotSet extends APIError {
	protected $responseCode = 500;
	protected $errorCode = 0;
	protected $internalMessage = 'Controller directory not set';
}