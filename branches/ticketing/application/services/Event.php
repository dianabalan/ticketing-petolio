<?php

class Petolio_Service_Event {
	
	/**
	 * Load logged in user's events
	 * @param DateTime $the_start - start date
	 * @param DateTime $the_end - end date
	 * @return array - events
	 */
	public static function loadYourEvents($the_start, $the_end) {
		
		if ( !Zend_Auth::getInstance()->hasIdentity() ) {
			return array();
		}
		
		$auth = Zend_Auth::getInstance();
		
		// format event in calendar template
		$in = array();
		
		$cal = new Petolio_Model_PoCalendar();
		
		$events = $cal->getMapper()->browseYourEvents($auth->getIdentity()->id, $the_start, $the_end);
		foreach($events as $line) {
			$array = Petolio_Service_Calendar::format($line);
	
			if($auth->getIdentity()) {
				$array['invited'] = $line['atype'] === '0' && $line['astatus'] === '0' ? true : false;
				$array['accepted'] = $line['atype'] === '0' && $line['astatus'] === '1' ? true : false;
				$array['access'] = $line['astatus'] == 1 ? true : false;
			}
	
			$array['formatted_start'] = Petolio_Service_Util::formatDate($array["start"], Petolio_Service_Util::MEDIUMDATE, ($array["allDay"] != 1), true, true);
			$in[] = $array;
		}
	
		// master repeats
		$results = Petolio_Service_Calendar::masterRepeats($in);
	
		// filter out events that have expired (remember to look out for all day events as well as continuous events)
		$now = clone $the_start;
		foreach($results as $idx => $line) {
			$start = new DateTime(date('Y-m-d H:i:s', $line['start']));
			$end = $line['end'] ? new DateTime(date('Y-m-d H:i:s', $line['end'])) : null;
	
			if($line['allDay'])
				$now->setTime(0, 0, 0);
	
			// if start is bigger than end date, unset
			if($start > $the_end)
				unset($results[$idx]);
	
			// unset if the event passed but check if the event is still running
			if($start < $now) {
				if($end) {
					if($end < $now)
						unset($results[$idx]);
				} else
					unset($results[$idx]);
			}
	
			// earlier we set the time to 00:00, and we reset it for the next event
			if($line['allDay'])
				$now = new DateTime('now');
		}
	
		return $results;
	}
	
	
}