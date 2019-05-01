<?php declare(strict_types = 1);

use Nextras\MailPanel\IPersistentMailer;
use Tester\TestCase;
use Tester\Assert;
use Nette\Mail\Message;


abstract class MailerTestCase extends TestCase
{
	public function testMailer(): void
	{
		$mailer = $this->createMailerInstance();

		$mailer->send((new Message)->setSubject('Subject 1'));
		$mailer->send((new Message)->setSubject('Subject 2'));
		$mailer->send((new Message)->setSubject('Subject 2'));

		Assert::same(3, $mailer->getMessageCount());
		Assert::count(1, $mailer->getMessages(1));
		Assert::count(2, $mailer->getMessages(2));
		Assert::count(3, $mailer->getMessages(3));

		$deletedMessageId = (string) array_keys($mailer->getMessages(2))[0];
		$mailer->deleteOne($deletedMessageId);
		Assert::same(2, $mailer->getMessageCount());

		$ids = array_keys($mailer->getMessages($mailer->getMessageCount()));
		Assert::false(in_array($deletedMessageId, $ids, true));

		$mailer->deleteAll();
		Assert::same(0, $mailer->getMessageCount());
		Assert::same([], $mailer->getMessages(1));
	}


	abstract public function createMailerInstance(): IPersistentMailer;
}
