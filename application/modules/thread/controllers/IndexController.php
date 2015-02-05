<?php

class IndexController extends Zend_Controller_Action
{
    public function indexAction() {
    	// nothing here
    }
}

class Filter
{
	private $db = null;
	private $log = null;

    public function process($msg) {
    	// log
    	$this->log = Zend_Registry::get('Zend_Log');
		$this->log->info("New message received.");

    	// get db connection
    	$db = Zend_Registry::get('db');
		$db->getConnection();

    	// load db
    	$this->db = new stdClass();
		$this->db->user = new Petolio_Model_PoUsers();
		$this->db->noty = new Petolio_Model_PoNotifications();
		$this->db->help = new Petolio_Model_PoHelp();

		// go to each module
    	$message = explode("_", $msg->body);
		$unserialized = unserialize($message[1]);

		// check the message, should be an array with more than 2 keys (3 keys or more normally)
		if($unserialized && is_array($unserialized) && count($unserialized) > 2) {
			$this->log->info("Going into specialized filter function.");
			if(method_exists($this, $message[0])) $this->$message[0]($unserialized);
			else $this->log->err("Specialized function does not exist. Message: " . print_r($message, true));
		} else $this->log->err("Message discarded, it is not compilant. Message: " . print_r($message, true));

		// close db connection
		$db->closeConnection();
		$this->log->info("-----------------------------------------------------");
	}

	/**
	 * Get all users except for 1
	 * @param user_id
	 */
	private function getAllUsers($user_id = false) {
		if(!$user_id) {
			$this->log->err("Arrived in Filter/" . __FUNCTION__ . " but without a user ID.");
			return array();
		}

		$this->log->info("Arrived in Filter/" . __FUNCTION__ . ". User ID: " . $user_id);
		$out = $this->db->user->fetchList("id <> {$user_id}");
		$this->log->info("Finished work in in Filter/" . __FUNCTION__);

		return $out;
	}

	/**
	 * Get all service users except for 1
	 * @param user_id
	 */
	private function getServiceUsers($user_id = false) {
		if(!$user_id) {
			$this->log->err("Arrived in Filter/" . __FUNCTION__ . " but without a user ID.");
			return array();
		}

		$this->log->info("Arrived in Filter/" . __FUNCTION__ . ". User ID: " . $user_id);
		$out = $this->db->user->fetchList("type = 2 AND id <> {$user_id}");
		$this->log->info("Finished work in in Filter/" . __FUNCTION__);

		return $out;
	}

	/**
	 * Get all friends and partners
	 * @param user_id
	 */
	private function getFriendsUsers($user_id = false) {
		if(!$user_id) {
			$this->log->err("Arrived in Filter/" . __FUNCTION__ . " but without a user ID.");
			return array();
		}

		$this->log->info("Arrived in Filter/" . __FUNCTION__ . ". User ID: " . $user_id);
		$this->db->user->find($user_id);
		$out = array_merge($this->db->user->getUserFriends(), $this->db->user->getUserPartners());
		$this->log->info("Finished work in in Filter/" . __FUNCTION__);

		return $out;
	}

    /**
	 * Forum
	 * @param -> msg, link, author
	 */
	private function forum($data) {
		$this->log->info("Arrived in Filter/" . __FUNCTION__ . ". Data: " . print_r($data, true));

		foreach($this->getAllUsers($data[2]) as $user) {
			$noty = clone $this->db->noty;
			$noty->setOptions(array(
				'user_id' => $user->getId(),
				'author_id' => $data[2],
				'scope' => __FUNCTION__,
				'data' => serialize($data)
			));
			$noty->save();
		}

		$this->log->info("Finished work in in Filter/" . __FUNCTION__);
	}

    /**
	 * Product
	 * @param -> msg, link, author
	 */
	private function product($data) {
		$this->log->info("Arrived in Filter/" . __FUNCTION__ . ". Data: " . print_r($data, true));

		foreach($this->getAllUsers($data[2]) as $user) {
			$noty = clone $this->db->noty;
			$noty->setOptions(array(
				'user_id' => $user->getId(),
				'author_id' => $data[2],
				'scope' => __FUNCTION__,
				'data' => serialize($data)
			));
			$noty->save();
		}

		$this->log->info("Finished work in in Filter/" . __FUNCTION__);
	}

    /**
	 * Service
	 * @param -> msg, link, author
	 */
	private function service($data) {
		$this->log->info("Arrived in Filter/" . __FUNCTION__ . ". Data: " . print_r($data, true));

		foreach($this->getAllUsers($data[2]) as $user) {
			$noty = clone $this->db->noty;
			$noty->setOptions(array(
				'user_id' => $user->getId(),
				'author_id' => $data[2],
				'scope' => __FUNCTION__,
				'data' => serialize($data)
			));
			$noty->save();
		}

		$this->log->info("Finished work in in Filter/" . __FUNCTION__);
	}

    /**
	 * Adoption
	 * @param -> msg, link, author
	 */
	private function adoption($data) {
		$this->log->info("Arrived in Filter/" . __FUNCTION__ . ". Data: " . print_r($data, true));

		foreach($this->getAllUsers($data[2]) as $user) {
			$noty = clone $this->db->noty;
			$noty->setOptions(array(
				'user_id' => $user->getId(),
				'author_id' => $data[2],
				'scope' => __FUNCTION__,
				'data' => serialize($data)
			));
			$noty->save();
		}

		$this->log->info("Finished work in in Filter/" . __FUNCTION__);
	}

    /**
	 * Member
	 * @param -> msg, link, author
	 */
	private function member($data) {
		$this->log->info("Arrived in Filter/" . __FUNCTION__ . ". Data: " . print_r($data, true));

		foreach($this->getAllUsers($data[2]) as $user) {
			$noty = clone $this->db->noty;
			$noty->setOptions(array(
				'user_id' => $user->getId(),
				'author_id' => $data[2],
				'scope' => __FUNCTION__,
				'data' => serialize($data)
			));
			$noty->save();
		}

		$this->log->info("Finished work in in Filter/" . __FUNCTION__);
	}

    /**
	 * Pet
	 * @param -> msg, link, author
	 */
	private function pet($data) {
		$this->log->info("Arrived in Filter/" . __FUNCTION__ . ". Data: " . print_r($data, true));

		foreach($this->getAllUsers($data[2]) as $user) {
			$noty = clone $this->db->noty;
			$noty->setOptions(array(
				'user_id' => $user->getId(),
				'author_id' => $data[2],
				'scope' => __FUNCTION__,
				'data' => serialize($data)
			));
			$noty->save();
		}

		$this->log->info("Finished work in in Filter/" . __FUNCTION__);
	}

    /**
	 * Gallery
	 * @param -> msg, link, author, action, gallery_id (for cooldown purposes)
	 */
	private function gallery($data) {
		$this->log->info("Arrived in Filter/" . __FUNCTION__ . ". Data: " . print_r($data, true));

		// on add pictures action
		$stop = false;
		if($data[3] == 'add') {
			// set limit to 10 minutes
			$limit = new \DateTime('now');
			$limit->sub(new \DateInterval('PT10M'));

			// go through each one
			foreach($this->db->noty->fetchList("scope = 'gallery' AND UNIX_TIMESTAMP(date_created) > {$limit->format('U')}") as $result) {
				$unserialized = unserialize($result->getData());
				if($unserialized[4] == $data[4] && $unserialized[3] == 'new')
					$stop = true;
			}
		}

		// continue only if not stopped
		if($stop == false) {
			foreach($this->getAllUsers($data[2]) as $user) {
				$noty = clone $this->db->noty;
				$noty->setOptions(array(
					'user_id' => $user->getId(),
					'author_id' => $data[2],
					'scope' => __FUNCTION__,
					'data' => serialize($data)
				));
				$noty->save();
			}
		}

		$this->log->info("Finished work in in Filter/" . __FUNCTION__);
	}

    /**
	 * Message
	 * @param ->  msg, link, author, user id
	 */
	private function message($data) {
		$this->log->info("Arrived in Filter/" . __FUNCTION__ . ". Data: " . print_r($data, true));

		$noty = clone $this->db->noty;
		$noty->setOptions(array(
			'user_id' => $data[3],
			'author_id' => $data[2],
			'scope' => __FUNCTION__,
			'data' => serialize($data)
		));
		$noty->save();

		$this->log->info("Finished work in in Filter/" . __FUNCTION__);
	}

    /**
	 * Dashboard
	 * @param -> msg, link, author, user id
	 *
	private function dashboard($data) {
		$this->log->info("Arrived in Filter/" . __FUNCTION__ . ". Data: " . print_r($data, true));

		$noty = clone $this->db->noty;
		$noty->setOptions(array(
			'user_id' => $data[3],
			'author_id' => $data[2],
			'scope' => __FUNCTION__,
			'data' => serialize($data)
		));
		$noty->save();

		$this->log->info("Finished work in in Filter/" . __FUNCTION__);
	}
	 */

    /**
	 * Event
	 * @param -> msg, link, author, user id (no user id means its for everybody)
	 */
	private function event($data) {
		$this->log->info("Arrived in Filter/" . __FUNCTION__ . ". Data: " . print_r($data, true));

		if(isset($data[3])) {
			$noty = clone $this->db->noty;
			$noty->setOptions(array(
				'user_id' => $data[3],
				'author_id' => $data[2],
				'scope' => __FUNCTION__,
				'data' => serialize($data)
			));
			$noty->save();
		} else {
			foreach($this->getAllUsers($data[2]) as $user) {
				$noty = clone $this->db->noty;
				$noty->setOptions(array(
					'user_id' => $user->getId(),
					'author_id' => $data[2],
					'scope' => __FUNCTION__,
					'data' => serialize($data)
				));
				$noty->save();
			}
		}

		$this->log->info("Finished work in in Filter/" . __FUNCTION__);
	}

    /**
	 * Event
	 * @param -> msg, link, author, question id (must see who to send this message, all, service providers or just friends) (no question id means its for everybody)
	 */
	private function question($data) {
		$this->log->info("Arrived in Filter/" . __FUNCTION__ . ". Data: " . print_r($data, true));

		if(!isset($data[3])) {
			foreach($this->getAllUsers($data[2]) as $user) {
				$noty = clone $this->db->noty;
				$noty->setOptions(array(
					'user_id' => $user->getId(),
					'author_id' => $data[2],
					'scope' => __FUNCTION__,
					'data' => serialize($data)
				));
				$noty->save();
			}
		} else {
			$question = $this->db->help->find($data[3]);

			// all
			if($question->getRights() == 0)
				foreach($this->getAllUsers($data[2]) as $user) {
					$noty = clone $this->db->noty;
					$noty->setOptions(array(
						'user_id' => $user->getId(),
						'author_id' => $data[2],
						'scope' => __FUNCTION__,
						'data' => serialize($data)
					));
					$noty->save();
				}

			// friends
			if($question->getRights() == 1)
				foreach($this->getFriendsUsers($question->getUserId()) as $user) {
					$noty = clone $this->db->noty;
					$noty->setOptions(array(
						'user_id' => $user->getId(),
						'author_id' => $data[2],
						'scope' => __FUNCTION__,
						'data' => serialize($data)
					));
					$noty->save();
				}

			// service providers
			if($question->getRights() == 2)
				foreach($this->getServiceUsers($data[2]) as $user) {
					$noty = clone $this->db->noty;
					$noty->setOptions(array(
						'user_id' => $user->getId(),
						'author_id' => $data[2],
						'scope' => __FUNCTION__,
						'data' => serialize($data)
					));
					$noty->save();
				}
		}

		$this->log->info("Finished work in in Filter/" . __FUNCTION__);
	}
}

// start amqpc
$channel = Petolio_Service_AMQPC::getChannel(Zend_Registry::get("config"));
$log = Zend_Registry::get('Zend_Log');

// declare and consume
echo ' [*] Thread is running. To exit press CTRL+C', "\n";
$log->info("Thread started.");
$log->info("-----------------------------------------------------");
$channel->basic_consume(Petolio_Service_AMQPC::getQueue(), '', false, true, false, false, array(new Filter(), 'process'));

// wait for callbacks
while(count($channel->callbacks))
    $channel->wait();

// disconnect
Petolio_Service_AMQPC::disconnect();