<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AdvertLang
 *
 * @ORM\Table(name="event_category_lang")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\EventCategoryLangRepository")
 */
class EventCategoryLang
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
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     */
    private $title;

    /**
     * @ORM\ManyToOne(targetEntity="EventCategory", inversedBy="langs")
     */
    private $event;

    /**
     * @ORM\ManyToOne(targetEntity="Language")
     */
    private $lang;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return EventCategoryLang
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set event
     *
     * @param \AppBundle\Entity\EventCategory $event
     *
     * @return EventCategoryLang
     */
    public function setEvent(\AppBundle\Entity\EventCategory $event = null)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return \AppBundle\Entity\EventCategory
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set lang
     *
     * @param \AppBundle\Entity\Language $lang
     *
     * @return EventCategoryLang
     */
    public function setLang(\AppBundle\Entity\Language $lang = null)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Get lang
     *
     * @return \AppBundle\Entity\Language
     */
    public function getLang()
    {
        return $this->lang;
    }


}
