<?php

/**
 * This file is part of the Nextras\MailPanel library.
 * @license    New BSD
 * @link       https://github.com/nextras/mail-panel
 */

namespace Nextras\MailPanel;

use Latte;
use Nette\Http\Request;
use Nette\Mail\Message;
use Nette\Mail\MimePart;
use Nette\Object;
use Nette\Utils\Strings;
use Tracy\IBarPanel;


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

	/** @var Latte\Engine */
	private $latteEngine;


	/**
	 * @param string  $tempDir
	 * @param Request $request
	 * @param IMailer $mailer
	 * @param int     $messagesLimit
	 */
	public function __construct($tempDir, Request $request, IMailer $mailer, $messagesLimit = self::DEFAULT_COUNT)
	{
		$this->tempDir = $tempDir;
		$this->request = $request;
		$this->mailer = $mailer;
		$this->messagesLimit = $messagesLimit;

		$query = $request->getQuery('mail-panel');
		$mailId = $request->getQuery('mail-panel-mail');

		if ($query === 'detail' && ctype_digit($mailId)) {
			$this->handleDetail($mailId);

		} elseif ($query === 'source' && ctype_digit($mailId)) {
			$this->handleSource($mailId);

		} elseif ($query === 'delete') {
			$this->handleDeleteAll();

		} elseif (ctype_digit($query)) {
			$this->handleDeleteOne($query);
		}

		$attachment = $request->getQuery('mail-panel-attachment');

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
		$label = $count . ' sent email' . ($count === 1 ? '' : 's');

		return '<span title="Mail Panel">' .
			'<svg viewBox="0 0 16 16">' .
  			'	<rect x="0" y="2" width="16" height="11" rx="1" ry="1" fill="#588ac8"/>' .
  			'	<rect x="1" y="3" width="14" height="9" fill="#eef3f8"/>' .
  			'	<rect x="2" y="4" width="12" height="7" fill="#dcebfe"/>' .
  			'	<path d="M 2 11 l 4 -4 q 2 -2 4 0 l 4 4" stroke="#bbccdd" fill="none"/>' .
  			'	<path d="M 2 4 l 4 4 q 2 2 4 0 l 4 -4" stroke="#85aae2" fill="#dee8f7"/>' .
			'</svg>' .
			'<span class="tracy-label">' . $label . '</span></span>';
	}


	/**
	 * @inheritdoc
	 */
	public function getPanel()
	{
		$latte = $this->getLatteEngine();
		$url = $this->request->getUrl();
		$baseUrl = substr($url->getPath(), strrpos($url->getScriptPath(), '/') + 1);

		return $latte->renderToString(__DIR__ . '/MailPanel.latte', array(
			'baseUrl'  => $baseUrl,
			'messages' => $this->mailer->getMessages($this->messagesLimit),
		));
	}


	/**
	 * @return Latte\Engine
	 */
	private function getLatteEngine()
	{
		if (!isset($this->latteEngine)) {
			$this->latteEngine = new Latte\Engine();
			$this->latteEngine->setTempDirectory($this->tempDir);
			$this->latteEngine->setAutoRefresh(FALSE);

			$this->latteEngine->addFilter('attachmentLabel', function (MimePart $attachment) {
				$contentDisposition = $attachment->getHeader('Content-Disposition');
				$contentType = $attachment->getHeader('Content-Type');
				$matches  = Strings::match($contentDisposition, '#filename="(.+?)"#');
				return ($matches ? "$matches[1] " : '') . "($contentType)";
			});

			$this->latteEngine->addFilter('plainText', function (MimePart $part) {
				$ref = new \ReflectionProperty('Nette\Mail\MimePart', 'parts');
				$ref->setAccessible(TRUE);

				$queue = array($part);
				for ($i = 0; $i < count($queue); $i++) {
					/** @var MimePart $subPart */
					foreach ($ref->getValue($queue[$i]) as $subPart) {
						$contentType = $subPart->getHeader('Content-Type');
						if (Strings::startsWith($contentType, 'text/plain') && $subPart->getHeader('Content-Transfer-Encoding') !== 'base64') { // Take first available plain text
							return (string) $subPart->getBody();
						} elseif (Strings::startsWith($contentType, 'multipart/alternative')) {
							$queue[] = $subPart;
						}
					}
				}

				return $part->getBody();
			});
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
}
