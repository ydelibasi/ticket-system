<?php
/**
 * Created by PhpStorm.
 * User: yavuz
 * Date: 23.10.2015
 * Time: 09:21
 */
namespace TicketSystem\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="User")
 */
class User
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=150)
     *
     * @var string
     */
    public $name;

    /**
     * @ORM\Column(type="string", length=150)
     *
     * @var string
     */
    public $surname;

    /**
     * @ORM\Column(type="string", length=20, unique=true)
     *
     * @var string
     */
    public $username;

    /**
     * @ORM\Column(type="string", length=150)
     *
     * @var string
     */
    public $email;

    /**
     * @ORM\Column(type="string", length=50)
     *
     * @var string
     */
    public $password;

    /**
     * @ORM\Column(type="integer")
     * @var integer
     */
    public $is_admin;

    /**
     * @ORM\Column(type="integer")
     * @var integer
     */
    public $status;



    public function __construct()
    {

    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        return $this->name = filter_var($name, FILTER_SANITIZE_STRING);
    }

    public function getSurname()
    {
        return $this->surname;
    }

    public function setSurName($surname)
    {
        return $this->surname = filter_var($surname, FILTER_SANITIZE_STRING);
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        return $this->username = $username;
    }


    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        return $this->email = $email;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        return $this->password = $password;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status = 1)
    {
        return $this->status = intval($status);
    }

    public function getIsAdmin()
    {
        return $this->is_admin;
    }

    public function setIsAdmin($isAdmin = 0)
    {
        return $this->is_admin = intval($isAdmin);
    }

}