<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras\MailPanel library.
 * @license    New BSD
 * @link       https://github.com/nextras/mail-panel
 */

namespace Nextras\MailPanel;

use Nette;
use Nette\Mail\Message;


/**
 * Mailer which persists sent mails.
 */
interface IPersistentMailer extends Nette\Mail\IMailer
{
	public function getMessageCount(): int;


	public function getMessage(string $messageId): Message;


	/**
	 * @return Nette\Mail\Message[]
	 */
	public function getMessages(int $limit): array;


	public function deleteOne(string $messageId): void;


	public function deleteAll(): void;
}
