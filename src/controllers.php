<?php

use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use TicketSystem\Model\Category;
use TicketSystem\Model\User;
use TicketSystem\Model\Tickets;
use TicketSystem\Model\Answers;

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
    foreach ($category as $row) {
        $categories[$row->id] = $row->name;
    }
    $priority = $em->getRepository('TicketSystem\Model\Priority')->findAll();
    foreach ($priority as $row) {
        $priorities[$row->id] = $row->name;
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
        ->add('FileUpload', 'file', array('label' => 'Dosya:'))
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
                $files = $request->files->get($form->getName());

                $filename = $files['FileUpload']->getClientOriginalName();

                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'doc','xls', 'xlsx'))) {
                    $app['session']->getFlashBag()->add('error', 'Geçersiz bir dosya seçtiniz.');
                    return $app->redirect('/user/ticket/add');
                }

                if ($_FILES['upload_file']['size'] > MAX_FILE_UPLOAD_SIZE) { // max: 5Mb
                    $app['session']->getFlashBag()->add('error', 'Dosya boyutu 5MB\'dan fazla olmamalı.');
                    return $app->redirect('/user/ticket/add');
                }

                $fileName = preg_replace('/[^a-zA-Z0-9\.]/ui','', $filename);
                $fileName = date('YmdHis')."_".basename($fileName);

                $files['FileUpload']->move(UPLOAD_DIR,$fileName);

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
                    $ticket->setAttachmentFile($fileName);

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

// Tickets Action

$app->match('/user/tickets', function ()  use ($app, $em) {
    $priority = $em->getRepository('TicketSystem\Model\Priority')->findAll();
    foreach ($priority as $row) {
        $priorities[$row->id] = $row->name;
    }
    $category = $em->getRepository('TicketSystem\Model\Category')->findAll();
    foreach ($category as $key => $row) {
        $categories[$row->id] = $row->name;
    }
    $token = $app['security']->getToken();
    if (null !== $token) {
        $username = $token->getUser();
        $user = $em->getRepository('TicketSystem\Model\User')->findBy(array('username' => $username));
        if (count($user) > 0) {
            $user = $user[0];
        }
    }
    if (intval($user->id) <= 0) {
        $app['session']->getFlashBag()->add('error', 'Bu işlemi yapmaya yetkiniz yoktur.');
    } else {
        if ($user->is_admin == 1) {
            $tickets = $em->getRepository('TicketSystem\Model\Tickets')->findAll();
        } else {
            $tickets = $em->getRepository('TicketSystem\Model\Tickets')->findBy(array('user_id' => $user->id));
        }
        return $app['twig']->render('tickets.html.twig', array(
            'tickets' => $tickets, 'categories' => $categories, 'priorities' => $priorities, 'user' => $user
        ));
    }

})->bind('user_tickets');

// Ticket Detail Action

$app->match('/user/ticket/detail/{id}', function (Request $request, $id)  use ($app, $em) {
    $priority = $em->getRepository('TicketSystem\Model\Priority')->findAll();
    foreach ($priority as $row) {
        $priorities[$row->id] = $row->name;
    }
    $category = $em->getRepository('TicketSystem\Model\Category')->findAll();
    foreach ($category as $key => $row) {
        $categories[$row->id] = $row->name;
    }
    $token = $app['security']->getToken();
    if (null !== $token) {
        $username = $token->getUser();
        $user = $em->getRepository('TicketSystem\Model\User')->findBy(array('username' => $username));
        if (count($user) > 0) {
            $user = $user[0];
        }
    }
    if (intval($user->id) <= 0) {
        $app['session']->getFlashBag()->add('error', 'Bu işlemi yapmaya yetkiniz yoktur.');
        return $app['twig']->render('ticket_detail.html.twig', array(
            'error' => $app['security.last_error']($request),
        ));
    } else {

        $ticket = $em->getRepository('TicketSystem\Model\Tickets')->findBy(array('id' => intval($id)));
        $userId = $ticket[0]->user_id;
        $ticket = $ticket[0];
        $ticket_user = $em->getRepository('TicketSystem\Model\User')->findBy(array('id' => intval($userId)));
        $ticket_user = $ticket_user[0];
        $answers = $em->getRepository('TicketSystem\Model\Answers')->findBy(
            array('ticket_id' => intval($id)),array('id' => 'DESC')
        );

        $form = $app['form.factory']->createBuilder('form')
            ->add('answer','textarea',
                array('label' => 'Açıklama:','constraints' => array(new Assert\NotBlank()), 'attr' => array('rows' => 5))
            )
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $desc = $form["answer"]->getData();
                $ticketAnswer = new Answers();
                $ticketAnswer->setTicketId($ticket->id);
                $ticketAnswer->setUserId($user->id);
                $ticketAnswer->setDescription($desc);
                $ticketAnswer->setCreateDate();
                $ticketAnswer->setUpdateDate();

                $em->persist($ticketAnswer);
                $em->flush();
                $app['session']->getFlashBag()->add('success', 'Cevap başarıyla eklendi.');
                return $app->redirect("/user/ticket/detail/$id");

            } else {
                $app['session']->getFlashBag()->add('error', 'Lütfen girdiğiniz bilgileri kontrol ediniz.');
            }
        }
        $ticketFile = '';
        $ticketImage = '';
        if ($ticket->attacment_file != '' && is_file(UPLOAD_DIR.$ticket->attacment_file)) {
            $ext = pathinfo(UPLOAD_DIR.$ticket->attacment_file, PATHINFO_EXTENSION);
            if (in_array($ext, array('jpg', 'jpeg', 'png', 'gif'))) {
                $data = file_get_contents(UPLOAD_DIR.$ticket->attacment_file);
                $ticketImage = 'data:image/' . $ext . ';base64,' . base64_encode($data);
            } else {
                $ticketFile = $ticket->attacment_file;
            }
        }

        return $app['twig']->render('ticket_detail.html.twig', array(
            'form'  => $form->createView(),
            'error' => $app['security.last_error']($request),
            'ticket' => $ticket, 'categories' => $categories, 'priorities' => $priorities, 'ticketuser' => $ticket_user,
            'answers' => $answers, 'users' => $app['users'], 'ticket_file' => $ticketFile, 'ticket_image' => $ticketImage
        ));
    }

});

// Ticket Solve Action

$app->match('/user/ticket/solve/{id}', function ($id)  use ($app, $em) {

    $token = $app['security']->getToken();
    if (null !== $token) {
        $username = $token->getUser();
        $user = $em->getRepository('TicketSystem\Model\User')->findBy(array('username' => $username));
        if (count($user) > 0) {
            $user = $user[0];
        }
    }
    if (intval($user->id) <= 0 || intval($user->is_admin) < 1) {
        $app['session']->getFlashBag()->add('error', 'Bu işlemi yapmaya yetkiniz yoktur.');
        return $app->redirect("/");
    }

    $ticket = $em->getRepository('TicketSystem\Model\Tickets')->find(intval($id));

    if ($ticket->status == 2) {
        $app['session']->getFlashBag()->add('error', 'Bu ticket zaten çözülmüş.');
        return $app->redirect("/user/tickets");
    }
    $ticket->setStatus(2);
    $ticket->setUpdateDate();
    $em->flush();

    $app['session']->getFlashBag()->add('success', 'Ticket çözüldü.');
    return $app->redirect("/user/tickets");
});

// download ticket file
$app->match('/user/ticket/file/{filename}', function ($filename)  use ($app, $em) {

    $token = $app['security']->getToken();
    if (null !== $token) {
        $username = $token->getUser();
        $user = $em->getRepository('TicketSystem\Model\User')->findBy(array('username' => $username));
        if (count($user) > 0) {
            $user = $user[0];
        }
    }
    $ticket = $em->getRepository('TicketSystem\Model\Tickets')->findBy(array('attacment_file' => $filename));
    $ticket = $ticket[0];

    if (intval($user->id) != intval($ticket->user_id) && intval($user->is_admin) < 1) {
        $app['session']->getFlashBag()->add('error', 'Bu işlemi yapmaya yetkiniz yoktur.');
        return $app['twig']->render('error.html.twig');
    }
    $file = UPLOAD_DIR.$filename;
    if ($filename != '' && is_file($file)) {
        $fileInfo = getimagesize($file);
        header("Content-type: ".$fileInfo['mime']);
        $result = readfile($file);
        echo $result;
    } else {
        $app['session']->getFlashBag()->add('error', 'Dosya bulunamadı.');
        return $app['twig']->render('error.html.twig');
    }
});


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
