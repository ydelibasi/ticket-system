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
 * @ORM\Table(name="Answers")
 */
class Answers
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="integer")
     * @var integer
     */
    public $user_id;

    /**
     * @ORM\Column(type="integer")
     * @var integer
     */
    public $ticket_id;

    /**
     * @ORM\Column(type="string", length=500)
     *
     * @var string
     */
    public $description;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    public $create_date;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    public $update_date;


    public function __construct()
    {

    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function setUserId($userId)
    {
        return $this->user_id = intval($userId);
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        return $this->description = filter_var($description, FILTER_SANITIZE_STRING);
    }

    public function getTicketId()
    {
        return $this->ticket_id;
    }

    public function setTicketId($ticket_id)
    {
        return $this->ticket_id = intval($ticket_id);
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function getCreateDate()
    {
        return $this->create_date;
    }

    public function setCreateDate()
    {
        return $this->create_date = date('Y-m-d H:i:s');
    }

    public function getUpdateDate()
    {
        return $this->update_date;
    }

    public function setUpdateDate()
    {
        return $this->update_date = date('Y-m-d H:i:s');
    }

}