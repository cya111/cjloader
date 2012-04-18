<?php

namespace plugins\riCjLoader;

use plugins\riPlugin\Plugin;

class CssHandler extends Handler{
    protected 
        $file_pattern = "<link rel=\"stylesheet\" type=\"text/css\" media=\"%s\" href=\"%s\" />\n",
        $extension = 'css';
    /**
     * (non-PHPdoc)
     * @see plugins\riCjLoader.Handler::load()
     */
    public function load(&$files, &$loaded_files, &$previous_files, $type, $file, $location, $options){

        if(!isset($options['media'])) $options['media'] = 'screen';
         
        // is this file loaded?
        if(!array_key_exists($file, $loaded_files)){

            $files['css'][$location][$options['media']][] = $file;

            $loaded_files[$file] = array('location' => $location, 'options' => $options);
        }
        elseif(isset($previous_files['css'])){
            $to_be_re_add = array();
            // a very special case, we need to traverse back
            foreach($previous_files['css'] as $previous_file){
                $to_be_re_add[] = $previous_file['css'];

                // remove from the files array
                unset($files['css'][$location][$previous_file['css']][array_search($previous_file['file'], $files['css'][$location][$previous_file['css'][$options['media']]])]);
                // re-add at the better location

                // update the location of the loaded files
                $loaded_files[$previous_file['css']][$options['media']] = $loaded_files[$file]['location'];
            }

            array_splice($files['css'][$location][$loaded_files[$file]['location']], array_search($file, $files['css'][$loaded_files[$file]['location']]), 0, $to_be_re_add);
        }
        $previous_files['css'][] = array('file' => $file, 'location' => $location);
    }

    /**
     * (non-PHPdoc)
     * @see plugins\riCjLoader.Handler::process()
     */
    public function process($files, $type, $loader){
                        
        ob_start();
        foreach ($files as $media => $_files){
            $_files = $loader->findAssets($_files, $type);  
            $filesrcs = $inject_content = '';   
                     
            foreach($_files as $file){                
                 
                // the file is external file or minify is off
                if(!$loader->get('minify') || $file['external']){
                    // if the inject content is not empty, we should push it into 1 file to cache
                    if(($cache_file = $this->cache($inject_content, $filesrcs)) !== false){
                        echo sprintf($this->file_pattern, $media, $cache_file);
                    }
                    echo sprintf($this->file_pattern, $media, $file['src']);                                            
                }
                else{
                    $ext = pathinfo($file['src'], PATHINFO_EXTENSION);
                    $file_info = $loader->getLoadedFile($file['src']);
                    // the file is php file and needs to be included
                    if($ext == 'php') {
                        if(($cache_file = $this->cache($inject_content, $filesrcs)) !== false){                                      
                            echo sprintf($this->file_pattern, $media, $cache_file);                            
                        }
                        //ob_start();
                        include($file['src']);  
                        //$inject_content .= ob_get_contents();
                        //ob_end_clean(); 
                        //$filesrcs .= $file['src'];
                    }
                    elseif(isset($file_info['options']['inline'])){
                        
                        if(($cache_file = $this->cache($inject_content, $filesrcs)) !== false){                                      
                            echo sprintf($this->file_pattern, $media, $cache_file);                            
                        }
                        
                        echo $file_info['options']['inline'];
                    
                    }
                    // minify
                    else {
                        ob_start();
                        echo Plugin::get('riCjLoader.MinifyFilter')->filter($file['src']);  
                        $inject_content .= ob_get_contents();                                             
                        ob_end_clean();  
                        $filesrcs .= $file['src'];            
                    }
                }                                    
            }

            if(($cache_file = $this->cache($inject_content, $filesrcs)) !== false){                                      
                echo sprintf($this->file_pattern, $media, $cache_file);                            
            }                                    
        }
        
        $result = ob_get_contents();
        ob_end_clean();
        
        return $result;
    }
}