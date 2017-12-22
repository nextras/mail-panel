<?php

use Nextras\MailPanel\SessionMailer;

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/MailerTestCase.php';

/**
 * Class SessionMailerTest
 *
 * @testCase SessionMailer
 */
class SessionMailerTest extends MailerTestCase
{

    /**
     * @return \Nextras\MailPanel\IPersistentMailer
     */
    public function createMailerInstance()
    {
        return new SessionMailer($this->createSession());
    }

    private function createSession()
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
