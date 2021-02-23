<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * AuctionWork
 *
 * @ORM\Table(name="auction_work")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AuctionWorkRepository")
 */
class AuctionWork
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
     * @ORM\ManyToOne(targetEntity="Work", inversedBy="auctionWorks", cascade={"all"})
     */
    private $work;

    /**
     * @ORM\ManyToOne(targetEntity="Auction")
     * @ORM\JoinColumn(name="auction_id", referencedColumnName="id")
     */
    private $auction;

    /**
     * @var int
     *
     * @ORM\Column(name="startPrice", type="decimal", precision=10, scale=2)
     */
    private $startPrice;

    /**
     * @var int
     *
     * @ORM\Column(name="currentPrice", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $currentPrice;

    /**
     * @ORM\Column(type="integer")
     */
    private $offersCount = 0;
    
    /**
     * @var int
     *
     * @ORM\Column(name="estimationStart", type="integer", nullable=true)
     */
    private $estimationStart;
    
    /**
     * @var int
     *
     * @ORM\Column(name="estimationEnd", type="integer", nullable=true)
     */
    private $estimationEnd;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $payment;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $shipsFrom;

    /**
     * @ORM\Column(name="work_condition", type="text")
     */
    private $condition;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="addDate", type="datetime")
     */
    private $addDate;

    /**
     * @var bool
     *
     * @ORM\Column(name="allowBuyNow", type="boolean")
     */
    private $allowBuyNow = false;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $buyNowPrice;

    /**
     * @var bool
     *
     * @ORM\Column(name="approved", type="boolean")
     */
    private $approved = false;

    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     */
    private $approved_by;

    /**
     * @ORM\OneToMany(targetEntity="AuctionBid", mappedBy="auctionWork", cascade={"all"})
     */
    private $bids;

    /**
     * @ORM\Column(type="integer")
     */
    private $views;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $provisionPaid = false;

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="AuctionWorkObserve", mappedBy="auctionWork", cascade={"all"})
     */
    private $observed;


    public function __construct()
    {
        $this->addDate = new \DateTime();
        $this->bids = new ArrayCollection();
        $this->observed = new ArrayCollection();
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
     * Set work
     *
     * @param string $work
     *
     * @return AuctionWork
     */
    public function setWork($work)
    {
        $this->work = $work;

        return $this;
    }

    /**
     * Get work
     *
     * @return string
     */
    public function getWork()
    {
        return $this->work;
    }

    /**
     * Set startPrice
     *
     * @param integer $startPrice
     *
     * @return AuctionWork
     */
    public function setStartPrice($startPrice)
    {
        $this->startPrice = $startPrice;

        return $this;
    }

    /**
     * Get startPrice
     *
     * @return int
     */
    public function getStartPrice()
    {
        return $this->startPrice;
    }

    /**
     * Set allowBuyNow
     *
     * @param boolean $allowBuyNow
     *
     * @return AuctionWork
     */
    public function setAllowBuyNow($allowBuyNow)
    {
        $this->allowBuyNow = $allowBuyNow;

        return $this;
    }

    /**
     * Get allowBuyNow
     *
     * @return bool
     */
    public function getAllowBuyNow()
    {
        return $this->allowBuyNow;
    }

    /**
     * Set auction
     *
     * @param string $auction
     *
     * @return AuctionWork
     */
    public function setAuction($auction)
    {
        $this->auction = $auction;

        return $this;
    }

    /**
     * Get auction
     *
     * @return string
     */
    public function getAuction()
    {
        return $this->auction;
    }

    /**
     * Set addDate
     *
     * @param \DateTime $addDate
     *
     * @return AuctionWork
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
     * Set approved
     *
     * @param boolean $approved
     *
     * @return AuctionWork
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
     * Set approvedBy
     *
     * @param \UserBundle\Entity\User $approvedBy
     *
     * @return AuctionWork
     */
    public function setApprovedBy(\UserBundle\Entity\User $approvedBy = null)
    {
        $this->approved_by = $approvedBy;

        return $this;
    }

    /**
     * Get approvedBy
     *
     * @return \UserBundle\Entity\User
     */
    public function getApprovedBy()
    {
        return $this->approved_by;
    }

    /**
     * Add bid
     *
     * @param \AppBundle\Entity\AuctionBid $bid
     *
     * @return AuctionWork
     */
    public function addBid(\AppBundle\Entity\AuctionBid $bid)
    {
        $this->bids[] = $bid;

        return $this;
    }

    /**
     * Remove bid
     *
     * @param \AppBundle\Entity\AuctionBid $bid
     */
    public function removeBid(\AppBundle\Entity\AuctionBid $bid)
    {
        $this->bids->removeElement($bid);
    }

    /**
     * Get bids
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBids()
    {
        return $this->bids;
    }

    /**
     * Set estimationStart
     *
     * @param integer $estimationStart
     *
     * @return AuctionWork
     */
    public function setEstimationStart($estimationStart)
    {
        $this->estimationStart = $estimationStart;

        return $this;
    }

    /**
     * Get estimationStart
     *
     * @return integer
     */
    public function getEstimationStart()
    {
        return $this->estimationStart;
    }

    /**
     * Set estimationEnd
     *
     * @param integer $estimationEnd
     *
     * @return AuctionWork
     */
    public function setEstimationEnd($estimationEnd)
    {
        $this->estimationEnd = $estimationEnd;

        return $this;
    }

    /**
     * Get estimationEnd
     *
     * @return integer
     */
    public function getEstimationEnd()
    {
        return $this->estimationEnd;
    }

    /**
     * Set payment
     *
     * @param string $payment
     *
     * @return AuctionWork
     */
    public function setPayment($payment)
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * Get payment
     *
     * @return string
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * Set shipsFrom
     *
     * @param string $shipsFrom
     *
     * @return AuctionWork
     */
    public function setShipsFrom($shipsFrom)
    {
        $this->shipsFrom = $shipsFrom;

        return $this;
    }

    /**
     * Get shipsFrom
     *
     * @return string
     */
    public function getShipsFrom()
    {
        return $this->shipsFrom;
    }

    /**
     * Set condition
     *
     * @param string $condition
     *
     * @return AuctionWork
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Get condition
     *
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * Set views
     *
     * @param integer $views
     *
     * @return AuctionWork
     */
    public function setViews($views)
    {
        $this->views = $views;

        return $this;
    }

    /**
     * Get views
     *
     * @return integer
     */
    public function getViews()
    {
        return $this->views;
    }

    /**
     * Set buyNowPrice
     *
     * @param integer $buyNowPrice
     *
     * @return AuctionWork
     */
    public function setBuyNowPrice($buyNowPrice)
    {
        $this->buyNowPrice = $buyNowPrice;

        return $this;
    }

    /**
     * Get buyNowPrice
     *
     * @return integer
     */
    public function getBuyNowPrice()
    {
        return $this->buyNowPrice;
    }

    /**
     * Set provisionPaid
     *
     * @param boolean $provisionPaid
     *
     * @return AuctionWork
     */
    public function setProvisionPaid($provisionPaid)
    {
        $this->provisionPaid = $provisionPaid;

        return $this;
    }

    /**
     * Get provisionPaid
     *
     * @return boolean
     */
    public function getProvisionPaid()
    {
        return $this->provisionPaid;
    }

    /**
     * Add observed
     *
     * @param \AppBundle\Entity\AuctionWorkObserve $observed
     *
     * @return AuctionWork
     */
    public function addObserved(\AppBundle\Entity\AuctionWorkObserve $observed)
    {
        $this->observed[] = $observed;

        return $this;
    }

    /**
     * Remove observed
     *
     * @param \AppBundle\Entity\AuctionWorkObserve $observed
     */
    public function removeObserved(\AppBundle\Entity\AuctionWorkObserve $observed)
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
     * Set currentPrice
     *
     * @param integer $currentPrice
     *
     * @return AuctionWork
     */
    public function setCurrentPrice($currentPrice)
    {
        $this->currentPrice = $currentPrice;

        return $this;
    }

    /**
     * Get currentPrice
     *
     * @return integer
     */
    public function getCurrentPrice()
    {
        return $this->currentPrice;
    }

    /**
     * Set offersCount
     *
     * @param integer $offersCount
     *
     * @return AuctionWork
     */
    public function setOffersCount($offersCount)
    {
        $this->offersCount = $offersCount;

        return $this;
    }

    /**
     * Get offersCount
     *
     * @return integer
     */
    public function getOffersCount()
    {
        return $this->offersCount;
    }
}
