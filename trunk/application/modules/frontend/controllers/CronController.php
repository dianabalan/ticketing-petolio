<?php

/**
 * Cron Controller
 */
class CronController extends Zend_Controller_Action
{
	private $translate = null;
	private $config = null;
	private $db = null;

	public function init() {
		// load custom objects
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->config = Zend_Registry::get("config");
		$this->db = new stdClass();

		// load models
		$this->db->calendar = new Petolio_Model_PoCalendar();
		$this->db->users = new Petolio_Model_PoUsers();
		$this->db->files = new Petolio_Model_PoFiles();
		$this->db->service = new Petolio_Model_PoServices();
		$this->db->microsite = new Petolio_Model_PoMicrosites();
		$this->db->gallery = new Petolio_Model_PoGalleries();
		$this->db->chat = new Petolio_Model_PoChat();
		$this->db->online = new Petolio_Model_PoOnline();
		$this->db->emails = new Petolio_Model_PoEmails();
		$this->db->notifications = new Petolio_Model_PoNotifications();
		$this->db->news = new Petolio_Model_PoNews();
	}

	/**
	 * Index
	 */
	public function indexAction() {
		die('nothing to see here, move along');
	}

	/**
	 * Running each minute
	 */
	public function perMinuteAction() {
		$this->emailsCron();
		$this->calendarCron();
		$this->featuredCron();
	}

	/**
	 * Running each hour
	 */
	public function perHourAction() {
		$this->youtubeCron();
		$this->newsCron();
	}

	/**
	 * Running each day
	 */
	public function perDayAction() {
		$this->dashboardCron();
		$this->notificationsCron();
	}

	/**
	 * Running each month
	 */
	public function perMonthAction() {
		$this->chatCron();
		$this->onlineCron();
	}

	/**
	 * Running each friday
	 */
	public function everyFridayAction() {
		$this->weeklynotificationCron();
	}

	/**
	 * Emails Cron
	 */
	private function emailsCron() {
		// get the emails
		$emails = $this->db->emails->fetchList('status = 0', array('priority DESC', 'date_created ASC'), 10);

		// mark them as in use
		foreach($emails as $email)
			$email->setStatus(1)->save();

		// create transporter
		$transport = new Zend_Mail_Transport_Smtp(
			$this->config['email']['server'],
			array(
				'auth' => 'login',
				'ssl' => 'ssl',
				'port' => $this->config['email']['port'],
				'username' => $this->config['email']['username'],
				'password' => $this->config['email']['password']
			)
		);

		// send each mail
		foreach($emails as $email) {
			// get the mail object
			$mail = unserialize($email->getSerialized());

			// send email
			try {
				$mail->send($transport);
			} catch (Zend_Mail_Exception $e) {
				Zend_Registry::get('Zend_Log')->debug("Zend_Mail_Exception Code: " . $e->getCode());
				Zend_Registry::get('Zend_Log')->debug("Zend_Mail_Exception File: " . $e->getFile());
				Zend_Registry::get('Zend_Log')->debug("Zend_Mail_Exception Line: " . $e->getLine());
				Zend_Registry::get('Zend_Log')->debug("Zend_Mail_Exception Message: " . $e->getMessage());
				Zend_Registry::get('Zend_Log')->debug("Zend_Mail_Exception Trace: " . $e->getTraceAsString());
				Zend_Registry::get('Zend_Log')->debug("Zend_Mail_Exception, the mail will be sent with sendmail: " . $transport->getConnection()->getLog());
				$mail->send();
			}

			// delete from queue
			$email->deleteRowByPrimaryKey();
		}
	}

	/**
	 * Calendar Cron
	 */
	private function calendarCron() {
		// get the events within the minute time frame (since we run this cron every minute)
		$results = $this->db->calendar->getMapper()->getEvents("reminder = '1'
			AND (UNIX_TIMESTAMP(date_next_run) - reminder_time * 60) <= UNIX_TIMESTAMP(NOW())
			AND (UNIX_TIMESTAMP(date_next_run) - reminder_time * 60) >= (UNIX_TIMESTAMP(NOW()) - 1 * 60)
		");
		if(empty($results))
			return true;

		// format event in calendar template
		$now = new DateTime('now');
		foreach($results as $line) {
			// format event
			$event = Petolio_Service_Calendar::format($line);

			// calcualte the now taking into account the reminder time
			$n = clone $now;
			$n->add(new DateInterval("PT{$event['reminder_time']}M"));

			// repeat ?
			if(isset($event['repeat'])) {
				// get repeat stuff
				$crontab = Petolio_Service_Calendar::getCronSyntax($event['start'], $event['repeat_syntax']);
				$repeat_until = Petolio_Service_Calendar::getRepeatUntil($event['repeat_until'])->format('U');
				$next_run_date = Petolio_Service_Calendar::getNextRunDate($crontab, $event['start'], $repeat_until, $n)->format('U');

				// send reminder
				$this->calendarCronSend($event);

				// save next run date
				$line->setDateNextRun(date('Y-m-d H:i:s', $next_run_date))->save();
			}

			// no repeat, one night stand ?
			else $this->calendarCronSend($event);
		}
	}

	/**
	 * Calendar Cron Helper (sends the reminders)
	 */
	private function calendarCronSend($event) {
		// get user
		$this->db->users->find($event['user_id']);

		// set locale based on user's language
		$this->translate->setLocale($this->db->users->getLanguage());

		// get types
		$types = Petolio_Service_Calendar::getTypes();

		// phase
		if($event['reminder_time'] > 1440) {
			$phase = $this->translate->_('The %1$s: <i>%2$s</i> is about to start in approximately %3$s Days!');
			$event['reminder_time'] = intval($event['reminder_time'] / 1440);
		} elseif($event['reminder_time'] > 60) {
			$phase = $this->translate->_('The %1$s: <i>%2$s</i> is about to start in approximately %3$s Day!');
			$event['reminder_time'] = intval($event['reminder_time'] / 1440);
		} else {
			$phase = $this->translate->_('The %1$s: <i>%2$s</i> is about to start in approximately %3$s Minutes!');
		}

		// send message
		Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Reminder Alert"),
			'message_html' => sprintf(
				$phase,
				$types[$event['type']],
				"<a href='{$this->view->url(array('controller'=>'events'), 'default', true)}#{$event['id']}'>{$event['title']}</a>",
				$event['reminder_time']
			),
			'from' => 0,
			'status' => 2,
			'template' => 'default'
		), array(array(
			'id' => $this->db->users->getId(),
			'name' => $this->db->users->getName(),
			'email' => $this->db->users->getEmail()
		)), $this->db->users->isOtherEmailNotification());
	}

	/**
	 * Dashboard Cron (garbage collector)
	 */
	private function dashboardCron() {
		// iterate over the uploaded directory
		$ds = DIRECTORY_SEPARATOR;
		$iterator = new DirectoryIterator("..{$ds}data{$ds}userfiles{$ds}dashboard{$ds}");
		foreach ($iterator as $file) {
			// not a file? skip
			if(!$file->isFile())
				continue;

			// get base name
			$name = $file->getBasename(".". pathinfo($file->getFilename(), PATHINFO_EXTENSION));

			// only get new (unused) files, skip the rest
			if(!(strlen($name) > 32 && substr($name, 0, 1) == 'n'))
				continue;

			// is the file older than a day? no, skip
			if(!($file->getCTime() < (time() - 86400)))
				continue;

			// delete files
			@unlink($file->getPathName());
		}
	}

	/**
	 * Notification Cron (deletes all notifications older than 1 month)
	 */
	private function notificationsCron() {
		// 1 month ago
		$onem = new DateTime('now');
		$onem->sub(new DateInterval("P1M"));

		// delete notifications older than 1 months
		$this->db->notifications->getMapper()->getDbTable()->delete("UNIX_TIMESTAMP(date_created) < '{$onem->format('U')}'");
	}

	/**
	 * Youtube Cron (refreshes youtube cache files)
	 */
	private function youtubeCron() {
		// start caching
		$cache = array();

		// youtube wrapper
		$youtube = Petolio_Service_YouTube::factory('Master');
		$youtube->CFG = array(
			'username' => $this->config["youtube"]["username"],
			'password' => $this->config["youtube"]["password"],
			'app' => $this->config["youtube"]["app"],
			'key' => $this->config["youtube"]["key"]
		);

		// iterate over the youtube cache files
		$ds = DIRECTORY_SEPARATOR;
		$result = $this->db->files->getMapper()->getFilesWFolders("type = 'video'");
		foreach($result as $file) {
			// pet detected
			if ($file['folder_pet_id'] && $file['folder_pet_id'] > 0)
				$upload_dir = "..{$ds}data{$ds}userfiles{$ds}pets{$ds}{$file['folder_pet_id']}{$ds}videos{$ds}";

			// other path needed (service / microsite)
			elseif($file['folder_name'] == 'service' || $file['folder_name'] == 'microsite') {
				// couldn't find service in cache
				if(!isset($cache[$file['folder_name']][$file['folder_id']])) {
					// get service / microsite
					$cache[$file['folder_name']][$file['folder_id']] = reset($this->db->{$file['folder_name']}->fetchList("folder_id = '{$file['folder_id']}'"));

					// skip if service / microsite not found in db
					if(!$cache[$file['folder_name']][$file['folder_id']] || !$cache[$file['folder_name']][$file['folder_id']]->getId())
						continue;
				}

				// set upload dir
				$upload_dir = "..{$ds}data{$ds}userfiles{$ds}{$file['folder_name']}s{$ds}{$cache[$file['folder_name']][$file['folder_id']]->getId()}{$ds}";

			// gallery
			} else {
				// couldn't find gallery in cache
				if(!isset($cache[$file['folder_name']][$file['folder_id']])) {
					// get gallery
					$cache[$file['folder_name']][$file['folder_id']] = reset($this->db->gallery->fetchList("folder_id = '{$file['folder_id']}'"));
				}

				// skip if gallery not found in db
				if(!$cache[$file['folder_name']][$file['folder_id']] || !$cache[$file['folder_name']][$file['folder_id']]->getId()) {
					continue;
				}

				// set upload dir
				$upload_dir = "..{$ds}data{$ds}userfiles{$ds}galleries{$ds}{$cache[$file['folder_name']][$file['folder_id']]->getId()}{$ds}";
			}

			// lost folders?
			if (!file_exists($upload_dir))
				if (!mkdir($upload_dir))
					continue;

			// refresh cache
			$youtube->setVideoEntryCache(pathinfo($file['file'], PATHINFO_FILENAME), $upload_dir);
		}
	}

	/**
	 * Chat Cron (archives chat messages older than 3 months)
	 */
	private function chatCron() {
		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}archives{$ds}chat";

		// 3 months ago
		$threem = new DateTime('now');
		$threem->sub(new DateInterval("P3M"));

		// get messages older than 3 months
		$write = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";
		$found = $this->db->chat->fetchList("UNIX_TIMESTAMP(date_created) < '{$threem->format('U')}'");
		foreach($found as $messages) {
			// get array and write it
			$data = $messages->toArray();
			$write .= "INSERT INTO `po_chat` (`id`, `calendar_id`, `user_id`, `message`, `date_created`) " .
				"VALUES ({$data['id']}, {$data['calendar_id']}, {$data['user_id']}, '{$data['message']}', '{$data['date_created']}');\n";

			// delete from db
			$messages->deleteRowByPrimaryKey();
		}

		// nothing found? don't bother
		if(!count($found) > 0)
			return false;

		// write archive
		file_put_contents($upload_dir . $ds . date('d-M-Y') . '.sql', $write);
	}

	/**
	 * Online Cron (archives online messages older than 3 months)
	 */
	private function onlineCron() {
		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}archives{$ds}online";

		// 3 months ago
		$threem = new DateTime('now');
		$threem->sub(new DateInterval("P3M"));

		// get messages older than 3 months
		$write = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";
		$found = $this->db->online->fetchList("UNIX_TIMESTAMP(date_created) < '{$threem->format('U')}'");
		foreach($found as $messages) {
			// get array and write it
			$data = $messages->toArray();
			$write .= "INSERT INTO `po_online` (`id`, `from_id`, `to_id`, `message`, `date_created`, `status`) " .
				"VALUES ({$data['id']}, {$data['from_id']}, {$data['to_id']}, '{$data['message']}', '{$data['date_created']}', {$data['status']});\n";

			// delete from db
			$messages->deleteRowByPrimaryKey();
		}

		// nothing found? don't bother
		if(!count($found) > 0)
			return false;

		// write archive
		file_put_contents($upload_dir . $ds . date('d-M-Y') . '.sql', $write);
	}

	/**
	 * Makes a chat featured or takes the featured status away
	 */
	private function featuredCron() {
		// 1 hour ago
		$ago = new DateTime('now');
		$ago->sub(new DateInterval("PT1H"));

		// now
		$now = new DateTime('now');

		// in 1 hour
		$in = new DateTime('now');
		$in->add(new DateInterval("PT1H"));

		// find chats that are not protected by cap
		$channels = $this->db->calendar->fetchList("type = '3' AND (cap < '{$now->format('U')}' OR cap IS NULL)");

		// go through found chats
		foreach($channels as $channel) {
			// check last 4 messages different between 2 separate users
			$activity = $this->db->chat->checkActivity("calendar_id = '{$channel->getId()}' AND UNIX_TIMESTAMP(date_created) > '{$ago->format('U')}'");

			// featured and no activity found? take out
			if($channel->getFee() == '1' && $activity === false)
				$channel->setFee(0)->setCap(new Zend_Db_Expr('NULL'))->save();

			// not featured and activity found? put back in
			if($channel->getFee() == '0' && $activity === true)
				$channel->setFee(1)->setCap($in->format('U'))->save();
		}
	}

	/**
	 * Weekly Notification Cron (sends an email to all users informing them of the notifications)
	 */
	private function weeklynotificationCron() {
		// get the users that are subscribed to this
		$found = $this->db->users->fetchList("active = 1 AND is_banned != 1 AND weekly_email_notification = 1");

		// go through each user
		foreach($found as $user) {
			// set locale based on user's language
			$this->translate->setLocale($user->getLanguage());

			// find unread notifications
			$count = $this->db->notifications->getMapper()->getDbTable()->getAdapter()->fetchOne("SELECT COUNT(*) FROM po_notifications AS a LEFT JOIN po_users AS x ON a.author_id = x.id WHERE x.active = 1 AND x.is_banned != 1 AND a.user_id = '{$user->getId()}' AND a.status = '0'");
			if($count > 0) {
				// send them emails! yay
			    Petolio_Service_Message::sendEmail(array(
					'subject' => $this->translate->_("What's up since your last visit?"),
					'message_html' => sprintf(
						$this->translate->_('Since your last visit on Petolio, there are %1$s new notifications that might interest you.'),
						$count
					) . '<br /><br />' .
					'<div style="text-align: center;">' .
						'<a href="'. $this->view->url(array('controller'=>'site', 'action'=>'view-notifications'), 'default', true) .'" style="font-size: 16px; font-weight: bold; padding: 7px 5px 7px 10px; background-color: #f85f19; color: #fff; border-radius: 5px; text-decoration: none;">' . $this->translate->_("Join Now") . ' <span style="font-size: 12px; padding: 3px 5px; margin: 0px 5px; background: red; color: #fff; border-radius: 10px;">'. $count .'</span></a>' .
					'</div>',
					'priority' => 0,
					'template' => 'default'
				), array(array(
					'id' => $user->getId(),
					'name' => $user->getName(),
					'email' => $user->getEmail()
				)));
			}
		}
	}

	/**
	 * Hourly syncronization with rss
	 */
	private function newsCron() {
		foreach($this->db->news->fetchList(null, "RAND()") as $source)
			Petolio_Service_Rss::sync($source->getId());
	}
}