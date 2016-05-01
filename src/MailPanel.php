<?php

/**
 * This file is part of the Nextras\MailPanel library.
 * @license    New BSD
 * @link       https://github.com/nextras/mail-panel
 */

namespace Nextras\MailPanel;

use Latte\Engine;
use Nette\Http\Request;
use Nette\Mail\Message;
use Nette\Mail\MimePart;
use Nette\Object;
use Nette\Utils\Strings;
use Tracy\IBarPanel;
use Latte;


/**
 * Extension for Tracy bar which shows sent emails
 */
class MailPanel extends Object implements IBarPanel
{
	/** @const int */
	const DEFAULT_COUNT = 5;

	/** @var Request */
	private $request;

	/** @var IMailer */
	private $mailer;

	/** @var int */
	private $messagesLimit;

	/** @var string|NULL */
	private $tempDir;

	/** @var Engine */
	private $latteEngine;


	/**
	 * @param string  $tempDir
	 * @param Request $request
	 * @param IMailer $mailer
	 * @param int     $messagesLimit
	 */
	public function __construct($tempDir, Request $request, IMailer $mailer, $messagesLimit = self::DEFAULT_COUNT)
	{
		$this->request = $request;
		$this->mailer = $mailer;
		$this->messagesLimit = $messagesLimit;
		$this->tempDir = $tempDir;

		$query = $request->getQuery("mail-panel");
		$mailId = $request->getQuery("mail-panel-mail");

		if ($query === 'detail' && is_numeric($mailId)) {
			$this->handleDetail($mailId);
		} elseif ($query === 'source' && is_numeric($mailId)) {
			$this->handleSource($mailId);
		} elseif ($query === 'delete') {
			$this->handleDeleteAll();
		} elseif (is_numeric($query)) {
			$this->handleDeleteOne($query);
		}

		$attachment = $request->getQuery("mail-panel-attachment");

		if ($attachment !== NULL && $mailId !== NULL) {
			$this->handleAttachment($mailId, $attachment);
		}
	}


	/**
	 * Returns panel ID
	 * @return string
	 */
	public function getId()
	{
		return __CLASS__;
	}


	/**
	 * Renders HTML code for custom tab
	 * @return string
	 */
	public function getTab()
	{
		$count = $this->mailer->getMessageCount();

		return '<span title="Mail Panel">' .
			'<svg viewBox="0 0 16 16">' .
  			'	<rect x="0" y="2" width="16" height="11" rx="1" ry="1" fill="#588ac8"/>' .
  			'	<rect x="1" y="3" width="14" height="9" fill="#eef3f8"/>' .
  			'	<rect x="2" y="4" width="12" height="7" fill="#dcebfe"/>' .
  			'	<path d="M 2 11 l 4 -4 q 2 -2 4 0 l 4 4" stroke="#bbccdd" fill="none"/>' .
  			'	<path d="M 2 4 l 4 4 q 2 2 4 0 l 4 -4" stroke="#85aae2" fill="#dee8f7"/>' .
			'</svg>' .
			'<span class="tracy-label">' . $count . ' sent email' . ($count === 1 ? '' : 's') . '</span></span>';
	}


	/**
	 * @inheritdoc
	 */
	public function getPanel()
	{
		$latte = $this->getLatteEngine();

		return $latte->renderToString(__DIR__ . '/MailPanel.latte', array(
			'baseUrl'  => $this->request->getUrl()->getBaseUrl(),
			'messages' => $this->mailer->getMessages($this->messagesLimit),
		));
	}

	/**
	 * @return Latte\Engine
	 */
	private function getLatteEngine()
	{
		if (!isset($this->latteEngine)) {
			$this->latteEngine = new Engine;
			$this->latteEngine->setTempDirectory($this->tempDir);
		}
		return $this->latteEngine;
	}


	/**
	 * @return void
	 */
	private function returnBack()
	{
		header('Location: ' . $this->request->getReferer());
		exit;
	}


	/**
	 * @return void
	 */
	private function handleDeleteAll()
	{
		$this->mailer->clear();
		$this->returnBack();
	}


	/**
	 * @param  int $id
	 * @return void
	 */
	private function handleDeleteOne($id)
	{
		$this->mailer->deleteByIndex($id);
		$this->returnBack();
	}


	/**
	 * @param  int $mailId
	 * @param  int $attachmentId
	 */
	private function handleAttachment($mailId, $attachmentId)
	{
		$list = $this->mailer->getMessages($this->messagesLimit);
		if (!isset($list[$mailId])) {
			return;
		}

		$attachments = $list[$mailId]->getAttachments();
		if (!isset($attachments[$attachmentId])) {
			return;
		}

		$attachment = $attachments[$attachmentId];
		if (!$attachment->getHeader('Content-Type')) {
			return;
		}

		header('Content-Type: ' . $attachment->getHeader('Content-Type'));
		echo $attachment->getBody();
		exit;
	}


	/**
	 * @param  int $mailId
	 * @return void
	 */
	private function handleSource($mailId)
	{
		$list = $this->mailer->getMessages($this->messagesLimit);
		if (!isset($list[$mailId])) {
			return;
		}

		header('Content-Type: text/plain');
		echo $list[$mailId]->getEncodedMessage();
		exit;
	}


	/**
	 * @param  int $mailId
	 * @return void
	 */
	private function handleDetail($mailId)
	{
		$list = $this->mailer->getMessages($this->messagesLimit);
		if (!isset($list[$mailId])) {
			return;
		}

		header('Content-Type: text/html');
		$latte = $this->getLatteEngine();
		$latte->render(__DIR__ . '/MailPanel_body.latte', array('message' => $list[$mailId]));
		exit;
	}


	/**
	 * @param  Message $message
	 * @return mixed
	 */
	public static function extractPlainText(Message $message)
	{
		$propertyReflection = $message->getReflection()->getParentClass()->getProperty('parts');
		$propertyReflection->setAccessible(true);
		$parts = $propertyReflection->getValue($message);
		/** @var MimePart $part */
		foreach ($parts as $part) {
			if (Strings::startsWith($part->getHeader('Content-Type'), 'text/plain') && $part->getHeader('Content-Transfer-Encoding') !== 'base64') { // Take first available plain text
				return $part->getBody();
			}
		}
	}


	/**
	 * @param  Message $message
	 * @return bool
	 */
	public static function isPlainText(Message $message)
	{
		return $message->getHtmlBody() === NULL; // naive heuristic
	}
}
