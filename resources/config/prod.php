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
$app['assetic.enabled']              = true;
$app['assetic.path_to_cache']        = $app['cache.path'] . '/assetic' ;
$app['assetic.path_to_web']          = __DIR__ . '/../../public/assets';
$app['assetic.input.path_to_assets'] = __DIR__ . '/../assets';

$app['assetic.input.path_to_css']       = $app['assetic.input.path_to_assets'] . '/less/style.less';
$app['assetic.output.path_to_css']      = 'css/styles.css';
$app['assetic.input.path_to_js']        = array(
    __DIR__.'/../../vendor/twitter/bootstrap/js/bootstrap-tooltip.js',
    __DIR__.'/../../vendor/twitter/bootstrap/js/*.js',
    $app['assetic.input.path_to_assets'] . '/js/script.js',
);
$app['assetic.output.path_to_js']       = 'js/scripts.js';

