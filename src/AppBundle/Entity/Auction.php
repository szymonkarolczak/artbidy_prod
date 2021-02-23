<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Auction
 *
 * @ORM\Table(name="auction")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AuctionRepository")
 */
class Auction
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
     * @ORM\Column(name="image", type="string", length=255)
     */
    private $image;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startDate", type="datetime")
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endDate", type="datetime")
     */
    private $endDate;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="addDate", type="datetime")
     */
    private $addDate;

    /**
     * @var array
     *
     * @ORM\Column(name="increment", type="json_array", length=255)
     */
    private $increment;

    /**
     * @var bool
     *
     * @ORM\Column(name="customStartPrice", type="boolean")
     */
    private $customStartPrice;

    /**
     * @var array
     *
     * @ORM\Column(name="startPrice", type="integer", length=10, nullable=true)
     */
    private $startPrice;

    /**
     * @var bool
     *
     * @ORM\Column(name="buyNow", type="boolean")
     */
    private $buyNow = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="approved", type="boolean")
     */
    private $approved = false;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="pinned", type="boolean")
     */
    private $pinned = false;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled = true;
    
    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     */
    private $author;

    /**
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="Currency")
     */
    private $currency;
    
    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="FineartsAuctionObserve", mappedBy="auction", cascade={"all"})
     */
    private $observed;

    /**
     * @ORM\OneToMany(targetEntity="AuctionLang", mappedBy="auction", cascade={"persist", "remove"})
     */
    private $langs;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $cronSend = false;

    public function __construct() 
    {
        $this->addDate = new \DateTime();
        $this->startDate = new \DateTime();
        $this->endDate = new \DateTime('+1 month');
        $this->observed = new ArrayCollection();
        $this->langs = new ArrayCollection();
    }
    
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
     * Set image
     *
     * @param string $image
     *
     * @return Auction
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return Auction
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     *
     * @return Auction
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set increment
     *
     * @param array $increment
     *
     * @return Auction
     */
    public function setIncrement($increment)
    {
        $this->increment = $increment;

        return $this;
    }

    /**
     * Get increment
     *
     * @return array
     */
    public function getIncrement()
    {
        return $this->increment;
    }

    /**
     * Set customStartPrice
     *
     * @param boolean $customStartPrice
     *
     * @return Auction
     */
    public function setCustomStartPrice($customStartPrice)
    {
        $this->customStartPrice = $customStartPrice;

        return $this;
    }

    /**
     * Get customStartPrice
     *
     * @return bool
     */
    public function getCustomStartPrice()
    {
        return $this->customStartPrice;
    }

    /**
     * Set startPrice
     *
     * @param array $startPrice
     *
     * @return Auction
     */
    public function setStartPrice($startPrice)
    {
        $this->startPrice = $startPrice;

        return $this;
    }

    /**
     * Get startPrice
     *
     * @return array
     */
    public function getStartPrice()
    {
        return $this->startPrice;
    }

    /**
     * Set buyNow
     *
     * @param boolean $buyNow
     *
     * @return Auction
     */
    public function setBuyNow($buyNow)
    {
        $this->buyNow = $buyNow;

        return $this;
    }

    /**
     * Get buyNow
     *
     * @return bool
     */
    public function getBuyNow()
    {
        return $this->buyNow;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     *
     * @return Auction
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set addDate
     *
     * @param \DateTime $addDate
     *
     * @return Auction
     */
    public function setAddDate($addDate)
    {
        $this->addDate = $addDate;

        return $this;
    }

    /**
     * Get addDate
     *
     * @return \DateTime
     */
    public function getAddDate()
    {
        return $this->addDate;
    }

    /**
     * Set pinned
     *
     * @param boolean $pinned
     *
     * @return Auction
     */
    public function setPinned($pinned)
    {
        $this->pinned = $pinned;

        return $this;
    }

    /**
     * Get pinned
     *
     * @return boolean
     */
    public function getPinned()
    {
        return $this->pinned;
    }

    /**
     * Set currency
     *
     * @param \AppBundle\Entity\Currency $currency
     *
     * @return Auction
     */
    public function setCurrency(\AppBundle\Entity\Currency $currency = null)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return \AppBundle\Entity\Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set author
     *
     * @param \UserBundle\Entity\User $author
     *
     * @return Auction
     */
    public function setAuthor(\UserBundle\Entity\User $author = null)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return \UserBundle\Entity\User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set approved
     *
     * @param boolean $approved
     *
     * @return Auction
     */
    public function setApproved($approved)
    {
        $this->approved = $approved;

        return $this;
    }

    /**
     * Get approved
     *
     * @return boolean
     */
    public function getApproved()
    {
        return $this->approved;
    }

    /**
     * Add observed
     *
     * @param \AppBundle\Entity\FineartsAuctionObserve $observed
     *
     * @return Auction
     */
    public function addObserved(\AppBundle\Entity\FineartsAuctionObserve $observed)
    {
        $this->observed[] = $observed;

        return $this;
    }

    /**
     * Remove observed
     *
     * @param \AppBundle\Entity\FineartsAuctionObserve $observed
     */
    public function removeObserved(\AppBundle\Entity\FineartsAuctionObserve $observed)
    {
        $this->observed->removeElement($observed);
    }

    /**
     * Get observed
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getObserved()
    {
        return $this->observed;
    }

    /**
     * Add lang
     *
     * @param \AppBundle\Entity\AuctionLang $lang
     *
     * @return Auction
     */
    public function addLang(\AppBundle\Entity\AuctionLang $lang)
    {
        $this->langs[] = $lang;

        return $this;
    }

    /**
     * Remove lang
     *
     * @param \AppBundle\Entity\AuctionLang $lang
     */
    public function removeLang(\AppBundle\Entity\AuctionLang $lang)
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

    /**
     * Set cronSend
     *
     * @param boolean $cronSend
     *
     * @return Auction
     */
    public function setCronSend($cronSend)
    {
        $this->cronSend = $cronSend;

        return $this;
    }

    /**
     * Get cronSend
     *
     * @return boolean
     */
    public function getCronSend()
    {
        return $this->cronSend;
    }
}
