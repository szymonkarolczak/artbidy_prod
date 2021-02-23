<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AdvertLang
 *
 * @ORM\Table(name="exhibition_lang")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\NewsLangRepository")
 */
class ExhibitionLang
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
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="metatitle", type="string", length=255, nullable=true)
     */
    private $metaTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="Exhibition", inversedBy="langs", cascade={"remove"})
     */
    private $exhibition;

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
     * @return ExhibitionLang
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
     * Set description
     *
     * @param string $description
     *
     * @return ExhibitionLang
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
     * Set exhibition
     *
     * @param \AppBundle\Entity\Exhibition $exhibition
     *
     * @return ExhibitionLang
     */
    public function setExhibition(\AppBundle\Entity\Exhibition $exhibition = null)
    {
        $this->exhibition = $exhibition;

        return $this;
    }

    /**
     * Get exhibition
     *
     * @return \AppBundle\Entity\Exhibition
     */
    public function getExhibition()
    {
        return $this->exhibition;
    }

    /**
     * Set lang
     *
     * @param \AppBundle\Entity\Language $lang
     *
     * @return ExhibitionLang
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
     * @return ExhibitionLang
     */
    public function setMetaTitle($metaTitle)
    {
        $this->metaTitle = $metaTitle;
        return $this;
    }


}
