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
 * SansPapier\UserDataBundle\Entity\UserSearch
 *
 * @ORM\Table(name="spdata_user_search")
 * @ORM\Entity(repositoryClass="SansPapier\UserDataBundle\Repository\UserSearchRepository")
 */
class UserSearch {

    /**
     * @var integer $id
     *
     * @ORM\Column(name="user_search_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $user_search_id;

    /**
     * @var string $query
     *
     * @ORM\Column(name="query", type="string", length=1024)
     */
    private $query;
    
    /**
     *
     * @var integer $preference
     * @ORM\ManyToOne(targetEntity="Preference")
     * @ORM\JoinColumn(name="preference_id_fk", referencedColumnName="preference_id")
     * 
     */
    
    private $preference;

    public function setPreference($preference) {
        $this->preference = $preference;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getUserSearchId() {
        return $this->user_search_id;
    }

    /**
     * Set query
     *
     * @param string $query
     */
    public function setQuery($query) {
        $this->query = $query;
    }

    /**
     * Get query
     *
     * @return string 
     */
    public function getQuery() {
        return $this->query;
    }

}