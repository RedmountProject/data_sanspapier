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
 * SansPapier\UserDataBundle\Entity\AddressType
 *
 * @ORM\Table(name="spdata_address_type")
 * @ORM\Entity(repositoryClass="SansPapier\UserDataBundle\Repository\AddressTypeRepository")
 */
class AddressType
{

  /**
   * @var integer $id
   *
   * @ORM\Column(name="address_type_id", type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $address_type_id;

  /**
   * @var string $name
   *
   * @ORM\Column(name="name", type="string", length=255)
   */
  private $name;

  /**
   * Set name
   *
   * @param string $name
   */
  public function setName($name)
  {
    $this->name = $name;
  }

  /**
   * Get name
   *
   * @return string 
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Get address_type_id
   *
   * @return integer 
   */
  public function getAddressTypeId()
  {
    return $this->address_type_id;
  }

}