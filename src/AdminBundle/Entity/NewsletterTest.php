<?php

namespace AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * NewsletterTest
 *
 * @ORM\Table(name="newsletter_test")
 * @ORM\Entity(repositoryClass="AdminBundle\Repository\NewsletterTestRepository")
 */
class NewsletterTest
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}

