<?php

use Nextras\MailPanel\FileMailer;

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/MailerTestCase.php';


/**
 * Class FileMailerTest
 *
 * @testCase FileMailer
 */
class FileMailerTest extends MailerTestCase
{

    /**
     * @return \Nextras\MailPanel\IPersistentMailer
     */
    public function createMailerInstance()
    {
        return new FileMailer(TEMP_DIR);
    }
}

(new FileMailerTest)->run();
