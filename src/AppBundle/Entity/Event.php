<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Event
 *
 * @ORM\Table(name="event")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\EventRepository")
 */
class Event
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
     * @Assert\File(
     *     maxSize = "1M",
     *     mimeTypes = {"image/jpeg", "image/png", "image/gif"}
     * )
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
     * @ORM\Column(name="endDate", type="datetime", nullable=true)
     */
    private $endDate;

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
     * @var bool
     *
     * @ORM\Column(name="pinned", type="boolean")
     */
    private $pinned = false;

    /**
     * @ORM\OneToMany(targetEntity="EventLang", mappedBy="event", cascade={"persist", "remove"})
     */
    private $langs;

    /**
     * @ORM\ManyToOne(targetEntity="EventCategory")
     */
    private $category;

    /**
     * @ORM\ManyToMany(targetEntity="UserBundle\Entity\User")
     * @ORM\JoinTable(name="users_events",
     *      joinColumns={@ORM\JoinColumn(name="event_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    private $users;

    /**
     * @ORM\ManyToOne(targetEntity="Exhibition")
     */
    private $exhibition;

    /**
     * @ORM\ManyToOne(targetEntity="HouseAuction")
     */
    private $houseAuction;
    
    /**
     * @ORM\Column(name="slug", type="string", length=255, options={ "comment"="unique name for url"} )
     */
    private $slug;


    public function setSlug( $slug ) {
        $this->slug = $slug;
        return $this;
    }
    
    public function getSlug(){
        return $this->slug;
    }


    public function __construct() {
        $this->startDate = new \DateTime();
        $this->endDate = new \DateTime('+1 month');
        $this->langs = new ArrayCollection();
        $this->users = new ArrayCollection();
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
     * Set title
     *
     * @param string $title
     *
     * @return Event
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
     * Set image
     *
     * @param string $image
     *
     * @return Event
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
     * Set description
     *
     * @param string $description
     *
     * @return Event
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
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return Event
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
     * @return Event
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
     * Set address
     *
     * @param string $address
     *
     * @return Event
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
     * @return Event
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
     * Set pinned
     *
     * @param boolean $pinned
     *
     * @return Event
     */
    public function setPinned($pinned)
    {
        $this->pinned = $pinned;

        return $this;
    }

    /**
     * Get pinned
     *
     * @return bool
     */
    public function getPinned()
    {
        return $this->pinned;
    }

    /**
     * Add lang
     *
     * @param \AppBundle\Entity\EventLang $lang
     *
     * @return Event
     */
    public function addLang(\AppBundle\Entity\EventLang $lang)
    {
        $this->langs[] = $lang;

        return $this;
    }

    /**
     * Remove lang
     *
     * @param \AppBundle\Entity\EventLang $lang
     */
    public function removeLang(\AppBundle\Entity\EventLang $lang)
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
     * Set category
     *
     * @param \AppBundle\Entity\EventCategory $category
     *
     * @return Event
     */
    public function setCategory(\AppBundle\Entity\EventCategory $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \AppBundle\Entity\EventCategory
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Add user
     *
     * @param \UserBundle\Entity\User $user
     *
     * @return Event
     */
    public function addUser(\UserBundle\Entity\User $user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user
     *
     * @param \UserBundle\Entity\User $user
     */
    public function removeUser(\UserBundle\Entity\User $user)
    {
        $this->users->removeElement($user);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Set exhibition
     *
     * @param \AppBundle\Entity\Exhibition $exhibition
     *
     * @return Event
     */
    public function setExhibition(\AppBundle\Entity\Exhibition $exhibition = null)
    {
        $this->exhibition = $exhibition;

        return $this;
    }

    /**
     * Get exhibition
     *
     * @return \AppBundle\Entity\Exhibition
     */
    public function getExhibition()
    {
        return $this->exhibition;
    }

    /**
     * Set houseAuction
     *
     * @param \AppBundle\Entity\HouseAuction $houseAuction
     *
     * @return Event
     */
    public function setHouseAuction(\AppBundle\Entity\HouseAuction $houseAuction = null)
    {
        $this->houseAuction = $houseAuction;

        return $this;
    }

    /**
     * Get houseAuction
     *
     * @return \AppBundle\Entity\HouseAuction
     */
    public function getHouseAuction()
    {
        return $this->houseAuction;
    }
}
