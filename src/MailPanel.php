<?php

namespace Nextras\MailPanel;

use Nette\Http\Request;
use Nette\Object;
use Tracy\IBarPanel;
use Latte;


/**
 * Extension for Nette debugger bar which shows sent emails
 *
 * @author Jan Drábek
 * @author Jan Marek
 * @copyright New BSD
 */
class MailPanel extends Object implements IBarPanel
{
	/** @const int */
	const DEFAULT_COUNT = 5;

	/** @var SessionMailer */
	private $mailer;

	/** @var Request */
	private $request;

	/** @var int */
	private $messagesLimit;

	/** @var string|NULL */
	private $tempDir;


	public function __construct($tempDir, IMailer $mailer, Request $request, $messagesLimit = self::DEFAULT_COUNT)
	{
		$this->mailer = $mailer;
		$this->request = $request;
		$this->messagesLimit = $messagesLimit;
		$this->tempDir = $tempDir;

		$query = $request->getQuery("mail-panel");

		if ($query === 'delete') {
			$this->handleDeleteAll();
		} elseif (is_numeric($query)) {
			$this->handleDelete($query);
		}
	}


	/**
	 * Returns panel ID
	 * @return string
	 */
	public function getId()
	{
		return __CLASS__;
	}


	/**
	 * Renders HTML code for custom tab
	 * @return string
	 */
	public function getTab()
	{
		$count = $this->mailer->getMessageCount();

		return '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAITSURBVBgZpcHLThNhGIDh9/vn7/RApwc5VCmFWBPi1mvwAlx7BW69Afeu3bozcSE7E02ILjCRhRrds8AEbKVS2gIdSjvTmf+TYqLu+zyiqszDMCf75PnnnVwhuNcLpwsXk8Q4BYeSOsWpkqrinJI6JXVK6lSRdDq9PO+19vb37XK13Hj0YLMUTVVyWY//Cf8IVwQEGEeJN47S1YdPo4npDpNmnDh5udOh1YsZRcph39EaONpnjs65oxsqvZEyTaHdj3n2psPpKDLBcuOOGUWpZDOG+q0S7751ObuYUisJGQ98T/Ct4Fuo5IX+MGZr95jKjRKLlSxXxFxOEmaaN4us1Upsf+1yGk5ZKhp8C74H5ZwwCGO2drssLZZo1ouIcs2MJikz1oPmapHlaoFXH1oMwphyTghyQj+MefG+RblcoLlaJG/5y4zGCTMikEwTctaxXq/w9kuXdm9Cuzfh9acujXqFwE8xmuBb/hCwl1GKAnGccDwIadQCfD9DZ5Dj494QA2w2qtQW84wmMZ1eyFI1QBVQwV5GiaZOpdsPaSwH5HMZULi9UmB9pYAAouBQbMHHrgQcnQwZV/KgTu1o8PMgipONu2t5KeaNiEkxgAiICDMCCFeEK5aNauAOfoXx8KR9ZOOLk8P7j7er2WBhwWY9sdbDeIJnwBjBWBBAhGsCmiZxPD4/7Z98b/0QVWUehjkZ5vQb/Un5e/DIsVsAAAAASUVORK5CYII=">' .
			$count . ' sent email' . ($count === 1 ? '' : 's');
	}


	/**
	 * Show content of panel
	 * @return string
	 */
	public function getPanel()
	{
		$latte = new Latte\Engine;
		$latte->setTempDirectory($this->tempDir);

		return $latte->renderToString(__DIR__ . '/MailPanel.latte', array(
			'baseUrl'  => $this->request->getUrl()->getBaseUrl(),
			'messages' => $this->mailer->getMessages($this->messagesLimit),
		));
	}


	private function returnBack()
	{
		header('Location: ' . $this->request->getReferer());
		exit;
	}


	private function handleDeleteAll()
	{
		$this->mailer->clear();
		$this->returnBack();
	}


	private function handleDelete($id)
	{
		$this->mailer->deleteByIndex($id);
		$this->returnBack();
	}

}
