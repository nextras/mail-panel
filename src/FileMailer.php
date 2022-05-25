<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras\MailPanel library.
 * @license    New BSD
 * @link       https://github.com/nextras/mail-panel
 */

namespace Nextras\MailPanel;

use Nette;
use Nette\Mail\Message;
use Nette\Utils\FileSystem;


/**
 * File mailer - emails are stored into files
 */
class FileMailer implements IPersistentMailer
{
	use Nette\SmartObject;

	/** @var string */
	private $tempDir;

	/** @var string[]|NULL */
	private $files;


	public function __construct(string $tempDir)
	{
		$this->tempDir = $tempDir;
	}


	/**
	 * Stores mail to a file.
	 */
	public function send(Message $message): void
	{
		// get message with generated html instead of set FileTemplate etc
		$ref = new \ReflectionMethod('Nette\Mail\Message', 'build');
		$ref->setAccessible(true);

		/** @var Message */
		$builtMessage = $ref->invoke($message);

		$time = date('YmdHis');
		$hash = substr(md5($builtMessage->getHeader('Message-ID')), 0, 6);
		$path = "{$this->tempDir}/{$time}-{$hash}.mail";
		FileSystem::write($path, serialize($builtMessage));
		$this->files = null;
	}


	public function getMessageCount(): int
	{
		return count($this->findFiles());
	}


	public function getMessage(string $messageId): Message
	{
		$files = $this->findFiles();
		if (!isset($files[$messageId])) {
			throw new \RuntimeException("Unable to find mail with ID $messageId");
		}

		return $this->readMail($files[$messageId]);
	}


	public function getMessages(int $limit): array
	{
		$files = array_slice($this->findFiles(), 0, $limit, true);
		$mails = array_map([$this, 'readMail'], $files);

		return $mails;
	}


	public function deleteOne(string $messageId): void
	{
		$files = $this->findFiles();
		if (!isset($files[$messageId])) {
			return; // assume that mail was already deleted
		}

		FileSystem::delete($files[$messageId]);
		$this->files = null;
	}


	public function deleteAll(): void
	{
		foreach ($this->findFiles() as $file) {
			FileSystem::delete($file);
		}
		$this->files = null;
	}


	/**
	 * @return string[]
	 */
	private function findFiles(): array
	{
		if ($this->files === null) {
			$this->files = [];
			foreach (glob("{$this->tempDir}/*.mail") ?: [] as $file) {
				$messageId = substr($file, -11, 6);
				$this->files[$messageId] = $file;
			}
			arsort($this->files);
		}

		return $this->files;
	}


	private function readMail(string $path): Message
	{
		$content = file_get_contents($path);
		if ($content === false) {
			throw new \RuntimeException("Unable to read message stored in file '$path'");
		}

		$message = unserialize($content);
		if (!$message instanceof Message) {
			throw new \RuntimeException("Unable to deserialize message stored in file '$path'");
		}

		return $message;
	}
}
