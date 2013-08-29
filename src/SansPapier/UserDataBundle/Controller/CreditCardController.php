<?php
/*  Copyright (C) 2013 NUNJE Aymeric

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
namespace SansPapier\UserDataBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use SansPapier\UserDataBundle\Entity\CreditCard;
use SansPapier\UserDataBundle\Form\Type\CreditCardFormType;
use FOS\RestBundle\Controller\Annotations\View;

/**
 * CreditCard controller.
 *
 * @Route("/creditcard")
 */
class CreditCardController extends ContainerAware
{

  /**
   * Lists all CreditCard entities.
   *
   * @Route("/list", name="sanspapier_creditcard_list")
   * @Template()
   */
  public function listAction()
  {
    // get user
    $securityContext = $this->container->get('security.context');
    $token = $securityContext->getToken();
    $user = $token->getUser();
    
    if (!is_object($user) || !$user instanceof \SansPapier\UserDataBundle\Entity\User)
    {
      throw new NotFoundHttpException('No Access here');
    }

    $em = $this->container->get('doctrine')->getEntityManager("user");
    $entities = $em->getRepository('SansPapierUserDataBundle:CreditCard')->findBy(array("user" => $user->getUserId())); // weird
    // replace numbers of cards to show only the last four numbers


    return array('entities' => $entities);
  }

  /**
   * Displays a form to create a new CreditCard entity.
   *
   * @Route("/form", name="sanspapier_creditcard_form")
   * @Template()
   */
  public function formAction()
  {
    $entity = new CreditCard();
    $form = $this->container->get('form.factory')->create(new CreditCardFormType(), $entity);
    $cc = new CreditCard();
    $form->setData($cc);
    return array(
      'entity' => $entity,
      'form' => $form->createView()
    );
  }

  /**
   * @Route("/get_selected.{_format}", name="sanspapier_creditcard_get_selected", defaults={"_format" = "json"}, options={"expose"=true})
   * @View()
   */
  public function getSelectedAction()
  {
    // get user
    $securityContext = $this->container->get('security.context');
    $token = $securityContext->getToken();
    $user = $token->getUser();
    if (!is_object($user) || !$user instanceof \SansPapier\UserDataBundle\Entity\User)
    {
      throw new NotFoundHttpException('No Access here');
    }

    if ($user->getSelectedCreditCard())
    {
      return array("status" => TRUE, "data" => $user->getSelectedCreditCard()->getCreditCardId());
    } else
    {
      return array("status" => FALSE);
    }
  }

  /**
   * @Route("/set_selected_{_id}.{_format}", name="sanspapier_creditcard_set_selected", defaults={"_format" = "json"}, options={"expose"=true})
   * @View()
   */
  public function setSelectedAction($_id)
  {
    // get user
    $securityContext = $this->container->get('security.context');
    $token = $securityContext->getToken();
    $user = $token->getUser();
    if (!is_object($user) || !$user instanceof \SansPapier\UserDataBundle\Entity\User)
    {
      throw new NotFoundHttpException('No Access here');
    }
    // get the credit card from id
    $em = $this->container->get('doctrine')->getEntityManager("user");
    $entity = $em->getRepository('SansPapierUserDataBundle:CreditCard')->find($_id);
    if ($entity)
    {
      $user->setSelectedCreditCard($entity);
      $em->persist($user);
      $em->flush();
      return array("status" => TRUE, "message" => "credit card have been selected");
    }

    return array("status" => FALSE);
  }

  /**
   * Creates a new CreditCard entity.
   *
   * @Route("/create.{_format}", name="sanspapier_creditcard_create", defaults={"_format" = "json"})
   * @Method("post")
   * @View
   */
  public function createAction()
  {
    // get user
    $securityContext = $this->container->get('security.context');
    $token = $securityContext->getToken();
    $user = $token->getUser();
    if (!is_object($user) || !$user instanceof \SansPapier\UserDataBundle\Entity\User)
    {
      throw new NotFoundHttpException('No Access here');
    }

    $entity = new CreditCard();
    $entity->setUser($user);

    $request = $this->container->get('request');
    $form = $this->container->get('form.factory')->create(new CreditCardFormType(), $entity);

    $form->bindRequest($request);
    $result = array();
    if ($form->isValid())
    {
      $em = $this->container->get('doctrine')->getEntityManager("user");
      $em->persist($entity);
      $em->flush();
      $result['status'] = TRUE;
      $result['message'] = $this->container->get('translator')->trans('credit_card.added.success', array(), 'SansPapierUserDataBundle');

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
   * Deletes a CreditCard entity.
   *
   * @Route("/delete_{id}.{_format}", name="sanspapier_creditcard_delete", defaults={"_format" = "json"})
   * @View()
   */
  public function deleteAction($id)
  {
    // get user
    $securityContext = $this->container->get('security.context');
    $token = $securityContext->getToken();
    $user = $token->getUser();
    if (!is_object($user) || !$user instanceof \SansPapier\UserDataBundle\Entity\User)
    {
      throw new NotFoundHttpException('No Access here');
    }

    $em = $this->container->get('doctrine')->getEntityManager("user");
    $entity = $em->getRepository('SansPapierUserDataBundle:CreditCard')->find($id);

    if (!$entity)
    {
      return array("status" => FALSE, "message" => "Not Found");
    }

    if (is_object($user->getSelectedCreditCard()))
    {
      // deselect because if it is selected in user...
      if ($user->getSelectedCreditCard()->getCreditCardId() == $entity->getCreditCardId())
      {
        $user->setSelectedCreditCard(NULL);// I remove the card here
        $em->persist($user);
      }
    }


    $em->remove($entity);
    $em->flush();

    return array("status" => TRUE, "message" => "Deleted");
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
