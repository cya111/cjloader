<?php
namespace plugins\riCjLoader;

use plugins\riCore\Event;
use plugins\riCore\PluginCore;
use plugins\riPlugin\Plugin;


class RiCjLoader extends PluginCore{
	public function init(){
		Plugin::get('dispatcher')->addListener(\plugins\riCore\Events::onPageEnd, array($this, 'onPageEnd'), -9999);

        global $autoLoadConfig;
        // we want to include the loader into the view for easy access, we need to do it after the template is loaded
        $autoLoadConfig[200][] = array('autoType' => 'require', 'loadFile' => __DIR__ . '/lib/init_includes.php');
	}	
    
	public function onPageEnd(Event $event)
    {
    	$content = &$event->getContent();
    	Plugin::get('riCjLoader.Loader')->injectAssets($content);
        // extend here the functionality of the core
        // ...
    }
}