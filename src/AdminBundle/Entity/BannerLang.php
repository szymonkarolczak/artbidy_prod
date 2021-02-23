<?php

namespace AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BannerLang
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\NewsLangRepository")
 */
class BannerLang
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
     * @ORM\Column(type="string", length=255)
     */
    private $buttonText;

    /**
     * @var string
     *
     * @ORM\Column(name="head", type="string", length=255, nullable=true)
     */
    private $head;

    /**
     * @var string
     *
     * @ORM\Column(name="mainText", type="string", length=255)
     */
    private $mainText;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="Banner", inversedBy="langs", cascade={"remove"})
     */
    private $banner;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Language")
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
     * Set buttonText
     *
     * @param string $buttonText
     *
     * @return BannerLang
     */
    public function setButtonText($buttonText)
    {
        $this->buttonText = $buttonText;

        return $this;
    }

    /**
     * Get buttonText
     *
     * @return string
     */
    public function getButtonText()
    {
        return $this->buttonText;
    }

    /**
     * Set head
     *
     * @param string $head
     *
     * @return BannerLang
     */
    public function setHead($head)
    {
        $this->head = $head;

        return $this;
    }

    /**
     * Get head
     *
     * @return string
     */
    public function getHead()
    {
        return $this->head;
    }

    /**
     * Set mainText
     *
     * @param string $mainText
     *
     * @return BannerLang
     */
    public function setMainText($mainText)
    {
        $this->mainText = $mainText;

        return $this;
    }

    /**
     * Get mainText
     *
     * @return string
     */
    public function getMainText()
    {
        return $this->mainText;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return BannerLang
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
     * Set banner
     *
     * @param \AdminBundle\Entity\Banner $banner
     *
     * @return BannerLang
     */
    public function setBanner(\AdminBundle\Entity\Banner $banner = null)
    {
        $this->banner = $banner;

        return $this;
    }

    /**
     * Get banner
     *
     * @return \AdminBundle\Entity\Banner
     */
    public function getBanner()
    {
        return $this->banner;
    }

    /**
     * Set lang
     *
     * @param \AppBundle\Entity\Language $lang
     *
     * @return BannerLang
     */
    public function setLang(\AppBundle\Entity\Language $lang = null)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Get lang
     *
     * @return \AdminBundle\Entity\Language
     */
    public function getLang()
    {
        return $this->lang;
    }
}
