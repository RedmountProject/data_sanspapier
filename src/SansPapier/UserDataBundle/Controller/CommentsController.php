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

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use SansPapier\UserDataBundle\Entity\Comments;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Controller\Annotations\View;

/**
 * CommentsController gets back comments from users.
 * @Route("/comments") 
 * @author kevin77
 */
class CommentsController extends ContainerAware
{
   /**
   * @Route("/sendcomments|{_subject}|{_text}|{_mail}|.{_format}", name="sanspapier_get_comments", defaults={"_format" = "json"})
   * @View()
   */
  public function sendCommentsAction($_subject, $_text, $_mail)
  {
      $em = $this->container->get('doctrine')->getEntityManager('user');
      
      $comment = new Comments();
      $comment->setSubject($_subject);
      $comment->setText($_text);
      $comment->setMail($_mail);
      $comment->setCreatedAt(new \DateTime());
      
      $em->persist($comment);
      $em->flush();
  }
}

?>