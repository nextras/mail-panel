<?php

/**
 * This file is part of the Nextras\MailPanel library.
 * @license    New BSD
 * @link       https://github.com/nextras/mail-panel
 */

namespace Nextras\MailPanel;

use Nette\Object;
use Nette\InvalidStateException;
use Nette\Utils\DateTime;
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

	/** @var array */
	private $files = array();


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

		$file = $this->tempDir . '/' . $this->prefix . md5($builtMail->getHeader('Message-ID')) . '.mail';
		$dir  = dirname($file);
		if (!is_dir($dir)) {
			mkdir($dir, 0777, TRUE);
		}

		if (!file_put_contents($file, serialize($builtMail))) {
			throw new InvalidStateException("Unable to write email to '{$file}'.");
		}
	}


	/**
	 * @inheritdoc
	 */
	public function getMessageCount()
	{
		$this->findMails();
		return count($this->files);
	}


	/**
	 * @inheritdoc
	 */
	public function getMessages($limit)
	{
		$this->findMails();
		$files = array_slice($this->files, 0, $limit);
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
		$this->findMails();
		if (!isset($this->files[$index])) {
			throw new \InvalidArgumentException('Undefined index');
		}

		@unlink($this->files[$index]);
	}


	/**
	 * @inheritdoc
	 */
	public function clear()
	{
		$this->findMails();
		foreach ($this->files as $file) {
			@unlink($file);
		}
	}


	/**
	 * @return Message[]
	 */
	private function findMails()
	{
		$this->files = array();

		if (!is_dir($this->tempDir)) {
			return;
		}

		$files = Finder::findFiles('*.mail')->in($this->tempDir);
		foreach ($files as $file) {
			$this->files[] = $file->getPathname();
		}
	}

}
