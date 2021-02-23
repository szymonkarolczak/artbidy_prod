<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
/**
 * Description of Notice
 *
 * @author tomsky.pl ( DStaroselskyi )
 */

/**
 * Notice
 *
 * @ORM\Table(name="notice")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\NoticeRepository")
 */

class Notice {
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_at", type="datetime")
     */
    private $create_at;
    
    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     */
    private $author;
    
    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     */
    private $recipient;
    
    /**
     * is readed notification message by recipient
     * 
     * @var bool
     *
     * @ORM\Column(name="is_readed", type="boolean")
     */
    private $is_readed = false;
    
    /**
     * is send email notification message
     * @var bool
     *
     * @ORM\Column(name="is_sended", type="boolean")
     */
    private $is_sended = false;
    /**
     * the notification message type
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=1)
     */
    private $type = 'M';
    
    /**
     * title of email and notification message
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;
    
    /**
     * the object id's of email and notification message
     * @var bigint
     *
     * @ORM\Column(name="object_id", type="bigint")
     */
    private $object_id;
    /**
     * the object classes of email and notification message
     * @var string
     *
     * @ORM\Column(name="object_class", type="string", length=255)
     */
    private $object_class;
    
    /**
     * content of email and notification message
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     */
    private $content;
    
    protected $object_type_lists = array(
        'M' => 'message',
        'N' => 'notification',
        'E' => 'email',
    );
    
    public function __construct() 
    {
        $this->create_at = new \DateTime();
        //$this->author = new ArrayCollection();
        //$this->recipient = new ArrayCollection();
    }
    /** return the id of notification
     * 
     * @return int
     */
    public function getId(){
        return $this->id;
    }
    /** return the author objects of notification
     * 
     * @return object or id of author objects
     */
    public function getAuthor(){
        return $this->author;
    }
    /** return the recipient objects of notification
     * 
     * @return object or id of recipient objects 
     */
    public function getRecipient(){
        return $this->recipient;
    }
    /** return the flag of read status by notification
     * 
     * @return bool
     */
    public function getIsReaded(){
        return $this->is_readed;
    }
    /** return the flag of send status by email notification
     * 
     * @return bool
     */
    public function getIsSended(){
        return $this->is_sended;
    }    
    /** return the title of notification
     * 
     * @return string
     */
    public function getTitle(){
        return $this->title;
    }
    /** return the notification content
     * 
     * @return string
     */
    public function getContent(){
        return $this->content;
    }
    /** return the notification object's class name
     * 
     * @return string
     */
    public function getObjectClass(){
        return $this->object_class;
    }
    /** return the notification object's id
     * 
     * @return int
     */
    public function getObjectId(){
        return $this->object_id;
    }
    /** return the notification created date and time in SQL format
     * 
     * @return SQL DateTime
     */
    public function getCreatedAt(){
        return $this->create_at;
    }
    /** return the notification type
     * 
     * @return string
     */
    public function getType(){
        if( isset( $this->object_type_lists[ $this->type ] ) )
        {
            return $this->object_type_lists[ $this->type ];
        }
        return $this->type;
    }
    /** Set the author object id of notification
     * 
     * @param int $author - the the author id of notification
     * @return object $this
     */
    public function setAuthor( $author ){
        $this->author = $author;
        return $this;
    }
    /** set the recipient object id of notification
     * 
     * @param int $recipient - the the recipient id of notification
     * @return object $this
     */
    public function setRecipient( $recipient ){
        $this->recipient = $recipient;
        return $this;
    }
    /** Set the flag of read notification status by recipient
     * 
     * @param bool $bool - the flag status of read notification by recipient
     * @return object $this
     */
    public function setIsReaded( $bool = true){
        $this->is_readed = (bool)$bool;
        return $this;
    }
    /** set the flag of send status by email notification
     * 
     * @param bool $bool - the flag status of send notification by email
     * @return object $this
     */
    public function setIsSended( $bool ){
        $this->is_sended = (bool)$bool;
        return $this;
    }    
    /** Set the title of notification
     * 
     * @param string $title - the notification title
     * @return object $this
     */
    public function setTitle( $title ){
        $this->title = $title;
        return $this;
    }
    /** Set the notification content
     * 
     * @param string $content - the notification content
     * @return object $this
     */
    public function setContent( $content ){
        $this->content = $content;
        return $this;
    }
    /** Set the notification object's class name
     * 
     * @param string $object_class - the notification object's class name
     * @return object $this
     */
    public function setObjectClass( $object_class ){
        $this->object_class = $object_class;
        return $this;
    }
    /** Set the notification object's id
     * 
     * @param int $id - the notification object's id
     * @return object $this
     */
    public function setObjectId( $id ){        
        $this->object_id = $id;
        return $this;
    }
    /** Set the notification created date and time in SQL format. If argument not send set current DateTime
     * 
     * @param string $type - notification created date and time in SQL format
     * @return object $this
     */
    public function setCreatedAt( $date = false ){
        if( empty( $date ) ) $date =  new \DateTime();
        $this->create_at = $date;
        return $this;
    }
    /** set the notification type
     * 
     * @param string $type - the notification type name
     * @return object $this
     */
    public function setType( $type ){
        if( empty( $type ) ) return $this->setDefaultType();
        $type = strtolower( (string)$type );
        foreach( $this->object_type_lists as $key => $name )
        {
            if( $type == $name )
            {
                $this->type = $key;
                return $this;
            }
        }
        $this->type = strtoupper( $type[0] );
        return $this;
    }
    /** set the default notification type at 'M' - message
     *      
     * @return object $this
     */
    public function setDefaultType()
    {
       $this->type = 'M';
       return $this;
    }
    
}
