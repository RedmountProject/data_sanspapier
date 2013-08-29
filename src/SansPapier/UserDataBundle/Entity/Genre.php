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
 * SansPapier\UserDataBundle\Entity\Genre
 *
 * @ORM\Table(name="spdata_genre")
 * @ORM\Entity
 */
class Genre {

    /**
     * @var integer $genre_id
     *
     * @ORM\Column(name="genre_id", type="integer")
     * @ORM\Id
     */
    private $genre_id;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    private $preferences;

    public function addPreferences($preference) {
        $this->preferences = $preference;
    }

    /**
     * Set id
     *
     * @return integer 
     */
    public function setGenreId($id) {
        $this->genre_id = $id;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getGenreId() {
        return $this->genre_id;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName() {
        return $this->name;
    }

    public function __toString() {
        return $this->name;
    }

}