<?php declare(strict_types = 1);

use Nette\Http\Request;
use Nette\Http\UrlScript;
use Nette\Mail\Message;
use Nextras\MailPanel\IPersistentMailer;
use Nextras\MailPanel\MailPanel;
use Tester\Assert;
use Tester\Helpers;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';


class MailPanelTest extends TestCase
{
	protected function setUp(): void
	{
		Helpers::purge(TEMP_DIR);
	}


	public function testRendersHtmlBodyFromTextHtmlMessageBody(): void
	{
		$message = (new Message())
			->setContentType('text/html', 'UTF-8')
			->setBody('<h1>Hello from HTML</h1>');

		$output = $this->renderMessageBody($message);

		Assert::contains('<h1>Hello from HTML</h1>', $output);
		Assert::notContains('&lt;h1&gt;Hello from HTML&lt;/h1&gt;', $output);
	}


	public function testPanelStoresEscapedHtmlPreviewInDataAttribute(): void
	{
		$message = (new Message())
			->setSubject('Panel preview')
			->setHtmlBody('<h1>Hello from HTML</h1>');

		$panel = $this->createPanel(new ArrayPersistentMailer(['message-id' => $message]));
		$output = $panel->getPanel();

		Assert::contains('data-content="&lt;h1&gt;Hello from HTML&lt;/h1&gt;"', $output);
	}


	private function renderMessageBody(Message $message): string
	{
		$panel = $this->createPanel(new NullPersistentMailer());

		$ref = new ReflectionMethod(MailPanel::class, 'getLatte');
		$ref->setAccessible(true);
		$latte = $ref->invoke($panel);

		return $latte->renderToString(__DIR__ . '/../src/MailPanel.body.latte', ['message' => $message]);
	}


	private function createPanel(IPersistentMailer $mailer): MailPanel
	{
		return new MailPanel(
			TEMP_DIR . '/latte',
			new Request(new UrlScript('http://localhost/index.php')),
			$mailer,
		);
	}
}


class NullPersistentMailer implements IPersistentMailer
{
	public function send(Message $message): void
	{
	}


	public function getMessageCount(): int
	{
		return 0;
	}


	public function getMessage(string $messageId): Message
	{
		throw new RuntimeException('No messages available.');
	}


	public function getMessages(int $limit): array
	{
		return [];
	}


	public function deleteOne(string $messageId): void
	{
	}


	public function deleteAll(): void
	{
	}
}


class ArrayPersistentMailer implements IPersistentMailer
{
	/**
	 * @param Message[] $messages
	 */
	public function __construct(
		private array $messages,
	) {
	}


	public function send(Message $message): void
	{
	}


	public function getMessageCount(): int
	{
		return count($this->messages);
	}


	public function getMessage(string $messageId): Message
	{
		return $this->messages[$messageId];
	}


	public function getMessages(int $limit): array
	{
		return array_slice($this->messages, 0, $limit, true);
	}


	public function deleteOne(string $messageId): void
	{
	}


	public function deleteAll(): void
	{
	}
}

(new MailPanelTest)->run();
