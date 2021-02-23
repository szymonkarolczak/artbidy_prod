<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * HouseAuction
 *
 * @ORM\Table(name="house_auction")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\HouseAuctionRepository")
 */
class HouseAuction
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
     * @ORM\Column(name="address", type="string", length=255)
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=255)
     * @Assert\File(
     *     maxSize = "1M",
     *     mimeTypes = {"image/jpeg", "image/png", "image/gif"}
     * )
     */
    private $image;

    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     */
    private $author;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="addDate", type="datetime")
     */
    private $addDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startDate", type="date")
     */
    private $startDate;

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
     * @var int
     *
     * @ORM\Column(name="status", type="integer", length=1, options={"comment":"0 - brak wyników, 1 - wyniki do akceptacji, 2 - wyniki wprowadzone"})
     */        
    private $status = 0;

    /**
     * @ORM\OneToMany(targetEntity="HouseAuctionWork", mappedBy="auction", cascade={"persist"})
     */
    private $works;

    /**
     * @ORM\OneToMany(targetEntity="HouseAuctionLang", mappedBy="auction", cascade={"persist", "remove"})
     */
    private $langs;
    

    public function __construct()
    {
        $this->addDate = new \DateTime();
        $this->langs = new ArrayCollection();
    }


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
     * Set address
     *
     * @param string $address
     *
     * @return HouseAuction
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return HouseAuction
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set image
     *
     * @param string $image
     *
     * @return HouseAuction
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
     * Set addDate
     *
     * @param \DateTime $addDate
     *
     * @return HouseAuction
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
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return HouseAuction
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
     * Set approved
     *
     * @param boolean $approved
     *
     * @return HouseAuction
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
     * Set pinned
     *
     * @param boolean $pinned
     *
     * @return HouseAuction
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
     * Set status
     *
     * @param integer $status
     *
     * @return HouseAuction
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set author
     *
     * @param \UserBundle\Entity\User $author
     *
     * @return HouseAuction
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
     * Add work
     *
     * @param \AppBundle\Entity\HouseAuctionWork $work
     *
     * @return HouseAuction
     */
    public function addWork(\AppBundle\Entity\HouseAuctionWork $work)
    {
        $this->works[] = $work;

        return $this;
    }

    /**
     * Remove work
     *
     * @param \AppBundle\Entity\HouseAuctionWork $work
     */
    public function removeWork(\AppBundle\Entity\HouseAuctionWork $work)
    {
        $this->works->removeElement($work);
    }

    /**
     * Get works
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getWorks()
    {
        return $this->works;
    }

    /**
     * Add lang
     *
     * @param \AppBundle\Entity\HouseAuctionLang $lang
     *
     * @return HouseAuction
     */
    public function addLang(\AppBundle\Entity\HouseAuctionLang $lang)
    {
        $this->langs[] = $lang;

        return $this;
    }

    /**
     * Remove lang
     *
     * @param \AppBundle\Entity\HouseAuctionLang $lang
     */
    public function removeLang(\AppBundle\Entity\HouseAuctionLang $lang)
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
}
