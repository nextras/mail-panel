<?php

namespace Nextras\MailPanel;

use Nette;


interface IMailer extends Nette\Mail\IMailer
{

	function getMessageCount();

	function getMessages($limit);

	function clear();

	function deleteByIndex($index);

}
