<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FineartsAuctionObserve
 *
 * @ORM\Table(name="finearts_auction_observe")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\FineartsAuctionObserveRepository")
 */
class FineartsAuctionObserve
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
     * @ORM\ManyToOne(targetEntity="Auction", inversedBy="observed", cascade={"all"})
     * @ORM\JoinColumn(name="auction_id", referencedColumnName="id")
     */
    private $auction;

    /**
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     */
    private $user;


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
     * Set auction
     *
     * @param \AppBundle\Entity\Auction $auction
     *
     * @return FineartsAuctionObserve
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
     * Set user
     *
     * @param \UserBundle\Entity\User $user
     *
     * @return FineartsAuctionObserve
     */
    public function setUser(\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}
