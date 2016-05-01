<?php

/**
 * This file is part of the Nextras\MailPanel library.
 * @license    New BSD
 * @link       https://github.com/nextras/mail-panel
 */

namespace Nextras\MailPanel;

use Nette;


/**
 * Mailer which persists sent mails.
 */
interface IPersistentMailer extends Nette\Mail\IMailer
{
	/**
	 * @return int
	 */
	public function getMessageCount();


	/**
	 * @param  string $messageId
	 * @return Nette\Mail\Message
	 */
	public function getMessage($messageId);


	/**
	 * @param  int $limit
	 * @return Nette\Mail\Message[]
	 */
	public function getMessages($limit);


	/**
	 * @param  string $messageId
	 * @return void
	 */
	public function deleteOne($messageId);


	/**
	 * @return void
	 */
	public function deleteAll();
}
