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
 * Description of NoticeNew
 *
 * @author tomsky.pl
 */

/**
 * Notice
 *
 * @ORM\Table(name="noticenew")
 */

class NoticeNew {
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @var int
     *
     * @ORM\Column(name="author", type="integer")
     */
    private $author;
    
    /**
     * @var int
     *
     * @ORM\Column(name="work", type="integer")
     */
    private $work;
    
    /**
     * is readed notification message by recipient
     * 
     * @var bool
     *
     * @ORM\Column(name="is_readed", type="boolean")
     */
    private $is_readed = false;
    
    function getId() {
        return $this->id;
    }

    function getAuthor() {
        return $this->author;
    }

    function getWork() {
        return $this->work;
    }

    function getIs_readed() {
        return $this->is_readed;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setAuthor($author) {
        $this->author = $author;
    }

    function setWork($work) {
        $this->work = $work;
    }

    function setIs_readed($is_readed) {
        $this->is_readed = $is_readed;
    }


            
    
}
