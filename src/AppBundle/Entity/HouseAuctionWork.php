<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HouseAuctionWork
 *
 * @ORM\Table(name="house_auction_work")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\HouseAuctionWorkRepository")
 */
class HouseAuctionWork
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
     * @ORM\Column(name="soldPrice", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $soldPrice;
    
    /**
     * @ORM\ManyToOne(targetEntity="Work")
     */
    private $work;

    /**
     * @ORM\ManyToOne(targetEntity="HouseAuction", inversedBy="works")
     * @ORM\JoinColumn(name="auction_id", referencedColumnName="id")
     */
    private $auction;


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
     * Set soldPrice
     *
     * @param string $soldPrice
     *
     * @return HouseAuctionWork
     */
    public function setSoldPrice($soldPrice)
    {
        $this->soldPrice = $soldPrice;

        return $this;
    }

    /**
     * Get soldPrice
     *
     * @return string
     */
    public function getSoldPrice()
    {
        return $this->soldPrice;
    }

    /**
     * Set work
     *
     * @param \AppBundle\Entity\Work $work
     *
     * @return HouseAuctionWork
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
     * Set auction
     *
     * @param \AppBundle\Entity\HouseAuction $auction
     *
     * @return HouseAuctionWork
     */
    public function setAuction(\AppBundle\Entity\HouseAuction $auction = null)
    {
        $this->auction = $auction;

        return $this;
    }

    /**
     * Get auction
     *
     * @return \AppBundle\Entity\HouseAuction
     */
    public function getAuction()
    {
        return $this->auction;
    }
}
