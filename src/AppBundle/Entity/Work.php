<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * Work
 *
 * @ORM\Table(name="work")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WorkRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Work
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
     * @ORM\Column(name="technique_type", type="string", length=255)
     */
    private $technique_type;

    /**
     * @var string
     *
     * @ORM\Column(name="creator", type="string", length=255)
     */
    private $creator;
    
    /**
     * @var string
     *
     * @ORM\Column(name="artist", type="string", length=255)
     */
    private $artist;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="metatitle", type="string", length=255,nullable=true)
     */
    private $metatitle;

    /**
     * @var string
     *
     * @ORM\Column(name="metatitle_en", type="string", length=255, nullable=true)
     */
    private $metatitleEn;
    
    /**
     * @var DateTime
     *
     * @ORM\Column(name="add_date", type="datetime")
     */
    private $add_date;

    /**
     * @var string
     *
     * @ORM\Column(name="technique", type="string", length=255, nullable=true)
     */
    private $technique;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="style", type="string", length=255, nullable=true)
     */
    private $style;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $dimensions;

    /**
     * @var bool
     *
     * @ORM\Column(name="priceOnRequest", type="boolean", nullable=true)
     */
    private $priceOnRequest = false;

    /**
     * @var string
     *
     * @ORM\Column(name="price", type="decimal", precision=15,scale=2, nullable=true)
     */
    private $price;

    /**
     * @var string
     *
     * @ORM\Column(name="work_year", type="string", length=4)
     */
    private $year;

    /**
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="Currency")
     */
    private $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     */
    private $author;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     * @Assert\File(
     *     maxSize = "1M",
     *     mimeTypes = {"image/jpeg", "image/png", "image/gif"}
     * )
     */
    private $image;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="approved", type="boolean")
     */
    private $approved = false;
    
    /**
     * @var DateTime
     *
     * @ORM\Column(name="approved_date", type="datetime", nullable=true)
     */
    private $approved_date;
    
    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     */
    private $approved_by;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="pinned", type="boolean")
     */
    private $pinned = false;

    /**
     * @var string
     *
     * @ORM\Column(name="gallery", type="json_array", nullable=true)
     */
    private $gallery;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="display", type="boolean")
     */
    private $display = true;

    /**
     * @ORM\Column(name="views", type="integer")
     */
    private $views = 0;

    /**
     * @ORM\OneToMany(targetEntity="AuctionWork", mappedBy="work", cascade={"all"})
     */
    private $auctionWorks;

    /**
     * @ORM\OneToMany(targetEntity="WorkBid", mappedBy="work", cascade={"all"})
     */
    private $bids;

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
        $this->add_date = new \DateTime();
        $this->auctionWorks = new ArrayCollection();
        $this->bids = new ArrayCollection();
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
     * Add bid
     *
     * @param \AppBundle\Entity\WorkBid $bid
     *
     * @return Work
     */
    public function addBid(\AppBundle\Entity\WorkBid $bid)
    {
        $this->bids[] = $bid;

        return $this;
    }

    /**
     * Remove bid
     *
     * @param \AppBundle\Entity\WorkBid $bid
     */
    public function removeBid(\AppBundle\Entity\WorkBid $bid)
    {
        $this->bids->removeElement($bid);
    }

    /**
     * Get bids
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBids()
    {
        return $this->bids;
    }

    /**
     * Set artist
     *
     * @param string $artist
     *
     * @return Work
     */
    public function setArtist($artist)
    {
        $this->artist = $artist;

        return $this;
    }

    /**
     * Get artist
     *
     * @return string
     */
    public function getArtist()
    {
        return $this->artist;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Work
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
     * Set technique
     *
     * @param string $technique
     *
     * @return Work
     */
    public function setTechnique($technique)
    {
        $this->technique = $technique;

        return $this;
    }

    /**
     * Get technique
     *
     * @return string
     */
    public function getTechnique()
    {
        return $this->technique;
    }

    /**
     * Set edition
     *
     * @param string $edition
     *
     * @return Work
     */
    public function setEdition($edition)
    {
        $this->edition = $edition;

        return $this;
    }

    /**
     * Get edition
     *
     * @return string
     */
    public function getEdition()
    {
        return $this->edition;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Work
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set style
     *
     * @param string $style
     *
     * @return Work
     */
    public function setStyle($style)
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Get style
     *
     * @return string
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * Set catalog
     *
     * @param string $catalog
     *
     * @return Work
     */
    public function setCatalog($catalog)
    {
        $this->catalog = $catalog;

        return $this;
    }

    /**
     * Get catalog
     *
     * @return string
     */
    public function getCatalog()
    {
        return $this->catalog;
    }

    /**
     * Set priceOnRequest
     *
     * @param boolean $priceOnRequest
     *
     * @return Work
     */
    public function setPriceOnRequest($priceOnRequest)
    {
        $this->priceOnRequest = $priceOnRequest;

        return $this;
    }

    /**
     * Get priceOnRequest
     *
     * @return bool
     */
    public function getPriceOnRequest()
    {
        return $this->priceOnRequest;
    }

    /**
     * Set price
     *
     * @param string $price
     *
     * @return Work
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Work
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
     * Set signatures
     *
     * @param string $signatures
     *
     * @return Work
     */
    public function setSignatures($signatures)
    {
        $this->signatures = $signatures;

        return $this;
    }

    /**
     * Get signatures
     *
     * @return string
     */
    public function getSignatures()
    {
        return $this->signatures;
    }

    /**
     * Set author
     *
     * @param integer $author
     *
     * @return Work
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return int
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set currency
     *
     * @param string $currency
     *
     * @return Work
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set image
     *
     * @param string $image
     *
     * @return Work
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
     * Set approved
     *
     * @param boolean $approved
     *
     * @return Work
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
     * @return Work
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
     * Set addDate
     *
     * @param \DateTime $addDate
     *
     * @return Work
     */
    public function setAddDate($addDate)
    {
        $this->add_date = $addDate;

        return $this;
    }

    /**
     * Get addDate
     *
     * @return \DateTime
     */
    public function getAddDate()
    {
        return $this->add_date;
    }

    /**
     * Set approvedDate
     *
     * @param \DateTime $approvedDate
     *
     * @return Work
     */
    public function setApprovedDate($approvedDate)
    {
        $this->approved_date = $approvedDate;

        return $this;
    }

    /**
     * Get approvedDate
     *
     * @return \DateTime
     */
    public function getApprovedDate()
    {
        return $this->approved_date;
    }

    /**
     * Set approvedBy
     *
     * @param integer $approvedBy
     *
     * @return Work
     */
    public function setApprovedBy($approvedBy)
    {
        $this->approved_by = $approvedBy;

        return $this;
    }

    /**
     * Get approvedBy
     *
     * @return integer
     */
    public function getApprovedBy()
    {
        return $this->approved_by;
    }

    /**
     * @ORM\PostRemove()
     */
    public function deleteImage()
    {
        if (file_exists('files/work/'.$this->getImage())) {
            @unlink('files/work/' . $this->getImage());
        }
    }

    /**
     * Set gallery
     *
     * @param array $gallery
     *
     * @return Work
     */
    public function setGallery($gallery)
    {
        $this->gallery = $gallery;

        return $this;
    }

    /**
     * Get gallery
     *
     * @return array
     */
    public function getGallery()
    {
        return $this->gallery;
    }

    /**
     * Set creator
     *
     * @param string $creator
     *
     * @return Work
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator
     *
     * @return string
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set display
     *
     * @param boolean $display
     *
     * @return Work
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Get display
     *
     * @return boolean
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set techniqueType
     *
     * @param string $techniqueType
     *
     * @return Work
     */
    public function setTechniqueType($techniqueType)
    {
        $this->technique_type = $techniqueType;

        return $this;
    }

    /**
     * Get techniqueType
     *
     * @return string
     */
    public function getTechniqueType()
    {
        return $this->technique_type;
    }

    /**
     * Set dimensions
     *
     * @param string $dimensions
     *
     * @return Work
     */
    public function setDimensions($dimensions)
    {
        $this->dimensions = $dimensions;

        return $this;
    }

    /**
     * Get dimensions
     *
     * @return string
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * Set year
     *
     * @param string $year
     *
     * @return Work
     */
    public function setYear($year)
    {
        $this->year = $year;

        return $this;
    }

    /**
     * Get year
     *
     * @return string
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Set views
     *
     * @param integer $views
     *
     * @return Work
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
     * Add auctionWork
     *
     * @param \AppBundle\Entity\AuctionWork $auctionWork
     *
     * @return Work
     */
    public function addAuctionWork(\AppBundle\Entity\AuctionWork $auctionWork)
    {
        $this->auctionWorks[] = $auctionWork;

        return $this;
    }

    /**
     * Remove auctionWork
     *
     * @param \AppBundle\Entity\AuctionWork $auctionWork
     */
    public function removeAuctionWork(\AppBundle\Entity\AuctionWork $auctionWork)
    {
        $this->auctionWorks->removeElement($auctionWork);
    }

    /**
     * Get auctionWorks
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAuctionWorks()
    {
        return $this->auctionWorks;
    }

    /**
     * @return string
     */
    public function getMetatitle()
    {
        return $this->metatitle;
    }

    /**
     * @param string $metatitle
     * @return Work
     */
    public function setMetatitle($metatitle)
    {
        $this->metatitle = $metatitle;
        return $this;
    }

    /**
     * @return string
     */
    public function getMetatitleEn()
    {
        return $this->metatitleEn;
    }

    /**
     * @param string $metatitleEn
     * @return Work
     */
    public function setMetatitleEn($metatitleEn)
    {
        $this->metatitleEn = $metatitleEn;
        return $this;
    }


}
