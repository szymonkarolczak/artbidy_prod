<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AuctionBid
 *
 * @ORM\Table(name="work_bid")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WorkBidRepository")
 */
class WorkBid
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
     * @ORM\ManyToOne(targetEntity="Work", inversedBy="bids")
     */
    private $work;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="bidDate", type="datetime")
     */
    private $bidDate;

    /**
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     */
    private $author;

    /**
     * @var decimal
     *
     * @ORM\Column(name="amount", type="decimal", precision=10, scale=2)
     */
    private $amount;

    /**
     * @ORM\ManyToOne(targetEntity="Currency")
     */
    private $currency;

    /**
     * @var bool
     *
     * @ORM\Column(name="provisionPaid",type="boolean")
     */
    private $provisionPaid = false;

    public function __construct()
    {
        $this->bidDate = new \DateTime();
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
     * Set bidDate
     *
     * @param \DateTime $bidDate
     *
     * @return WorkBid
     */
    public function setBidDate($bidDate)
    {
        $this->bidDate = $bidDate;

        return $this;
    }

    /**
     * Get bidDate
     *
     * @return \DateTime
     */
    public function getBidDate()
    {
        return $this->bidDate;
    }

    /**
     * Set amount
     *
     * @param string $amount
     *
     * @return WorkBid
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set work
     *
     * @param \AppBundle\Entity\AuctionWork $auctionWork
     *
     * @return WorkBid
     */
    public function setWork(\AppBundle\Entity\Work $work = null)
    {
        $this->work = $work;

        return $this;
    }

    /**
     * Get work
     *
     * @return \AppBundle\Entity\Work
     */
    public function getWork()
    {
        return $this->work;
    }

    /**
     * Set author
     *
     * @param \AppBundle\Entity\User $author
     *
     * @return AuctionBid
     */
    public function setAuthor(\UserBundle\Entity\User $author = null)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return \AppBundle\Entity\User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set currency
     *
     * @param \AppBundle\Entity\Currency $currency
     *
     * @return AuctionBid
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
     * Set provisionPaid
     *
     * @param boolean $provisionPaid
     *
     * @return WorkBid
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

}
