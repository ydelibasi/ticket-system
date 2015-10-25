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
 * @ORM\Table(name="Tickets")
 */
class Tickets
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
     * @ORM\Column(type="string", length=150)
     *
     * @var string
     */
    public $title;

    /**
     * @ORM\Column(type="string", length=500)
     *
     * @var string
     */
    public $description;

    /**
     * @ORM\Column(type="integer")
     *
     * @var integer
     */
    public $priority;

    /**
     * @ORM\Column(type="integer")
     *
     * @var integer
     */
    public $category;

    /**
     * @ORM\Column(type="string", length=150)
     *
     * @var string
     */
    public $attacment_file;

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

    /**
     * @ORM\Column(type="integer")
     * @var integer
     */
    public $status;



    public function __construct()
    {

    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        return $this->title = filter_var($title, FILTER_SANITIZE_STRING);
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        return $this->description = filter_var($description, FILTER_SANITIZE_STRING);
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setPriority($priority)
    {
        return $this->priority = intval($priority);
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function setUserId($user_id)
    {
        return $this->user_id = intval($user_id);
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory($category)
    {
        return $this->category = intval($category);
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function getAttachmentFile()
    {
        return $this->attacment_file;
    }

    public function setAttachmentFile($file)
    {
        return $this->attacment_file = $file;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status = 1)
    {
        return $this->status = intval($status);
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