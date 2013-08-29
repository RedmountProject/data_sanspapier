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
 * SansPapier\ShopBundle\Entity\Operation
 *
 * @ORM\Table(name="spshop_operation")
 * @ORM\Entity(repositoryClass="SansPapier\ShopBundle\Repository\OperationRepository")
 */
class Operation
{

  /**
   * @var integer $id
   *
   * @ORM\Column(name="operation_id", type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $operation_id;
  
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
   * @var integer $provider_id_fk
   *
   * @ORM\Column(name="provider_id_fk", type="integer", nullable=true)
   */
  private $provider_id_fk;

  /**
   * @var float $total_price
   *
   * @ORM\Column(name="total_price", type="float")
   */
  private $total_price;

  /**
   * @ORM\OneToMany(targetEntity="Transaction", mappedBy="operation",cascade={"persist"})
   */
  protected $transactions;
  
  /**
   * Pour stocker le status (0 -> rien fait, 1-> refusee, 2-> paiment OK 3-> dilicom livre )
   * @ORM\Column(name="status", type="integer")
   */
  protected $status;
  
  /**
   * Le moyen de paiement utilise.
   * @ORM\Column(name="mean", type="string", length=32)
   */
  protected $mean;
  
  /**
   * Socgen_transaction_id
   * @ORM\Column(name="socgen_transaction_id", type="integer")
   */
  protected $socgen_transaction_id;
  
  /**
   * Dilicom_transaction_id
   * @ORM\Column(name="dilicom_transaction_id", type="string", length=16, nullable=true)
   */
  protected $dilicom_transaction_id;
  
  /**
   * To complete shopping tunnel
   * @ORM\Column(name="from_website", type="string", length=512)
   */
  protected $from_website;

  /**
   * Get id
   *
   * @return integer 
   */
  public function getOperationId()
  {
    return $this->operation_id;
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
   * Set total_price
   *
   * @param float $totalPrice
   */
  public function setTotalPrice($totalPrice)
  {
    $this->total_price = $totalPrice;
  }

  /**
   * Get total_price
   *
   * @return float 
   */
  public function getTotalPrice()
  {
    return $this->total_price;
  }

    public function __construct()
    {
        $this->transactions = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add transactions
     *
     * @param SansPapier\ShopBundle\Entity\Transaction $transactions
     */
    public function addTransaction(\SansPapier\ShopBundle\Entity\Transaction $transaction)
    {
        $transaction->setOperation($this);
        $this->transactions[] = $transaction;
    }

    /**
     * Get transactions
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * Set transactionAt
     *
     * @param datetime $transactionAt
     */
    public function setTransactionAt($transactionAt)
    {
        $this->transactionAt = $transactionAt;
        // for transactions
        foreach ($this->transactions as $transaction)
        {
          $transaction->setTransactionAt($transactionAt);
        }
    }

    /**
     * Get transactionAt
     *
     * @return datetime 
     */
    public function getTransactionAt()
    {
        return $this->transactionAt;
    }

    /**
     * Set status
     *
     * @param integer $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set mean
     *
     * @param string $mean
     */
    public function setMean($mean)
    {
        $this->mean = $mean;
    }

    /**
     * Get mean
     *
     * @return string 
     */
    public function getMean()
    {
        return $this->mean;
    }

    /**
     * Set socgen_transaction_id
     *
     * @param integer $socgenTransactionId
     */
    public function setSocgenTransactionId($socgenTransactionId)
    {
        $this->socgen_transaction_id = $socgenTransactionId;
    }

    /**
     * Get socgen_transaction_id
     *
     * @return integer 
     */
    public function getSocgenTransactionId()
    {
        return $this->socgen_transaction_id;
    }
    
    /**
     * Set dilicom_transaction_id
     *
     * @param string $socgenTransactionId
     */
    public function setDilicomTransactionId($dilicomTransactionId)
    {
        $this->dilicom_transaction_id = $dilicomTransactionId;
    }

    /**
     * Get dilicom_transaction_id
     *
     * @return string 
     */
    public function getDilicomTransactionId()
    {
        return $this->dilicom_transaction_id;
    }

    /**
     * Set from_website
     *
     * @param string $fromWebsite
     */
    public function setFromWebsite($fromWebsite)
    {
        $this->from_website = $fromWebsite;
    }

    /**
     * Get from_website
     *
     * @return string 
     */
    public function getFromWebsite()
    {
        return $this->from_website;
    }
    
    /**
     * Set provider_id_fk
     *
     * @param integer $providerIdFk
     */
    public function setProviderIdFk($providerIdFk)
    {
        $this->provider_id_fk = $providerIdFk;
    }

    /**
     * Get provider_id_fk
     *
     * @return integer 
     */
    public function getProviderIdFk()
    {
        return $this->provider_id_fk;
    }
}