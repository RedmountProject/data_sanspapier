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
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations\View;

class SecurityController extends ContainerAware
{
  /**
   * 
   * @Route("/ajax_logout.{_format}", name="sanspapier_ajax_logout", defaults={"_format" = "json"})
   * @View()
   */
  public function ajaxLogoutAction()
  {
    $this->container->get('security.context')->setToken(null);
    $this->container->get('request')->getSession()->invalidate();
    return array("status" => TRUE, "message" => "user logged out");
  }

  /**
   * 
   * @Route("/ajax_is_logged.{_format}", name="sanspapier_ajax_is_logged", defaults={"_format" = "json"})
   * @View()
   */
  public function isLoggedAction()
  {
    // get user
    $token = $this->container->get('security.context')->getToken();
    $user = $token->getUser();
    if (!is_object($user) || !$user instanceof \SansPapier\UserDataBundle\Entity\User) // SESSION SPACE
    {
      return array("status"=>FALSE, "message"=>"user is not logged");
    }
    return array("status"=>TRUE, "message"=>"user is logged", "user"=>$user->getEmail(), "pref"=>$user->getPreference());
  }

  public function loginAction()
  {
    $request = $this->container->get('request');
    $session = $request->getSession();
    // get the error if any (works with forward and redirect -- see below)
    if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR))
    {
      $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
    } elseif (null !== $session && $session->has(SecurityContext::AUTHENTICATION_ERROR))
    {
      $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
      $session->remove(SecurityContext::AUTHENTICATION_ERROR);
    } else
    {
      $error = '';
    }

    if ($error)
    {
      // TODO: this is a potential security risk (see http://trac.symfony-project.org/ticket/9523)
      $error = $error->getMessage();
    }
    // last username entered by the user
    $lastUsername = (null === $session) ? '' : $session->get(SecurityContext::LAST_USERNAME);

    $csrfToken = $this->container->get('form.csrf_provider')->generateCsrfToken('authenticate');

    return $this->container->get('templating')->renderResponse('FOSUserBundle:Security:login.html.' . $this->container->getParameter('fos_user.template.engine'), array(
       'last_username' => $lastUsername,
       'error' => $error,
       'csrf_token' => $csrfToken,
     ));
  }

  public function checkAction()
  {
    throw new \RuntimeException('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');
  }

  public function logoutAction()
  {
    throw new \RuntimeException('You must activate the logout in your security firewall configuration.');
  }

}
