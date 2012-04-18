<?php
// we use php file for now, we will later move to using yaml or another format
$libs['jquery.ui'] = array(
	'1.8.16' => array(
		'jscript_files' => array(
			'ui.js' => array(
				'local' => 'ui.js', //if not set, we will use the key name '1.4.2.js' 
				'cdn' => array(
					'http' => 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js', 
					'https' => 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js'
				)
			)
		),
		'css_files' => array(
			'ui.css' => array(
				'local' => 'ui.js', //if not set, we will use the key name '1.4.2.js' 
				'cdn' => array(
					'http' => 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/base/jquery-ui.css', 
					'https' => 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/base/jquery-ui.css'
				)
			)
		)		
	)
);