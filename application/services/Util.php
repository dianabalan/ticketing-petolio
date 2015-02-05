<?php

class Petolio_Service_Util {

	const FULLDATE = 1;
	const LONGDATE = 2;
	const MEDIUMDATE = 3;
	const SHORTDATE = 4;
	const NEWSDATE = 5; // like SHORTDATE, only the time is displayed first
	const JUSTTIME = 6;

	/**
	 * Will try and get the user's ip address even if its behind a proxy
	 *
	 * X-Forwarded-For: client1, proxy1, proxy2
	 * where the value is a comma+space separated list of IP addresses, the left-most being the farthest downstream client,
	 * and each successive proxy that passed the request adding the IP address where it received the request from.
	 */
	public static function get_remote_address()
	{
		$remote_addr = $_SERVER['REMOTE_ADDR'];

		// If we are behind a reverse proxy try to find the real users IP
		if (defined('FORUM_BEHIND_REVERSE_PROXY')) {
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$remote_addr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
				$remote_addr = trim($remote_addr[0]);
			}
		}

		return $remote_addr;
	}

	/**
	 * translates a string to the currently selected language
	 * first tries for db translate, then on failure gettext translate
	 *
	 * @param string $label
	 */
	public static function Tr($label) {
		$default = "en";
		$string = null;

		if (Zend_Registry::isRegistered('Zend_Translate_Db')) {
			$db_translator = Zend_Registry::get('Zend_Translate_Db');
			if ($db_translator->isTranslated($label)) {
				$string = $db_translator->_($label);
			} elseif (Zend_Registry::isRegistered('Zend_Translate')) {
				$po_translator = Zend_Registry::get('Zend_Translate');
				$string = $po_translator->_($label);
			}
		} elseif (Zend_Registry::isRegistered('Zend_Translate')) {
			$po_translator = Zend_Registry::get('Zend_Translate');
			$string = $po_translator->_($label);
		} else {
			$string = $label;
		}
		return $string;
	}

	/**
	 * db translate of a string to latin
	 *
	 * @param string $label
	 */
	public static function Latin($label) {

		$string = null;

		if (Zend_Registry::isRegistered('Zend_Translate_Db')) {
			$db_translator = Zend_Registry::get('Zend_Translate_Db');
			$current_locale = $db_translator->getLocale();
			$db_translator->setLocale('ln');
			if ($db_translator->isTranslated($label)) {
				$string = $db_translator->_($label);
			}
			$db_translator->setLocale($current_locale);
		}
		return $string;

	}

	/**
	 * basic function for formating a date
	 * the date_format parameter is optional and it should be only used when there are not enough space for displaying the date in the default format
	 *
	 * @param unix timestamp or string $date
	 * @param int $date_format
	 * @param bool $showtime - show h:i:s
	 * @param bool $timezone - bool or timezone string, if bool uses logged in user's timezone.
	 * @param bool $offset - bool or timezone string, if bool uses logged in user's timezone.
	 *
	 * @return string the formatted date
	 */
	public static function formatDate($date, $date_format = Petolio_Service_Util::LONGDATE, $showtime = true, $timezone = false, $offset = false) {
		// get config
		$config = Zend_Registry::get("config");

		// invalid?
		if (!(isset($date) && strlen($date) > 0))
			return '-';

		// not number ? get number
		if (!is_numeric($date))
			$date = strtotime($date);

		// figure out timezone
		if($timezone != false)
			$date = self::calculateTimezone($date, is_bool($timezone) ? @$_COOKIE['user_timezone'] : $timezone);

		// custom date set by user? need to calculate offset too
		if($offset != false)
			$date += Petolio_Service_Util::timezoneOffset(is_bool($timezone) ? @$_COOKIE['user_timezone'] : $timezone);

		// figure out locale
		$locale = Zend_Registry::get('Zend_Translate')->getLocale();
		$withtime = $showtime ? ' %H:%M' : '';

		if ($locale == 'de') {
			switch ($date_format) {
				case Petolio_Service_Util::FULLDATE:
					return strftime("%A, %d. %B %Y".$withtime, $date);
					break;
				case Petolio_Service_Util::LONGDATE:
					return strftime("%d. %B %Y".$withtime, $date);
					break;
				case Petolio_Service_Util::MEDIUMDATE:
					return strftime("%d.%m.%Y".$withtime, $date);
					break;
				case Petolio_Service_Util::SHORTDATE:
					return strftime("%d.%m.%y".($showtime ? ' %H:%M' : ''), $date);
					break;
				case Petolio_Service_Util::NEWSDATE:
					return strftime(($showtime ? '%H:%M ' : '')."%d.%m.%y", $date);
					break;
				case Petolio_Service_Util::JUSTTIME:
					return strftime('%H:%M', $date);
					break;

				default:
					return strftime("%d. %B %Y".$withtime, $date);
					break;
			}
		} else {
			switch ($date_format) {
				case Petolio_Service_Util::FULLDATE:
					return strftime("%A, %B %d, %Y".$withtime, $date);
					break;
				case Petolio_Service_Util::LONGDATE:
					return strftime("%B %d, %Y".$withtime, $date);
					break;
				case Petolio_Service_Util::MEDIUMDATE:
					return strftime("%b %d, %Y".$withtime, $date);
					break;
				case Petolio_Service_Util::SHORTDATE:
					return strftime("%m/%d/%y".($showtime ? ' %H:%M' : ''), $date);
					break;
				case Petolio_Service_Util::NEWSDATE:
					return strftime(($showtime ? '%H:%M ' : '')."%m/%d/%y", $date);
					break;
				case Petolio_Service_Util::JUSTTIME:
					return strftime('%H:%M', $date);
					break;

				default:
					return strftime("%B %d, %Y".$withtime, $date);
					break;
			}
		}
	}

	/**
	 * Calculate time facebook style
	 * @param int $timestamp
	 * @param bool $age - if age dont show beginning or ending
	 *
	 * @return string
	 */
	public static function formatTime($timestamp, $age = false) {
		// invalid?
		if (!(isset($timestamp) && strlen($timestamp) > 0))
			return 'Invalid Date';

		// not number ? get number
		if (!is_numeric($timestamp))
			$timestamp = strtotime($timestamp);

		// get translate and calculate difference
		$translate = Zend_Registry::get('Zend_Translate');
		$difference = time() - $timestamp;

		// period definition singular and plural
		$periods = array(
			$translate->_("second"),
			$translate->_("minute"),
			$translate->_("hour"),
			$translate->_("day"),
			$translate->_("week"),
			$translate->_("month"),
			$translate->_("year")
		);
		$pplural = array(
			$translate->_("seconds"),
			$translate->_("minutes"),
			$translate->_("hours"),
			$translate->_("days"),
			$translate->_("weeks"),
			$translate->_("months"),
			$translate->_("years")
		);

		// lengths
		$lengths = array("60", "60", "24", "7", "4.35", "12", "1000");

		// nullify
		$ending = null;
		$beginning = null;

		// this was in the past
		if ($difference >= 0) {
			$ending = " " . $translate->_("ago");

		// this was in the future
		} else {
			$difference = -$difference;
			$beginning = $translate->_("in") . " ";
		}

		// calc diff
		for($j = 0; $difference >= $lengths[$j]; $j++)
			$difference /= $lengths[$j];

		// round it up
		$difference = round($difference);

		// return string
		return $age ? sprintf('%1$s %2$s', $difference, $difference != 1 ? $pplural[$j] : $periods[$j]) :
			sprintf('%1$s%2$s %3$s%4$s', $beginning, $difference, $difference != 1 ? $pplural[$j] : $periods[$j], $ending);
	}

	/**
	 * Will make ucwords in UTF8 without lowering everything
	 * @param string $string
	 */
    public static function my_ucwords($string)
    {
	    $arr = preg_split("//u", $string, -1, PREG_SPLIT_NO_EMPTY);
	    $result = "";
	    $mode = false;
	    foreach ($arr as $char) {
	        $res = preg_match(
	            '/\\p{Mn}|\\p{Me}|\\p{Cf}|\\p{Lm}|\\p{Sk}|\\p{Lu}|\\p{Ll}|'.
	            '\\p{Lt}|\\p{Sk}|\\p{Cs}/u', $char) == 1;
	        if ($mode) {
	            if (!$res)
	                $mode = false;
	        }
	        elseif ($res) {
	            $mode = true;
	            $char = mb_convert_case($char, MB_CASE_TITLE, "UTF-8");
	        }
	        $result .= $char;
	    }

	    return $result;
    }

    /**
     * Will ucwords without common adjectives ('of', 'a', 'the', etc)
     * @param string $title
     */
    public static function title_case($title)
    {
        $smallwordsarray = array('of','a','the','and','an','or','nor','but',
        	'is','if','then','else','when','at','from','by',
        	'on','off','for','in','out','over','to','into','with','without','mit','und'
        );

        $words = explode(' ', $title);
        foreach($words as $key => $word) {
            if($key == 0 or !in_array($word, $smallwordsarray))
            $words[$key] = self::my_ucwords($word);
        }

        $newtitle = implode(' ', $words);
        return $newtitle;
    }

    /**
     * @see    http://www.php.net/manual/en/function.usort.php#93194
     * @author Will Shaver 27-Aug-2009 09:31
     *
     * An even better implementation of osort [than my original, posted on 24-AUG-09 (since deleted)],
     * allowing for multiple properties and directions.  With php 5.3.0 sorting by properties of an object becomes MUCH simpler.
     * Note that this uses anonymous functions / closures. Might find reviewing the php docs on that useful.
     * Look below for examples for previous version of php.
	 *
	 * // Usage:
	 * <?php
	 * osort($items, array("Color" => true, "Size" => false));
	 * // or
	 * osort($items, "Color");
	 * ?>
     */
	public static function array_sort(&$array, $props)
	{
	    if(!is_array($props))
	        $props = array($props => true);

	    usort($array, function($a, $b) use ($props) {
	        foreach($props as $prop => $ascending)
	        {
	            if($a[$prop] != $b[$prop])
	            {
	                if($ascending)
	                    return $a[$prop] > $b[$prop] ? 1 : -1;
	                else
	                    return $b[$prop] > $a[$prop] ? 1 : -1;
	            }
	        }
	        return -1; //if all props equal
	    });
	}

	/**
	 * Remove dirs and files recursive from a target
	 * @param string $dir - Target
	 */
    public static function rrmdir($dir)
    {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "."&& $object != "..") {
					if (filetype($dir.DIRECTORY_SEPARATOR.$object) == "dir")
						self::rrmdir($dir.DIRECTORY_SEPARATOR.$object);
					else @unlink($dir.DIRECTORY_SEPARATOR.$object);
				}
			}

			reset($objects);
			rmdir($dir);
		}
    }

    /**
     * formats a currency value
     *
     * @param double $value
     * @param string $currency_code
     * @return string the formatted value containing the currency code
     */
	public static function formatCurrency($value, $currency_code = '') {
    	$decimals = doubleval($value) > intval($value) ? 2 : 0;

    	$first = $currency_code == 'EUR' ? ',' : '.';
    	$second = $currency_code == 'EUR' ? '.' : ',';

    	return number_format($value, $decimals, $first, $second) . ((strlen($currency_code) > 0) ? " {$currency_code}" : '');
    }

    /**
     * Get inside or outside between 2 identificators
     *
     * @param string $text
     * @param string $start
     * @param string $end
     * @param string &$errors
     * @return array
     */
	public static function get_string_between($text, $start, $end, &$errors) {
		$tokens = explode($start, $text);

		$inside = array();
		$outside[] = $tokens[0];
		$num_tokens = count($tokens);
		for ($i = 1; $i < $num_tokens; ++$i)
		{
			$temp = explode($end, $tokens[$i]);
			if (count($temp) != 2) {
				$errors[] = 'BBCode code problem';
				return array(null, array($text));
			}

			$inside[] = $temp[0];
			$outside[] = $temp[1];
		}

		return array($inside, $outside);
	}

	/**
	 * Die with json :D
	 * @param array or json $a
	 */
	public static function json($a) {

		// disable layout and view
		Zend_Layout::getMvcInstance()->disableLayout();
		Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->setNoRender();
		
		header('Content-type: application/json');
		if ( is_array($a) ) {
			$a = json_encode($a);
		}
		print($a);
		// here was die($a);
	}

	/**
	 * Pagination
	 *
	 * @param int $all
	 * @param int $page
	 */
	public static function paginate($all, $page) {
		$pages = array();
		$link_to_all = false;

		if($page != 1 && $page != 0) {
			$prevpage = $page - 1;
			$pages['previous'] = $prevpage;
		}

		if ($all > 1) {
			if ($page > 3) {
				$pages['pages'][] = 1;
				if ($page != 4) $pages['pages'][] = 'delimiter';
			}

			for ($current = $page - 2, $stop = $page + 3; $current < $stop; ++$current) {
				if ($current < 1 || $current > $all) continue;
				elseif ($current != $page || $link_to_all) $pages['pages'][] = $current;
				else $pages['pages'][] = array($current);
			}

			if ($page <= ($all-3)) {
				if($page != ($all-3)) $pages['pages'][] = 'delimiter';
				$pages['pages'][] = $all;
			}
		}

		if ($page != $all) {
			$nextpage = $page + 1;
			$pages['next'] = $nextpage;
		}

		return $pages;
	}

	// http://stackoverflow.com/questions/5830387/php-regex-find-all-youtube-video-ids-in-string
	public static function ExtractYoutubeVideoID($text) {
	    $text = preg_replace('~
	        # Match non-linked youtube URL in the wild. (Rev:20111012)
	        https?://         # Required scheme. Either http or https.
	        (?:[0-9A-Z-]+\.)? # Optional subdomain.
	        (?:               # Group host alternatives.
	          youtu\.be/      # Either youtu.be,
	        | youtube\.com    # or youtube.com followed by
	          \S*             # Allow anything up to VIDEO_ID,
	          [^\w\-\s]       # but char before ID is non-ID char.
	        )                 # End host alternatives.
	        ([\w\-]{11})      # $1: VIDEO_ID is exactly 11 chars.
	        (?=[^\w\-]|$)     # Assert next char is non-ID or EOS.
	        (?!               # Assert URL is not pre-linked.
	          [?=&+%\w]*      # Allow URL (query) remainder.
	          (?:             # Group pre-linked alternatives.
	            [\'"][^<>]*>  # Either inside a start tag,
	          | </a>          # or inside <a> element text contents.
	          )               # End recognized pre-linked alts.
	        )                 # End negative lookahead assertion.
	        [?=&+%\w]*        # Consume any URL (query) remainder.
	        ~ix',
	        '$1',
	        $text);

	    return strlen($text) == 11 ? $text : false;
	}

	/**
	 * saves the current request to be able to redirect later (after login)
	 */
	public static function saveRequest() {
		// save the current url into the session
		$url = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri(); // or $_SERVER['REQUEST_URI'];
		$session = new Zend_Session_Namespace('Petolio_Redirect');
		$session->redirect = $url;
	}


	/**
	 * Convert special characters to HTML entities
	 * @param string $str
	 * @return string
	 */
	public static function escape($str) {
		return $str === null ? null : htmlspecialchars($str);
	}

	/**
	 * Convert special HTML entities back to characters
	 * @param string $str
	 */
	public static function unescape($str) {
		return htmlspecialchars_decode($str);
	}

	/**
	 *  Given a file, i.e. /css/base.css, replaces it with a string containing the
	 *  file's mtime, i.e. /css/base.css?_=1221534296.
	 *
	 *  @param $file  The file to be loaded.  Must be an absolute path (i.e.
	 *                starting with slash).
	 */
	public static function autoVersion($file)
	{
		if(strpos($file, '/') !== 0 || !file_exists($_SERVER['DOCUMENT_ROOT'] . $file))
			return $file;

		$mtime = @filemtime($_SERVER['DOCUMENT_ROOT'] . $file);
		return $file . "?_=" . $mtime;
	}

	/**
	 * Update timezone for our logged in user from the cookie set by javascript
	 */
	public static function updateTimezone() {
		// get auth
		$auth = Zend_Auth::getInstance();

		// not logged in, cookie not set?
		if(!($auth->hasIdentity() && isset($_COOKIE['user_timezone']) && strlen($_COOKIE['user_timezone']) > 0))
			return false;

		// timezone the same as saved?
		if(!($auth->getIdentity()->timezone != $_COOKIE['user_timezone']))
			return false;

		// update user in db
		$user = new Petolio_Model_PoUsers();
		$user->find($auth->getIdentity()->id);
		$user->setTimezone($_COOKIE['user_timezone'])->save();

		// update user in session
		$auth->getStorage()->write((object)$user->toArray());
	}

	/**
	 * Calculate timezone
	 *
	 * @param string or int $date
	 * @param string $timezone
	 * @param bool $reverse - transform from user to server date
	 *
	 * @return int unix timestamp
	 */
	public static function calculateTimezone($date, $timezone, $reverse = false) {
		// handle date multiple formats
		if (!is_numeric($date))
			$date = strtotime($date);

		// check user timezone for safety
		$zone = date_default_timezone_get();
		if(isset($timezone) && strlen($timezone) > 0)
			$zone = $timezone;

		// get timezones
		$server_zone = new DateTimeZone(date_default_timezone_get());
		$user_zone = new DateTimeZone($zone);

		// calculate based on given zones
		$timezone = new DateTime(date('F j, Y, g:i:s a', $date), $reverse ? $user_zone : $server_zone);
		$timezone->setTimezone($reverse ? $server_zone : $user_zone);

		// return corrected date in unixtime
		return (int) strtotime($timezone->format('Y-m-d H:i:s'));
	}

	/**
	 * Get offset from timezone
	 *
	 * @param string $timezone
	 * @return int offset
	 */
	public static function timezoneOffset($timezone) {
		// check user timezone for safety
		$zone = date_default_timezone_get();
		if(isset($timezone) && strlen($timezone) > 0)
			$zone = $timezone;

		// system timezone
		$server_zone = new DateTimeZone(date_default_timezone_get());
		$user_zone = new DateTimeZone($zone);

		// timestamp DateTime object
		$server_time = new DateTime("now", $server_zone);
		$user_time = new DateTime("now", $user_zone);

		// offset between the two
		return $server_zone->getOffset($server_time) - $user_zone->getOffset($user_time);
	}

	/**
	 * parses an url using the current route
	 * returns an array, where is specified the module, controller, action and other parameters
	 * 
	 * @param string $url
	 */
	public static function parseUrl($url) {
		if ( !isset($url) || empty($url) ) {
			return array(
				'controller' => null,
				'action' => null
			);
		}
		$route = Zend_Controller_Front::getInstance()->getRouter()->getCurrentRoute();
		$uri = Zend_Uri_Http::factory($url);
		return $route->match($uri->getPath());
	}
	
	/**
	 * 
	 * @param string $text
	 * @param number $max_chars
	 * @return string
	 */
	public static function shortenText($text, $max_chars = 500, $remove_all = false) {
		if ($remove_all) {
			$text = trim(
						preg_replace(
							'/\s+/', 
							' ', 
							strip_tags(
								nl2br(
									substr(
										html_entity_decode(strip_tags($text)), 
										0, 
										$max_chars))))); 
			/*$text = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', preg_replace(
							'/\s+/', ' ', strip_tags(nl2br(substr(html_entity_decode(strip_tags($text)), 0, $max_chars))))));*/ 
			return $text;
		}
		return nl2br(substr(strip_tags($text), 0, $max_chars));
	}

	/**
	 * StartsWith implementation.
	 * @param string $haystack where to search
	 * @param string $needle what to search
	 * @return boolean
	 */
	public static function startsWith($haystack, $needle) {
		return $needle === "" || strpos($haystack, $needle) === 0;
	}

	/**
	 * EndsWith implementation.
	 * @param string $haystack where to search
	 * @param string $needle what to search
	 * @return boolean
	 */
	public static function endsWith($haystack, $needle) {
		return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
	}
	
	public static function createCacheID($str) {
		$pattern = "/[^a-zA-Z0-9_]/";
		return preg_replace($pattern, "", $str);
	}
}