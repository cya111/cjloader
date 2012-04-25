<?php

namespace plugins\riCjLoader;

class JscriptHandler extends Handler{
    protected 
        $file_pattern = "<script type=\"text/javascript\" src=\"%s\"></script>\n",
        $extension = 'js';
}