<?php

namespace SansPapier\UserDataBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Form\Handler\RegistrationFormHandler as BaseHandler;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Mailer\MailerInterface;
use SansPapier\UserDataBundle\Entity\Address;
use SansPapier\UserDataBundle\Entity\AddressType;

class RegistrationFormHandler extends BaseHandler
{

  public function __construct(Form $form, Request $request, UserManagerInterface $userManager, MailerInterface $mailer, Registry $doctrine, Logger $logger)
  {
    $this->form = $form;
    $this->request = $request;
    $this->userManager = $userManager;
    $this->mailer = $mailer;
    $this->doctrine = $doctrine;
    $this->logger = $logger;
  }

  protected function onSuccess(UserInterface $user, $confirmation)
  {
    parent::onSuccess($user, $confirmation);

    // post processing
    $em = $this->doctrine->getEntityManager('user');
    // gender
    $gender_repo = $em->getRepository('SansPapierUserDataBundle:Gender');
    $all_genders = $gender_repo->findAll();
    
    $preference = $user->getPreference();
    $preference->setGender(($all_genders[0]));
    // create adresses
    $all_address_types = $em->getRepository('SansPapierUserDataBundle:AddressType')->findAll();

    $delivery = new Address($all_address_types[0]);
    $billing = new Address($all_address_types[1]);
    
    $preference->addAddress($delivery);
    $preference->addAddress($billing);
    
    $em->persist($user);
    $em->flush();
  }

  public function process($confirmation = false)
  {
    $user = $this->userManager->createUser();
    $this->form->setData($user);

    if ('POST' === $this->request->getMethod())
    {
      $this->form->bindRequest($this->request);

      if ($this->form->isValid())
      {
        $this->onSuccess($user, $confirmation);

        return true;
      }
    }

    return false;
  }

};