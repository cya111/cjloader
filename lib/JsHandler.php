<?php

namespace plugins\riCjLoader;

class JsHandler extends Handler{
    protected 
        $file_pattern = "<script type=\"text/javascript\" src=\"%s\"></script>\n",
        $extension = 'js',
        $template_base_dir = 'jscript';
}