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
 * SansPapier\UserDataBundle\Entity\ProductShelf
 *
 * @ORM\Table(name="spdata_product_shelf")
 * @ORM\Entity(repositoryClass="SansPapier\UserDataBundle\Repository\ProductShelfRepository")
 */
class ProductShelf
{

  /**
   *
   * @ORM\Column(name="product_shelf_id", type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $product_shelf_id;

  /**
   * @var integer $product_id_solr
   *
   * @ORM\Column(name="product_id_solr", type="integer")
   */
  private $product_id_solr;

    /**
   * @var integer $transaction_id_fk
   *
   * @ORM\Column(name="transaction_id_fk", type="integer")
   */
  private $transaction_id_fk;
  
   /**
   * @var integer $operation_id_fk
   *
   * @ORM\Column(name="operation_id_fk", type="integer")
   */
  private $operation_id_fk;

  /**
   * @ORM\ManyToOne(targetEntity="User", fetch="EXTRA_LAZY")
   * @ORM\JoinColumn(name="user_id_fk",referencedColumnName="user_id")
   */
  private $user;

  /**
   * @var \DateTime $transactionAt
   * @ORM\Column(type="datetime",name="transaction_at")
   */
  protected $transactionAt;

  /**
   * @var ArrayCollection $product_links
   * @ORM\OneToMany(targetEntity="\SansPapier\UserDataBundle\Entity\ProductLink", mappedBy="product_shelf", cascade={"persist", "remove"})
   */
  protected $product_links;

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

  /**
   * Get product_shelf_id
   *
   * @return integer 
   */
  public function getProductShelfId()
  {
    return $this->product_shelf_id;
  }

  public function __construct()
  {
    $this->users = new \Doctrine\Common\Collections\ArrayCollection;
    $this->transactionAt = new \DateTime('now');
  }

  /**
   * Set transactionAt
   *
   * @param date $transactionAt
   */
  public function setTransactionAt($transactionAt)
  {
    $this->transactionAt = $transactionAt;
  }

  /**
   * Get transactionAt
   *
   * @return date 
   */
  public function getTransactionAt()
  {
    return $this->transactionAt;
  }

  /**
   * Set user
   *
   * @param SansPapier\UserDataBundle\Entity\User $user
   */
  public function setUser(\SansPapier\UserDataBundle\Entity\User $user)
  {
    $this->user = $user;
  }

  /**
   * Get user
   *
   * @return SansPapier\UserDataBundle\Entity\User 
   */
  public function getUser()
  {
    return $this->user;
  }

  /**
   * Add product_links
   *
   * @param SansPapier\UserDataBundle\Entity\ProductLink $productLinks
   */
  public function addProductLink(\SansPapier\UserDataBundle\Entity\ProductLink $productLink)
  {
    $productLink->setProductShelf($this);
    $this->product_links[] = $productLink;
  }

  /**
   * Get product_links
   *
   * @return Doctrine\Common\Collections\Collection 
   */
  public function getProductLinks()
  {
    return $this->product_links;
  }


    /**
     * Set transaction_id_fk
     *
     * @param integer $transactionIdFk
     */
    public function setTransactionIdFk($transactionIdFk)
    {
        $this->transaction_id_fk = $transactionIdFk;
    }

    /**
     * Get transaction_id_fk
     *
     * @return integer 
     */
    public function getTransactionIdFk()
    {
        return $this->transaction_id_fk;
    }
    
    /**
     * Set operation_id_fk
     *
     * @param integer $operationIdFk
     */
    public function setOperationIdFk($operationIdFk)
    {
        $this->operation_id_fk = $operationIdFk;
    }

    /**
     * Get operation_id_fk
     *
     * @return integer 
     */
    public function getOperationIdFk()
    {
        return $this->operation_id_fk;
    }
}