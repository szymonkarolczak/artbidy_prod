<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Invoice
 *
 * @ORM\Table(name="invoice")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\InvoiceRepository")
 */
class Invoice
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
     * @ORM\Column(name="buyer", type="text")
     */
    private $buyer;

    /**
     * @var string
     *
     * @ORM\Column(name="number", type="string", length=255, unique=true)
     */
    private $number;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sellDate", type="date")
     */
    private $sellDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="exposeDate", type="date")
     */
    private $exposeDate;

    /**
     * @var array
     *
     * @ORM\Column(name="products", type="array")
     */
    private $products;

    /**
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Currency")
     */
    private $currency;

    /**
     * @ORM\Column(name="tax", type="integer", length=2)
     */
    private $tax;


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
     * Set buyer
     *
     * @param string $buyer
     *
     * @return Invoice
     */
    public function setBuyer($buyer)
    {
        $this->buyer = $buyer;

        return $this;
    }

    /**
     * Get buyer
     *
     * @return string
     */
    public function getBuyer()
    {
        return $this->buyer;
    }

    /**
     * Set number
     *
     * @param string $number
     *
     * @return Invoice
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set sellDate
     *
     * @param \DateTime $sellDate
     *
     * @return Invoice
     */
    public function setSellDate($sellDate)
    {
        $this->sellDate = $sellDate;

        return $this;
    }

    /**
     * Get sellDate
     *
     * @return \DateTime
     */
    public function getSellDate()
    {
        return $this->sellDate;
    }

    /**
     * Set exposeDate
     *
     * @param \DateTime $exposeDate
     *
     * @return Invoice
     */
    public function setExposeDate($exposeDate)
    {
        $this->exposeDate = $exposeDate;

        return $this;
    }

    /**
     * Get exposeDate
     *
     * @return \DateTime
     */
    public function getExposeDate()
    {
        return $this->exposeDate;
    }

    /**
     * Set products
     *
     * @param array $products
     *
     * @return Invoice
     */
    public function setProducts($products)
    {
        $this->products = $products;

        return $this;
    }

    /**
     * Get products
     *
     * @return array
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Set user
     *
     * @param \UserBundle\Entity\User $user
     *
     * @return Invoice
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

    /**
     * Set tax
     *
     * @param integer $tax
     *
     * @return Invoice
     */
    public function setTax($tax)
    {
        $this->tax = $tax;

        return $this;
    }

    /**
     * Get tax
     *
     * @return integer
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * Set currency
     *
     * @param \AppBundle\Entity\Currency $currency
     *
     * @return Invoice
     */
    public function setCurrency(\AppBundle\Entity\Currency $currency = null)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return \AppBundle\Entity\Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }
}
