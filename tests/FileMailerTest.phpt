<?php declare(strict_types = 1);

use Nextras\MailPanel\IPersistentMailer;
use Nextras\MailPanel\FileMailer;

require __DIR__ . '/bootstrap.php';


class FileMailerTest extends MailerTestCase
{
	public function createMailerInstance(): IPersistentMailer
	{
		return new FileMailer(TEMP_DIR);
	}
}

(new FileMailerTest)->run();
