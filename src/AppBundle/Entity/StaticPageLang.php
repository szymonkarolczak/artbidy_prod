<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StaticPageLang
 *
 * @ORM\Table(name="static_page_lang")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\StaticPageLangRepository")
 */
class StaticPageLang
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
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="metatitle", type="string", length=255, nullable=true)
     */
    private $metaTitle;

    /**
     * @ORM\ManyToOne(targetEntity="StaticPage", inversedBy="langs")
     */
    private $page;

    /**
     * @ORM\ManyToOne(targetEntity="Language")
     */
    private $lang;

    /**
     * @ORM\Column(type="datetime")
     */
    private $editDate;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return StaticPageLang
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set editDate
     *
     * @param \DateTime $editDate
     *
     * @return StaticPageLang
     */
    public function setEditDate($editDate)
    {
        $this->editDate = $editDate;

        return $this;
    }

    /**
     * Get editDate
     *
     * @return \DateTime
     */
    public function getEditDate()
    {
        return $this->editDate;
    }

    /**
     * Set page
     *
     * @param \AppBundle\Entity\StaticPage  $page
     *
     * @return StaticPageLang
     */
    public function setPage(\AppBundle\Entity\StaticPage  $page = null)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Get page
     *
     * @return \AppBundle\Entity\StaticPage 
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set lang
     *
     * @param \AppBundle\Entity\Language $lang
     *
     * @return StaticPageLang
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

    /**
     * @return string
     */
    public function getMetaTitle()
    {
        return $this->metaTitle;
    }

    /**
     * @param string $metaTitle
     * @return StaticPageLang
     */
    public function setMetaTitle($metaTitle)
    {
        $this->metaTitle = $metaTitle;
        return $this;
    }


}
