<?php

namespace AppBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * NewsCategory
 *
 * @ORM\Table(name="news_category")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\NewsCategoryRepository")
 * @UniqueEntity("title")
 */
class NewsCategory
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
     * @ORM\OneToMany(targetEntity="NewsCategoryLang", mappedBy="category")
     */
    private $langs;


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
     * Constructor
     */
    public function __construct()
    {
        $this->langs = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add lang
     *
     * @param \AppBundle\Entity\NewsCategoryLang $lang
     *
     * @return NewsCategory
     */
    public function addLang(\AppBundle\Entity\NewsCategoryLang $lang)
    {
        $this->langs[] = $lang;

        return $this;
    }

    /**
     * Remove lang
     *
     * @param \AppBundle\Entity\NewsCategoryLang $lang
     */
    public function removeLang(\AppBundle\Entity\NewsCategoryLang $lang)
    {
        $this->langs->removeElement($lang);
    }

    /**
     * Get langs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLangs()
    {
        return $this->langs;
    }
}
