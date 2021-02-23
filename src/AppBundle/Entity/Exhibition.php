<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Exhibition
 *
 * @ORM\Table(name="exhibition")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ExhibitionRepository")
 */
class Exhibition
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
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     */
    private $author;

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
     * @ORM\Column(name="startDate", type="datetimetz")
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endDate", type="datetime")
     */
    private $endDate;
    
    /**
     * @ORM\ManyToMany(targetEntity="UserBundle\Entity\User")
     * @ORM\JoinTable(name="users_exhibitions",
     *      joinColumns={@ORM\JoinColumn(name="exhibition_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    private $users;
    
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
     * @var bool
     *
     * @ORM\Column(name="finearts", type="boolean")
     */
    private $finearts = false;

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
     * @ORM\OneToMany(targetEntity="ExhibitionLang", mappedBy="exhibition", cascade={"persist", "remove"})
     */
    private $langs;
    

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->langs = new ArrayCollection();
        $this->startDate = new \DateTime();
        $this->endDate = new \DateTime('+1 month');
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
     * Set image
     *
     * @param string $image
     *
     * @return Exhibition
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
     * @return Exhibition
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
     * @return Exhibition
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
     * Set approved
     *
     * @param boolean $approved
     *
     * @return Exhibition
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
     * @return Exhibition
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
     * Set enabled
     *
     * @param boolean $enabled
     *
     * @return Exhibition
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
     * Set finearts
     *
     * @param boolean $finearts
     *
     * @return Exhibition
     */
    public function setFinearts($finearts)
    {
        $this->finearts = $finearts;

        return $this;
    }

    /**
     * Get finearts
     *
     * @return boolean
     */
    public function getFinearts()
    {
        return $this->finearts;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return Exhibition
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
     * @return Exhibition
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
     * Set author
     *
     * @param \UserBundle\Entity\User $author
     *
     * @return Exhibition
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
     * Add user
     *
     * @param \UserBundle\Entity\User $user
     *
     * @return Exhibition
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
     * Add lang
     *
     * @param \AppBundle\Entity\ExhibitionLang $lang
     *
     * @return Exhibition
     */
    public function addLang(\AppBundle\Entity\ExhibitionLang $lang)
    {
        $this->langs[] = $lang;

        return $this;
    }

    /**
     * Remove lang
     *
     * @param \AppBundle\Entity\ExhibitionLang $lang
     */
    public function removeLang(\AppBundle\Entity\ExhibitionLang $lang)
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
