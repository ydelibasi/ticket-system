<?php

use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

$app->match('/', function () use ($app) {
    return $app['twig']->render('index.html.twig');
})->bind('homepage');

$app->match('/login', function (Request $request) use ($app) {
    $form = $app['form.factory']->createBuilder('form')
        ->add(
            'username',
            'text',array('label' => 'E-posta')
        )
        ->add('password', 'password', array('label' => 'Şifre'))
        ->getForm()
    ;

    return $app['twig']->render('login.html.twig', array(
        'form'  => $form->createView(),
        'error' => $app['security.last_error']($request),
    ));
})->bind('login');

$app->get('/user/{id}', function ($id) use ($app) {
    $sql = "SELECT * FROM user WHERE id = ?";
    $user = $app['db']->fetchAssoc($sql, array((int) $id));
    if (!$user) {
        return $app->redirect('/');
    }

    return $app['twig']->render(
        'index.html.twig',
        array('user' => $user)
    );
});

$app->get('/users', function ()  use ($app, $em) {
    $q = $em->createQuery("select u from TicketSystem\Model\User u");
    $users = $q->getResult();

    return $app['twig']->render('users.html.twig', array(
        'users' => $users
    ));
});



$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    switch ($code) {
        case 404:
            $message = 'The requested page could not be found.';
            break;
        default:
            $message = 'We are sorry, but something went terribly wrong.';
    }

    return new Response($message, $code);
});

return $app;
