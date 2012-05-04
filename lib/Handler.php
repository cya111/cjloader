<?php
namespace plugins\riCjLoader;

use plugins\riPlugin\Plugin;

abstract class Handler{
    
    protected $file_pattern = '', $extension = '', $template_base_dir = '';
    /**
     * 
     * This function is responsible for loading the files into the array for later parsing
     * @param array $files
     * @param array $loaded_files
     * @param array $previous_files
     * @param array $type
     * @param string $file
     * @param string $location
     * @param array $options
     */
    public function load(&$files, $file, $location, $options){                   
    	$files[$options['type']][$location][$file] = $options;         
    }
    
    public function getTemplateBaseDir(){
    	return $this->template_base_dir;
    }

    /**
     * 
     * This function is responsible for outputing the files (and also doing combining, minifying etc if needed)
     * @param array $files
     * @param string $type
     * @param object Loader $loader
     */
    public function process($files, $loader){
        $files = $loader->findAssets($files, $type);

        $to_load = array();
                
        ob_start();
        foreach($files as $file => $options){
            // the file is external file or minify is off
            if($options['external']){
                // if the inject content is not empty, we should push it into 1 file to cache
                if(($cache_file = $this->cache($to_load)) !== false){
                    printf($this->file_pattern, $cache_file);
                }

                printf($this->file_pattern, $file);                
            }
            else{                
                // the file is php file and needs to be included
                if($options['ext'] == 'php') {
                    if(($cache_file = $this->cache($to_load)) !== false){
                        printf($this->file_pattern, $cache_file);
                    }
                    include($file);      
                }
                elseif(isset($options['inline'])){

                    if(($cache_file = $this->cache($to_load)) !== false){
                        printf($this->file_pattern, $cache_file);
                    }
                    echo $this->processInline($options['inline']);
                }                

                // minify
                else {
                	$to_load[] = $file;                    
                }
            }            
        }

        if(($cache_file = $this->cache($to_load)) !== false){
            printf($this->file_pattern, $cache_file);
        }

        $result = ob_get_clean();        
        
        return $result;
    }
    
    /**
     * 
     * Outputing as array 
     * @param array $files
     * @param string $type
     * @param object Loader $loader
     */
    public function processArray($files, $type, $loader){        
        return $loader->findAssets($files, $type);                            
    }
    
    /**
     * 
     * This function assits in caching the loaded content into a file to be able to serve from content different than 
     * the file original location
     * @param string $inject_content
     * @param string $filesrcs
     * @param string $type
     */
    protected function cache(&$to_load){
        global $request_type;
        
        $cache_file = false;
        if(!empty($to_load)){        	
        	            
            $cache_filename = md5(serialize($to_load)) . '.' . $this->extension; 
            
            if(($cache_file = Plugin::get('riCache.Cache')->exists($cache_filename, 'cjloader')) === false){
            	// Todo: what to do if we do not turn on the minify?
                $cache_file = Plugin::get('riCache.Cache')->write($cache_filename, 'cjloader', Plugin::get('riCjLoader.MinifyFilter')->filter($to_load));
            }    

            if($cache_file !== false){
	            // temp hack for admin support
	            if(IS_ADMIN_FLAG){               	                
	                $cache_file = 
	                //Plugin::get('riUtility.File')->getRelativePath(Plugin::get('riUtility.Uri')->getCurrent(), $request_type == 'SSL' ? DIR_WS_HTTPS_ADMIN : DIR_WS_ADMIN) . 
	                Plugin::get('riUtility.File')->getRelativePath(DIR_FS_ADMIN, $cache_file);
	            }else{
	                $cache_file = 
	                //Plugin::get('riUtility.File')->getRelativePath(Plugin::get('riUtility.Uri')->getCurrent(), $request_type == 'SSL' ? DIR_WS_HTTPS_CATALOG : DIR_WS_CATALOG) .
	                Plugin::get('riUtility.File')->getRelativePath(DIR_FS_CATALOG, $cache_file);    
	            }            	
            }
            
            $to_load = array();           
        }
        return $cache_file;
    }

    protected function processInline($content){
        return $content;
    }
}