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
 * SansPapier\UserDataBundle\Entity\Publisher
 *
 * @ORM\Table(name="spdata_publisher")
 * @ORM\Entity(repositoryClass="SansPapier\UserDataBundle\Repository\PublisherRepository")
 */
class Publisher
{

  /**
   * @var integer $id
   *
   * @ORM\Column(name="publisher_id", type="integer")
   * @ORM\Id
   */
  private $publisher_id;

  /**
   * @var string 
   *
   * @ORM\Column(name="name", type="string", length=255)
   */
  private $name;

  /**
   * @var string 
   *
   * @ORM\Column(name="website", type="string", length=255)
   */
  private $website;

  /**
   * @var string
   *
   * @ORM\Column(name="external_id_ext", type="string", length=32)
   */
  private $external_id_ext;

  
  private $preferences;
  
  
  public function addPreferences($preference){
      $this->preferences = $preference;
  }
  
  /**
   * Set id
   *
   * @return integer 
   */
  public function setPublisherId($id)
  {
    $this->publisher_id = $id;
  }
  
  /**
   * Get id
   *
   * @return integer 
   */
  public function getPublisherId()
  {
    return $this->publisher_id;
  }

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
   * Set website
   *
   * @param string $website
   */
  public function setWebsite($website)
  {
    $this->website = $website;
  }

  /**
   * Get website
   *
   * @return string 
   */
  public function getWebsite()
  {
    return $this->website;
  }

  /**
   * Set external_id_ext
   *
   * @param string $externalIdExt
   */
  public function setExternalIdExt($externalIdExt)
  {
    $this->external_id_ext = $externalIdExt;
  }

  /**
   * Get external_id_ext
   *
   * @return string 
   */
  public function getExternalIdExt()
  {
    return $this->external_id_ext;
  }

}