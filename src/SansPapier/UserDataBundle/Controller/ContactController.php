<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SansPapier\UserDataBundle\Controller;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use SansPapier\UserDataBundle\Entity\Contacts;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Controller\Annotations\View;
use SansPapier\UserDataBundle\Model;

/**
 * ContactController gets back contacts from users.
 * @Route("/contact") 
 */
class ContactController extends Controller {

    /**
     * @Route("/sendcontact|{_subject}|{_subject_name}|{_text}|{_mail}|{_name}.{_format}", name="sanspapier_get_contact", defaults={"_format" = "json"})
     * @View()
     */
    public function sendContactAction($_subject, $_subject_name, $_text, $_mail, $_name) {
        $em = $this->container->get('doctrine')->getEntityManager('user');

        $contact = new Contacts();
        $contact->setSubject($_subject);
        $contact->setText($_text);
        $contact->setMail($_mail);
        $contact->setName($_name);
        $contact->setCreatedAt(new \DateTime());

        $em->persist($contact);
        $em->flush();

        $mailSubject = "";

        if ($_subject_name == "savcommande") {
            $to = "sav@sanspapier.com";
            $mailSubject = "URGENT " . $_subject;
        } else if($_subject_name == "savtech"){
            $to = "sav@sanspapier.com";
            $mailSubject = $_subject;
        } else if ($_subject_name == "contact") {
            $to = "contact@sanspapier.com";
            $mailSubject = $_subject;
        } else if ($_subject_name == "info"){
            $to = "info@sanspapier.com";
            $mailSubject = $_subject;
        }
        
        $mailText = "Message de " . $_name . "<br/><br/>" . "<b>" . $_text . "</b>";
        $this->get('mail_helper')->sendEmail($_mail, $to, $mailText, $mailSubject);
    }

}

?>