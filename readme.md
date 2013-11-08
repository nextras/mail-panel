MailPanel
=========

Nette Debug panel for sent emails.

* Authors: Jan Marek, Jan Drábek
* License: New BSD

Based on http://git.yavanna.cz/p/mailpanel/ by Jan Drábek.

Installation
------------

Install library via composer:

```
composer require nextras/mail-panel
```

Session mailer has to be set as service "nette.mailer" in development configuration.

```
services:
	nette.mailer:
		class: Nextras\MailPanel\SessionMailer
```

Add MailPanel to debug bar:

```
nette:
	debugger:
		bar:
			- Nextras\MailPanel\MailPanel
```

Usage
-----

Messages has to be sent by injected mailer or created by Nette mail factory.

```php
class ExamplePresenter extends BasePresenter
{

	private $mailer;

	public function injectMailer(\Nette\Mail\IMailer $mailer)
	{
		$this->mailer = $mailer;
	}

	public function renderDefault()
	{
		// recommended way
		$mail = new Nette\Mail\Message();
		$mail->setFrom('foo@bar.net');
		$mail->addTo('john@doe.cz');
		$mail->setSubject('Subject');
		$mail->setBody('Message body');

		$this->mailer->send($mail);

		// or
		// $mail = $this->context->createNette__mail();
		// $mail->send();
	}

}
```
