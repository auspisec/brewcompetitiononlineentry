<?php
function get_timezone($offset) {

	$offset = number_format($offset,3);

	// Allow per-installation timezone override via config.php.
	//
	// Some regions share the same UTC offset but need different timezone
	// identifiers for correct display. For example, Asia/Seoul and
	// Asia/Tokyo are both UTC+9 with no DST, but an installation in Korea
	// should display "KST" (not "JST") to its users.
	//
	// Set $override_timezone in config.php to the desired PHP timezone
	// identifier (e.g., 'Asia/Seoul') to override the offset-based mapping.
	// If not set, the existing offset-based mapping below is used.
	global $override_timezone;
	if (isset($override_timezone) && !empty($override_timezone)) {
		return $override_timezone;
	}

	$timezones = array(
        '-12.000' => 'Pacific/Kwajalein',
        '-11.000' => 'Pacific/Midway',
        '-10.000' => 'Pacific/Honolulu',
        '-9.500' => 'Pacific/Marquesas',
        '-9.000' => 'America/Anchorage',
        '-8.000' => 'America/Los_Angeles',
				'-7.000' => 'America/Denver',
        '-7.001' => 'America/Phoenix', // No DST for Arizona
        '-6.000' => 'America/Chicago',
				'-6.001' => 'America/Hermosillo', // No DST in this area of Mexico
				'-6.002' => 'America/Regina', // No DST in this area of Canada
        '-5.000' => 'America/New_York',
        '-4.000' => 'America/Virgin',
        '-4.001' => 'America/Asuncion', // DST observed in Paraguay
        '-3.500' => 'America/St_Johns',
        '-3.000' => 'America/Argentina/Buenos_Aires',
				'-3.001' => 'America/Sao_Paulo', // No DST for region of Brazil
        '-2.000' => 'Atlantic/South_Georgia',
        '-1.000' => 'Atlantic/Azores',
        '0.000' => 'Europe/London',
        '1.000' => 'Europe/Paris',
        '2.000' => 'Europe/Helsinki',
        '3.000' => 'Europe/Moscow',
        '3.500' => 'Asia/Tehran',
        '4.000' => 'Asia/Baku',
        '4.500' => 'Asia/Kabul',
        '5.000' => 'Asia/Karachi',
        '5.500' => 'Asia/Calcutta',
				'5.750' => 'Asia/Kathmandu',
        '6.000' => 'Asia/Colombo',
        '7.000' => 'Asia/Bangkok',
        '8.000' => 'Asia/Singapore',
				'8.001' => 'Australia/Perth', // No DST for this part of Australia
        '9.000' => 'Asia/Tokyo',
        '9.500' => 'Australia/Darwin',
        '10.000' => 'Pacific/Guam',
				'10.001' => 'Australia/Brisbane', // No DST for this part of Australia
				'10.002' => 'Australia/Melbourne', // DST observed in this part of Australia
        '11.000' => 'Asia/Magadan',
        '12.000' => 'Asia/Kamchatka',
				'13.000' => 'Pacific/Tongatapu',
    );

	$timezone = $timezones[$offset];
	
	return $timezone;

}

function convert_timestamp($time_string, $timezone, $offset, $method) {

	$timezone = get_timezone($timezone);

	// Method 1: convert to GMT for storage in DB
	if ($method == 1) {

		// 1. convert the time string specified in the current timezone to UTC (GMT) using built in PHP functions
		date_default_timezone_set($timezone);
		$timestamp = strtotime($time_string);

		// 2. return the value
		return $timestamp;

	}

	// Method 2: convert from GMT to selected timezone
	if ($method == 2) {
		
		// GMT date/time is always stored in DB
		// 1. make sure the timezone is UTC (GMT)
		date_default_timezone_set('UTC');

		// 2. convert the GMT timestamp to the desired timezone using the provided offset
		$timestamp = $time_string += ($offset * 3600);

		// 3. return the value
		return $timestamp;

	}

}

/**
 * Map a BCOE&M language code (e.g. "ko-KR", "en-US") to an ICU locale
 * string suitable for IntlDateFormatter. Returns null for English
 * so the caller can fall back to the original date() behaviour
 * (which is already correct for English).
 *
 * @param string|null $lang_code  BCOE&M prefsLanguage value
 * @return string|null  ICU locale or null to use default date() behaviour
 */
function get_locale_from_language($lang_code = null) {
	if (!isset($lang_code) || empty($lang_code)) return null;
	if ($lang_code == "en-US" || $lang_code == "en-GB") return null;
	// BCOE&M language codes are already in the correct format for ICU
	// (e.g. "ko-KR", "fr-FR", "es-419", "cs-CZ", "hu-HU", "pt-BR")
	return $lang_code;
}

/**
 * Format a timestamp using IntlDateFormatter for locale-aware output.
 * Falls back to date() if the intl extension is not available.
 *
 * @param int    $timestamp  Unix timestamp
 * @param string $locale     ICU locale (e.g. "ko_KR")
 * @param string $tz         PHP timezone identifier
 * @param string $pattern     ICU date/time pattern
 * @return string  Formatted date/time string
 */
function format_locale_date($timestamp, $locale, $tz, $pattern) {
	if (!class_exists('IntlDateFormatter')) {
		return null; // caller should fall back
	}
	$fmt = new IntlDateFormatter($locale, IntlDateFormatter::FULL, IntlDateFormatter::FULL, $tz, null, $pattern);
	return $fmt->format($timestamp);
}

function getTimeZoneDateTime($timezone_offset, $timestamp, $date_format, $time_format, $display_format, $return_format) {

	$tz = get_timezone($timezone_offset); // convert offset number to PHP timezone
  
  date_default_timezone_set($tz);

	// Determine the user's locale for locale-aware formatting.
	// When the user's language is non-English and the intl extension
	// is available, "long" and "xml" formats use IntlDateFormatter so
	// that day and month names are rendered in the user's language
	// (e.g. "일요일 7월 26, 2026" for Korean instead of
	// "Sunday 26 July, 2026").
	$locale = null;
	if (isset($_SESSION['prefsLanguage'])) {
		$locale = get_locale_from_language($_SESSION['prefsLanguage']);
	}

	switch($display_format) {

		// Long Format
		case "long":
			if ($locale !== null) {
				// Locale-aware formatting via IntlDateFormatter
				// Map the existing date_format preferences:
				//   1 => "Weekday, Month D, YYYY" (US style)
				//   else => "Weekday D Month, YYYY" (intl style)
				if ($date_format == "1")
					$pattern = 'EEEE, MMMM d, y';
				else
					$pattern = 'EEEE d MMMM, y';

				$formatted = format_locale_date($timestamp, $locale, $tz, $pattern);
				if ($formatted !== null) {
					$date = $formatted;
				} else {
					// Fallback: intl extension not available
					if ($date_format == "1") $date = date('l, F j, Y', $timestamp);
					else $date = date('l j F, Y', $timestamp);
				}
			} else {
				// English: keep original date() behaviour for backwards compat
				if ($date_format == "1") $date = date('l, F j, Y', $timestamp);
				else $date = date('l j F, Y', $timestamp);
			}
		break;

		// Short Format
		case "short":
			if ($date_format == 1) $date = date('m/d/Y', $timestamp);
			elseif ($date_format == 2) $date = date('d/m/Y',$timestamp);
			elseif ($date_format == 999) $date = date('Y-m-d H:i:s',$timestamp);
			else $date = date('Y/m/d', $timestamp);
		break;

		// MySQL Format
		case "system":
			$date = date('Y-m-d', $timestamp);
		break;

		// XML Report Format
		case "xml":
			if ($locale !== null) {
				$formatted = format_locale_date($timestamp, $locale, $tz, 'EEEE d MMMM y');
				if ($formatted !== null) {
					$date = $formatted;
				} else {
					$date = date('l j F Y', $timestamp);
				}
			} else {
				$date = date('l j F Y', $timestamp);
			}
		break;

	}

	// Time formatting
	if ($time_format == "1") {
		// 24-hour format is locale-neutral (numeric only)
		$time = date('H:i',$timestamp);
	} else {
		// 12-hour format with AM/PM
		if ($locale !== null) {
			$formatted_time = format_locale_date($timestamp, $locale, $tz, 'h:mm a');
			if ($formatted_time !== null) {
				$time = $formatted_time;
			} else {
				$time = date('g:i A',$timestamp);
			}
		} else {
			$time = date('g:i A',$timestamp);
		}
	}

	switch($return_format) {
		
		case "date-time":
			$return = $date." ".$time.", ".date('T',$timestamp);
		break;
		
		case "date-time-no-gmt":
			$return = $date." ".$time;
		break;
		
		case "date-time-system":
			$return = $date." ".$time;
		break;
		
		case "date-no-gmt":
			$return = $date;
		break;
		
		case "time-gmt":
			$return = $time.", ".date('T',$timestamp);
		break;
		
		case "time":
			$return = $time;
		break;

		case "year":
			$return = date('Y', $timestamp);
		break;
		
		default: $return = $date;
	
	}

	return $return;

}

function greaterDate($start_date, $end_date) {
  
  $start = strtotime($start_date);
  $end = strtotime($end_date);
  
  if ($start > $end) return TRUE;
  else return FALSE;

}

function judging_date_return() {
	
	require(CONFIG.'config.php');
	mysqli_select_db($connection,$database);

	$r = 0;
	$today = time();

	$query_check = sprintf("SELECT judgingDate FROM %s", $prefix."judging_locations");
	$check = mysqli_query($connection,$query_check) or die (mysqli_error($connection));
	$row_check = mysqli_fetch_assoc($check);
	$totalRows_check = mysqli_num_rows($check);

	// Check if the start date/time has passed
	// If so, increase output by 1
	if ($totalRows_check > 0) {
		
		do {
			
			if (isset($row_check['judgingDate'])) {
				if ($row_check['judgingDate'] >= time()) $r += 1;
			}

		} while ($row_check = mysqli_fetch_assoc($check));
		
	}
	
	return $r;

}

?>