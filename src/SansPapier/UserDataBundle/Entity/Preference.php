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
use Doctrine\Common\Collections\ArrayCollection as ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SansPapier\UserDataBundle\Entity\Preference
 *
 * @ORM\Table(name="spdata_preference")
 * @ORM\Entity
 */
class Preference {

    /**
     * @var integer $preference_id
     *
     * @ORM\Column(name="preference_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $preference_id;

    /**
     * @ORM\OneToOne(targetEntity="\SansPapier\UserDataBundle\Entity\User", inversedBy="preference",cascade={"persist"})
     * @ORM\JoinColumn(name="user_id_fk",referencedColumnName="user_id",onDelete="CASCADE")
     * @Assert\Valid()
     */
    private $user;

    /**
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="\SansPapier\UserDataBundle\Entity\Gender",cascade={"persist"})
     * @ORM\JoinColumn(name="gender_id_fk",referencedColumnName="gender_id",onDelete="CASCADE")
     */
    private $gender;

    /**
     * @var string $firstname
     * @Assert\MinLength(limit="2",message="Votre nom de famille doit comporter au moins 2 caractères.")
     * @Assert\MaxLength(limit="35",message="Votre nom de famille ne doit pas comporter plus de 35 caractères.")
     * @ORM\Column(name="firstname", type="string", length=35, nullable=true)
     */
    private $firstname;

    /**
     * @var string $lastname
     * @Assert\MinLength(limit="2",message="Votre prénom doit comporter au moins 2 caractères.")
     * @Assert\MaxLength(limit="35",message="Votre prénom ne doit pas comporter plus de 35 caractères.")
     * @ORM\Column(name="lastname", type="string", length=35, nullable=true)
     */
    private $lastname;

    /**
     *
     * @var \DateTime $birthdate
     * 
     * @ORM\Column(name="birthdate", type="date", nullable=true)
     */
    private $birthdate;
    
    /**
     * @var boolean $notifiable
     * @ORM\Column(name="notifiable", type="boolean")
     */
    private $notifiable;

    /**
     * @var ArrayCollection $genres
     *
     * @ORM\ManytoMany(targetEntity="Genre" )
     * @ORM\JoinTable(name="Preference_Genre",
     *     joinColumns={@ORM\JoinColumn(name="preference_id_fk", referencedColumnName="preference_id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="genre_id_fk", referencedColumnName="genre_id")}
     *     )
     * 
     */
    private $genres;

    /**
     * @var ArrayCollection $publisher
     * 
     * @ORM\ManytoMany(targetEntity="Publisher")
     * @ORM\JoinTable(name="Preference_Publisher",
     *     joinColumns={@ORM\JoinColumn(name="preference_id_fk", referencedColumnName="preference_id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="publisher_id_fk", referencedColumnName="publisher_id")}
     *     )
     * 
     */
    private $publishers;


    /**
     * 
     * @var ArrayCollection $addresses
     * 
     * @ORM\OneToMany(targetEntity="Address", mappedBy="preference" ,cascade={"persist"})
     */
    private $addresses;
    
    
    
    public function __construct() {
        $this->addresses = new ArrayCollection();
        $this->notifiable = FALSE;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getPreferenceId() {
        return $this->preference_id;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     */
    public function setFirstname($firstname) {
        $this->firstname = $firstname;
    }

    /**
     * Get firstname
     *
     * @return string 
     */
    public function getFirstname() {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     */
    public function setLastname($lastname) {
        $this->lastname = $lastname;
    }

    /**
     * Get lastname
     *
     * @return string 
     */
    public function getLastname() {
        return $this->lastname;
    }

    /**
     * Set notifiable
     *
     * @param boolean $notifiable
     */
    public function setNotifiable($notifiable) {
        $this->notifiable = $notifiable;
    }

    /**
     * Get notifiable
     *
     * @return boolean 
     */
    public function getNotifiable() {
        return $this->notifiable;
    }

   
    /**
     * Set gender
     *
     * @param SansPapier\UserDataBundle\Entity\Gender $gender
     */
    public function setGender(\SansPapier\UserDataBundle\Entity\Gender $gender) {
        $this->gender = $gender;
    }

    /**
     * Get gender
     *
     * @return SansPapier\UserDataBundle\Entity\Gender 
     */
    public function getGender() {
        return $this->gender;
    }

    /**
     * Set birthdate
     *
     * @param date $birthdate
     */
    public function setBirthdate($birthdate) {
        $this->birthdate = $birthdate;
    }

    /**
     * Get birthdate
     *
     * @return date 
     */
    public function getBirthdate() {
        return $this->birthdate;
    }
    
    /**
     * Set user
     *
     * @param SansPapier\UserDataBundle\Entity\User $user
     */
    public function setUser(\SansPapier\UserDataBundle\Entity\User $user) {
        $this->user = $user;
    }

    /**
     * Add publishers
     *
     * @param SansPapier\UserDataBundle\Entity\Publisher $publishers
     */
    public function addPublisher(\SansPapier\UserDataBundle\Entity\Publisher $publisher) {
        $this->publishers[] = $publisher;
    }

    public function addPublisherArray($publishers) {
        $tmp = new ArrayCollection();
        foreach ($publishers as $publisher) {
            if (!$tmp->contains($publisher)) {
                $tmp->add($publisher);
            }
        }
        $this->publishers = $tmp;
    }

    /**
     * Get publishers
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getPublishers() {
        return $this->publishers;
    }
    
    
     /**
     * Add genres
     *
     * @param SansPapier\UserDataBundle\Entity\Genre $genres
     */
    public function addGenre(\SansPapier\UserDataBundle\Entity\Genre $genre) {
        //$genres->addPreference($this);
        $this->genres[] = $genre;
    }

    public function addGenreArray($genres) {
        $tmp = new ArrayCollection();
        foreach ($genres as $genre) {
            if (!$tmp->contains($genre)) {
                $tmp->add($genre);
            }
        }
        $this->genres = $tmp;
    }

    /**
     * Get genres
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getGenres() {
        return $this->genres;
    }



    /**
     * Add addresses
     *
     * @param SansPapier\UserDataBundle\Entity\Address $addresses
     */
    public function addAddress(\SansPapier\UserDataBundle\Entity\Address $addresse) {
        $this->addresses[] = $addresse;
    }

    /**
     * Get addresses
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getAddresses() {
        return $this->addresses;
    }

    public function getBillingAddress() {
        return $this->addresses[1];
    }

}