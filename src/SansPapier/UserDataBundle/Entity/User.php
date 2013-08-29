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

use FOS\UserBundle\Entity\User as BaseUser;
use Doctrine\Common\Collections\ArrayCollection as ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;

/**
 * @ORM\Entity
 * @ORM\Table(name="spdata_user")
 */
class User extends BaseUser
{

  /**
   * @ORM\Id
   * @ORM\Column(type="integer",name="user_id")
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  protected $user_id;

  /**
   * @var \DateTime $createdAt
   * @ORM\Column(type="date",name="created_at")
   */
  protected $createdAt;

  /**
   * @ORM\OneToOne(targetEntity="Preference", mappedBy="user",cascade={"persist"})
   * @ORM\JoinColumn(name="preference_id_fk",referencedColumnName="preference_id",onDelete="CASCADE") 
   */
  protected $preference;

  /**
   * @var ArrayCollection $product_selections
   * @OrderBy({"createdAt" = "DESC"})
   * @ORM\ManyToMany(targetEntity="ProductSelection", inversedBy="users", cascade={"persist"})
   * @ORM\JoinTable(name="spdata_user_product_selection",
   *      joinColumns={@ORM\JoinColumn(name="user_id_fk", referencedColumnName="user_id")},
   *      inverseJoinColumns={@ORM\JoinColumn(name="product_selection_id_fk", referencedColumnName="product_selection_id")}
   *      )
   */
  protected $product_selections;

  /**
   * @var ArrayCollection $product_shelfs
   * @ORM\OneToMany(targetEntity="\SansPapier\UserDataBundle\Entity\ProductShelf", mappedBy="user", cascade={"persist", "remove"})
   */
  protected $product_shelfs;

  /**
   * Url to include in the email for confirmation token validation
   * @ORM\Column(type="string",name="confirmation_url", length=1024, nullable=true)
   */
  protected $confirmation_url;

  /**
   * @ORM\OneToMany(targetEntity="\SansPapier\UserDataBundle\Entity\CreditCard", mappedBy="user", cascade={"persist", "remove"})
   */
  protected $credit_cards;

  /**
   * @ORM\OneToOne(targetEntity="CreditCard",  cascade={"persist", "remove"})
   * @ORM\JoinColumn(name="selected_credit_card_id_fk",referencedColumnName="credit_card_id",onDelete="CASCADE") 
   */
  protected $selected_credit_card;

  public function __construct()
  {
    parent::__construct();
    // created at
    $this->createdAt = new \DateTime('now');
    // client code is username
    $this->username = strtoupper(uniqid("SP"));

    //
    $this->product_selections = new ArrayCollection();
    $this->product_shelfs = new ArrayCollection();
    // default preference
    $preference = new Preference();
    
    $this->preference = $preference; // persisted
    $this->preference->setUser($this);
  }

  /**
   * Set preference
   *
   * @param SansPapier\UserDataBundle\Entity\Preference $preference
   */
  public function setPreference(\SansPapier\UserDataBundle\Entity\Preference $preference)
  {
    $this->preference = $preference;
  }

  /**
   * Get preference
   *
   * @return SansPapier\UserDataBundle\Entity\Preference 
   */
  public function getPreference()
  {
    return $this->preference;
  }

  /**
   * Get id
   *
   * @return integer 
   */
  public function getUserId()
  {
    return $this->user_id;
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

  /**
   * Set product_selections
   *
   * @param ArrayCollection $productSelections
   */
  public function setProductSelections(ArrayCollection $productSelections)
  {
    $this->product_selections = $productSelections;
  }

  /**
   * Get product_selections
   *
   * @return ArrayCollection 
   */
  public function getProductSelections()
  {
    return $this->product_selections;
  }

  /**
   * Set product_shelfs
   *
   * @param ArrayCollection $productShelfs
   */
  public function setProductShelfs(ArrayCollection $productShelfs)
  {
    $this->product_shelfs = $productShelfs;
  }

  /**
   * Get product_shelfs
   *
   * @return ArrayCollection
   */
  public function getProductShelfs()
  {
    return $this->product_shelfs;
  }

  /**
   * Add product_selections
   *
   * @param SansPapier\UserDataBundle\Entity\ProductSelection $productSelections
   */
  public function addProductSelection(\SansPapier\UserDataBundle\Entity\ProductSelection $productSelection)
  {
    $productSelection->addUser($this);
    $this->product_selections[] = $productSelection;
  }

  /**
   * Add product_shelfs
   *
   * @param SansPapier\UserDataBundle\Entity\ProductShelf $productShelfs
   */
  public function addProductShelf(\SansPapier\UserDataBundle\Entity\ProductShelf $productShelf)
  {
    $productShelf->addUser($this);
    $this->product_shelfs[] = $productShelf;
  }

  /**
   * Add credit_cards
   *
   * @param SansPapier\UserDataBundle\Entity\CreditCard $creditCards
   */
  public function addCreditCard(\SansPapier\UserDataBundle\Entity\CreditCard $creditCards)
  {
    $this->credit_cards[] = $creditCards;
  }

  /**
   * Get credit_cards
   *
   * @return Doctrine\Common\Collections\Collection 
   */
  public function getCreditCards()
  {
    return $this->credit_cards;
  }

  /**
   * Set confirmation_url
   *
   * @param string $confirmationUrl
   */
  public function setConfirmationUrl($confirmationUrl)
  {
    $this->confirmation_url = $confirmationUrl;
  }

  /**
   * Get confirmation_url
   *
   * @return string 
   */
  public function getConfirmationUrl()
  {
    return $this->confirmation_url;
  }

  /**
   * Set selected_credit_card
   *
   * @param SansPapier\UserDataBundle\Entity\CreditCard $selectedCreditCard
   */
  public function setSelectedCreditCard(\SansPapier\UserDataBundle\Entity\CreditCard $selectedCreditCard = NULL)
  {
    $this->selected_credit_card = $selectedCreditCard;
  }

  /**
   * Get selected_credit_card
   *
   * @return SansPapier\UserDataBundle\Entity\CreditCard 
   */
  public function getSelectedCreditCard()
  {
    return $this->selected_credit_card;
  }
  
  public function isPreferenceComplete()
  {
    // prefs
    $pref = $this->preference;
    // no first name
    if ($pref->getFirstName() == NULL)
    {
      return FALSE;
    }
    // no lastname
    if ($pref->getLastName() == NULL)
    {
      return FALSE;
    }

    // billing address
    $addresses = $pref->getAddresses();
    $billing = $addresses[1];
//    if ($billing->getAddressee() == NULL)
//    {
//      return FALSE;
//    }
//    if ($billing->getAddress() == NULL)
//    {
//      return FALSE;
//    }
//    if ($billing->getZip() == NULL)
//    {
//      return FALSE;
//    }
//    if ($billing->getCity() == NULL)
//    {
//      return FALSE;
//    }
    if ($billing->getCountryCode() == NULL)
    {
      return FALSE;
    }

    return TRUE;
  }
  
}