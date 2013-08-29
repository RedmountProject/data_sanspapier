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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Controller\ResettingController as BaseController;
use FOS\RestBundle\Controller\Annotations\View;

/**
 * Controller managing the resetting of the password
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class ResettingController extends BaseController
{
  const SESSION_EMAIL = 'fos_user_send_resetting_email/email';

  /**
   * Request reset user password: show form
   */
  public function requestAction()
  {
    return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:request.html.' . $this->getEngine());
  }

  /**
   * Request reset user password: submit form and send email
   * @View()
   */
  public function sendEmailAction()
  {
    $username = $this->container->get('request')->request->get('username');
    $user = $this->container->get('fos_user.user_manager')->findUserByUsernameOrEmail($username);
    $result = array();

    if (null === $user)
    {
      // construct json form response
      $result['status'] = FALSE;
      $result['message'] = "validation error";
      $result['valid_errors'] = array(array(
          "global", $this->container->get('translator')->trans("resetting.request.invalid_username", array('%username%' => $username), 'FOSUserBundle')
       ));
      return $result;
    }

    if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl')))
    {
      //return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:passwordAlreadyRequested.html.'.$this->getEngine());
      $result['status'] = FALSE;
      $result['message'] = "validation error";
      $result['valid_errors'] = array(array(
          "global", $this->container->get('translator')->trans("resetting.password_already_requested", array(), 'FOSUserBundle')
       ));
      
      return $result;
    }
    
    // update user for token
    $user->generateConfirmationToken();
    $this->container->get('fos_user.mailer')->sendResettingEmailMessage($user);
    $user->setPasswordRequestedAt(new \DateTime());
    $this->container->get('fos_user.user_manager')->updateUser($user);

    //return new RedirectResponse($this->container->get('router')->generate('fos_user_resetting_check_email'));
    $result['status'] = TRUE;
    $result['message'] = $this->container->get('translator')->trans("resetting.check_email", array('%email%' => $user->getEmail()), 'FOSUserBundle');
    return $result;
  }


}
