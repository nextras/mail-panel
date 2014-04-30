MailPanel
=========

Nette Debug panel for sent emails. Supports storing emails into
- files
- session

Based on http://git.yavanna.cz/p/mailpanel/ by Jan Drábek.

Installation
------------

Install library via composer:

```
composer require nextras/mail-panel
```

Mailer has to be set as service "nette.mailer" in development configuration.

```
services:
	nette.mailer:
		class: Nextras\MailPanel\FileMailer(%tempDir%/mails)
		# class: Nextras\MailPanel\SessionMailer
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

	public function injectMailer(Nette\Mail\IMailer $mailer)
	{
		$this->mailer = $mailer;
	}

	public function renderDefault()
	{
		$mail = new Nette\Mail\Message;
		$mail->setFrom('foo@bar.net');
		$mail->addTo('john@doe.cz');
		$mail->setSubject('Subject');
		$mail->setBody('Message body');

		$this->mailer->send($mail);
	}

}
```
