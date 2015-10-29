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
    return $app['twig']->render('index.html.twig');
})->bind('homepage');

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
                $fileName = '';
                if ($files['FileUpload'] !== null) {
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
                }

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


$app->match('/admin/users', function ()  use ($app, $em) {
    $q = $em->createQuery("select u from TicketSystem\Model\User u");
    $users = $q->getResult();

    return $app['twig']->render('users.html.twig', array(
        'users' => $users
    ));
})->bind('admin_users');

// Ticket Categories
$app->match('/admin/categories', function (Request $request)  use ($app, $em) {
    $categories = $em->getRepository('TicketSystem\Model\Category')->findAll();
    $form = $app['form.factory']->createBuilder('form')
        ->add('category','text',
            array('label' => 'Kategori Adı:','constraints' => array(new Assert\NotBlank()))
        )
        ->getForm();
    $form->handleRequest($request);
    if ($form->isSubmitted()) {
        if ($form->isValid()) {
            $category_name = trim($form["category"]->getData());
            $categoryObj = $em->getRepository('TicketSystem\Model\Category')->findBy(array('name' => $category_name));
            if (count($categoryObj) == 0) {
                $category = New Category();
                $category->setName($category_name);
                $em->persist($category);
                $em->flush();
                $app['session']->getFlashBag()->add('success', 'Kategori başarıyla eklendi.');
                return $app->redirect("/admin/categories");
            } else {
                $app['session']->getFlashBag()->add('error', 'Bu kategori daha önceden eklenmiş.');
            }

        } else {
            $app['session']->getFlashBag()->add('error', 'Lütfen girdiğiniz bilgileri kontrol ediniz.');
        }
    }

    return $app['twig']->render('category.html.twig', array(
        'categories' => $categories, 'form' => $form->createView()
    ));
})->bind('admin_categories');

// Tickets Action

$app->match('/user/tickets', function (Request $request)  use ($app, $em) {
    $priority = $em->getRepository('TicketSystem\Model\Priority')->findAll();
    $priorities[0] = 'Seçiniz';
    foreach ($priority as $row) {
        $priorities[$row->id] = $row->name;
    }
    $category = $em->getRepository('TicketSystem\Model\Category')->findAll();
    $categories[0] = 'Seçiniz';
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
            ->add('title','text',array('label' => 'Başlık:'))
            ->add('create_date','date', array('label' => 'Eklenme Tarihi:','input' => 'string',
                'widget' => 'single_text')
            )
            ->getForm();

        $query = 'SELECT t from TicketSystem\Model\Tickets t';
        $subQuery = '';
        $params = array();

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $title = trim($form["title"]->getData());
                $create_date = trim($form["create_date"]->getData());
                $category = intval($form["category"]->getData());
                $priority = intval($form["priority"]->getData());

                if ($title != '') {
                    $subQuery .= " WHERE t.title LIKE :title";
                    $params['title'] =  "%$title%";
                }
                if (strtotime($create_date) !== false) {
                    $subQuery .= ($subQuery != '') ? ' and' : ' WHERE';
                    $subQuery .= " t.create_date >= :min_date";
                    $subQuery .= " and t.create_date <= :max_date";
                    $create_date = date('Y-m-d', strtotime($create_date));
                    $params['min_date'] = $create_date.' 00:00:00';
                    $params['max_date'] = $create_date.' 23:59:59';
                }
                if (intval($category) > 0) {
                    $subQuery .= ($subQuery != '') ? ' and' : ' WHERE';
                    $subQuery .= ' t.category = :category';
                    $params['category'] =  $category;
                }
                if (intval($priority) > 0) {
                    $subQuery .= ($subQuery != '') ? ' and' : ' WHERE';
                    $subQuery .= ' t.priority = :priority';
                    $params['priority'] =  $priority;
                }
            } else {
                $app['session']->getFlashBag()->add('error', 'Lütfen girdiğiniz bilgileri kontrol ediniz.');
            }
        }
        if ($user->is_admin != 1) {
            $subQuery .= ($subQuery != '') ? ' and' : ' WHERE';
            $subQuery .= ' t.user_id = :user_id';
            $params['user_id'] =  $user->id;
        }

        if ($subQuery != '') {
            $app['monolog']->addDebug(sprintf('Ticket Query: %s Params:',$query.$subQuery,implode(',',$params)));
            $query = $em->createQuery($query.$subQuery)->setParameters($params);
            $tickets = $query->getResult();
        } else {
            $tickets = $em->getRepository('TicketSystem\Model\Tickets')->findAll();
        }

        return $app['twig']->render('tickets.html.twig', array(
            'tickets' => $tickets, 'categories' => $categories, 'priorities' => $priorities, 'user' => $user,
            'form' => $form->createView()
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

$app->match('/admin/ticket/solve/{id}', function ($id)  use ($app, $em) {

    $ticket = $em->getRepository('TicketSystem\Model\Tickets')->find(intval($id));

    if ($ticket === null) {
        $app['session']->getFlashBag()->add('error', 'Ticket bulunamadı.');
        return $app->redirect("/user/tickets");
    } elseif ($ticket->status == 2) {
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
$app->match('/logout', function () use ($app) {
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
