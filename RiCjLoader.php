<?php
namespace plugins\riCjLoader;

use plugins\riCore\PluginCore;
use plugins\riPlugin\Plugin;

class RiCjLoader extends PluginCore{
	public function init(){
		$listener = Plugin::get('riCore.Listener');
		$this->dispatcher->addListener(\plugins\riCore\Events::onPageEnd, array($listener, 'onPageEnd'));
	}	
}