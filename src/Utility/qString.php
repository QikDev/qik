<?php 

namespace Qik\Utility;

class qString
{
	public static function CamelCaseToLowerDashed($string)
	{
		// Initialise the array to be returned
		$array = array();
 
		// Initialise a temporary string to hold the current array element before it's pushed onto the end of the array
		$segment = '';
		 
		// Loop through each character in the string
		foreach (str_split($string) as $char) 
		{
			// If the current character is uppercase
			if (ctype_upper($char)) 
			{
				// If the old segment is not empty (for when the original string starts with an uppercase character)
				if ($segment) 
				{
					// Push the old segment onto the array
					$array[] = $segment;
				}
								     
				// Set the character (either uppercase or lowercase) as the start of the new segment
				$segment = strtolower($char);
			} 
			else // If the character is lowercase or special 
			{
				// Add the character to the end of the current segment
				$segment .= $char;
			}
		}
		 
		// If the last segment exists (for when the original string is empty)
		if ($segment) {
			// Push it onto the array
			$array[] = $segment;
		}
		 
		// Return the resulting array
		return implode('-', $array);
	}

	public static function LowerDashedToCamelCase($str, $firstCharLowerCase = false)
	{
		// make sure we're all lower
		$str = strtolower($str);
		$parts = explode('-', $str);
		$new_str = '';
		$x = 0;
		
		foreach($parts as $p)
		{
			$x++;
			if ($firstCharLowerCase && $x == 1)
				$new_str .= $p;
			else
				$new_str .= ucfirst($p);
		}
		
		return $new_str;
	}
}