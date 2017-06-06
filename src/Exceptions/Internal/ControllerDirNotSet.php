<?php 

namespace Qik\Exceptions\Internal;

use Qik\Exceptions\APIInternalException;

class ControllerDirNotSet extends APIInternalException {
	protected $internalMessage = 'Controller directory not set';
}