<?php

use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

// Homepage Action
$app->match('/', function () use ($app) {
    return $app->redirect('/user');
    //return $app['twig']->render('index.html.twig');
})->bind('homepage');

$app->match('/user', function () use ($app) {

    return $app['twig']->render('user.html.twig');
})->bind('user');

// Login Action
$app->match('/login', function (Request $request) use ($app, $em) {
    $form = $app['form.factory']->createBuilder('form')
        ->add(
            'username',
            'text',array(
                'label' => 'Kullanıcı Adı:',
                'constraints' => array(new Assert\NotBlank()),
                'data' => $app['session']->get('_security.last_username')
            )
        )
        ->add('password',
            'password', array('label' => 'Şifre:','constraints' => array(new Assert\NotBlank()))
        )
        ->getForm();

    return $app['twig']->render('login.html.twig', array(
        'form'  => $form->createView(),
        'error' => $app['security.last_error']($request),
    ));
})->bind('login');

// Register Action
$app->match('/register', function (Request $request) use ($app, $em) {

    $form = $app['form.factory']->createBuilder('form')
        ->add('name','text',array(
                'label' => 'Adı:',
                'constraints' => array(new Assert\NotBlank())
            )
        )
        ->add('surname','text',array('label' => 'Soyadı:','constraints' => array(new Assert\NotBlank())))
        ->add('email','text',array(
            'label' => 'E-posta:',
            'constraints' => array(new Assert\NotBlank(), new Assert\Email()))
        )
        ->add('username','text',array('label' => 'Kullanıcı Adı:','constraints' => array(new Assert\NotBlank())))
        ->add('password', 'password', array(
                'label' => 'Şifre:','constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 6)))
            )
        )
        ->getForm();
    $form->handleRequest($request);
    if ($form->isSubmitted()) {
        if ($form->isValid()) {

            $name = $form["name"]->getData();
            $surname = $form["surname"]->getData();
            $username = $form["username"]->getData();
            $password = $form["password"]->getData();
            $email = $form["email"]->getData();
            $user = $em->getRepository('TicketSystem\Model\User')->findBy(array('username' => $username));
            if (count($user) == 0) {
                $user = new TicketSystem\Model\User();
                $user->setName($name);
                $user->setSurName($surname);
                $user->setUsername($username);
                $user->setEmail($email);
                $user->setPassword($password);
                $user->setStatus();
                $user->setIsAdmin();

                $em->persist($user);
                $em->flush();
                return $app->redirect('/login');
            } else {
                $app['session']->getFlashBag()->add('error', 'Bu kullanıcı adı daha önceden kullanılmış.');
            }
        } else {
            $app['session']->getFlashBag()->add('error', 'Lütfen girdiğiniz bilgileri kontrol ediniz.');
        }
    }

    return $app['twig']->render('register.html.twig', array(
        'form'  => $form->createView(),
        'error' => $app['security.last_error']($request),
    ));
})->bind('register');;


$app->match('/users', function ()  use ($app, $em) {
    $q = $em->createQuery("select u from TicketSystem\Model\User u");
    $users = $q->getResult();

    return $app['twig']->render('users.html.twig', array(
        'users' => $users
    ));
})->bind('users');

// Logout
$app->match('/user/logout', function () use ($app) {
    $app['session']->clear();

    return $app->redirect('/');
})->bind('logout');


$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    switch ($code) {
        case 404:
            $message = 'Aradığınız sayfa bulunamadı.';
            break;
        default:
            $message = 'İşlem sırasında bir hata oluştu.';
    }

    return new Response($message, $code);
});

return $app;
