<?php

namespace Nextras\MailPanel;

use Nette\Object;
use Nette\DateTime;
use Nette\InvalidStateException;
use Nette\Utils\Finder;
use Nette\Mail\Message;


/**
 * Session mailer - emails are stored into files
 *
 * @author  Jan Skrasek
 * @license MIT
 */
class FileMailer extends Object implements IMailer
{
	/** @var string */
	private $tempDir;

	/** @var string */
	private $prefix;

	/** @var array */
	private $files = array();


	public function __construct($tempDir)
	{
		$now = new DateTime();
		$this->tempDir = $tempDir;
		$this->prefix = $now->format("YmdHis") . '-';
	}


	/**
	 * Store mails to files.
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


	public function getMessageCount()
	{
		$this->findMails();
		return count($this->files);
	}


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


	public function deleteByIndex($index)
	{
		$this->findMails();
		if (!isset($this->files[$index])) {
			throw new \InvalidArgumentException('Undefined index');
		}

		@unlink($this->files[$index]);
	}


	public function clear()
	{
		foreach ($this->files as $file) {
			@unlink($file);
		}
	}


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
