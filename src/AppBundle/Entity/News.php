<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * News
 *
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="news")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\NewsRepository")
 */
class News
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
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User", inversedBy="newses")
     */
    private $author;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="add_date", type="datetime")
     */
    private $addDate;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     * @Assert\File(mimeTypes={ "image/jpeg", "image/png", "image/gif" })
     */
    private $image;

    /**
     * @ORM\ManyToOne(targetEntity="NewsCategory")
     */
    private $category;

    /**
     * @ORM\ManyToMany(targetEntity="UserBundle\Entity\User", cascade={"all"})
     * @ORM\JoinTable(name="users_news",
     *      joinColumns={@ORM\JoinColumn(name="news_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    private $users;

    /**
     * @ORM\OneToMany(targetEntity="NewsLang", mappedBy="news", cascade={"persist", "remove"})
     */
    private $langs;

    /**
     * @ORM\Column(name="views", type="integer")
     */
    private $views = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $pinned = false;
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


    public function __construct()
    {
        $this->addDate = new \DateTime();
        $this->users = new ArrayCollection();
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
     * Set addDate
     *
     * @param \DateTime $addDate
     *
     * @return News
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
     * Set image
     *
     * @param string $image
     *
     * @return News
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
     * Set pinned
     *
     * @param boolean $pinned
     *
     * @return News
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
     * Set author
     *
     * @param \UserBundle\Entity\User $author
     *
     * @return News
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
     * Set category
     *
     * @param \AppBundle\Entity\NewsCategory $category
     *
     * @return News
     */
    public function setCategory(\AppBundle\Entity\NewsCategory $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \AppBundle\Entity\NewsCategory
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
     * @return News
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
     * @param \AppBundle\Entity\NewsLang $lang
     *
     * @return News
     */
    public function addLang(\AppBundle\Entity\NewsLang $lang)
    {
        $this->langs[] = $lang;

        return $this;
    }

    /**
     * Remove lang
     *
     * @param \AppBundle\Entity\NewsLang $lang
     */
    public function removeLang(\AppBundle\Entity\NewsLang $lang)
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
     * Set views
     *
     * @param integer $views
     *
     * @return News
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
    public function getObjectTitle( $location = false){
        $langs = $this->getLangs();
        if( $langs && $langs->count() )
        {
            foreach( $langs->getValues() as $elem )
            {
                if( !empty( $elem->getTitle() ) ){
                    if( $location ) {
                        if ($elem->getLang()->getShortcut() == $location) {
                            return $elem->getTitle();
                        }
                    }else {
                        return $elem->getTitle();
                    }
                }
            }
        }
        return false;
    }
}