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
class FileMailer extends Object implements IPersistentMailer
{
	/** @var string */
	private $tempDir;

	/** @var string[]|NULL */
	private $files;


	/**
	 * @param string $tempDir
	 */
	public function __construct($tempDir)
	{
		$this->tempDir = $tempDir;
	}


	/**
	 * Stores mail to a file.
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
		$this->files = NULL;
	}


	/**
	 * @inheritdoc
	 */
	public function getMessageCount()
	{
		return count($this->findFiles());
	}


	/**
	 * @inheritDoc
	 */
	public function getMessage($messageId)
	{
		$files = $this->findFiles();
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
		$files = array_slice($this->findFiles(), 0, $limit, TRUE);
		$mails = array_map(array($this, 'readMail'), $files);

		return $mails;
	}


	/**
	 * @inheritdoc
	 */
	public function deleteOne($messageId)
	{
		$files = $this->findFiles();
		if (!isset($files[$messageId])) {
			return; // assume that mail was already deleted
		}

		FileSystem::delete($files[$messageId]);
	}


	/**
	 * @inheritdoc
	 */
	public function deleteAll()
	{
		foreach ($this->findFiles() as $file) {
			FileSystem::delete($file);
		}
	}


	/**
	 * @return string[]
	 */
	private function findFiles()
	{
		if ($this->files === NULL) {
			$this->files = array();
			foreach (glob("{$this->tempDir}/*.mail") as $file) {
				$messageId = substr($file, -11, 6);
				$this->files[$messageId] = $file;
			}
			arsort($this->files);
		}

		return $this->files;
	}


	/**
	 * @param  string $path
	 * @return Nette\Mail\Message
	 */
	private function readMail($path)
	{
		$content = file_get_contents($path);
		if ($content === FALSE) {
			throw new \RuntimeException("Unable to read message stored in file '$path'");
		}

		$message = unserialize($content);
		if (!$message instanceof Message) {
			throw new \RuntimeException("Unable to deserialize message stored in file '$path'");
		}

		return $message;
	}
}
