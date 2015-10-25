<?php

use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use TicketSystem\Model\Category;
use TicketSystem\Model\User;
use TicketSystem\Model\Tickets;

// Homepage Action
$app->match('/', function () use ($app) {
    //return $app['twig']->render('index.html.twig');
    return $app->redirect('/user');
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
})->bind('register');

// Ticket Add Action
$app->match('/user/ticket/add', function (Request $request) use ($app, $em) {

    $category = $em->getRepository('TicketSystem\Model\Category')->findAll();
    foreach ($category as $key => $row) {
        $categories[$key+1] = $row->name;
    }
    $priority = $em->getRepository('TicketSystem\Model\Priority')->findAll();
    foreach ($priority as $key => $row) {
        $priorities[$key+1] = $row->name;
    }

    $form = $app['form.factory']->createBuilder('form')
        ->add('category', 'choice', array(
            'label' => 'Kategori:',
            'choices' => $categories,
            'expanded' => false,
        ))
        ->add('priority', 'choice', array(
            'label' => 'Öncelik:',
            'choices' => $priorities,
            'expanded' => false,
        ))
        ->add('title','text',array('label' => 'Konu:','constraints' => array(new Assert\NotBlank())))
        ->add('desc','textarea',
            array('label' => 'Açıklama:','constraints' => array(new Assert\NotBlank()), 'attr' => array('rows' => 5))
        )
        ->getForm();

    $token = $app['security']->getToken();
    if (null !== $token) {
        $username = $token->getUser();
        $user = $em->getRepository('TicketSystem\Model\User')->findBy(array('username' => $username));
        if (count($user) > 0) {
            $userId = $user[0]->id;
        }
    }
    if (intval($userId) <= 0) {
        $app['session']->getFlashBag()->add('error', 'Bu işlemi yapmaya yetkiniz yoktur.');
    } else {

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $title = $form["title"]->getData();
                $desc = $form["desc"]->getData();
                $category = $form["category"]->getData();
                $priority = $form["priority"]->getData();

                $ticket = $em->getRepository('TicketSystem\Model\Tickets')->findBy(
                    array('user_id' => $userId, 'title' => $title, 'category' => $category)
                );
                if (count($ticket) == 0) {
                    $ticket = new Tickets();
                    $ticket->setUserId($userId);
                    $ticket->setTitle($title);
                    $ticket->setDescription($desc);
                    $ticket->setCategory($category);
                    $ticket->setPriority($priority);
                    $ticket->setStatus();
                    $ticket->setCreateDate();
                    $ticket->setUpdateDate();

                    $em->persist($ticket);
                    $em->flush();
                    $app['session']->getFlashBag()->add('success', 'Ticket başarıyla eklendi.');
                    return $app->redirect('/user/ticket/add');
                } else {
                    $app['session']->getFlashBag()->add('error', 'Bu ticket daha önceden açılmış.');
                }
            } else {
                $app['session']->getFlashBag()->add('error', 'Lütfen girdiğiniz bilgileri kontrol ediniz.');
            }
        }
    }

    return $app['twig']->render('add_ticket.html.twig', array(
        'form'  => $form->createView(),
        'error' => $app['security.last_error']($request),
    ));
})->bind('user_ticket_add');


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
