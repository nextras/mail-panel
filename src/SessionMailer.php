<?php

/**
 * This file is part of the Nextras\MailPanel library.
 * @license    New BSD
 * @link       https://github.com/nextras/mail-panel
 */

namespace Nextras\MailPanel;

use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Mail\Message;


/**
 * Session mailer - emails are stored into session
 */
class SessionMailer implements IMailer
{
	/** @var int */
	private $limit;

	/** @var Session */
	private $session;

	/** @var SessionSection */
	private $sessionSection;


	/**
	 * @param Session $session
	 * @param int     $limit
	 * @param string  $sectionName
	 */
	public function __construct(Session $session, $limit = 100, $sectionName = __CLASS__)
	{
		$this->limit = $limit;
		$this->session = $session;
		$this->sessionSection = $session->getSection($sectionName);
	}


	/**
	 * Store mails to sessions.
	 *
	 * @param  Message $message
	 * @return void
	 */
	public function send(Message $message)
	{
		// get message with generated html instead of set FileTemplate etc
		$ref = new \ReflectionMethod('Nette\Mail\Message', 'build');
		$ref->setAccessible(TRUE);

		/** @var Message $builtMessage */
		$builtMessage = $ref->invoke($message);

		$this->requireSessions();
		$hash = substr(md5($builtMessage->getHeader('Message-ID')), 0, 6);
		$this->sessionSection->sentMessages = array_slice(
			array($hash => $builtMessage) + $this->sessionSection->sentMessages,
			0, $this->limit, TRUE
		);
	}


	/**
	 * @inheritdoc
	 */
	public function getMessageCount()
	{
		return count($this->getMessages());
	}


	/**
	 * @inheritDoc
	 */
	public function getMessage($messageId)
	{
		$this->requireSessions();

		if (!isset($this->sessionSection->sentMessages[$messageId])) {
			throw new \RuntimeException("Unable to find mail with ID $messageId");
		}

		return $this->sessionSection->sentMessages[$messageId];
	}


	/**
	 * @inheritdoc
	 */
	public function getMessages($limit = NULL)
	{
		if ($this->session->isStarted() && isset($this->sessionSection->sentMessages)) {
			$messages = $this->sessionSection->sentMessages;
			return array_slice($messages, 0, $limit, TRUE);

		} else {
			return array();
		}
	}


	/**
	 * @inheritdoc
	 */
	public function deleteOne($messageId)
	{
		$this->requireSessions();
		if (!isset($this->sessionSection->sentMessages[$messageId])) {
			throw new \RuntimeException("Unable to find mail with ID $messageId");
		}

		unset($this->sessionSection->sentMessages[$messageId]);
	}


	/**
	 * @inheritdoc
	 */
	public function deleteAll()
	{
		$this->sessionSection->sentMessages = array();
	}


	/**
	 * Return limit of stored mails
	 * @return int
	 */
	public function getLimit()
	{
		return $this->limit;
	}


	/**
	 * @return void
	 */
	private function requireSessions()
	{
		if (!$this->session->isStarted()) {
			throw new \RuntimeException('Session is not started, start session or use FileMailer instead.');
		}

		if (!isset($this->sessionSection->sentMessages)) {
			$this->sessionSection->sentMessages = array();
		}
	}
}
