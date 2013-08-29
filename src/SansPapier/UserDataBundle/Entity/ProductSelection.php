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
 * SansPapier\UserDataBundle\Entity\ProductSelection
 *
 * @ORM\Table(name="spdata_product_selection")
 * @ORM\Entity(repositoryClass="SansPapier\UserDataBundle\Repository\ProductSelectionRepository")
 */
class ProductSelection
{

  /**
   * @ORM\Column(name="product_selection_id", type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $product_selection_id;

  /**
   * @var integer $product_id_solr
   *
   * @ORM\Column(name="product_id_solr", type="integer")
   */
  private $product_id_solr;

  /**
   * @ORM\ManyToMany(targetEntity="User", mappedBy="product_selections", cascade={"persist"})
   */
  private $users;

  /**
   * @var \DateTime $createdAt
   * @ORM\Column(type="date",name="created_at")
   */
  protected $createdAt;

  /**
   * Get id
   *
   * @return integer 
   */
  public function getProductSelectionId()
  {
    return $this->product_selection_id;
  }

  /**
   * Set product_id_solr
   *
   * @param integer $productIdSolr
   */
  public function setProductIdSolr($productIdSolr)
  {
    $this->product_id_solr = $productIdSolr;
  }

  /**
   * Get product_id_solr
   *
   * @return integer 
   */
  public function getProductIdSolr()
  {
    return $this->product_id_solr;
  }

  public function __construct()
  {
    $this->users = new \Doctrine\Common\Collections\ArrayCollection;
    $this->createdAt =  $this->createdAt = new \DateTime('now');
  }

  /**
   * Add users
   *
   * @param SansPapier\UserDataBundle\Entity\User $users
   */
  public function addUser(\SansPapier\UserDataBundle\Entity\User $user)
  {

    $this->users[] = $user;
  }

  /**
   * Get users
   *
   * @return Doctrine\Common\Collections\Collection 
   */
  public function getUsers()
  {
    return $this->users;
  }


    /**
     * Set createdAt
     *
     * @param date $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get createdAt
     *
     * @return date 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}