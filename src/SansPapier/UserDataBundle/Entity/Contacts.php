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
 * SansPapier\UserDataBundle\Entity\Contacts
 *
 * @ORM\Table(name="spdata_contacts")
 * @ORM\Entity
 */
class Contacts {

    /**
     * @var integer $id
     *
     * @ORM\Column(name="contact_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $contact_id;

    /**
     * @var string $subject
     *
     * @ORM\Column(name="contact_subject", type="string")
     */
    private $contact_subject;

    /**
     * @var text $text
     *
     * @ORM\Column(name="contact_text", type="text")
     */
    private $contact_text;
    
    /**
     * @var string $contact_mail
     *
     * @ORM\Column(name="contact_mail", type="string")
     */
    private $contact_mail;
    
    /**
     * @var string $contact_name
     *
     * @ORM\Column(name="contact_name", type="string")
     */
    private $contact_name;

    /**
     * @var \DateTime $createdAt
     * 
     * @ORM\Column(type="datetime",name="created_at")
     */
    private $createdAt;

    public function setId($id) {
        $this->contact_id = $id;
    }

    public function setSubject($subject) {
        $this->contact_subject = $subject;
    }

    public function setText($text) {
        $this->contact_text = $text;
    }
    
    public function setMail($mail) {
        $this->contact_mail = $mail;
    }
    
    public function setName($name) {
        $this->contact_name = $name;
    }

    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;
    }
}

?>