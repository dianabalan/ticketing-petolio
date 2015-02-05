<?php

/**
 * AMQPC class
 */
final class Petolio_Service_AMQPC {
	private static $cfg = array();
	private static $connection = null;
	private static $channel = null;
	private static $queue = 'notify';

	private function connect() {
		self::$connection = new AMQPConnection(
			self::$cfg['amqpc']['server'],
			self::$cfg['amqpc']['port'],
			self::$cfg['amqpc']['username'],
			self::$cfg['amqpc']['password']);
	}

	public static function getQueue() {
		return self::$queue;
	}

	public static function getChannel($cfg = array()) {
		self::$cfg = $cfg;

		self::connect();
		self::$channel = self::$connection->channel();
		self::$channel->queue_declare(self::$queue, false, false, false, false);

		return self::$channel;
	}

	public static function sendMessage($module, $data) {
		self::getChannel(Zend_Registry::get("config"));
		self::$channel->basic_publish(new AMQPMessage($module . '_' . serialize($data)), '', self::$queue);
		self::disconnect();
	}

	public static function disconnect() {
		self::$channel->close();
		self::$connection->close();
	}
}