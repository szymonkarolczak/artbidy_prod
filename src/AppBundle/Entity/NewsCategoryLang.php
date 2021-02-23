<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AdvertLang
 *
 * @ORM\Table(name="news_category_lang")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\NewsCategoryLangRepository")
 */
class NewsCategoryLang
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
     * @ORM\ManyToOne(targetEntity="NewsCategory", inversedBy="langs")
     */
    private $category;

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
     * @return NewsCategoryLang
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
     * Set category
     *
     * @param \AppBundle\Entity\NewsCategory $category
     *
     * @return NewsCategoryLang
     */
    public function setCategory(\AppBundle\Entity\NewsCategory $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \AppBundle\Entity\NewsCategory
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set lang
     *
     * @param \AppBundle\Entity\Language $lang
     *
     * @return NewsCategoryLang
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
