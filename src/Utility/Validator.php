<?php

namespace Qik\Utility;

use Qik\Exceptions\APIException;

class Validator
{
	
	public static function ValidateCharacters($text = null, $illegalCharacters = null)
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
				
				throw new APIException($msg);
			}
		}
	}
	
	public static function ValidateInt($input = null)
	{
       	if (is_null($input) || !preg_match('/^(-?)[0-9]+$/', $input))
            throw new APIException("Input must be a whole number");
	}
	
	public static function ValidateEmail($email = null, $checkDomain = true)
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
			throw new APIException("Email address is invalid.");
		
		list( $local, $domain ) = preg_split( "/@/", $email, 2 );
		if ( strlen($local) > 64 || strlen($domain) > 255 ) throw new Exception("Email address is invalid.");

		if ($checkDomain)
			Validator::ValidateDomain($domain);
	}
	
	public static function ValidateDomain($domain = null, $record = 'MX')
	{
		if (empty($domain) || empty($record))
			return;
			
		if(function_exists('checkdnsrr'))
		{
			if (!checkdnsrr($domain, $record))
				throw new APIException("Email domain appears to be invalid");
		}
	}
	
	public static function ValidateMatch($val1 = null, $val2 = null, $strict = true)
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
			throw new APIException("Values must match");
	}
	
	public static function ValidateNotEmpty($val = null)
	{
		if (empty($val))
			throw new APIException("Value cannot be empty");
	}
	
	public static function ValidateDate($date = null, $delimiter = null, $format = 'auto')
	{
		if (empty($date))
			throw new APIException("No date given");
		
		$parts = array();
		// parse the date
		if (empty($delimiter))
			$delimiter = Date::FindDelimiter($date);

		$dateParts = Date::Parse($date, $delimiter, $format);

		
		if (empty($dateParts['year']))
			throw new APIException("No year provided");
		if (empty($dateParts['month']))
			throw new APIException("No month provided");
		if (empty($dateParts['day']))
			throw new APIException("No day provided");
		
		if (!is_numeric($dateParts['year']))
			throw new APIException('You entered an invalid year');
		if (!is_numeric($dateParts['month']) || $dateParts['month'] < 1 || $dateParts['month'] > 12)
			throw new APIException('You entered an invalid month');
		if (!is_numeric($dateParts['day']) || $dateParts['day'] < 1)
			throw new APIException('You enered an invalid day');

		switch ((int)$dateParts['month'])
		{
			case 4:
			case 6:
			case 9:
			case 11:
				if ((int)$dateParts['day'] > 30)
					throw new APIException("The month you selected only has 30 days");
				break;
				
			case 1:
			case 3:
			case 5:
			case 7:
			case 8:
			case 10:
			case 12:
				if ((int)$dateParts['day'] > 31)
					throw new APIException("The month you selected only has 31 days");
				break;				
			case 2:
				// is it a leap year?
				$leap = checkdate(2, 29, $dateParts['year']);
				if ($leap && $dateParts['day'] > 29)
					throw new APIException("February only has 29 days (in the year provided)");
				else if (!$leap && $dateParts['day'] > 28)
					throw new APIException("February only has 28 days (in the year provided)");
				break;					
			default:
				throw new APIException('You entered an invalid valid month');
				break;
		}
		
		// and one final check
		if (!checkdate($dateParts['month'], $dateParts['day'], $dateParts['year']))
			throw new APIException('You entered an invalid date');
			
	}
	
	public static function ValidateMaxLength($text = null, $maxlength = null)
	{
		if (empty($text) || empty($maxlength))
			return;
		
		if (strlen($text) > $maxlength)
			throw new APIException("The value was greater than the allowed length of $maxlength characters");
		else
			return;
	}

	public static function ValidateLength($text = null, $length = null)
	{
		if (empty($text) || empty($length))
			return;
		
		if (strlen($text) != $length)
		{
			throw new APIException("The value must be $length characters long");
		}
		else
			return;
	}
	
	public static function ValidateRegexMatch($text = null, $pattern = null)
	{
		if (empty($text) || empty($pattern))
			return false;
			
		$match = preg_match($pattern, $text);
		if ($match == 0)
			throw new APIException('The given text of "'.$text.'" did not match the pattern of "'.$pattern.'"');
	}
	
	
}
?>