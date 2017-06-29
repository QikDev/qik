<?php 

namespace Qik\Exceptions\Internal;

use Qik\Exceptions\APIInternalException;

class DbManagerMissingConnection extends APIInternalException
{
	function __construct($message) { return parent::__construct($message ?? 'DB connection missing.'); }
}