<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SocialMedia
 *
 * @ORM\Table(name="social_media")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SocialMediaRepository")
 */
class SocialMedia
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
     * @ORM\Column(name="url", type="string", length=255, unique=true)
     */
    private $url;

    /**
     * @var string
     * 
     * @Assert\Choice(callback = "getIcons")
     * @ORM\Column(name="icon", type="string", length=255)
     */
    private $icon;

    /**
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;

    /**
     * Get images from folder
     *
     * @return array
     */
    public static function getIcons()
    {
        $files = glob('assets/images/social/*');
        $files = array_map(function($file) {
            return basename($file);
        }, $files);
        return array_combine($files, $files);
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
     * Set url
     *
     * @param string $url
     *
     * @return SocialMedia
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set icon
     *
     * @param string $icon
     *
     * @return SocialMedia
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get icon
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     *
     * @return SocialMedia
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }
}

