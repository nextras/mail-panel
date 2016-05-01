<?php

/**
 * This file is part of the Nextras\MailPanel library.
 * @license    New BSD
 * @link       https://github.com/nextras/mail-panel
 */

namespace Nextras\MailPanel;

use Nette;


/**
 * MailPanel's mailer which caches sends mails.
 */
interface IMailer extends Nette\Mail\IMailer
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
