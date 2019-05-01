<?php declare(strict_types = 1);

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
 * @deprecated
 */
class SessionMailer implements IPersistentMailer
{
	/** @var int */
	private $limit;

	/** @var Session */
	private $session;

	/** @var SessionSection */
	private $sessionSection;


	public function __construct(Session $session, int $limit = 100, string $sectionName = __CLASS__)
	{
		$this->limit = $limit;
		$this->session = $session;
		$this->sessionSection = $session->getSection($sectionName);
	}


	/**
	 * Store mails to sessions.
	 */
	public function send(Message $message): void
	{
		// get message with generated html instead of set FileTemplate etc
		$ref = new \ReflectionMethod('Nette\Mail\Message', 'build');
		$ref->setAccessible(TRUE);

		/** @var Message $builtMessage */
		$builtMessage = $ref->invoke($message);

		$this->requireSessions();
		$hash = substr(md5($builtMessage->getHeader('Message-ID')), 0, 6);
		$this->sessionSection->messages = array_slice(
			[$hash => $builtMessage] + $this->sessionSection->messages,
			0, $this->limit, TRUE
		);
	}


	/**
	 * @inheritdoc
	 */
	public function getMessageCount(): int
	{
		return count($this->getMessages());
	}


	/**
	 * @inheritDoc
	 */
	public function getMessage(string $messageId): Message
	{
		$this->requireSessions();

		if (!isset($this->sessionSection->messages[$messageId])) {
			throw new \RuntimeException("Unable to find mail with ID $messageId");
		}

		return $this->sessionSection->messages[$messageId];
	}


	/**
	 * @inheritdoc
	 */
	public function getMessages(int $limit = NULL): array
	{
		if ($this->session->isStarted() && isset($this->sessionSection->messages)) {
			$messages = $this->sessionSection->messages;
			return array_slice($messages, 0, $limit, TRUE);

		} else {
			return [];
		}
	}


	/**
	 * @inheritdoc
	 */
	public function deleteOne(string $messageId): void
	{
		$this->requireSessions();
		if (!isset($this->sessionSection->messages[$messageId])) {
			throw new \RuntimeException("Unable to find mail with ID $messageId");
		}

		unset($this->sessionSection->messages[$messageId]);
	}


	/**
	 * @inheritdoc
	 */
	public function deleteAll(): void
	{
		$this->sessionSection->messages = [];
	}


	/**
	 * Return limit of stored mails
	 */
	public function getLimit(): int
	{
		return $this->limit;
	}


	private function requireSessions(): void
	{
		if (!$this->session->isStarted()) {
			throw new \RuntimeException('Session is not started, start session or use FileMailer instead.');
		}

		if (!isset($this->sessionSection->messages)) {
			$this->sessionSection->messages = [];
		}
	}
}
