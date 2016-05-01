# Nextras Mail Panel

[![Downloads this Month](https://img.shields.io/packagist/dm/nextras/mail-panel.svg?style=flat)](https://packagist.org/packages/nextras/mail-panel)
[![Stable version](http://img.shields.io/packagist/v/nextras/mail-panel.svg?style=flat)](https://packagist.org/packages/nextras/mail-panel)

Tracy panel which mocks Nette\Mail\IMailer and displays sent mails in Tracy.


### Screenshot

<img src="doc/assets/screenshot.png" width="681">


### Installation

Install library via composer:

```bash
composer require nextras/mail-panel
```

Mailer has to be set as service "nette.mailer" in development configuration.

```yml
services:
	nette.mailer: Nextras\MailPanel\FileMailer(%tempDir%/mail-panel/mails)
```

Add MailPanel to Tracy bar:

```yml
tracy:
	bar:
		- Nextras\MailPanel\MailPanel(%tempDir%/mail-panel/latte)
```


### Usage

Messages has to be sent by injected instance of `Nette\Mail\IMailer`.

```php
class ExamplePresenter extends BasePresenter
{
	/** @var Nette\Mail\IMailer @inject */
	public $mailer;


	public function actionSendMail()
	{
		$mail = new Nette\Mail\Message();
		$mail->setFrom('john.doe@example.com', 'John Doe');
		$mail->addTo('jack@example.com');
		$mail->setSubject('Order Confirmation');
		$mail->setHtmlBody('Hello Jack,<br>Your order has been accepted.');

		$this->mailer->send($mail);
	}
}
```


### License

*Based on [MailPanel by Jan Drábek](https://packagist.org/packages/jandrabek/nette-mailpanel).*

New BSD License. See full [license](license.md).
