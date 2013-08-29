<?php
/*  Copyright (C) 2013 DELABY Benoit

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
namespace SansPapier\UserDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SansPapier\UserDataBundle\Entity\ProductLink
 *
 * @ORM\Table(name="spdata_product_link")
 * @ORM\Entity(repositoryClass="SansPapier\UserDataBundle\Repository\ProductLinkRepository")
 */
class ProductLink
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="product_link_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $product_link_id;

    /**
     * @var string $url
     *
     * @ORM\Column(name="url", type="string", length=1024)
     */
    private $url;
    
    /**
     * @var string $format_id
     *
     * @ORM\Column(length=4)
     */
    private $format_id;
    
    /**
     * @var string $format_description
     *
     * @ORM\Column(length=128)
     */
    private $format_description;
    
    /**
     * @ORM\ManyToOne(targetEntity="\SansPapier\UserDataBundle\Entity\ProductShelf")
     * @ORM\JoinColumn(name="product_shelf_id_fk",referencedColumnName="product_shelf_id")
     */
    private $product_shelf;


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
     * Set url
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
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
     * Set format_id
     *
     * @param string $format_id
     */
    public function setFormatId($format_id)
    {
        $this->format_id = $format_id;
    }

    /**
     * Get format_id
     *
     * @return string 
     */
    public function getFormatId()
    {
        return $this->format_id;
    }
    
    /**
     * Set format_description
     *
     * @param string $format_description
     */
    public function setFormatDescription($format_description)
    {
        $this->format_description = $format_description;
    }

    /**
     * Get format_description
     *
     * @return string 
     */
    public function getFormatDescription()
    {
        return $this->format_description;
    }

    /**
     * Get product_link_id
     *
     * @return integer 
     */
    public function getProductLinkId()
    {
        return $this->product_link_id;
    }

    /**
     * Set product_shelf
     *
     * @param SansPapier\UserDataBundle\Entity\ProductShelf $productShelf
     */
    public function setProductShelf(\SansPapier\UserDataBundle\Entity\ProductShelf $productShelf)
    {
        $this->product_shelf = $productShelf;
    }

    /**
     * Get product_shelf
     *
     * @return SansPapier\UserDataBundle\Entity\ProductShelf 
     */
    public function getProductShelf()
    {
        return $this->product_shelf;
    }
}