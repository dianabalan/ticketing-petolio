<?php

/**
 * Text parsing class
 *
 * @author Seth
 * @version 0.1
 *
 */
class Petolio_Service_Parse {
	/**
	 * The actual parsing function
	 * @param string $txt the text
	 * @param int $max max chars
	 * @param int $cut wordrwap cut
	 * @param bool $word word safe?
	 * @param bool $dots trailing dots or not
	 */
	public static function _($txt, $max = 3000, $cut = 50, $word = true, $dots = false) {
		$txt = ' ' . $txt;
		$txt = htmlspecialchars($txt);

		$txt = self::do_limit($txt, $max, $word, $dots);
		$txt = self::do_newlines($txt);
		$txt = self::do_clickable($txt, $cut);

		return trim($txt);
	}

	/**
	 * Cuts a string and adds ... UTF8 Safe
	 * @param string $txt
	 * @param int $length
	 * @param bool $wordsafe
	 * @param bool $dots
	 * @return string
	 */
	public static function do_limit($txt, $length, $wordsafe = true, $dots = false)
	{
		$slen = strlen($txt);
		if ($slen <= $length) return $txt;

		if ($wordsafe) {
			$end = $length;
			while (($txt[--$length] != ' ') && ($length > 0)) {};
			if ($length == 0)
				$length = $end;
		}

		if ((ord($txt[$length]) < 0x80) || (ord($txt[$length]) >= 0xC0))
			return substr($txt, 0, $length) . ($dots ? ' ...' : '');

		while (--$length >= 0 && ord($txt[$length]) >= 0x80 && ord($txt[$length]) < 0xC0) {};
		return substr($txt, 0, $length) . ($dots ? ' ...' : '');
	}

	/**
	 * Do new lines
	 * @param string $txt
	 */
	private static function do_newlines($txt) {
		return nl2br($txt);
	}

	/**
	 * Do the supposed links clickable
	 * @param string $txt
	 * @param int $cut
	 * @return string
	 */
	private static function do_clickable($txt, $cut)
	{
		$txt = preg_replace('#([\s\(\)])(https?|ftp|news){1}://([\w\-]+\.([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^"\s\(\)<\[]*)?)#ie', '\'$1\'.self::do_url(\'$2://$3\', false, $cut)', $txt);
		$txt = preg_replace('#([\s\(\)])(www|ftp)\.(([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^"\s\(\)<\[]*)?)#ie', '\'$1\'.self::do_url(\'$2.$3\', \'$2.$3\', $cut)', $txt);

		return $txt;
	}

	/**
	 * Make sure we make it a link
	 * @param string $url
	 * @param bool $link
	 * @param int $cut
	 * @return string
	 */
	private static function do_url($url, $link = false, $cut)
	{
		if (strpos($url, 'www.') === 0) $full_url = 'http://'.$url;
		else if (strpos($url, 'ftp.') === 0) $full_url = 'ftp://'.$url;
		else if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) $full_url = 'http://'.$url;
		else $full_url = $url;

		if($link == false || $link == $url) {
			if(strlen($url) > $cut) $link = substr($url, 0 , round($cut / 1.3)).' ... '.substr($url, round($cut / -6));
			else $link = $url;
		} else $link = stripslashes($link);

		return '<a href="'.$full_url.'" target="_blank">'.$link.'</a>';
	}
}