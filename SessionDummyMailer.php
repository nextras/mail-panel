<?php
/**
 * Session dummy mailer - emails are only stored into session.
 * 
 * @author Jan Drábek
 * @version 1.0
 * @copyright GNU-GPLv3
 */
class SessionDummyMailer implements IMailer {
	
	/** @var int */
	private static $limit = 10000;
	
	/**
	 * Sets limit of maximal stored messages
	 * @param int value
	 */
	public function setLimit($value) {
		if(!is_numeric($value) || $value <= 0) {
			throw new Exception("Wrong value.");
		}
		self::$limit = $value;
	}
	
	/**
	 * Return limit of stored mails
	 * @return int
	 */
	public function getLimit() {
		return self::$limit;
	}
	
	public function send(Mail $mail) {
		$container = Environment::getSession("session-dummy");
		if($container->queue === NULL || !$container->queue instanceof ArrayList) {
			$container->queue = new ArrayList();
		}
		$storage = $container->queue;
		if(count($storage) >= $this->getLimit()) {
			$storage->offsetUnset($storage->getIterator()->key());
		}
		$storage[] = $mail;
	}
}