<?php

// include the prod configuration
require __DIR__.'/prod.php';
ini_set('display_errors',1);
error_reporting(E_ALL);

// enable the debug mode
$app['debug'] = true;
