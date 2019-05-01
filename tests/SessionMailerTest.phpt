<?php declare(strict_types = 1);

use Nette\Http\Session;
use Nextras\MailPanel\IPersistentMailer;
use Nextras\MailPanel\SessionMailer;

require __DIR__ . '/bootstrap.php';


class SessionMailerTest extends MailerTestCase
{
	public function createMailerInstance(): IPersistentMailer
	{
		return new SessionMailer($this->createSession());
	}


	private function createSession(): Session
	{
		$sessionSection = Mockery::mock('alias:Nette\Http\SessionSection');

		$session = Mockery::mock('Nette\Http\Session');
		$session->shouldReceive('getSection')->andReturn($sessionSection);
		$session->shouldReceive('getId')->andReturn('session_id_1');
		$session->shouldReceive('isStarted')->andReturn(true);

		return $session;
	}
}

(new SessionMailerTest)->run();
