<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras\MailPanel library.
 * @license    New BSD
 * @link       https://github.com/nextras/mail-panel
 */

namespace Nextras\MailPanel;

use Latte;
use Nette;
use Nette\Http;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Nette\Mail\MimePart;
use Nette\Utils\Strings;
use Tracy\Debugger;
use Tracy\IBarPanel;


/**
 * Extension for Tracy bar which shows sent emails
 */
class MailPanel implements IBarPanel
{
	use Nette\SmartObject;

	/** @const int */
	const DEFAULT_COUNT = 20;

	/** @var Http\IRequest */
	private $request;

	/** @var IPersistentMailer|NULL */
	private $mailer;

	/** @var int */
	private $messagesLimit;

	/** @var string|NULL */
	private $tempDir;

	/** @var Latte\Engine|NULL */
	private $latte;

	/** @var \ReflectionProperty|NULL */
	private $mimePartPartsProperty;


	public function __construct(?string $tempDir, Http\IRequest $request, Mailer $mailer, int $messagesLimit = self::DEFAULT_COUNT)
	{
		if (!$mailer instanceof IPersistentMailer) {
			return;
		}

		$this->tempDir = $tempDir;
		$this->request = $request;
		$this->mailer = $mailer;
		$this->messagesLimit = $messagesLimit;

		$this->tryHandleRequest();
	}


	/**
	 * Renders HTML code for custom tab
	 */
	public function getTab(): string
	{
		if ($this->mailer === null) {
			return '';
		}

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


	public function getPanel(): string
	{
		if ($this->mailer === null) {
			return '';
		}

		return $this->getLatte()->renderToString(__DIR__ . '/MailPanel.latte', [
			'panelId' => substr(md5(uniqid('', true)), 0, 6),
			'messages' => $this->mailer->getMessages($this->messagesLimit),
		]);
	}


	/**
	 * Run-time link helper
	 * @param array<string, string> $params
	 */
	public function getLink(string $action, array $params = []): string
	{
		$url = $this->request->getUrl();
		$baseUrl = substr($url->getPath(), strrpos($url->getScriptPath(), '/') + 1);

		$params = ['action' => $action] + $params;
		$query = [];
		foreach ($params as $key => $value) {
			$query["nextras-mail-panel-$key"] = $value;
		}

		return $baseUrl . '?' . http_build_query($query);
	}


	private function getLatte(): Latte\Engine
	{
		if ($this->latte === null) {
			$this->latte = new Latte\Engine();
			$this->latte->setAutoRefresh(false);

			if ($this->tempDir !== null) {
				$this->latte->setTempDirectory($this->tempDir);
			}

			$this->latte->addFunction('link', [$this, 'getLink']);

			$this->latte->addFilter('attachmentLabel', function (MimePart $attachment) {
				$contentDisposition = $attachment->getHeader('Content-Disposition');
				$contentType = $attachment->getHeader('Content-Type');
				$matches = Strings::match($contentDisposition, '#filename="(.+?)"#');
				return ($matches ? "$matches[1] " : '') . "($contentType)";
			});

			$this->latte->addFilter('plainText', function (MimePart $part) {
				$plainText = $this->findBodyByContentType($part, 'text/plain');
				if ($plainText !== null) {
					return $plainText;
				}

				return $this->decodeBody($part);
			});

			$this->latte->addFilter('previewHtml', function (MimePart $part): string {
				$htmlBody = $this->extractHtmlBody($part);
				if ($htmlBody !== '') {
					return $htmlBody;
				}

				$plainText = $this->findBodyByContentType($part, 'text/plain') ?? $this->decodeBody($part);
				return '<!doctype html>'
					. '<meta charset="utf-8">'
					. '<style>html,body{margin:0;padding:0;border:none;font-family:sans-serif;font-size:12px;white-space:pre;}body{padding:10px;}</style>'
					. '<body>' . htmlspecialchars($plainText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</body>';
			});
		}

		return $this->latte;
	}


	private function extractHtmlBody(MimePart $part): string
	{
		if ($part instanceof Message && $part->getHtmlBody() !== '') {
			return $part->getHtmlBody();
		}

		return $this->findBodyByContentType($part, 'text/html') ?? '';
	}


	private function findBodyByContentType(MimePart $part, string $contentTypePrefix): ?string
	{
		$queue = [$part];
		for ($i = 0; $i < count($queue); $i++) {
			$currentPart = $queue[$i];
			$contentType = $currentPart->getHeader('Content-Type');
			if (is_string($contentType) && Strings::startsWith(strtolower($contentType), $contentTypePrefix)) {
				return $this->decodeBody($currentPart);
			}

			/** @var MimePart $subPart */
			foreach ($this->getMimePartParts($currentPart) as $subPart) {
				$queue[] = $subPart;
			}
		}

		return null;
	}


	/**
	 * @return MimePart[]
	 */
	private function getMimePartParts(MimePart $part): array
	{
		if ($this->mimePartPartsProperty === null) {
			$this->mimePartPartsProperty = new \ReflectionProperty(MimePart::class, 'parts');
			if (PHP_VERSION_ID < 80100) {
				$this->mimePartPartsProperty->setAccessible(true);
			}
		}

		$parts = $this->mimePartPartsProperty->getValue($part);
		return is_array($parts) ? $parts : [];
	}


	private function decodeBody(MimePart $part): string
	{
		$body = $part->getBody();
		$transferEncoding = strtolower((string) $part->getHeader('Content-Transfer-Encoding'));

		if ($transferEncoding === MimePart::EncodingQuotedPrintable) {
			return quoted_printable_decode($body);
		}

		if ($transferEncoding === MimePart::EncodingBase64) {
			$decodedBody = base64_decode($body, true);
			return is_string($decodedBody) ? $decodedBody : $body;
		}

		return $body;
	}


	private function tryHandleRequest(): void
	{
		if (Debugger::$productionMode !== false) {
			return;
		}

		$action = $this->request->getQuery('nextras-mail-panel-action');
		$messageId = $this->request->getQuery('nextras-mail-panel-message-id');
		$attachmentId = $this->request->getQuery('nextras-mail-panel-attachment-id');

		if ($action === 'detail' && is_string($messageId)) {
			$this->handleDetail($messageId);

		} elseif ($action === 'source' && is_string($messageId)) {
			$this->handleSource($messageId);

		} elseif ($action === 'attachment' && is_string($messageId) && ctype_digit($attachmentId)) {
			$this->handleAttachment($messageId, (int) $attachmentId);

		} elseif ($action === 'delete-one' && is_string($messageId)) {
			$this->handleDeleteOne($messageId);

		} elseif ($action === 'delete-all') {
			$this->handleDeleteAll();
		}
	}


	private function handleDetail(string $messageId): void
	{
		assert($this->mailer !== null);
		$message = $this->mailer->getMessage($messageId);

		header('Content-Type: text/html');
		$this->getLatte()->render(__DIR__ . '/MailPanel.body.latte', ['message' => $message]);
		exit;
	}


	private function handleSource(string $messageId): void
	{
		assert($this->mailer !== null);
		$message = $this->mailer->getMessage($messageId);

		header('Content-Type: text/plain');
		echo $message->getEncodedMessage();
		exit;
	}


	private function handleAttachment(string $messageId, int $attachmentId): void
	{
		assert($this->mailer !== null);
		$attachments = $this->mailer->getMessage($messageId)->getAttachments();
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


	private function handleDeleteOne(string $id): void
	{
		assert($this->mailer !== null);
		$this->mailer->deleteOne($id);
		$this->returnBack();
	}


	private function handleDeleteAll(): void
	{
		assert($this->mailer !== null);
		$this->mailer->deleteAll();
		$this->returnBack();
	}


	private function returnBack(): void
	{
		$currentUrl = $this->request->getUrl();
		$refererUrl = $this->request->getHeader('referer');

		if ($refererUrl === null) {
			throw new \RuntimeException('Unable to redirect back because your browser did not send referrer');

		} elseif ($currentUrl->isEqual($refererUrl)) {
			throw new \RuntimeException('Unable to redirect back because it would create loop');
		}

		header('Location: ' . $refererUrl);
		exit;
	}
}
