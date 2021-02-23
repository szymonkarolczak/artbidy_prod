<?php
// src/UserBundle/Entity/User.php

namespace UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 * @ORM\HasLifecycleCallbacks()
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $newsletter = false;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $fullname;
    
    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $birthdate;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $deathdate;
    
    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $phone;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $invoice_fullname;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $invoice_address;
    
    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $invoice_postal_code;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $invoice_city;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $invoice_country;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $invoice_nip;
    
    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $card;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $image = 'default.png';

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"=0})
     */
    private $views = 0;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $country;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $pinned = false;
    
    /**
     * @ORM\Column(name="premium_annoucement", type="datetime", nullable=true)
     */
    private $annoucement;
    
    /**
     * @ORM\Column(name="premium_database", type="datetime", nullable=true)
     */
    private $database;
    
    /**
     * @ORM\Column(name="role_end", type="datetime", nullable=true)
     */
    private $roleEnd;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $address;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $city;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $googleMaps;
    
    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="User")
     */
    private $creator;
    
    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $socialMedia;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $website;

    /**
     * One Product has Many Features.
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\News", mappedBy="author", cascade={"all"})
     */
    private $newses;

    /**
     * One Product has Many Features.
     * @ORM\OneToMany(targetEntity="UserVisits", mappedBy="user", cascade={"all"})
     */
    private $visits;
    
    public $profilePrefix;
    
    
    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $metatitle;

    /**
     * @ORM\Column(name="metatitle_en",type="string", nullable=true)
     */
    private $metatitleEn;


    /**
     * {@inheritdoc}
     */
    public function setEnabled($boolean)
    {
        $this->enabled = (bool) $boolean;

        return $this;
    }
    
    
    public function getProfilePrefix(){
        return $this->profilePrefix;
    }
    /**
     * @ORM\Column(name="slug", type="string", length=255, options={ "comment"="unique name for url"} )
     */
    public $profileSlug;

    public function setProfileSlug( $slug ) {
        $this->profileSlug = $slug;
        return $this;
    }
    public function getProfileSlug(){
        return $this->profileSlug;
    }
    public function setSlug( $slug ) {
        return $this->setProfileSlug( $slug );
    }
    public function getSlug(){
        return $this->getProfileSlug();
    }
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Set newsletter
     *
     * @param boolean $newsletter
     *
     * @return User
     */
    public function setNewsletter($newsletter)
    {
        $this->newsletter = $newsletter;

        return $this;
    }

    /**
     * Get newsletter
     *
     * @return boolean
     */
    public function getNewsletter()
    {
        return $this->newsletter;
    }

    public function getFlattenRoles()
    {
        $returnData = array();

        foreach($this->roles as $value)
        {
            $value = str_replace("ROLE_", '', $value);
            $value = ucwords(strtolower(str_replace("_", ' ', $value)));
            $returnData[] = $value;
        }
        return $returnData;
    }

    /**
     * Set fullname
     *
     * @param string $fullname
     *
     * @return User
     */
    public function setFullname($fullname)
    {
        $this->fullname = $fullname;

        return $this;
    }

    /**
     * Get fullname
     *
     * @return string
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * Set birthdate
     *
     * @param \DateTime $birthdate
     *
     * @return User
     */
    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    /**
     * Get birthdate
     *
     * @return \DateTime
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    /**
     * Set phone
     *
     * @param string $phone
     *
     * @return User
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set invoiceFullname
     *
     * @param string $invoiceFullname
     *
     * @return User
     */
    public function setInvoiceFullname($invoiceFullname)
    {
        $this->invoice_fullname = $invoiceFullname;

        return $this;
    }

    /**
     * Get invoiceFullname
     *
     * @return string
     */
    public function getInvoiceFullname()
    {
        return $this->invoice_fullname;
    }

    /**
     * Set invoiceAddress
     *
     * @param string $invoiceAddress
     *
     * @return User
     */
    public function setInvoiceAddress($invoiceAddress)
    {
        $this->invoice_address = $invoiceAddress;

        return $this;
    }

    /**
     * Get invoiceAddress
     *
     * @return string
     */
    public function getInvoiceAddress()
    {
        return $this->invoice_address;
    }

    /**
     * Set invoicePostalCode
     *
     * @param string $invoicePostalCode
     *
     * @return User
     */
    public function setInvoicePostalCode($invoicePostalCode)
    {
        $this->invoice_postal_code = $invoicePostalCode;

        return $this;
    }

    /**
     * Get invoicePostalCode
     *
     * @return string
     */
    public function getInvoicePostalCode()
    {
        return $this->invoice_postal_code;
    }

    /**
     * Set invoiceCity
     *
     * @param string $invoiceCity
     *
     * @return User
     */
    public function setInvoiceCity($invoiceCity)
    {
        $this->invoice_city = $invoiceCity;

        return $this;
    }

    /**
     * Get invoiceCity
     *
     * @return string
     */
    public function getInvoiceCity()
    {
        return $this->invoice_city;
    }

    /**
     * Set invoiceCountry
     *
     * @param string $invoiceCountry
     *
     * @return User
     */
    public function setInvoiceCountry($invoiceCountry)
    {
        $this->invoice_country = $invoiceCountry;

        return $this;
    }

    /**
     * Get invoiceCountry
     *
     * @return string
     */
    public function getInvoiceCountry()
    {
        return $this->invoice_country;
    }

    /**
     * Set invoiceNip
     *
     * @param string $invoiceNip
     *
     * @return User
     */
    public function setInvoiceNip($invoiceNip)
    {
        $this->invoice_nip = $invoiceNip;

        return $this;
    }

    /**
     * Get invoiceNip
     *
     * @return string
     */
    public function getInvoiceNip()
    {
        return $this->invoice_nip;
    }

    /**
     * Set image
     *
     * @param string $image
     *
     * @return User
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
     * @ORM\PostLoad
     */
    public function setProfilePrefix()
    {
        $roles = $this->getRoles();
        if(in_array('ROLE_ARTYSTA', $roles)) { 
            $this->profilePrefix = 'artists'; 
        }
        else if(in_array('ROLE_DOM_AUKCYJNY', $roles)) { 
            $this->profilePrefix = 'auction-houses';
        }
        else if(in_array('ROLE_GALERIA', $roles)) {
            $this->profilePrefix = 'galleries';
        }
        else if(in_array('ROLE_REDAKTOR', $roles)) {
            $this->profilePrefix = 'redactor';
        }
        else if(in_array('ROLE_MUZEUM', $roles)) {
            $this->profilePrefix = 'museum';
        } else {
            $this->profilePrefix = 'profile'; 
        }
    }

    /**
     * Set views
     *
     * @param integer $views
     *
     * @return User
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
     * Set pinned
     *
     * @param boolean $pinned
     *
     * @return User
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
     * Set country
     *
     * @param string $country
     *
     * @return User
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set deathdate
     *
     * @param \DateTime $deathdate
     *
     * @return User
     */
    public function setDeathdate($deathdate)
    {
        $this->deathdate = $deathdate;

        return $this;
    }

    /**
     * Get deathdate
     *
     * @return \DateTime
     */
    public function getDeathdate()
    {
        return $this->deathdate;
    }

    /**
     * Set annoucement
     *
     * @param \DateTime $annoucement
     *
     * @return User
     */
    public function setAnnoucement($annoucement)
    {
        $this->annoucement = $annoucement;

        return $this;
    }

    /**
     * Get annoucement
     *
     * @return \DateTime
     */
    public function getAnnoucement()
    {
        return $this->annoucement;
    }

    /**
     * Set database
     *
     * @param \DateTime $database
     *
     * @return User
     */
    public function setDatabase($database)
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Get database
     *
     * @return \DateTime
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Set creator
     *
     * @param \UserBundle\Entity\User $creator
     *
     * @return User
     */
    public function setCreator(\UserBundle\Entity\User $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator
     *
     * @return \UserBundle\Entity\User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set socialMedia
     *
     * @param array $socialMedia
     *
     * @return User
     */
    public function setSocialMedia($socialMedia)
    {
        $this->socialMedia = $socialMedia;

        return $this;
    }

    /**
     * Get socialMedia
     *
     * @return array
     */
    public function getSocialMedia()
    {
        return $this->socialMedia;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return User
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
     * @return User
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
     * Set googleMaps
     *
     * @param string $googleMaps
     *
     * @return User
     */
    public function setGoogleMaps($googleMaps)
    {
        $this->googleMaps = $googleMaps;

        return $this;
    }

    /**
     * Get googleMaps
     *
     * @return string
     */
    public function getGoogleMaps()
    {
        return $this->googleMaps;
    }

    /**
     * Set website
     *
     * @param string $website
     *
     * @return User
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set roleEnd
     *
     * @param \DateTime $roleEnd
     *
     * @return User
     */
    public function setRoleEnd($roleEnd)
    {
        $this->roleEnd = $roleEnd;

        return $this;
    }

    /**
     * Get roleEnd
     *
     * @return \DateTime
     */
    public function getRoleEnd()
    {
        return $this->roleEnd;
    }

    /**
     * Add newse
     *
     * @param \AppBundle\Entity\News $newse
     *
     * @return User
     */
    public function addNewse(\AppBundle\Entity\News $newse)
    {
        $this->newses[] = $newse;

        return $this;
    }

    /**
     * Remove newse
     *
     * @param \AppBundle\Entity\News $newse
     */
    public function removeNewse(\AppBundle\Entity\News $newse)
    {
        $this->newses->removeElement($newse);
    }

    /**
     * Get newses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNewses()
    {
        return $this->newses;
    }

    /**
     * Set card
     *
     * @param array $card
     *
     * @return User
     */
    public function setCard($card)
    {
        $this->card = $card;

        return $this;
    }

    /**
     * Get card
     *
     * @return array
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * Add visit
     *
     * @param \UserBundle\Entity\UserVisits $visit
     *
     * @return User
     */
    public function addVisit(\UserBundle\Entity\UserVisits $visit)
    {
        $this->visits[] = $visit;

        return $this;
    }

    /**
     * Remove visit
     *
     * @param \UserBundle\Entity\UserVisits $visit
     */
    public function removeVisit(\UserBundle\Entity\UserVisits $visit)
    {
        $this->visits->removeElement($visit);
    }

    /**
     * Get visits
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVisits()
    {
        return $this->visits;
    }

    /**
     * Add obserf
     *
     * @param \AppBundle\Entity\ProfileObserve $obserf
     *
     * @return User
     */
    public function addObserf(\AppBundle\Entity\ProfileObserve $obserf)
    {
        $this->observes[] = $obserf;

        return $this;
    }

    /**
     * Remove obserf
     *
     * @param \AppBundle\Entity\ProfileObserve $obserf
     */
    public function removeObserf(\AppBundle\Entity\ProfileObserve $obserf)
    {
        $this->observes->removeElement($obserf);
    }

    /**
     * Get observes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getObserves()
    {
        return $this->observes;
    }

    /**
     * @return mixed
     */
    public function getMetatitle()
    {
        return $this->metatitle;
    }

    /**
     * @param mixed $metatitle
     * @return User
     */
    public function setMetatitle($metatitle)
    {
        $this->metatitle = $metatitle;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMetatitleEn()
    {
        return $this->metatitleEn;
    }

    /**
     * @param mixed $metatitleEn
     * @return User
     */
    public function setMetatitleEn($metatitleEn)
    {
        $this->metatitleEn = $metatitleEn;
        return $this;
    }


}
