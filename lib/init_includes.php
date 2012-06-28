<?php
$riview->get('container')->get('riCjLoader.Loader')->set(array(
    'dirs' => IS_ADMIN_FLAG ? array(DIR_WS_ADMIN) : array(DIR_WS_TEMPLATE . '%type%' . '/')
));

$riview->set(array('loader' => $riview->get('container')->get('riCjLoader.Loader')));

$riview->get('loader')->setGlobalVariables();