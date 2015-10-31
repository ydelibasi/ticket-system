<?php

// Local
$app['locale'] = 'tr';
$app['session.default_locale'] = $app['locale'];
$app['translator.messages'] = array(
    'tr' => __DIR__.'/../resources/locales/tr.yml',
);
define('MAX_FILE_UPLOAD_SIZE', 5 * 1024 * 1024); //5Mb
define("ROOT_DIR" , realpath(dirname(__FILE__) ."/../.."));
define('UPLOAD_DIR', ROOT_DIR.'/data/upload/');

// Cache
$app['cache.path'] = __DIR__ . '/../cache';

// Http cache
$app['http_cache.cache_dir'] = $app['cache.path'] . '/http';

// Twig cache
$app['twig.options.cache'] = $app['cache.path'] . '/twig';

// Assetic
$app['assetic.enabled'] = false;

