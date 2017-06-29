<?php 

namespace Qik\Exceptions\Internal;

use Qik\Exceptions\APIInternalException;

class DbObjectColumnNotFound extends APIInternalException
{
	function __construct($message) { return parent::__construct($message ?? 'DB table column not found.'); }
}