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
 * SansPapier\UserDataBundle\Entity\Address
 *
 * @ORM\Table(name="spdata_address")
 * @ORM\Entity(repositoryClass="SansPapier\UserDataBundle\Repository\AddressRepository")
 */
class Address {

    /**
     *
     * @ORM\Column(name="address_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $address_id;

    /**
     * @var string $addressee
     * @ORM\Column(name="addressee", type="string", length=255, nullable=true)
     */
    private $addressee;

    /**
     * @var string $company_name
     * 
     * @ORM\Column(name="company_name", type="string", length=255, nullable=true)
     */
    private $company_name;

    /**
     * @var string $address
     * @ORM\Column(name="address", type="string", length=510, nullable=true)
     */
    private $address;

    /**
     * @var string $complement
     *
     * @ORM\Column(name="complement", type="string", length=510, nullable=true)
     */
    private $complement;

    /**
     * @var string $zip;
     * @ORM\Column(name="zip", type="string", length=10, nullable=true)
     */
    private $zip;

    /**
     * @var string $city;
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @var string $country_code;
     * @Assert\NotBlank()
     * @ORM\Column(name="country_code", type="string", length=2, nullable=true)
     */
    private $country_code;

    /**
     *   
     * @ORM\ManyToOne(targetEntity="\SansPapier\UserDataBundle\Entity\Preference")
     * @ORM\JoinColumn(name="preference_id_fk",referencedColumnName="preference_id")
     */
    private $preference;
    
    /**
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="\SansPapier\UserDataBundle\Entity\AddressType",cascade={"persist"})
     * @ORM\JoinColumn(name="address_type_id_fk",referencedColumnName="address_type_id",onDelete="CASCADE")
     */
    private $type;
    
    public function setPreference($preference){
        $this->preference = $preference;
    }

    public function __construct(AddressType $type) {
        $this->type = $type;
        $this->country_code = "FR";
    }

    /**
     * @return bool
     */
    public function isZipValid() {

        if (!preg_match('/\d{5}/', $this->zip)) {
            return FALSE;
        }
    }

    /**
     * Set addressee
     *
     * @param string $addressee
     */
    public function setAddressee($addressee) {
        $this->addressee = $addressee;
    }

    /**
     * Get addressee
     *
     * @return string 
     */
    public function getAddressee() {
        return $this->addressee;
    }

    /**
     * Set company_name
     *
     * @param string $companyName
     */
    public function setCompanyName($companyName) {
        $this->company_name = $companyName;
    }

    /**
     * Get company_name
     *
     * @return string 
     */
    public function getCompanyName() {
        return $this->company_name;
    }

    /**
     * Set address
     *
     * @param string $address
     */
    public function setAddress($address) {
        $this->address = $address;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress() {
        return $this->address;
    }

    /**
     * Set complement
     *
     * @param string $complement
     */
    public function setComplement($complement) {
        $this->complement = $complement;
    }

    /**
     * Get complement
     *
     * @return string 
     */
    public function getComplement() {
        return $this->complement;
    }

    /**
     * Get address_id
     *
     * @return integer 
     */
    public function getAddressId() {
        return $this->address_id;
    }

    /**
     * Set zip
     *
     * @param string $zip
     */
    public function setZip($zip) {
        $this->zip = $zip;
    }

    /**
     * Get zip
     *
     * @return string 
     */
    public function getZip() {
        return $this->zip;
    }

    /**
     * Set country_code
     *
     * @param string $countryCode
     */
    public function setCountryCode($countryCode) {
        $this->country_code = $countryCode;
    }

    /**
     * Get country_code
     *
     * @return string 
     */
    public function getCountryCode() {
        return $this->country_code;
    }


    /**
     * Set type
     *
     * @param SansPapier\UserDataBundle\Entity\AddressType $type
     */
    public function setType(\SansPapier\UserDataBundle\Entity\AddressType $type) {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return SansPapier\UserDataBundle\Entity\AddressType 
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set city
     *
     * @param string $city
     */
    public function setCity($city) {
        $this->city = $city;
    }

    /**
     * Get city
     *
     * @return string 
     */
    public function getCity() {
        return $this->city;
    }

}