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
 * @ORM\Table(name="Priority")
 */
class Priority
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

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

}