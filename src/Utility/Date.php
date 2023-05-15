<?php

namespace Qik\Utility;

class Date
{
	public static function GetMonthFromInt($month = null, $format = "F")
	{
		if (empty($month) || $month < 1 || $month > 12)
			return false;
		
		return date($format, mktime(0, 0, 0, $month, 1, date("Y")));
	}
	
	public static function GetTimeSpan($span = null, $as = 'auto', $short = false)
	{
		if (is_null($span))
			return false;

		if (empty($as))
			$as = 'auto';
		
		if ($span < 1 && !$short)
			return "moments";
		
		$second = 1;
		$minute = $second * 60;
		$hour = $minute * 60;
		$day = $hour * 24;
		$week = $day * 7;
		$month = $day * 30;
		$year = $day * 365;
		
		if ($as == 'auto')
		{
			// we have to find the best "as"
			if ($span >= $year)
			{
				$as = 'year';
			}
			elseif ($span >= $month)
			{
				$as = 'month';
			}
			elseif ($span >= $week)
			{
				$as = 'week';
			}
			elseif ($span >= $day)
			{
				$as = 'day';
			}
			elseif ($span >= $hour)
			{
				$as = 'hour';
			}
			elseif ($span >= $minute)
			{
				$as = 'minute';
			}
			else
			{
				$as = 'second';
			}
		}
		
		
		// convert the stamp to the as
		$val = round( $span / $$as );

		if ($val <= 0)
			$val  = 0;
		
		if ($short)
			$as = substr($as, 0, 1);

		return $val.(!$short ? ' '.xString::Pluralize($as, $val) : $as);
		
	}
	
	public static function GetTimeAgo($stamp = null, $as = 'auto', $short = false)
	{
		// in order to get the time ago, we need to take the current time minus the stamp to acquire a timespan
		$span = time() - $stamp;

	  
		return Date::GetTimeSpan($span, $as, $short);
	} 
	
	public static function GetAge($birthdate = null, $delimiter = null, $format = 'auto', $referenceStamp = null)
	{
		if (empty($birthdate))
			return false;
			
		if (empty($delimiter))
		{
			if (stristr($birthdate, '-'))
				$delimiter = '-';
			elseif (stristr($birthdate, '/'))
				$delimiter = '/';
		}
	
		// parse it
		$parsed = Date::Parse($birthdate, $delimiter, $format);
	
		if ($parsed['month'] == 2 && $parsed['day'] == 29)
			$birthdate = $parsed['year'].'-03-01'; // leap day birth, make it march 1
		
		// convert to timestamp
		$stamp = @mktime(0, 0, 0, $parsed['month'], $parsed['day'], $parsed['year']);
			
		// calcualte the difference
		if (!empty($referenceStamp))
			$now = $referenceStamp;
		else	
			$now = time();
		$diff = $now - $stamp;
		
		$minute = 60;
		$hour = $minute * 60;
		$day = $hour * 24;
		$week = $day * 7;
		$month = $day * 30;
		$year = $day * 365.25;
		
		// get the years
		$age = floor( $diff / $year );

		return $age;		
	}
	
	public static function Parse($date = null, $delimiter = null, $format = 'auto')
	{
		$output = array();
		$output['month'] = null;
		$output['day'] = null;
		$output['year'] = null;
		
		if (empty($date))
			return $output;
			
		if (empty($delimiter))
			$delimiter = Date::FindDelimiter($date);
			
		if (empty($format) || $format == 'auto')
			$format = Date::FindFormat($date, $delimiter);

		$parts = explode($delimiter, $date);
		
		$dateParts = array();
		$mask = explode('-', $format);
		foreach ($mask AS $key=>$part)
			$dateParts[$part] = $parts[$key];
		
		$output['month'] = (isset($dateParts['mm']) ? $dateParts['mm'] : null);
		$output['day'] = (isset($dateParts['dd']) ? $dateParts['dd'] : null);
		$output['year'] = (isset($dateParts['yyyy']) ? $dateParts['yyyy'] : null);
		
		return $output;
		
	}
	
	public static function FindDelimiter($date = null)
	{
		if (empty($date))
			return false;
			
		if (strpos($date,'-') !== false)
			$delimiter = '-';
		else if (strpos($date,'/') !== false)
			$delimiter = '/';
		else if (strpos($date, '.') !== false)
			$delimiter = '.';	
		
		return $delimiter;
	}
	
	public static function FindFormat($date = null, $delimiter = null)
	{
		if (empty($date))
			return false;
			
		if (empty($delimiter))
			$delimiter = Date::FindDelimiter($date);
			
		$parts = explode($delimiter, $date);

		$yearKey = null;
		$monthKey = null;
		$dayKey = null;
		
		if (strlen($parts[0] ?? '') == 4 || (int)$parts[0] > 31)
			$yearKey = 0;
		else if (strlen($parts[1] ?? '') == 4 || (int)$parts[1] > 31)
			$yearKey = 1;
		else if (strlen($parts[2] ?? '') == 4 || (int)$parts[2] > 31)
			$yearKey = 2;

		if ($yearKey != 0 && (int)$parts[0] > 12)
			$dayKey = 0;
		else if ($yearKey != 1 && (int)$parts[1] > 12)
			$dayKey = 1;
		else if ($yearKey != 2 && (int)$parts[2] > 12)
			$dayKey = 2;
		
		if (is_null($yearKey) && is_null($dayKey))
		{
			// everything is null. Check the length of the parts to try to find
			// the year by process of elimination
			if (strlen($parts[0] ?? '') == 1 && strlen($parts[1] ?? '') == 1)
				$yearKey = 2;
			if (strlen($parts[0] ?? '') == 1 && strlen($parts[1] ?? '') == 2)
				$yearKey = 1;
			if (strlen($parts[1] ?? '') == 1 && strlen($parts[2] ?? '') == 1)
				$yearKey = 0;
		}	
			
		if (!is_null($yearKey) && !is_null($dayKey))
		{
			$monthKey = 3 - ($yearKey + $dayKey);			
		}
		else if (is_null($yearKey) && !is_null($dayKey))
		{
			// we found the day, but the year must be a 2 digit that could also be 
			// a month. We will just have to make our best guess
			
			if ($dayKey == 2)
			{
				// if dayKey is 2 then year is probably 0 (Y-M-D)
				$yearKey = 0;
				$monthKey = 1;
			}
			else if ($dayKey == 1)
			{
				// if dayKey is 1 then year is probably 2 (M-D-Y)
				$yearKey = 2;
				$monthKey = 0;
			}
			else
			{
				// dayKey is 0, which is really strange, but it's probably (D-M-Y)
				$yearKey = 2;
				$monthKey = 0;
			}
		}
		else if (is_null($dayKey) && !is_null($yearKey))
		{
			// we found the year, but the day must be a 2 digit that could also be
			// a month. We'll just have to guess
			if ($yearKey == 0)
			{
				// this is the worst one... some places to Y-M-D, some do Y-D-M
				// Y-M-D is most common though
				$monthKey = 1;
				$dayKey = 2;
			}
			else if ($yearKey == 1)
			{
				// this is a very odd date... Year in the middle doesn't really happen
				// we just have to guess, so let's go with M-Y-D
				$monthKey = 0;
				$dayKey = 2;
			}
			else
			{
				// yearKey is 2 so it's probably M-D-Y
				$monthKey = 0;
				$dayKey = 1;
			}
		}
		else
		{
			// everything is still null... just guess the most common of M-D-Y
			$monthKey = 0;
			$dayKey = 1;
			$yearKey = 2;
		}
		
		$format = array();
		$format[$monthKey] = 'mm';
		$format[$dayKey] = 'dd';
		$format[$yearKey] = 'yyyy';
		
		ksort($format);
		
		return implode('-', $format);		
		
	}

	public static function FormatTimezoneOffset($offset = null)
	{
		if (empty($offset))
			return 0;

		return sprintf('%+03d:%02u', floor($offset / 3600), floor(abs($offset) % 3600 / 60));
	}

	public static function GetTimezoneList($country = null)
	{
		$utc = new DateTimeZone('UTC');
		$dt = new DateTime('now', $utc);

		$x = 0;
		foreach (\DateTimeZone::ListIdentifiers() as $tz) 
		{
		    $current_tz = new \DateTimeZone($tz);
		    $offset =  $current_tz->getOffset($dt);
		    $transition =  $current_tz->getTransitions($dt->getTimestamp(), $dt->getTimestamp());
		    $abbr = $transition[0]['abbr'];

		    $data[$x]['timezone'] = $tz;
		    $data[$x]['offset'] = $offset;
		    $data[$x]['abbreviation'] = $abbr;

		    $x++;
		}

		return $data;
	}
}
?>