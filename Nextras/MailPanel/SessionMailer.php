<?php

namespace Nextras\MailPanel;

use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Mail\Message;


/**
 * Session mailer - emails are stored into session
 *
 * @author Jan Drábek
 * @author Jan Marek
 * @author Jan Skrasek
 * @license New BSD
 */
class SessionMailer implements IMailer
{
	/** @var int */
	private $limit;

	/** @var Session */
	private $session;

	/** @var SessionSection */
	private $sessionSection;


	public function __construct(Session $session, $limit = 100, $sectionName = __CLASS__)
	{
		$this->limit = $limit;
		$this->session = $session;
		$this->sessionSection = $session->getSection($sectionName);
	}


	/**
	 * Sends given message via this mailer
	 * @param Message $mail
	 */
	public function send(Message $mail)
	{
		// get message with generated html instead of set FileTemplate etc
		$reflectionMethod = $mail->getReflection()->getMethod('build');
		$reflectionMethod->setAccessible(TRUE);
		$builtMail = $reflectionMethod->invoke($mail);

		if ($this->canAccessSession()) {
			$mails = $this->getMessages();
			if (count($mails) === $this->limit) {
				array_pop($mails);
			}
			array_unshift($mails, $builtMail);
			$this->setMessages($mails);
		} else {
			throw new \RuntimeException('Session is not started and you have already printed some contents.');
		}
	}


	public function getMessages($limit = NULL)
	{
		if ($this->canAccessSession() && isset($this->sessionSection->sentMessages)) {
			$messages = $this->sessionSection->sentMessages;
			return array_slice($messages, 0, $limit);
		} else {
			return array();
		}
	}
	
	
	public function setMessages($messages)
	{
		if ($this->canAccessSession()) {
			$this->sessionSection->sentMessages = $messages;
		}
	}


	public function getMessageCount()
	{
		return count($this->getMessages());
	}


	public function clear()
	{
		$this->setMessages(array());
	}


	public function deleteByIndex($index)
	{
		if ($this->canAccessSession()) {
			$messages = $this->getMessages();
			array_splice($messages, (int) $index, 1);
			$this->setMessages($messages);
		}
	}


	/**
	 * Return limit of stored mails
	 * @return int
	 */
	public function getLimit()
	{
		return $this->limit;
	}


	private function canAccessSession()
	{
		return $this->session->isStarted();
	}

}
