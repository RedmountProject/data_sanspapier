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

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * SansPapier\UserDataBundle\Entity\CreditCard
 *
 * @ORM\Table(name="spdata_credit_card")
 * @ORM\Entity
 */
class CreditCard
{

  /**
   * @var integer $credit_card_id
   *
   * @ORM\Column(name="credit_card_id", type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $credit_card_id;

  /**
   * @var string $number
   * @Assert\NotBlank()
   * @ORM\Column(name="number", type="string", length=255)
   */
  private $number;

  /**
   * @var date $expiration
   * @Assert\Date();
   * @ORM\Column(name="expiration", type="date")
   */
  private $expiration;

  /**
   * @var string $holder
   * 
   * @ORM\Column(name="holder", type="string", length=255)
   */
  private $holder;

  /**
   * @Assert\NotNull()
   * @var type 
   * @ORM\ManyToOne(targetEntity="\SansPapier\UserDataBundle\Entity\User", inversedBy="credit_cards")
   * @ORM\JoinColumn(name="user_id_fk",referencedColumnName="user_id")
   */
  private $user;

  /**
   * @Assert\NotBlank()
   * @ORM\ManyToOne(targetEntity="\SansPapier\UserDataBundle\Entity\CreditCardType",cascade={"persist"})
   * @ORM\JoinColumn(name="credit_card_type_id_fk",referencedColumnName="credit_card_type_id",onDelete="CASCADE")
   */
  private $credit_card_type;
  
  public function __construct()
  {
    $this->expiration = new \DateTime("now");
  }

  /**
   * @Assert\True(message="credit_card.expired")
   * @return bool
   */
  public function isExpirationDateValid()
  {
    if (substr($this->expiration->format('ym'), 2, 2) < 1 || substr($this->expiration->format('ym'), 2, 2) > 12)
      return false;
    if ($this->expiration->format('ym') < date('ym'))
      return false;
  }

  /**
   * @Assert\True(message="credit_card.number.invalid")
   * @return bool
   */
  public function isNumberCorrectForType()
  {
    $regexp = $this->getCreditCardType()->getRegex();
    if (!preg_match($regexp, $this->number))
    {
      return false;
    }
  }

  /**
   * This is based in Luhn Algorithm
   * @see http://en.wikipedia.org/wiki/Luhn_algorithm
   *
   * @Assert\True(message="credit_card.checksum.invalid")
   * @return bool
   */
  public function isChecksumCorrect()
  {
    $cardnumber = $this->number;

    $aux = '';
    foreach (str_split(strrev($cardnumber)) as $pos => $digit)
    {
// Multiply * 2 all even digits
      $aux .= ($pos % 2 != 0) ? $digit * 2 : $digit;
    }
// Sum all digits in string
    $checksum = array_sum(str_split($aux));

// Card is OK if the sum is an even multiple of 10 and not 0
    return ($checksum != 0 && $checksum % 10 == 0);
  }

  /**
   * Set number
   *
   * @param string $number
   */
  public function setNumber($number)
  {
    $this->number = $number;
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
  
  public function getMaskedNumber()
  {
   return "****-****-****-".substr($this->number,-4);
  }

  /**
   * Set expiration
   *
   * @param date $expiration
   */
  public function setExpiration($expiration)
  {
    $this->expiration = $expiration;
  }

  /**
   * Get expiration
   *
   * @return date 
   */
  public function getExpiration()
  {
    return $this->expiration;
  }

  /**
   * Set holder
   *
   * @param string $holder
   */
  public function setHolder($holder)
  {
    $this->holder = $holder;
  }

  /**
   * Get holder
   *
   * @return string 
   */
  public function getHolder()
  {
    return $this->holder;
  }

  /**
   * for this mother fucking CRUD generator
   */
  public function getCreditCardId()
  {
    return $this->credit_card_id;
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
   * Set credit_card_type
   *
   * @param SansPapier\UserDataBundle\Entity\CreditCardType $creditCardType
   */
  public function setCreditCardType(\SansPapier\UserDataBundle\Entity\CreditCardType $creditCardType)
  {
    $this->credit_card_type = $creditCardType;
  }

  /**
   * Get credit_card_type
   *
   * @return SansPapier\UserDataBundle\Entity\CreditCardType 
   */
  public function getCreditCardType()
  {
    return $this->credit_card_type;
  }
}