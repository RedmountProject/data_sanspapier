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
namespace SansPapier\ShopBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SansPapier\ShopBundle\Entity\Transaction
 *
 * @ORM\Table(name="spshop_transaction")
 * @ORM\Entity(repositoryClass="SansPapier\ShopBundle\Repository\TransactionRepository")
 */
class Transaction
{

  /**
   * @var integer $id
   *
   * @ORM\Column(name="transaction_id", type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $transaction_id;

  /**
   * @var datetime $transactionAt
   *
   * @ORM\Column(name="transaction_at", type="datetime")
   */
  private $transactionAt;

  /**
   * @var integer $user_id_fk
   *
   * @ORM\Column(name="user_id_fk", type="integer")
   */
  private $user_id_fk;

  /**
   * @var integer $credit_card_id_fk
   *
   * @ORM\Column(name="credit_card_id_fk", type="integer")
   */
  private $credit_card_id_fk;

  /**
   * @var integer $product_id_solr
   *
   * @ORM\Column(name="product_id_solr", type="integer")
   */
  private $product_id_solr;

  /**
   * Product external_id, "ean13"
   * @var string $external_id
   *
   * @ORM\Column(name="external_id", type="string", length=255)
   */
  private $external_id;

    /**
   * Distributor distributor_id, "GLN"
   * @var string $distributor_id
   *
   * @ORM\Column(name="distributor_id", type="string", length=255)
   */
  private $distributor_id;

  /**
   * @var float price
   *
   * @ORM\Column(name="price", type="float")
   */
  private $price;
  
  /**
   * @var string format_name
   *
   * @ORM\Column(type="string", length=32, nullable=true)
   */
  private $format_name;
  
  /**
   * @var integer $origin_shop_id
   *
   * @ORM\Column(name="origin_shop_id", type="integer")
   */
  private $origin_shop_id;

  /**
   * @var operation
   * @ORM\ManyToOne(targetEntity="Operation", inversedBy="transactions")
   * @ORM\JoinColumn(name="operation_id_fk",referencedColumnName="operation_id")
   */
  private $operation;

  /**
   * Get id
   *
   * @return integer 
   */
  public function getTransactionId()
  {
    return $this->transaction_id;
  }
  
  /**
   * Set id
   */
  public function setTransactionId($transaction_id)
  {
    $this->transaction_id = $transaction_id;
  }

  /**
   * Set transactionAt
   *
   * @param datetime $transactionAt
   */
  public function setTransactionAt($transactionAt)
  {
    $this->transactionAt = $transactionAt;
  }

  /**
   * Set distributorId
   *
   * @param string $distributor_id
   */
  public function setDistributorId($distributor_id)
  {
    $this->distributor_id = $distributor_id;
  }
  
  /**
   * Get distributorId
   *
   * @return string 
   */
  public function getDistributorId()
  {
    return $this->distributor_id;
  }

  /**
   * Set user_id_fk
   *
   * @param integer $userIdFk
   */
  public function setUserIdFk($userIdFk)
  {
    $this->user_id_fk = $userIdFk;
  }

  /**
   * Get user_id_fk
   *
   * @return integer 
   */
  public function getUserIdFk()
  {
    return $this->user_id_fk;
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

  /**
   * Set external_id
   *
   * @param integer $externalId
   */
  public function setExternalId($externalId)
  {
    $this->external_id = $externalId;
  }

  /**
   * Get external_id
   *
   * @return integer 
   */
  public function getExternalId()
  {
    return $this->external_id;
  }

  /**
   * Set credit_card_id_fk
   *
   * @param integer $creditCardIdFk
   */
  public function setCreditCardIdFk($creditCardIdFk)
  {
    $this->credit_card_id_fk = $creditCardIdFk;
  }

  /**
   * Get credit_card_id_fk
   *
   * @return integer 
   */
  public function getCreditCardIdFk()
  {
    return $this->credit_card_id_fk;
  }

  /**
   * Set price
   *
   * @param float $price
   */
  public function setPrice($price)
  {
    $this->price = $price;
  }

  /**
   * Get price
   *
   * @return float 
   */
  public function getPrice()
  {
    return $this->price;
  }

  /**
   * @param string $formatName
   */
  public function setFormatName($formatName)
  {
    $this->format_name = $formatName;
  }

  /**
   * @return string 
   */
  public function getFormatName()
  {
    return $this->format_name;
  }
  
  /**
   * Set origin_shop_id
   *
   * @param integer $originShopId
   */
  public function setOriginShopId($originShopId)
  {
    $this->origin_shop_id = $originShopId;
  }

  /**
   * Get origin_shop_id
   *
   * @return integer 
   */
  public function getOriginShopId()
  {
    return $this->origin_shop_id;
  }

  /**
   * Set operation
   *
   * @param SansPapier\ShopBundle\Entity\Operation $operation
   */
  public function setOperation(\SansPapier\ShopBundle\Entity\Operation $operation)
  {
    $this->operation = $operation;
  }

  /**
   * Get operation
   *
   * @return SansPapier\ShopBundle\Entity\Operation 
   */
  public function getOperation()
  {
    return $this->operation;
  }

}