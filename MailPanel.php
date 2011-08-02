<?php
/**
 * Extension for Debugger bar
 * 
 * @author Jan Drábek
 * @version 1.0
 * @copyright GNU-GPLv3
 */

class MailPanel extends Object implements IBarPanel {
	const VERSION = "1.0";
	/** @var bool */
	private static $registered = FALSE;
	
	/** @var Session */
	private static $container;
	
	public function __construct() {
		// Get mail storage
		self::$container = Environment::getSession("session-dummy");
		// Check (and fix) how many items is displayed
		if(empty(self::$container->count)) {
			self::$container->count = 3;
		} 
	}
	
	/**
	 * Returns panel ID.
	 * @return string
	 * @see Nette\IDebugPanel::getId()
	 */
	public function getId() {
		return "mail-panel";
	}

	/**
	 * Renders HTML code for custom tab.
	 * @return string
	 * @see Nette\IDebugPanel::getTab()
	 */
	public function getTab() {
		return '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAITSURBVBgZpcHLThNhGIDh9/vn7/RApwc5VCmFWBPi1mvwAlx7BW69Afeu3bozcSE7E02ILjCRhRrds8AEbKVS2gIdSjvTmf+TYqLu+zyiqszDMCf75PnnnVwhuNcLpwsXk8Q4BYeSOsWpkqrinJI6JXVK6lSRdDq9PO+19vb37XK13Hj0YLMUTVVyWY//Cf8IVwQEGEeJN47S1YdPo4npDpNmnDh5udOh1YsZRcph39EaONpnjs65oxsqvZEyTaHdj3n2psPpKDLBcuOOGUWpZDOG+q0S7751ObuYUisJGQ98T/Ct4Fuo5IX+MGZr95jKjRKLlSxXxFxOEmaaN4us1Upsf+1yGk5ZKhp8C74H5ZwwCGO2drssLZZo1ouIcs2MJikz1oPmapHlaoFXH1oMwphyTghyQj+MefG+RblcoLlaJG/5y4zGCTMikEwTctaxXq/w9kuXdm9Cuzfh9acujXqFwE8xmuBb/hCwl1GKAnGccDwIadQCfD9DZ5Dj494QA2w2qtQW84wmMZ1eyFI1QBVQwV5GiaZOpdsPaSwH5HMZULi9UmB9pYAAouBQbMHHrgQcnQwZV/KgTu1o8PMgipONu2t5KeaNiEkxgAiICDMCCFeEK5aNauAOfoXx8KR9ZOOLk8P7j7er2WBhwWY9sdbDeIJnwBjBWBBAhGsCmiZxPD4/7Z98b/0QVWUehjkZ5vQb/Un5e/DIsVsAAAAASUVORK5CYII=" /> Sent mails';
	}

	/**
	 * Renders HTML code for custom panel.
	 * @return string
	 * @see Nette\IDebugPanel::getPanel()
	 */
	public function getPanel() {
		ob_start();
		$template = new FileTemplate(__DIR__. "/MailPanel.latte");
		$template->registerFilter(new LatteFilter);
		$template->count = self::$container->count;
		$template->data = array();
		if(isSet(self::$container->queue) && self::$container->queue instanceof ArrayList) {
			$template->data = self::$container->queue;
		}
		$template->render();
		return ob_get_clean();
	}

	/**
	 * Register this panel
	 *
	 * @param Context of this application
	 */
	public static function register(IDiContainer $context)
	{
		if (self::$registered) {
			throw new InvalidStateException("Mail panel is already registered");
		}
		// Register panel
		Debugger::addPanel(new self);
		// Switch mailers
		$context->removeService("mailer");
		$context->addService("mailer", new SessionDummyMailer());
		// Set routes to service presenter
		$router = $context->getService("router");
		$router[] = new Route("mail-panel/delete-all", array(
			"presenter"		=> "MailPanel",
			"action"		=> "deleteAll"
		));
		$router[] = new Route("mail-panel/delete/<id>", array(
			"presenter"		=> "MailPanel",
			"action"		=> "delete"
		));
		$router[] = new Route("mail-panel/show-more/<count>", array(
			"presenter"		=> "MailPanel",
			"action"		=> "showMore"
		));
		$router[] = new Route("mail-panel/show-less/<count>", array(
			"presenter"		=> "MailPanel",
			"action"		=> "showLess"
		));
		$router[] = new Route("mail-panel/detail/<id>", array(
			"presenter"		=> "MailPanel",
			"action"		=> "detail"
		));
		self::$registered = TRUE;
	}
	
	/**
	 * Return mail with mailer set in context. (It should be SessionDummyMailer)
	 * @return Mail 
	 */
	public static function getMailPrototype() {
		$ret = new Mail();
		$ret->setMailer(Environment::getMailer());
		return $ret;
	}
}