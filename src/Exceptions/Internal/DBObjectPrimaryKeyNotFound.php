<?php 

namespace Turnkey\Exceptions;

use Qik\Exceptions\APIInternalException;

class DbObjectPrimaryKeyNotFound extends APIInternalException
{
	function __construct($message) { return parent::__construct($message ?? 'DB primary key not found.'); }
}