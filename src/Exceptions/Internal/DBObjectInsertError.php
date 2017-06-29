<?php 

namespace Qik\Exceptions;

use Qik\Exceptions\APIInternalException;

class DbObjectInsertError extends APIInternalException
{
	function __construct($message) { return parent::__construct($message ?? 'DB table insertion failed.'); }
}