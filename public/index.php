<?php
/**
 * Created by PhpStorm.
 * User: Yavuz
 * Date: 18.10.2015
 * Time: 22:40
 */
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$blogPosts = array(
    1 => array(
        'date'      => '2011-03-29',
        'author'    => 'igorw',
        'title'     => 'Using Silex',
        'body'      => '...',
    ),
);
$app->get('/', function () {
    return 'Homepage';
});
$app->get('/blog', function () use ($blogPosts) {
    $output = '';
    foreach ($blogPosts as $post) {
        $output .= $post['title'];
        $output .= '<br />';
    }

    return $output;
});
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_sqlite',
        'path'     => __DIR__.'/../resources/ticket.db',
    )
));

$app->get('/user/{id}', function ($id) use ($app) {
    $sql = "SELECT * FROM user WHERE id = ?";
    $user = $app['db']->fetchAssoc($sql, array((int) $id));
    if (!$user) {
        return $app->redirect('/');
    }

    return  "<h1>hosgeldiniz {$user['name']} {$user['surname']}</h1>";
});

$app['debug'] = true;
$app->run();