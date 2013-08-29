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
 * SansPapier\UserDataBundle\Entity\Comments
 *
 * @ORM\Table(name="spdata_comments")
 * @ORM\Entity
 */
class Comments {

    /**
     * @var integer $id
     *
     * @ORM\Column(name="comment_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $comment_id;

    /**
     * @var string $subject
     *
     * @ORM\Column(name="comment_subject", type="string")
     */
    private $comment_subject;

    /**
     * @var text $text
     *
     * @ORM\Column(name="comment_text", type="text")
     */
    private $comment_text;
    
    /**
     * @var string $comment_mail
     *
     * @ORM\Column(name="comment_mail", type="string")
     */
    private $comment_mail;

    /**
     * @var \DateTime $createdAt
     * 
     * @ORM\Column(type="datetime",name="created_at")
     */
    private $createdAt;

    public function setId($id) {
        $this->comment_id = $id;
    }

    public function setSubject($subject) {
        $this->comment_subject = $subject;
    }

    public function setText($text) {
        $this->comment_text = $text;
    }
    
    public function setMail($mail) {
        $this->comment_mail = $mail;
    }

    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;
    }
}

?>