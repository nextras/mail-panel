<?php

/**
 * This file is part of the Nextras\MailPanel library.
 * @license    New BSD
 * @link       https://github.com/nextras/mail-panel
 */

namespace Nextras\MailPanel;

use Nette\Object;
use Nette\Utils\DateTime;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Mail\Message;


/**
 * File mailer - emails are stored into files
 */
class FileMailer extends Object implements IMailer
{
	/** @var string */
	private $tempDir;

	/** @var string */
	private $prefix;


	/**
	 * @param string $tempDir
	 */
	public function __construct($tempDir)
	{
		$now = new DateTime();
		$this->tempDir = $tempDir;
		$this->prefix = $now->format("YmdHis") . '-';
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
		$reflectionMethod = $message->getReflection()->getMethod('build');
		$reflectionMethod->setAccessible(TRUE);

		/** @var Message $builtMail */
		$builtMail = $reflectionMethod->invoke($message);

		$path = $this->tempDir . '/' . $this->prefix . md5($builtMail->getHeader('Message-ID')) . '.mail';
		FileSystem::write($path, serialize($builtMail));
	}


	/**
	 * @inheritdoc
	 */
	public function getMessageCount()
	{
		return count($this->findMails());
	}


	/**
	 * @inheritdoc
	 */
	public function getMessages($limit)
	{
		$files = array_slice($this->findMails(), 0, $limit);
		$mails = array();
		foreach ($files as $file) {
			$mails[] = unserialize(file_get_contents($file));
		}

		return $mails;
	}


	/**
	 * @inheritdoc
	 */
	public function deleteByIndex($index)
	{
		$files = $this->findMails();
		if (!isset($files[$index])) {
			throw new \InvalidArgumentException('Undefined index');
		}

		FileSystem::delete($files[$index]);
	}


	/**
	 * @inheritdoc
	 */
	public function clear()
	{
		foreach ($this->findMails() as $file) {
			FileSystem::delete($file);
		}
	}


	/**
	 * @return Message[]
	 */
	private function findMails()
	{
		$files = array();

		if (is_dir($this->tempDir)) {
			foreach (Finder::findFiles('*.mail')->in($this->tempDir) as $file) {
				$files[] = $file->getPathname();
			}
		}

		return $files;
	}
}
