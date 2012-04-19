<?php

namespace plugins\riCjLoader;

/**
 * Required functions for the CSS/JS Loader
 *
 * @author yellow1912 (RubikIntegration.com)
 * @author John William Robeson, Jr <johnny@localmomentum.net>
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License V2.0
 *
 * NOTES:
 * All .php files can be manipulated by PHP when they're called, and are copied in-full to the browser page
 */

use plugins\riPlugin\Plugin;

class Loader
{
    protected $template,
    $page_directory = '',    
    $current_page = '',
    $request_type,
    $loaders = array(),
    $files = array(), 
    $loaded_libs = array(), 
    $loaded_files = array(), 
    $previous_file = array(), 
    $handlers = array(),    
    $options = array(
		'cdn' => true, 
		'loaders' => '*', 
		'status' => true,  
		'load_global' => true, 
    	'load_loaders' => true,
		'load_print' => true, 
		'minify' => true, 
		'minify_time' => 0, 
		'inheritance' => '',
        'dirs' => array(),
		'supported_externals' => array('http', 'https', '//')
    );

    function __construct()
    {
        global $page_directory, $request_type, $template;
        /*if (defined('MINIFY_STATUS')) {
            if (MINIFY_STATUS === 'true') {
                // @todo FIXME we shouldn't set the cache time until a minify file is successfully generated
                global $db;
                $this->options['minify'] = true;
                $now = time();
                $this->options['minify_time'] = (int)MINIFY_CACHE_TIME_LATEST;
                if($now - $this->options['minify_time'] > (int)MINIFY_CACHE_TIME_LENGHT){
                    $db->Execute("UPDATE ".TABLE_CONFIGURATION." SET configuration_value = $now WHERE configuration_key = 'MINIFY_CACHE_TIME_LATEST'");
                    $this->options['minify_time'] = $now;
                }
            }
        }*/
        $this->template = $template;
        $this->page_directory = $page_directory;
        $this->request_type = $request_type;      

        // temp hack for admin support
        if(IS_ADMIN_FLAG){
        	
        }
    }    

    function set($options){
        $this->options = array_merge($this->options, $options);
    }

    function get($key = ''){
        if(!empty($key))
        return isset($this->options[$key]) ? $this->options[$key] : false;
        else return $this->options;
    }

    /**
     *
     * Load the file or set of files or libs
     * @param array $file array(array('path' => 'path/to/file', 'type' => 'css'))
     * @param string $location allows loading the file at header/footer or current location
     */
    function load($files, $location = ''){

    	$files = (array)$files;
    	
        $previous_files = array();
        // rather costly operation here but we need to determine the location
        if(empty($location))
        $location = md5(serialize($files));            
            
        foreach($files as $file => $options){
            // user can either pass in the file as a string or an associative array (usually for php files)
            if(!is_array($options)) $file = $options;
            // we need to determine the file extension first
            $path_info = pathinfo($file);
                         
            switch($path_info['extension']){
                // lib? load the library
                case 'lib':
                    // we need to try loading the config file                    
                    $lib = str_replace('.lib', '', $file);
                    if (!in_array($lib, $this->loaded_libs) && file_exists(DIR_FS_CATALOG . 'plugins/riCjLoader/configs/' . $lib . '.php'))
                    {
                        $this->loaded_libs[] = $lib;
                        include (DIR_FS_CATALOG . 'plugins/riCjLoader/configs/' . $lib . '.php');
                        
                        $lib_versions = array_keys($libs[$lib]);
                        
                        // if options are passed in
                        if(is_array($options)){                            
                            if (isset($options['min']) && (($pos = array_search($options['min'], $lib_versions)) != 0))
                            {
                                $lib_versions = array_slice($lib_versions, $pos);
                            }
                            	
                            if (isset($options['max']) && (($pos = array_search($options['max'], $lib_versions)) < count($lib_versions)-1))
                            {
                                array_splice($lib_versions, $pos+1);
                            }                            
                        }
                        
                        if (empty($lib_versions))
                        {
                            // houston we have a problem
                            // TODO: we need to somehow print out the error in this case
                        }
                        else
                        {
                            // we prefer the latest version
                            $lib_version = end($lib_versions);
                            
                            // add the files
                            if (isset($libs[$lib][$lib_version]['css_files']))
                                foreach ($libs[$lib][$lib_version]['css_files'] as $css_file => $css_file_options)
                                {
                                    if($this->get('cdn') && isset($css_file_options['cdn'])){
                                        $file = $this->request_type == 'NONSSL' ? $css_file_options['cdn']['http'] : $css_file_options['cdn']['https'];
                                        $this->_load($previous_files, $file, $location, array('type' => 'css'));                          
                                    }
                                    else
                                    {
                                        $file = __DIR__ . '/../content/resources/' . $lib . '/' . $lib_version . '/' . (!empty($css_file_options['local']) ? $css_file_options['local'] : $css_file);
                                        $this->_load($previous_files, $file, $location, array('type' => 'css'));
                                    }
                                }

                            if (isset($libs[$lib][$lib_version]['jscript_files']))
                                foreach ($libs[$lib][$lib_version]['jscript_files'] as $jscript_file => $jscript_file_options)
                                {
                                    if($this->get('cdn') && isset($jscript_file_options['cdn'])){
                                        $file = $this->request_type == 'NONSSL' ? $jscript_file_options['cdn']['http'] : $jscript_file_options['cdn']['https'];
                                        $this->_load($previous_files, $file, $location, array('type' => 'jscript'));
                                    }
                                    else
                                    {
                                        $file = __DIR__ . '/../content/resources/' . $lib . '/' . $lib_version . '/' . (!empty($jscript_file_options['local']) ? $jscript_file_options['local'] : $jscript_file);
                                        $this->_load($previous_files, $file, $location, array('type' => 'jscript'));
                                    }
                                }
                        }
                    }
                    break;                   
                default:
                    $this->_load($previous_files, $file, $location, $options, $path_info);
                    break;
            }
        }
        
        // now we will have to echo out the string to be replaced here
        if($location != 'header' && $location != 'footer')
        echo  '<!-- ' . $location . ' -->';
    }

    private function _load(&$previous_files, $file, $location, $options, $path_info = array()){

        if(!is_array($options)) $options = array();        
            
        $type = isset($options['type']) ? $options['type'] : $path_info['extension'];
                      
        if(isset($options['inline'])) {$file = md5($options['inline']) . '.' . $type;}
        
        // for css, they MUST be loaded at header
        switch($type){
            case 'css':
                $location = 'header';
                break;
            case 'js':
                $type = 'jscript';
                break;
        }
                 
        $this->getHandler($type)->load($this->files, $this->loaded_files, $previous_files, $type, $file, $location, $options);
    } 

    private function getHandler($handler){
        if(!isset($this->handlers[$handler])){
            $this->handlers[$handler] = Plugin::get('riCjLoader.' . ucfirst($handler) . 'Handler');
        }
        return $this->handlers[$handler];
    }
    /**
     * 
     * Inject the assets into the content of the page
     * @param string $content
     */
    public function injectAssets(&$content){
        
        // set the correct base
        $this->setCurrentPage();
        
        if($this->get('load_global')) $this->loadGlobal();
        
        if($this->get('load_loaders')) $this->loadLoaders();
 
        foreach ($this->files as $type => $locations){
            foreach($locations as $location => $files){
                               
                // we may want to do some caching here
                
                //$cache = md5(serialize($files));
                
//                if(($cache_file = Plugin::get('riCache.Cache')->exists($cache_filename)) === false)
//                    $cache_file = Plugin::get('riCache.Cache')->getRelativePath(Plugin::get('riCache.Cache')->write($cache_filename, '', $inject_content));
                        
                $inject_content = $this->getHandler($type)->process($files, $type, $this);
            
                // inject
                switch($location){  
                    case 'header':              
                        $content = str_replace('</head>', $inject_content . '</head>', $content);
                        break;
                    case 'footer':                        
                        $content = str_replace('</body>', $inject_content . '</body>', $content);
                        break;
                    default:
                        $content = str_replace('<!-- ' . $location . ' -->', $inject_content, $content);
                        break;
                }
            }
        }                      
    }       
    
    /**
     * 
     * This function should return the assets in array format
     */
    public function getAssetsArray(){
        $result = array();
        foreach ($this->files as $type => $locations){
            foreach($locations as $location => $files){
                               
                // we may want to do some caching here               
                $result[$location][$type] = $this->getHandler($type)->processArray($files, $type, $this);
            }
        }
        
        return $result;
    }
    
    private function strposArray($haystack, $needles) {
        $pos = false;
        if ( is_array($needles) ) {
            foreach ($needles as $str) {
                if ( is_array($str) ) {
                    $pos = $this->strposArray($haystack, $str);
                } else {
                    $pos = strpos($haystack, $str);
                }
                if ($pos !== FALSE) {
                    break;
                }
            }
        } else {
            $pos = strpos($haystack, $needles);
        }
        return $pos;
    }     

    public function loadGlobal(){                
                
        /**
         * load all template-specific stylesheets, named like "style*.css", alphabetically
         */
        $files = $this->findAssetsByPattern('.css', 'css', '/^style/');        
        $this->load($files, 'header');

        /**
         * load all template-specific stylesheets, named like "style*.php", alphabetically
         */
        $files = $this->findAssetsByPattern('.php', 'css', '/^style/');
        $this->load($files, 'header');

        /**
         * load all site-wide jscript_*.js files from includes/templates/YOURTEMPLATE/jscript, alphabetically
         */
        $files = $this->findAssetsByPattern('.js', 'jscript', '/^jscript_/');
        $this->load($files, 'footer');

        /**
         * include content from all site-wide jscript_*.php files from includes/templates/YOURTEMPLATE/jscript, alphabetically.
         */
        $files = $this->findAssetsByPattern('.php', 'jscript', '/^jscript_/');
        $this->load($files, 'footer');
        
    	/**
         * TODO: we shouldn't use $_GET here, it breaks the encapsulation
         * load stylesheets on a per-page/per-language/per-product/per-manufacturer/per-category basis. Concept by Juxi Zoza.
         */
        $manufacturers_id = (isset($_GET['manufacturers_id'])) ? $_GET['manufacturers_id'] : '';
        $tmp_products_id = (isset($_GET['products_id'])) ? (int)$_GET['products_id'] : '';
        $tmp_pagename = ($this_is_home_page) ? 'index_home' : $this->current_page;
        $sheets_array = array('/' . $_SESSION['language'] . '_stylesheet',
								'/' . $tmp_pagename,
								'/' . $_SESSION['language'] . '_' . $tmp_pagename,
	                        '/c_' . $cPath,
	                        '/' . $_SESSION['language'] . '_c_' . $cPath,
	                        '/m_' . $manufacturers_id,
	                        '/' . $_SESSION['language'] . '_m_' . (int)$manufacturers_id,
	                        '/p_' . $tmp_products_id,
	                        '/' . $_SESSION['language'] . '_p_' . $tmp_products_id
        );
        
        foreach ($sheets_array as $key => $value) {
            $perpagefile = $this->getAssetDir('.css', 'css') . $value . '.css';
            if (file_exists($perpagefile)) $this->load(array($perpagefile => array('type' => 'css')), 'header');

            $perpagefile = $this->getAssetDir('.php', 'css') . $value . '.php';
            if (file_exists($perpagefile)) $this->load(array($perpagefile => array('type' => 'css')), 'header');

            $perpagefile = $this->getAssetDir('.js', 'jscript') . $value . '.js';
            if (file_exists($perpagefile)) $this->load(array($perpagefile => array('type' => 'jscript')), 'footer');

            $perpagefile = $this->getAssetDir('.php', 'jscript') . $value . '.php';
            if (file_exists($perpagefile)) $this->load(array($perpagefile => array('type' => 'jscript')), 'jscript');

        }

        /**
         * load printer-friendly stylesheets -- named like "print*.css", alphabetically
         */
        if($this->get('load_print')) {
            $directory_array = $this->findAssetsByPattern('.css','css', '/^print/');
            // TODO: custom processing this
            foreach ($directory_array as $key => $value) {
                $this->load(array($key => array('type' => 'css', 'media' => 'print')), 'header');                
            }
        }

        /*
        if (file_exists(DIR_FS_CATALOG . 'plugins/riCjLoader/lib/browser.php') && floatval(phpversion()) > 5) {
            include(DIR_FS_CATALOG . 'plugins/riCjLoader/lib/browser.php');
            $browser = new _Browser();
            $browser_name = preg_replace("/[^a-zA-Z0-9s]/", "-", strtolower($browser->getBrowser()));
            $browser_version = floor($browser->getVersion());

            // this is to make it compatible with the other ie css hack
            if ($browser->getBrowser() == $browser->BROWSER_IE) {
                $browser_name = 'ie';
            }

            // get the browser specific files
            $files = $this->findAssets('.css', 'css', "/^{$browser_name}-/", -100);
            $this->addAssets($files, 'css');

            $files = $this->findAssets('.js', 'jscript', "/^{$browser_name}-/", -500);
            $this->addAssets($files, 'jscript');

            // get the browser version specific files
            $files = $this->findAssets('.css', 'css', "/^{$browser_name}{$browser_version}-/", -100);
            $this->addAssets($files, 'css');

            $directory_array = $this->findAssets('.js', 'jscript', "/^{$browser_name}{$browser_version}-/", -500);
            $this->addAssets($files, 'jscript');
        }
		*/

        /**
         * load all page-specific jscript_*.js files from includes/modules/pages/PAGENAME, alphabetically
         */
        $files = $this->template->get_template_part($page_directory, '/^jscript_/', '.js');        
        foreach ($files as $key => $value) {
            $this->load(array("$page_directory/$value" => array('type' => 'jscript')), 'jscript');            
        }

        /**
         * include content from all page-specific jscript_*.php files from includes/modules/pages/PAGENAME, alphabetically.
         */        
        $files = $this->template->get_template_part($page_directory, '/^jscript_/', '.php');
        foreach ($files as $key => $value) {
            $this->load(array("$page_directory/$value" => array('type' => 'jscript')), 'jscript');            
        }
    }

    /**
     * Get asset directory
     */
    function getAssetDir($extension, $directory, $template = DIR_WS_TEMPLATE)
    {
        return $this->template->get_template_dir($extension, $template, $this->current_page, $directory);
    }

    /**
     * Find asset files in a template directory
     *
     * @param string extension - file extension to look for
     * @param directory - subdirectory of the template containing the assets
     */    
    function findAssetsByPattern($extension, $directory, $file_pattern = '')
    {
        $templateDir = $this->getAssetDir($extension, $directory, DIR_WS_TEMPLATE);
        $allFiles = $this->template->get_template_part($templateDir, $file_pattern, $extension);

        if($this->get('inheritance') != ''){
            $defaultDir = $this->getAssetDir($extension, $directory, DIR_WS_TEMPLATES. $this->get('inheritance'));
            $allFiles = array_unique(array_merge($this->template->get_template_part($defaultDir, $file_pattern, $extension),$allFiles));
        }

        $files = array();
        foreach ($allFiles as $file) {
            // case 1: file is in server but full path not passed, assuming it is under corresponding template css/js folder
            if(file_exists(DIR_FS_CATALOG.DIR_WS_TEMPLATE.$directory.'/'.$file)){
                $files[DIR_WS_TEMPLATE.$directory.'/'.$file] = array('type' => $directory);
            }
            elseif ($this->get('inheritance') != '' && file_exists(DIR_FS_CATALOG.DIR_WS_TEMPLATES.$this->get('inheritance').'/'.$directory.'/'.$file)){
                $files[DIR_WS_TEMPLATES.$this->get('inheritance').'/'.$directory.'/'.$file] = array('type' => $directory);
            }
        }

        return $files;
    }
    
    public function findAssets($files, $type){
        $list = array();
        foreach ($files as $file) {
            $error = false; $include = false; $external = false;
            $options = array();
            // plugin?
            if(strpos($file, '::') !== false){
            	$file = explode('::', $file);
            	if(!file_exists($path = DIR_FS_CATALOG . 'plugins/' . $file[0] . '/content/resources/' . $file[1]))
            		$error = true;
            }
            // inline?
            elseif(!empty($this->loaded_files[$file]['options']['inline'])){
                $path = $file;
            }
            else{
                // external?
                if($this->strposArray($file, $this->options['supported_externals']) !== false){
                    $path = $file;
                    $external = true;
                }
                else{
                    $error = true;
                    // can we find the path?
                    foreach($this->get('dirs') as $dir){
                        $path = str_replace('%type%', $type, $dir) . $file;
                        if(file_exists(DIR_FS_CATALOG . $path)){                            
                            $error = false;
                            break;
                        }
                    }
                    
                    // 
                    if($error && file_exists($path = $file)) $error = false;
                }
            }

                     
            if(!$error){                                
                $list[] = array('src' => $path, 'external' => $external);
            }
            else
            {
                // some kind of error logging here
            }
        }
        return $list;
    }    
    
    public function getLoadedFile($file){
        return $this->loaded_files[$file];
    }
    
    // for backward compatibility
        
    function addLibs ($libs){
        foreach ($libs as $lib => $option)
        {
            $this->libs[$lib][] = $option;
        }
    }
    
    function setCurrentPage(){
        if(!$this->get('admin')){
            global $current_page, $this_is_home_page;
            
            // set current page
            if($this_is_home_page)
                $this->current_page = 'index_home';
            elseif($current_page == 'index'){
                if(isset($_GET['cPath']))
                    $this->current_page = 'index_category';
                elseif(isset($_GET['manufacturers_id']))
                    $this->current_page = 'index_manufacturer';
            }
            else
                $this->current_page = $current_page;
        }
        else{
            $this->current_page = preg_replace('/\.php/','',substr(strrchr($_SERVER['PHP_SELF'],'/'),1),1);
        }
    }
    
    function addLoaders($loaders, $multi = false){
        if($multi)
        $this->loaders = array_merge($this->loaders, $loaders);
        else
        $this->loaders[] = $loaders;
    }
    
    public function loadLoaders()
    {
        global $this_is_home_page, $cPath;
        $template = $this->template;
        $page_directory = $this->page_directory;;

        if($this->get('loaders') == '*')
        {
            $directory_array = $this->template->get_template_part(DIR_WS_TEMPLATE.'auto_loaders', '/^loader_/', '.php');
            while(list ($key, $value) = each($directory_array)) {
                /**
                 * include content from all site-wide loader_*.php files from includes/templates/YOURTEMPLATE/jscript/auto_loaders, alphabetically.
                 */
                require(DIR_WS_TEMPLATE.'auto_loaders'. '/' . $value);
            }
        }
        elseif(count($this->get('loaders')) > 0)
        {
            foreach($this->get('loaders') as $loader)
            if(file_exists($path = DIR_WS_TEMPLATE.'auto_loaders'. '/loader_' . $loader .'.php')) require($path);
        }
        else
        return;
        if(count($loaders) > 0)	$this->addLoaders($loaders, true);
        
        /**
         * load the loader files
         */
        if((is_array($this->loaders)) && count($this->loaders) > 0)	{
            foreach($this->loaders as $loader){
                $load = false;
                if(isset($loader['conditions']['pages']) && (in_array('*', $loader['conditions']['pages']) || in_array($this->current_page, $loader['conditions']['pages']))){
                    $load = true;
                }
                else{                    
                    if(isset($loader['conditions']['call_backs']))
                    foreach($loader['conditions']['call_backs'] as $function){
                        $f = explode(',',$function);
                        if(count($f) == 2){
                            $load = call_user_func(array($f[0], $f[1]));
                        }
                        else $load = $function();                        
                    }
                }
                
                // do we satistfy al the conditions to load?
                if($load){
                    $files = array();
                    if(isset($loader['libs'])){                        
                        foreach ($loader['libs'] as $key => $value) {
                            $files[$key . '.lib'] = $value;
                        }                        
                    }
                    if(isset($loader['jscript_files'])){
                        asort($loader['jscript_files']);
                        foreach ($loader['jscript_files'] as $key => $value) {
                            $files[$key] = array('type' => 'jscript');
                        }                        
                    }
                    if(isset($loader['css_files'])){
                        asort($loader['css_files']);
                        foreach ($loader['css_files'] as $key => $value) {
                            $files[$key] = array('type' => 'css');
                        }                        
                    }    
                    $this->load($files, 'footer');
                }
            }            
        }
    }
}
