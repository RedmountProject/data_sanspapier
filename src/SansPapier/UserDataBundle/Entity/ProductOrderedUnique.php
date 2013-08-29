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
 * SansPapier\UserDataBundle\Entity\ProductOrderedUnique
 *
 * @ORM\Table(name="spdata_product_ordered_unique")
 * @ORM\Entity(repositoryClass="SansPapier\UserDataBundle\Repository\ProductOrderedUniqueRepository")
 */
class ProductOrderedUnique
{
  /**
   * @var integer $product_id
   * @ORM\Id
   * @ORM\Column(name="product_id", type="integer")
   */
  private $product_id;
  
  /**
   * @var integer $publisher_id
   *
   * @ORM\Column(name="publisher_id", type="integer")
   */
  private $publisher_id;
  
  /**
   * @var string $publisher_name
   *
   * @ORM\Column(name="publisher_name", type="string")
   */
  private $publisher_name;
  
  /**
   * @var string $title
   *
   * @ORM\Column(name="title", type="string")
   */
  private $title;
  
  /**
   * @var string $author_firstname
   *
   * @ORM\Column(name="author_firstname", type="string", nullable=true)
   */
  private $author_firstname;

  /**
   * @var string $author_lastname
   *
   * @ORM\Column(name="author_lastname", type="string", nullable=true)
   */
  private $author_lastname;

  public function __construct($pProductId, $pPublisherId, $pPublisherName, $pTitle, $pAuthorFirstname, $pAuthorLastname) {
        $this->product_id = $pProductId;
        $this->publisher_id = $pPublisherId;
        $this->publisher_name = $pPublisherName;
        $this->title = $pTitle;
        $this->author_firstname = $pAuthorFirstname;
        $this->author_lastname = $pAuthorLastname;
    }

  /**
   * Get product_id
   *
   * @return integer 
   */
  public function getProductId()
  {
    return $this->product_id;
  }

  /**
   * Get publisher_id
   *
   * @return integer 
   */
  public function getPublisherId()
  {
    return $this->publisher_id;
  }
  
  /**
   * Get publisher_name
   *
   * @return string 
   */
  public function getPublisherName()
  {
    return $this->publisher_name;
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
   * Get author_firstname
   *
   * @return string 
   */
  public function getAuthorFirstname()
  {
    return $this->author_firstname;
  }
  
  /**
   * Get author_lastname
   *
   * @return string 
   */
  public function getAuthorLastname()
  {
    return $this->author_lastname;
  }
}