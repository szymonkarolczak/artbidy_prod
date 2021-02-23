<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AdvertLang
 *
 * @ORM\Table(name="news_lang")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\NewsLangRepository")
 */
class NewsLang
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
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="metatitle", type="string", length=255, nullable=true)
     */
    private $metaTitle;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="small_text", type="text")
     */
    private $smallText;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="text", type="text")
     */
    private $text;

    /**
     * @ORM\ManyToOne(targetEntity="News", inversedBy="langs", cascade={"remove"})
     */
    private $news;

    /**
     * @ORM\ManyToOne(targetEntity="Language")
     */
    private $lang;


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
     * Set title
     *
     * @param string $title
     *
     * @return NewsLang
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
     * Set smallText
     *
     * @param string $smallText
     *
     * @return NewsLang
     */
    public function setSmallText($smallText)
    {
        $this->smallText = $smallText;

        return $this;
    }

    /**
     * Get smallText
     *
     * @return string
     */
    public function getSmallText()
    {
        return $this->smallText;
    }

    /**
     * Set text
     *
     * @param string $text
     *
     * @return NewsLang
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set news
     *
     * @param \AppBundle\Entity\News $news
     *
     * @return NewsLang
     */
    public function setNews(\AppBundle\Entity\News $news = null)
    {
        $this->news = $news;

        return $this;
    }

    /**
     * Get news
     *
     * @return \AppBundle\Entity\News
     */
    public function getNews()
    {
        return $this->news;
    }

    /**
     * Set lang
     *
     * @param \AppBundle\Entity\Language $lang
     *
     * @return NewsLang
     */
    public function setLang(\AppBundle\Entity\Language $lang = null)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Get lang
     *
     * @return \AppBundle\Entity\Language
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @return string
     */
    public function getMetaTitle()
    {
        return $this->metaTitle;
    }

    /**
     * @param string $metaTitle
     * @return NewsLang
     */
    public function setMetaTitle($metaTitle)
    {
        $this->metaTitle = $metaTitle;
        return $this;
    }


}
