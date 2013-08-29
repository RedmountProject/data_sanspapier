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
 * SansPapier\UserDataBundle\Entity\Format
 *
 * @ORM\Table(name="spdata_format")
 * @ORM\Entity
 */
class Format {

    /**
     * @var integer $format_id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $format_id;

    /**
     * @var string $name
     *
     * @ORM\Column(type="string", length=32)
     */
    private $name;
    
    /**
     * @var string $onix_code
     *
     * @ORM\Column(type="string", length=4)
     */
    private $onix_code;

    /**
     * @return integer 
     */
    public function getFormatId() {
        return $this->format_id;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return string 
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * @return string 
     */
    public function getOnixCode() {
        return $this->onix_code;
    }

    public function __toString() {
        return $this->getName();
    }

}