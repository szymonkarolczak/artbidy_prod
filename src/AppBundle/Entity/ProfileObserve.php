<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProfileObserve
 *
 * @ORM\Table(name="profile_observe")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProfileObserveRepository")
 */
class ProfileObserve
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
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User", cascade={"remove"})
     */
    private $targetUser;

    /**
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User", cascade={"remove"})
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
     * Set targetUser
     *
     * @param \UserBundle\Entity\User $targetUser
     *
     * @return ProfileObserve
     */
    public function setTargetUser(\UserBundle\Entity\User $targetUser = null)
    {
        $this->targetUser = $targetUser;

        return $this;
    }

    /**
     * Get targetUser
     *
     * @return \UserBundle\Entity\User
     */
    public function getTargetUser()
    {
        return $this->targetUser;
    }

    /**
     * Set user
     *
     * @param \UserBundle\Entity\User $user
     *
     * @return ProfileObserve
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
