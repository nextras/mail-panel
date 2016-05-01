<?php

/**
 * This file is part of the Nextras\MailPanel library.
 * @license    New BSD
 * @link       https://github.com/nextras/mail-panel
 */

namespace Nextras\MailPanel;

use Nette;
use Nette\Object;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Mail\Message;
use Nette\Utils\Strings;


/**
 * File mailer - emails are stored into files
 */
class FileMailer extends Object implements IMailer
{
	/** @var string */
	private $tempDir;


	/**
	 * @param string $tempDir
	 */
	public function __construct($tempDir)
	{
		$this->tempDir = $tempDir;
	}


	/**
	 * Store mails to files.
	 *
	 * @param  Message $message
	 * @return void
	 */
	public function send(Message $message)
	{
		// get message with generated html instead of set FileTemplate etc
		$ref = new \ReflectionMethod('Nette\Mail\Message', 'build');
		$ref->setAccessible(TRUE);

		/** @var Message $builtMail */
		$builtMessage = $ref->invoke($message);

		$time = date('YmdHis');
		$hash = substr(md5($builtMessage->getHeader('Message-ID')), 0, 6);
		$path = "{$this->tempDir}/{$time}-{$hash}.mail";
		FileSystem::write($path, serialize($builtMessage));
	}


	/**
	 * @inheritdoc
	 */
	public function getMessageCount()
	{
		return count($this->findMails());
	}


	/**
	 * @inheritDoc
	 */
	public function getMessage($messageId)
	{
		$files = $this->findMails();
		if (!isset($files[$messageId])) {
			throw new \RuntimeException("Unable to find mail with ID $messageId");
		}

		return $this->readMail($files[$messageId]);
	}


	/**
	 * @inheritdoc
	 */
	public function getMessages($limit)
	{
		$files = array_slice($this->findMails(), 0, $limit, TRUE);
		$mails = array_map(array($this, 'readMail'), $files);

		return $mails;
	}


	/**
	 * @inheritdoc
	 */
	public function deleteOne($messageId)
	{
		$files = $this->findMails();
		if (!isset($files[$messageId])) {
			throw new \RuntimeException("Unable to find mail with ID $messageId");
		}

		FileSystem::delete($files[$messageId]);
	}


	/**
	 * @inheritdoc
	 */
	public function deleteAll()
	{
		foreach ($this->findMails() as $file) {
			FileSystem::delete($file);
		}
	}


	/**
	 * @return string[]
	 */
	private function findMails()
	{
		$files = array();

		if (is_dir($this->tempDir)) {
			/** @var \SplFileInfo $file */
			foreach (Finder::findFiles('*.mail')->in($this->tempDir) as $file) {
				if ($matches = Strings::match($file->getBasename('.mail'), '#^\d+[-](\w+)\z#')) {
					$messageId = $matches[1];
					$files[$messageId] = $file->getPathname();
				}
			}

			arsort($files);
		}

		return $files;
	}


	/**
	 * @param  string $path
	 * @return Nette\Mail\Message
	 */
	private function readMail($path)
	{
		$message = unserialize(file_get_contents($path));
		if (!$message instanceof Message) {
			throw new \RuntimeException("Unable to deserialize message from file '$path'");
		}

		return $message;
	}
}
