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
 * @ORM\Entity
 * @ORM\Table(name="spdata_log")
 */
class LogAction
{

  /**
   * @ORM\Id
   * @ORM\Column(type="integer",name="log_id")
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  protected $log_id;

  /**
   * @var string $ip_address
   * @ORM\Column(type="string", length=15)
   */
  protected $ip_address;
  
  /**
   * @var \DateTime $createdAt
   * @ORM\Column(type="datetime",name="created_at")
   */
  protected $createdAt;

  /**
   * @var string $action
   * @ORM\Column(type="string", length=64)
   */
  protected $action;

  public function __construct()
  {
    // created at
    $this->createdAt = new \DateTime('now');
    $this->ip_address = $_SERVER['REMOTE_ADDR'];
  }

  /**
   * Set action
   *
   * @param string $action
   */
  public function setAction($action)
  {
    $this->action = $action;
  }
  
  public function getIpAddress()
  {
    return $this->ip_address;
  }
  
  public function getCreatedAt()
  {
    return $this->createdAt;
  }
  
  public function getAction()
  {
    return $this->action;
  }
}