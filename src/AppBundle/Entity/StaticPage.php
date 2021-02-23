<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StaticPage
 *
 * @ORM\Table(name="static_page")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\StaticPageRepository")
 */
class StaticPage
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
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, unique=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="StaticPageLang", mappedBy="page")
     */
    private $langs;

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
     * Set url
     *
     * @param string $url
     *
     * @return StaticPage
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return StaticPage
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
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
     * @param \AppBundle\Entity\StaticPageLang $lang
     *
     * @return StaticPage
     */
    public function addLang(\AppBundle\Entity\StaticPageLang $lang)
    {
        $this->langs[] = $lang;

        return $this;
    }

    /**
     * Remove lang
     *
     * @param \AppBundle\Entity\StaticPageLang $lang
     */
    public function removeLang(\AppBundle\Entity\StaticPageLang $lang)
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
