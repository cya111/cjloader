<?php
/**
 * Created by RubikIntegration Team.
 * Date: 9/27/12
 * Time: 6:49 AM
 * Question? Come to our website at http://rubikintegration.com
 */

namespace plugins\riCjLoader;

use plugins\riPlugin\Plugin;

class BrowserHandler implements BrowserInterface{

    protected $_browser;

    public function __construct(){
        $this->_browser = new \plugins\riCjLoader\vendor\Browser();
    }

    public function isBrowser($browser_name){
       return $this->_browser->isBrowser($browser_name);
    }

    public function getVersion(){
        return $this->_browser->getVersion();
    }
}