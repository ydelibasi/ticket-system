<?php
/**
 * Created by PhpStorm.
 * User: yavuz
 * Date: 22.10.2015
 * Time: 21:34
 */

use Silex\Provider\FormServiceProvider;
use Silex\Provider\HttpCacheServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder;
use Symfony\Component\Translation\Loader\YamlFileLoader;

require_once __DIR__.'/../bootstrap.php';

$q = $em->createQuery("select u from TicketSystem\Model\User u");
$users = $q->getResult();

foreach ($users as $user) {
    $role = ($user->is_admin) ? 'ROLE_ADMIN' : 'ROLE_USER';
    $securityUsers[$user->username] = array($role, $user->password);
    $appUsers[$user->id] = $user;
}
$app['users'] = $appUsers;

$app->register(new HttpCacheServiceProvider());
$app->register(new SessionServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new FormServiceProvider());
$app->register(new UrlGeneratorServiceProvider());

$app->register(new SecurityServiceProvider(), array(
    'security.firewalls' => array(
        'login_path' => array(
            'pattern' => '^/login$',
            'anonymous' => true
        ),
        'default' => array(
            'pattern' => '^/.*$',
            'anonymous' => true,
            'form' => array(
                'login_path' => '/login',
                'check_path' => '/login_check',
                'username_parameter' => 'form[username]',
                'password_parameter' => 'form[password]',
            ),
            'logout' => array(
                'logout_path' => '/logout',
                'invalidate_session' => false
            ),
            'users' => $securityUsers
        )
    ),
    'security.access_rules' => array(
        array('^/login$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
        array('^/register$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
        array('^/admin', 'ROLE_ADMIN'),
        array('^.*$', 'ROLE_USER'),
    )
));
$app['security.role_hierarchy'] = array(
    'ROLE_ADMIN' => array('ROLE_USER'),
);

$app['security.encoder.digest'] = $app->share(function ($app) {
    return new PlaintextPasswordEncoder();
});

$app->register(new TranslationServiceProvider());
$app['translator'] = $app->share($app->extend('translator', function ($translator, $app) {
    $translator->addLoader('yaml', new YamlFileLoader());

    $translator->addResource('yaml', __DIR__.'/../resources/locales/tr.yml', 'tr');

    return $translator;
}));

$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../resources/log/app.log',
    'monolog.name'    => 'app'
));

$app->register(new TwigServiceProvider(), array(
    'twig.options'        => array(
        'cache'            => isset($app['twig.options.cache']) ? $app['twig.options.cache'] : false,
        'strict_variables' => true,
        'autoescape' => true,
        'debug' => true
    ),
    'twig.form.templates' => array('bootstrap_3_layout.html.twig', 'common/bootstrap_3_layout.html.twig'),
    'twig.path'           => array(__DIR__ . '/../resources/views')
));

if ($app['debug'] && isset($app['cache.path'])) {
    $app->register(new ServiceControllerServiceProvider());
}
$app->register(new Silex\Provider\DoctrineServiceProvider());

return $app;
