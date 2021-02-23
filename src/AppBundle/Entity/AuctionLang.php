<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AdvertLang
 *
 * @ORM\Table(name="auctions_lang")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AuctionLangRepository")
 */
class AuctionLang
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
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="metatitle", type="string", length=255, nullable=true)
     */
    private $metatitle;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="Auction", inversedBy="langs", cascade={"remove"})
     */
    private $auction;

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
     * @return AuctionLang
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
     * @return AuctionLang
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
     * Set auction
     *
     * @param \AppBundle\Entity\Auction $auction
     *
     * @return AuctionLang
     */
    public function setAuction(\AppBundle\Entity\Auction $auction = null)
    {
        $this->auction = $auction;

        return $this;
    }

    /**
     * Get auction
     *
     * @return \AppBundle\Entity\Auction
     */
    public function getAuction()
    {
        return $this->auction;
    }

    /**
     * Set lang
     *
     * @param \AppBundle\Entity\Language $lang
     *
     * @return AuctionLang
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
    public function getMetatitle()
    {
        return $this->metatitle;
    }

    /**
     * @param string $metaTitle
     * @return AuctionLang
     */
    public function setMetatitle($metatitle)
    {
        $this->metatitle = $metatitle;
        return $this;
    }


}
