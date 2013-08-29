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

use FOS\RestBundle\Controller\Annotations\View;
use FOS\UserBundle\Controller\ChangePasswordController as BaseController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;

/**
 * Controller managing the password change
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class ChangePasswordController extends BaseController
{

  /**
   * Change user password
   */
  public function formAction()
  {
    // manual security, no firewall because different use cases
    $user = $this->container->get('security.context')->getToken()->getUser();
    if (!is_object($user) || !$user instanceof UserInterface)
    {
      throw new AccessDeniedException('This user does not have access to this section.'); // @TODO to clean
    }

    $form = $this->container->get('fos_user.change_password.form');
    return $this->container->get('templating')->renderResponse(
      'SansPapierUserDataBundle:ChangePassword:form.html.' . $this->container->getParameter('fos_user.template.engine'), array('form' => $form->createView(), 'theme' => $this->container->getParameter('fos_user.template.theme'))
    );
  }

  /**
   * Methods that handles the ajax registration form submission.
   * @View()
   */
  public function submitAction()
  {
    $user = $this->container->get('security.context')->getToken()->getUser();
    if (!is_object($user) || !$user instanceof UserInterface)
    {
      throw new AccessDeniedException('This user does not have access to this section.'); // @TODO to clean
    }
    
    $form = $this->container->get('fos_user.change_password.form');
    $formHandler = $this->container->get('fos_user.change_password.form.handler');

    $process = $formHandler->process($user);
    $result = array(); // to be jsonized

    if ($process)
    {
      // create the json result with good message
      $result['status'] = TRUE;
      $result['message'] = $this->container->get('translator')->trans('change_password.flash.success', array(), 'FOSUserBundle');
      return $result;
    }

    // validation errors occured;here is a super function that helps a lot to map
    $valid_errors = $this->getFormErrors($form, $this->container->get('translator'));

    // construct json form response
    $result['status'] = FALSE;
    $result['message'] = "validation error";
    $result['valid_errors'] = $valid_errors;
    return $result;
  }

  /**
   * Generate the redirection url when the resetting is completed.
   *
   * @param \FOS\UserBundle\Model\UserInterface $user
   *
   * @return string
   */
  protected function getRedirectionUrl(UserInterface $user)
  {
        return $this->container->get('router')->generate('fos_user_profile_show'); // @TODO TO BE CHANGED
  }

  protected function setFlash($action, $value)
  {
    $this->container->get('session')->setFlash($action, $value);
  }

  private function getFormErrors(\Symfony\Component\Form\Form $form, \Symfony\Component\Translation\Translator $translator, &$errors = array())
  {
    foreach ($form->getErrors() as $key => $error)
    {
      $template = $error->getMessageTemplate();
      $vars = $form->createView()->getVars();
      if ($vars['id'] == $form->getName())
      {
        $vars['id'] = 'global';
      }
      $errors[] = array($vars['id'], $translator->trans($template, array(), 'validators')); // $error->getMessageTemplate();
    }

    if ($form->hasChildren())
    {
      foreach ($form->getChildren() as $child)
      {
        if (!$child->isValid())
        {
          $this->getFormErrors($child, $translator, $errors);
        }
      }
    }
    return $errors;
  }

}
