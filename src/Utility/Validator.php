<?php

namespace Qik\Utility;

use Qik\Exceptions\APIException;

class Validator
{
	
	public static function ValidateCharacters($text = null, $illegalCharacters = null, $error = null, $errorTag = null)
	{
		if (empty($text))
			return;
			
		$defaultChars = ' *$@(){}[]|\;:\'",<>/?`';
		
		if (empty($illegalCharacters))
			$illegalCharacters = $defaultChars;
		else if (substr($illegalCharacters, 0, 3) == "ADD")
		{
			$chars = substr($illegalCharacters, 4);
			$illegalCharacters = $defaultChars . $chars;
		}
		
		for ($x = 0; $x < strlen($illegalCharacters); $x++)
		{
			if (mb_strpos($text, $illegalCharacters[$x], 0, 'UTF-8') !== false)
			{
				if (mb_strpos($illegalCharacters, " ", 0, 'UTF-8') !== false)
					$msg = "May not contain a space or any of the following characters: ";
				else
					$msg = "May not contain any of the following characters: ";
				
				$characterString = str_split($illegalCharacters);
				$msg .= implode(' ', $characterString);
				
				throw new APIException($error ?? $msg);
			}
		}
	}
	
	public static function ValidateInt($input = null, $error = null, $errorTag = null)
	{
       	if (is_null($input) || !preg_match('/^(-?)[0-9]+$/', $input))
            throw new APIException($error ?? "Input must be a whole number", $errorTag);
	}
	
	public static function ValidateEmail($email = null, $checkDomain = true, $error = null, $errorTag = null)
	{
		if (empty($email) || !preg_match('/'
			. '^'
			. '[-!#$%&\'*+\/0-9=?A-Z^_a-z{|}~]'
			. '(\\.?[-!#$%&\'*+\/0-9=?A-Z^_a-z{|}~])*'
			. '@'
			. '[a-zA-Z0-9](-?[a-zA-Z0-9.])*'
			. '(\\.[a-zA-Z](-?[a-zA-Z0-9])*)+'
			. '$'
			. '/'
			, $email
		))
			throw new APIException($error ?? "Email address is invalid.", $errorTag);
		
		list( $local, $domain ) = preg_split( "/@/", $email, 2 );
		if ( strlen($local) > 64 || strlen($domain) > 255 ) 
			throw new APIException($error ?? "Email address is invalid.", $errorTag);

		if ($checkDomain)
			Validator::ValidateDomain($domain, 'MX', $error, $errorTag);
	}
	
	public static function ValidateDomain($domain = null, $record = 'MX', $error = null, $errorTag = null)
	{
		if (empty($domain) || empty($record))
			return;
			
		if(function_exists('checkdnsrr'))
		{
			if (!checkdnsrr($domain, $record))
				throw new APIException($error ?? "Email domain appears to be invalid", $errorTag);
		}
	}
	
	public static function ValidateMatch($val1 = null, $val2 = null, $error = null, $errorTag = null)
	{
		$match = true;
		if ($strict)
		{
			if ($val1 !== $val2)
				$match = false;
		}
		else
		{
			if ($val != $val2)
				$match = false;
		}
		
		if (!$match)
			throw new APIException($error ?? "Values must match", $errorTag);
	}
	
	public static function ValidateNotEmpty($val = null, $error = null, $errorTag = null)
	{
		if (empty($val))
			throw new APIException($error ?? "Value cannot be empty", $errorTag);
	}
	
	public static function ValidateDate($date = null, $delimiter = null, $format = 'auto', $error = null, $errorTag = null)
	{
		if (empty($date))
			throw new APIException($error ?? "No date given", $errorTag);
		
		$parts = array();
		// parse the date
		if (empty($delimiter))
			$delimiter = Date::FindDelimiter($date);

		$dateParts = Date::Parse($date, $delimiter, $format);

		
		if (empty($dateParts['year']))
			throw new APIException($error ?? "No year provided", $errorTag);
		if (empty($dateParts['month']))
			throw new APIException($error ?? "No month provided", $errorTag);
		if (empty($dateParts['day']))
			throw new APIException($error ?? "No day provided", $errorTag);
		
		if (!is_numeric($dateParts['year']))
			throw new APIException($error ?? 'You entered an invalid year', $errorTag);
		if (!is_numeric($dateParts['month']) || $dateParts['month'] < 1 || $dateParts['month'] > 12)
			throw new APIException($error ?? 'You entered an invalid month', $errorTag);
		if (!is_numeric($dateParts['day']) || $dateParts['day'] < 1)
			throw new APIException($error ?? 'You entered an invalid day', $errorTag);

		switch ((int)$dateParts['month'])
		{
			case 4:
			case 6:
			case 9:
			case 11:
				if ((int)$dateParts['day'] > 30)
					throw new APIException($error ?? "The month you selected only has 30 days", $errorTag);
				break;
				
			case 1:
			case 3:
			case 5:
			case 7:
			case 8:
			case 10:
			case 12:
				if ((int)$dateParts['day'] > 31)
					throw new APIException($error ?? "The month you selected only has 31 days", $errorTag);
				break;				
			case 2:
				// is it a leap year?
				$leap = checkdate(2, 29, $dateParts['year']);
				if ($leap && $dateParts['day'] > 29)
					throw new APIException($error ?? "February only has 29 days (in the year provided)", $errorTag);
				else if (!$leap && $dateParts['day'] > 28)
					throw new APIException($error ?? "February only has 28 days (in the year provided)", $errorTag);
				break;					
			default:
				throw new APIException($error ?? 'You entered an invalid valid month');
				break;
		}
		
		// and one final check
		if (!checkdate($dateParts['month'], $dateParts['day'], $dateParts['year']))
			throw new APIException($error ?? 'You entered an invalid date', $errorTag);
			
	}
	
	public static function ValidateMaxLength($text = null, $maxlength = null, $error = null, $errorTag = null)
	{
		if (empty($text) || empty($maxlength))
			return;
		
		if (strlen($text) > $maxlength)
			throw new APIException($error ?? "The value was greater than the allowed length of $maxlength characters", $errorTag);
		else
			return;
	}

	public static function ValidateLength($text = null, $length = null, $error = null, $errorTag = null)
	{
		if (empty($text) || empty($length))
			return;
		
		if (strlen($text) != $length)
		{
			throw new APIException($error ?? "The value must be $length characters long", $errorTag);
		}
		else
			return;
	}
	
	public static function ValidateRegexMatch($text = null, $pattern = null, $error = null, $errorTag = null)
	{
		if (empty($text) || empty($pattern))
			return false;
			
		$match = preg_match($pattern, $text);

		if ($match == 0)
			throw new APIException($error ?? 'The given text of "'.$text.'" did not match the pattern of "'.$pattern.'"', $errorTag);
	}
}
?>