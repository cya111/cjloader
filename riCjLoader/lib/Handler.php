<?php
namespace plugins\riCjLoader;

use plugins\riPlugin\Plugin;

abstract class Handler{
    
    protected $file_pattern = '', $extension = '';
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
    public function load(&$files, &$loaded_files, &$previous_files, $type, $file, $location, $options){

        if(!array_key_exists($file, $loaded_files)){            
            if(isset($files[$type][$location]) && isset($previous_file[$type]))   {             
                $latest_file = end($previous_file[$type]);           
                array_splice( $files[$type][$location], array_search($latest_file['file'], $files[$type][$location]) + 1, 0, $file);
            }
            else 
                $files[$type][$location][] = $file;
            
            $loaded_files[$file] = array('location' => $location, 'options' => $options);            
        }
        elseif(isset($previous_files[$type])){
            $to_be_re_add = array();
            // a very special case, we need to traverse back            
            foreach($previous_files[$type] as $previous_file){
                $to_be_re_add[] = $previous_file['file'];
                
                // remove from the files array
                unset($files[$type][$previous_file['location']][array_search($previous_file['file'], $files[$type][$previous_file['location']])]); 
                // re-add at the better location
                
                // update the location of the loaded files
                $loaded_files[$previous_file['file']]['location'] = $loaded_files[$file]['location'];
            }
    
            array_splice($files[$type][$loaded_files[$file]['location']], array_search($file, $files[$type][$loaded_files[$file]['location']]), 0, $to_be_re_add);
            
        }
        
        $previous_files[$type][] = array('file' => $file, 'location' => $location);
    }

    /**
     * 
     * This function is responsible for outputing the files (and also doing combining, minifying etc if needed)
     * @param array $files
     * @param string $type
     * @param object Loader $loader
     */
    public function process($files, $type, $loader){
        $files = $loader->findAssets($files, $type);

        $to_load = array();
                
        ob_start();
        foreach($files as $file){
            // the file is external file or minify is off
            if(!$loader->get('minify') || $file['external']){
                // if the inject content is not empty, we should push it into 1 file to cache
                if(($cache_file = $this->cache($to_load)) !== false){
                    echo sprintf($this->file_pattern, $cache_file);
                }

                echo sprintf($this->file_pattern, $file['src']);                
            }
            else{
                $ext = pathinfo($file['src'], PATHINFO_EXTENSION);
                $file_info = $loader->getLoadedFile($file['src']);
                // the file is php file and needs to be included
                if($ext == 'php') {
                    if(($cache_file = $this->cache($to_load)) !== false){
                        echo sprintf($this->file_pattern, $cache_file);
                    }
                    //ob_start();
                    include($file['src']);
                    //$inject_content .= ob_get_contents();
                    //ob_end_clean();
                    //$filesrcs .= $file['src'];
                }
                elseif(isset($file_info['options']['inline'])){

                    if(($cache_file = $this->cache($to_load)) !== false){
                        echo sprintf($this->file_pattern, $cache_file);
                    }

                    echo $file_info['options']['inline'];

                }                

                // minify
                else {
                	$to_load[] = $file['src'];                    
                }
            }
            
        }

        if(($cache_file = $this->cache($to_load)) !== false){
            echo sprintf($this->file_pattern, $cache_file);
        }

        $result = ob_get_contents();
        ob_end_clean();
        
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
        $cache_file = false;
        if(!empty($to_load)){        	
        	            
            $cache_filename = md5(serialize($to_load)) . '.' . $this->extension; 
            
            if(($cache_file = Plugin::get('riCache.Cache')->exists($cache_filename, 'cjloader')) === false){
                $cache_file = Plugin::get('riCache.Cache')->write($cache_filename, 'cjloader', Plugin::get('riCjLoader.MinifyFilter')->filter($to_load));
            }    

            if($cache_file !== false)
            	$cache_file = Plugin::get('riCache.Cache')->getRelativePath($cache_file);
            	
            $to_load = array();           
        }
        return $cache_file;
    }
}