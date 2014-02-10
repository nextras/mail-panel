<?php

namespace Nextras\MailPanel;

use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Mail\IMailer;
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

	/** @var SessionSection */
	private $sessionSection;


	public function __construct(Session $session, Response $response, $limit = 100, $sectionName = __CLASS__)
	{
		$this->limit = $limit;
		$this->sessionSection = $session->getSection($sectionName);
		if (!$response->isSent() && !$session->isStarted()) {
			$session->start();
		}
	}


	/**
	 * Sends given message via this mailer
	 * @param Message $mail
	 */
	public function send(Message $mail)
	{
		$mails = $this->sessionSection->sentMessages ?: array();

		if (count($mails) === $this->limit) {
			array_pop($mails);
		}

		// get message with generated html instead of set FileTemplate etc
		$reflectionMethod = $mail->getReflection()->getMethod('build');
		$reflectionMethod->setAccessible(TRUE);
		$builtMail = $reflectionMethod->invoke($mail);

		array_unshift($mails, $builtMail);

		$this->sessionSection->sentMessages = $mails;
	}


	public function getMessages($limit = NULL)
	{
		$messages = $this->sessionSection->sentMessages ?: array();
		return array_slice($messages, 0, $limit);
	}


	public function getMessageCount()
	{
		return count($this->sessionSection->sentMessages);
	}


	public function clear()
	{
		$this->sessionSection->sentMessages = array();
	}


	public function deleteByIndex($index)
	{
		$messages = $this->sessionSection->sentMessages ?: array();
		array_splice($messages, (int) $index, 1);
		$this->sessionSection->sentMessages = $messages;
	}


	/**
	 * Return limit of stored mails
	 * @return int
	 */
	public function getLimit()
	{
		return $this->limit;
	}

}
