<?php
/**
 * Created by PhpStorm.
 * User: Yavuz
 * Date: 18.10.2015
 * Time: 22:40
 */
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

require __DIR__.'/../resources/config/dev.php';
require __DIR__.'/../src/app.php';
require __DIR__.'/../src/controllers.php';

$app['http_cache']->run();
