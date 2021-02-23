<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AuctionBid
 *
 * @ORM\Table(name="auction_bid")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AuctionBidRepository")
 */
class AuctionBid
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
     * @ORM\ManyToOne(targetEntity="AuctionWork", inversedBy="bids")
     */
    private $auctionWork;

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
     * @return AuctionBid
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
     * @return AuctionBid
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
     * Set auctionWork
     *
     * @param \AppBundle\Entity\AuctionWork $auctionWork
     *
     * @return AuctionBid
     */
    public function setAuctionWork(\AppBundle\Entity\AuctionWork $auctionWork = null)
    {
        $this->auctionWork = $auctionWork;

        return $this;
    }

    /**
     * Get auctionWork
     *
     * @return \AppBundle\Entity\AuctionWork
     */
    public function getAuctionWork()
    {
        return $this->auctionWork;
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
}
