<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FineartsAuctionObserve
 *
 * @ORM\Table(name="auction_work_observe")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AuctionWorkObserveRepository")
 */
class AuctionWorkObserve
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
     * @ORM\ManyToOne(targetEntity="AuctionWork", inversedBy="observed", cascade={"all"})
     */
    private $auctionWork;

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
     * Set auctionWork
     *
     * @param \AppBundle\Entity\AuctionWork $auctionWork
     *
     * @return AuctionWorkObserve
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
     * Set user
     *
     * @param \UserBundle\Entity\User $user
     *
     * @return AuctionWorkObserve
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
