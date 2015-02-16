<?php

/**
 * @author     Seth^^
 * @copyright  2011 @ riffcode.eu
 *
 * Calendar class that is used on the Events controller and Calendar controller
 * This class controlls all the aspects of appointments, tasks, events and special events
 *
 * @uses mtdowling@gmail.com's cron class (Michael Dowling)
 */
class Petolio_Service_Calendar {
	private static $countries = array();
    private static $colors = array();
    private static $types = array();
    private static $status = array();
	private static $species = array();
	private static $mods = array();
	private static $users = array();
	private static $pets = array();

    /**
     * Setter for countries
     */
	private static function setCountries() {
		$src = new Petolio_Model_PoCountriesMapper();
		foreach($src->fetchAll() as $c)
			self::$countries[$c->getId()] = $c->getName();
	}

    /**
     * Setter for colors
     */
	private static function setColors() {
		self::$colors = array(
	    	'0' => '#a22c2c',	// appointment
	    	'1' => '#2f8c68',	// task
	    	'2' => '#7f33cc',	// event /w attendees
	    	'3' => '#b4af1d'	// chat channel
	    );
	}

    /**
     * Setter for types
     */
	private static function setTypes() {
		$t = Zend_Registry::get('Zend_Translate');
		self::$types = array(
			'0' => $t->_("Appointment"),
			'1' => $t->_("To-Do"),
			'2' => $t->_("Event"),
			'3' => $t->_("Chat Channel")
		);
	}

    /**
     * Setter for status
     */
	private static function setStatus() {
		$t = Zend_Registry::get('Zend_Translate');
		self::$status = array(
			'-1' => "<span class='grey'>".$t->_('Owner')."</span>",
			'0' => array(
				'0' => array(
					'0' => "<span class='orange'>".$t->_('Invitation pending, accept or decline it')."</span>",
					'1' => "<span class='orange'>".$t->_('Appointment request pending')."</span>",
				),
				'1' => array(
					'0' => "<span class='orange'>".$t->_('Admin must accept your request')."</span>",
					'1' => "<span class='orange'>".$t->_('Admin must accept your request')."</span>",
				)
			),
			'1' => array(
				'0' => array(
					'0' => "<span class='green'>".$t->_('You have accepted the invitation')."</span>",
					'1' => "<span class='green'>".$t->_('Appointment request accepted')."</span>",
				),
				'1' => array(
					'0' => "<span class='green'>".$t->_('Admin has accepted your request')."<span>",
					'1' => "<span class='green'>".$t->_('Admin has accepted your request')."<span>"
				)
			),
			'2' => array(
				'0' => array(
					'0' => "<span class='red'>".$t->_('You have declined the invitation')."</span>",
					'1' => "<span class='red'>".$t->_('Appointment request declined')."</span>",
				),
				'1' => array(
					'0' => "<span class='red'>".$t->_('Admin has declined your request')."<span>",
					'1' => "<span class='red'>".$t->_('Admin has declined your request')."<span>"
				)
			)
		);
	}

	/**
	 * Setter for species
	 */
	private static function setSpecies() {
		// db
		$db = new Petolio_Model_PoAttributes();
		$db2 = new Petolio_Model_PoAttributeOptions();

		// get types
		$species = array();
		$attr = reset($db->fetchList("code = 'product_species'"));
		foreach($db2->fetchList("attribute_id = '{$attr->getId()}'") as $type)
			$species[$type->getId()] = Petolio_Service_Util::Tr($type->getValue());
		asort($species);

		// set it
		self::$species = array($attr->getId(), $species);
	}

    /**
     * Setter for mods
     */
	private static function setMods() {
		$t = Zend_Registry::get('Zend_Translate');
		self::$mods = array(
			'0' => $t->_("Fair"),
			'1' => $t->_("TV"),
			'2' => $t->_("Show"),
			'3' => $t->_("Tournament"),
			'4' => $t->_("In-house exhibition"),
			'5' => $t->_("Tutorial"),
			'6' => $t->_("Training"),
			'7' => $t->_("Spare time")
		);
	}

    /**
     * Setter for users
     */
	private static function setUsers() {
		if ( Zend_Auth::getInstance()->hasIdentity() ) {
			$auth = Zend_Auth::getInstance();
			$db = new Petolio_Model_PoUsers();
	
	    	// load user's friends and partners
	    	$friends = $partners = array();
			$db->find($auth->getIdentity()->id);
	
			foreach($db->getUserFriends() as $row)
				$friends[$row->getId()] = array('name' => $row->getName());
			foreach($db->getUserPartners() as $row)
				$partners[$row->getId()] = array('name' => $row->getName());
	
			self::$users = array('friends' => $friends, 'partners' => $partners);
		}
	}

    /**
     * Setter for pets
     */
	private static function setPets() {
		if ( Zend_Auth::getInstance()->hasIdentity() ) {
			$auth = Zend_Auth::getInstance();
			$db = new Petolio_Model_PoPets();
	
			// search pets
			$pets = array();
			foreach($db->getPets("array", "a.user_id = {$auth->getIdentity()->id}") as $one)
				$pets[$one['id']] = $one['name'];
			asort($pets);
	
			self::$pets = $pets;
		}
	}

    /**
     * Universal getter
     * check if resource requested is already loaded
     * if yes return it, if not call the setter and return that
     */
	private static function _get($what) {
		if(count(self::$$what) > 0)
			return self::$$what;

		call_user_func("self::set" . ucfirst($what));
		return self::$$what;
	}

	/**
	 * Country getter
	 */
	public static function getCountries() {
		return self::_get('countries');
	}

	/**
	 * Colors getter
	 */
	public static function getColors() {
		return self::_get('colors');
	}

	/**
	 * Types getter
	 */
	public static function getTypes() {
		return self::_get('types');
	}

	/**
	 * Status getter
	 */
	public static function getStatus() {
		return self::_get('status');
	}

	/**
	 * Species getter
	 */
	public static function getSpecies() {
		return self::_get('species');
	}

	/**
	 * Mods getter
	 */
	public static function getMods() {
		return self::_get('mods');
	}

	/**
	 * Users getter
	 */
	public static function getUsers() {
		return self::_get('users');
	}

	/**
	 * Pets getter
	 */
	public static function getPets() {
		return self::_get('pets');
	}

	/**
	 * Get the linux cron syntax based on the time and our custom made syntax
	 * @param int $syntax
	 * 		1 - every day
	 * 		2 - every work day
	 * 		3 - every month
	 * 		4 - every year
	 *
	 * @return array(
	 * 		day of week (0 - 7) (Sunday=0 or 7)
	 * 		month (1 - 12)
	 * 		day of month (1 - 31)
	 * 		hour (0 - 23)
	 * 		min (0 - 59)
	 *	);
	 */
    public static function getCronSyntax($time, $syntax)
    {
    	// get without leading zeros
    	$i = intval(date('i', $time));
    	$h = date('G', $time);
    	$d = date('j', $time);
    	$m = date('n', $time);
    	$w = date('w', $time);

    	// return linux cron based on custom syntax
    	if($syntax == 1) return array($i, $h, '*', '*', '*');
    	if($syntax == 2) return array($i, $h, '*', '*', '1-5');
    	if($syntax == 3) return array($i, $h, '*', '*', $w);
    	if($syntax == 4) return array($i, $h, $d, '*', '*');
    	if($syntax == 5) return array($i, $h, $d, $m, '*');
    }

	/**
	 * Set our custom made syntax based on linux cron syntax
	 * @param int $i min (0 - 59)
	 * @param int $h hour (0 - 23)
	 * @param int $d day of month (1 - 31)
	 * @param int $m month (1 - 12)
	 * @param int $w day of week (0 - 7) (Sunday=0 or 7)
	 *
 	 * @return int
	 * 		1 - every day
	 * 		2 - every work day
	 * 		3 - every month
	 * 		4 - every year
	 */
    public static function setCronSyntax($i, $h, $d, $m, $w)
    {
    	$s = "{$d} {$m} {$w}"; // build without time (we dont care about that)

    	// get custom syntax based on linux cron
    	if($s == '* * *') return 1;
		elseif($s == '* * 1-5') return 2;
    	elseif(preg_match("/\\* \\* [0-9]/i", $s)) return 3;
    	elseif(preg_match("/[0-9] \\* \\*/i", $s)) return 4;
		else return 5;
    }

	/*
	 * Has promotion ?
	 */
	private static function hasPromotion($event_id) {
		$promo = new Petolio_Model_PoPromotions();
		$cal = new Petolio_Model_PoCalendar();

		$event = $cal->find($event_id);
		$auth = Zend_Auth::getInstance();

		if($auth->hasIdentity() && $event->getUserId() == $auth->getIdentity()->id) $results = $promo->getMapper()->fetchList("event_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($event_id, Zend_Db::BIGINT_TYPE));
		else $results = reset($promo->getMapper()->fetchList("event_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($event_id, Zend_Db::BIGINT_TYPE)." AND active = '1'"));

		return $results ? $event_id : false;
	}

    /**
     * Format our event to calendar specifications
     * @param $e array/object event
     */
    public static function format($e) {
    	$o = array();
    	$colors = self::getColors();
    	$countries = self::getCountries();
    	$status = self::getStatus();
    	$auth = Zend_Auth::getInstance();

    	// event model ? get array
    	if(is_object($e))
    		$e = $e->toArray();

    	// get basic stuff
    	$o['id'] = $e['id'];
    	$o['pid'] = $e['id'];
    	$o['title'] = $e['subject'];
    	$o['type'] = $e['type'];

		// species and mod
		$o['species'] = $e['species'];
		$o['mod'] = $e['mod'];

		// fee and cap
    	$o['fee'] = $e['fee'];
    	$o['cap'] = $e['cap'];

		// links
    	$o['link_id'] = $e['link_id'];
    	$o['link_type'] = $e['link_type'];

		// user stuff
		$o['user_id'] = $e['user_id'];
		$o['user_name'] = $e['user_name'];
		$o['owner'] = $auth->hasIdentity() && $o['user_id'] == $auth->getIdentity()->id ? true : false;

		// get user avatar control
		$avatar = "/images/no-avatar.jpg";
		if(!is_null($e['user_avatar'])) {
			$ds = DIRECTORY_SEPARATOR;
			$image = "..{$ds}data{$ds}userfiles{$ds}avatars{$ds}{$e['user_id']}{$ds}thumb_{$e['user_avatar']}";

			// get cache
			if(is_file($image)) {
				$cache = filemtime($image);
				$avatar = "/images/userfiles/avatars/{$e['user_id']}/thumb_{$e['user_avatar']}?{$cache}";
			}
		} $o['user_avatar'] = $avatar;

    	// get date stuff
    	$o['offset'] = Petolio_Service_Util::timezoneOffset(@$_COOKIE['user_timezone']);
    	$o['start'] = Petolio_Service_Util::calculateTimezone($e['date_start'], @$_COOKIE['user_timezone']);
    	$o['end'] = $e['date_end'] ? Petolio_Service_Util::calculateTimezone($e['date_end'], @$_COOKIE['user_timezone']) : null;
    	$o['allDay'] = $e['all_day'] == 1 ? true : false;

    	// get description and color
    	$o['description'] = $e['description'];
    	$o['color'] = $colors[$o['type']];

    	// get address stuff
    	$o['street'] = $e['street'];
    	$o['address'] = $e['address'];
    	$o['zip'] = $e['zipcode'];
    	$o['location'] = $e['location'];
    	$o['countryName'] = null;

    	// get country
    	if($e['country_id']) {
    		$o['countryId'] = $e['country_id'];
    		$o['countryName'] = $countries[$e['country_id']];
    	}

    	// get position
    	if($e['gps_latitude'] && $e['gps_longitude']) {
    		$o['lat'] = $e['gps_latitude'];
    		$o['long'] = $e['gps_longitude'];
    	}

    	// get repeat
    	if($e['repeat'] == 1) {
    	    $o['repeat'] = $e['repeat'];
    		$o['repeat_syntax'] = self::setCronSyntax($e['repeat_minutes'], $e['repeat_hours'], $e['repeat_day_of_month'], $e['repeat_month'], $e['repeat_day_of_week']);
    		$o['repeat_until'] = $e['repeat_until'] ? strtotime($e['repeat_until']) : null;
    	}

    	// get reminder
    	if($e['reminder'] == 1) {
    		$o['reminder'] = $e['reminder'];
    		$o['reminder_time'] = $e['reminder_time'];
    	}

    	// get availability
    	$o['availability'] = $e['availability'];

    	// is logged in ?
    	$o['logged'] = $auth->hasIdentity();

    	// get status & promo
		$o['status'] = (isset($e["astatus"]) ? $status[$e["astatus"]][$e["atype"]][$e['link_id'] ? 1 : 0] : ($auth->hasIdentity() && $o['user_id'] == $auth->getIdentity()->id ? $status[-1] : false));
		$o['promo'] = self::hasPromotion($e['id']);

    	return $o;
    }

    /**
     * Since diff from php is broken on windows, we had to build this function
     * @param DateTime Object $date1 - date to compare with
     * @param DateTime Object $date2 - date to compare to
     * @param string $syntax - could be day/month/year
     *
     * @return int based on syntax
     */
    private static function nDiff($date1, $date2, $syntax = 'day') {
	    $count = 0;
	    while($date1 < $date2) {
	        $date1 = new DateTime(date("Y-m-d H:i:s", strtotime("+1 {$syntax}", strtotime($date1->format('Y-m-d H:i:s')))));
	        $count++;
	    }

	    return $count;
    }

    /**
     * Master all repeats -> basically we duplicate all events that we find
     * to be repeating getting all occurences based on cron and changing the start date, end dates and pid (unique event id)
     * @param array $d - array of events
     *
     * @return array of events correctly mastered :)
     */
    public static function masterRepeats($d, $for_calendar = false) {
    	$out = array();
    	foreach($d as $e) {
    		// do we have repeating events ?
    		if(isset($e['repeat']) && $e['repeat'] == 1) {
    			$crontab = self::getCronSyntax($e['start'], $e['repeat_syntax']);
				$occ = self::getOccurences($crontab, $e['start'], $e['repeat_until']);

				// loop through occurences
				foreach($occ as $idx => $one) {
					$z = $e;
    				$z['pid'] = $e['id'] . '_' . $idx;
    				$z['start'] = $one->format('U');
    				$z['ostart'] = $e['start'];
					if($e['end']) {
						// get the days difference and add it to the end date
						$days = self::nDiff(new DateTime(date('Y-m-d H:i:s', $e['start'])), $one, 'day');
						$end = new DateTime(date('Y-m-d H:i:s', $e['end']));
						$z['end'] = $end->modify("+{$days} days")->format('U');
						$z['oend'] = $e['end'];
					}

					// duplicate events
					$out[] = $z;
				}

			// no repeating events ? no duplicates
    		} else $out[] = $e;
    	}

    	// sort events :)
    	Petolio_Service_Util::array_sort($out, array("start" => true));

    	// return output
    	return $out;
	}

    /**
     * Load the cron files
     */
    private static function initCron() {
    	$prefix = __DIR__ . DIRECTORY_SEPARATOR . 'cron' . DIRECTORY_SEPARATOR;
		foreach (array(
		    'FieldFactory.php',
		    'FieldInterface.php',
		    'AbstractField.php',
		    'CronExpression.php',
		    'DayOfMonthField.php',
		    'DayOfWeekField.php',
		    'HoursField.php',
		    'MinutesField.php',
		    'MonthField.php',
		    'YearField.php',
			'Exceptions.php'
		) as $class) require_once $prefix . $class;
    }

    /**
     * Reset the time to reflect to 23:59:59
     * @param int $until timestamp
     */
    public static function getRepeatUntil($until) {
		$until = new DateTime(date('Y-m-d', $until));
		$until->setTime(23, 59, 59);

		return $until;
    }

    /**
     * Get the accurate next run date
     * 		- if now is earlier than event's start date, just return that
     * 		- elseif now is after all of the occurences, get the last one
     * 		- else return next occurance
     *
     * @param array $crontab - Output from self::getCronSyntax
     * @param int $start - Event start date timestamp
     * @param int $until - Event repeat until timestamp
     * @param DateTime/string $from - Now plus reminder time, because we might run this
     * 		to send reminders, and we need the real next occurance
     *
     * @return DateTime - next run date
     */
	public static function getNextRunDate($crontab, $start, $until, $from = 'now') {
		self::initCron();
		$cron = Cron\CronExpression::factory("{$crontab[0]} {$crontab[1]} {$crontab[2]} {$crontab[3]} {$crontab[4]}");

        $from = $from instanceof DateTime ? $from : new DateTime($from);
		$start = new DateTime(date('Y-m-d H:i:s', $start));
		if($from <= $start)
			return $start;

		$next = $cron->getNextRunDate($from);
		$last = end(self::getOccurences($crontab, $start, $until));
		return $next > $last ? $last : $next;
	}

	/**
	 * Get all occurences based on cron tab, basically get all next run dates until param $until
     * @param array $crontab - Output from self::getCronSyntax
     * @param DateTime/int $start - Event start date timestamp
     * @param DateTime/int $until - Event repeat until timestamp
     *
     * @return array containing the next occurences in DateTime
	 */
	public static function getOccurences($crontab, $start, $until) {
		self::initCron();
		$cron = Cron\CronExpression::factory("{$crontab[0]} {$crontab[1]} {$crontab[2]} {$crontab[3]} {$crontab[4]}");

		$start = $start instanceof DateTime ? $start : new DateTime(date('Y-m-d H:i:s', $start));
		$until = $until instanceof DateTime ? $until : new DateTime(date('Y-m-d H:i:s', $until));

		$all = array($start);
		$from = $start;

		$next = $cron->getNextRunDate($from);
		while($next < $until) {
			$all[] = $next;
			$next = $cron->getNextRunDate($next);
		}

		return $all;
	}
}