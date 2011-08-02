<?php
/**
 * Service presenter for Mail Panel
 * @author Jan Drábek
 * @version 1.0
 * @copyright GNU-GPLv3
 */
class MailPanelPresenter extends Presenter {
	/** @var Session */
	private $container;
	
	public function __construct() {
		$this->container = Environment::getSession("session-dummy");
	}
	
	public function actionDeleteAll() {
		unset($this->container->queue);
		$this->returnWay();
	}
	public function actionDelete($id) {
		foreach($this->container->queue as $key => $row) {
			if($key == $id)	$this->container->queue->offsetUnset($key);
		}
		$this->returnWay();
	}
	
	public function actionShowMore($count) {
		if (!is_numeric($count) || $count <= 0) {
			return;
		}
		$this->container->count = $this->container->count+$count;
		$this->returnWay();
	}
	
	public function actionShowLess($count) {
		if (!is_numeric($count) || $count <= 0) {
			return;
		}
		$this->container->count = $this->container->count-$count;
		$this->returnWay();
	}
	
	public function actionDetail($id) {
		// Disable showing debugger etc
		Debugger::enable(Debugger::PRODUCTION);
		// Template and rendering
		$template = new FileTemplate(__DIR__. "/MailPanelDetail.latte");
		$template->registerFilter(new LatteFilter);
		$template->mail = $this->container->queue->offsetGet($id);
		$template->render();
		$this->terminate();
	}
	
	private function returnWay() {
		$this->redirectUrl(Environment::getHttpRequest()->getReferer());
		$this->terminate();
	}
}