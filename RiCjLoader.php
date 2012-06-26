<?php
namespace plugins\riCjLoader;

use plugins\riCore\Event;
use plugins\riCore\PluginCore;
use plugins\riPlugin\Plugin;


class RiCjLoader extends PluginCore{
	public function init(){
		Plugin::get('dispatcher')->addListener(\plugins\riCore\Events::onPageEnd, array($this, 'onPageEnd'));
	}	
    
	public function onPageEnd(Event $event)
    {        
    	$content = &$event->getContent();
    	Plugin::get('riCjLoader.Loader')->injectAssets($content);
        // extend here the functionality of the core
        // ...
    }
}