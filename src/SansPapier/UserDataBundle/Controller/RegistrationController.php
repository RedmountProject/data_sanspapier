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

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\UserBundle\Controller\RegistrationController as BaseController;
use FOS\RestBundle\Controller\Annotations\View;

/**
 * Overriden for Ajax magix
 */
class RegistrationController extends BaseController {

    /**
     * Form provider only 
     * @return type 
     */
    public function registerAction() {
        $form = $this->container->get('fos_user.registration.form');
        return $this->container->get('templating')->renderResponse('FOSUserBundle:Registration:register.html.' . $this->getEngine(), array(
                    'form' => $form->createView(),
                    'theme' => $this->container->getParameter('fos_user.template.theme'),
                ));
    }

    /**
     * Methods that handles the ajax registration form submission.
     * @View()
     */
    public function submitAction() {
        $form = $this->container->get('fos_user.registration.form');
        $formHandler = $this->container->get('fos_user.registration.form.handler');
        $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');

        $process = $formHandler->process($confirmationEnabled);
        $user = $form->getData();
        $result = array(); // to be jsonized
    
        if ($process) { // if process = 1, no errors, everything is validated, returning confirmation email notice
            // stores the referer to redirect for the confirmation mail
            $req = $this->container->get('request');
            $user->setConfirmationUrl($req->request->get('referer'));
            $em = $this->container->get('doctrine')->getEntityManager('user');
            
            $user = $this->AddAddresses($user);
            
            $em->persist($user);
            $em->flush();

            // create the json result with good message
            $result['status'] = TRUE;
            $result['message'] = $this->container->get('translator')->trans('registration.check_email', array("%email%" => $user->getEmail()), 'FOSUserBundle');
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

    private function AddAddresses($user){
        $preference = $user->getPreference();
        $Addresses = $preference->getAddresses();
        foreach($Addresses as $addr){
            $addr->setPreference($preference);
        }
        $user->setPreference($preference);
        return $user;
    }
    
    /**
     * Receive the confirmation token from user email provider, login the user
     */
    public function confirmAction($token) {
        $user = $this->container->get('fos_user.user_manager')->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);
        $user->setLastLogin(new \DateTime());

        $this->container->get('fos_user.user_manager')->updateUser($user);
        $this->authenticateUser($user);

        if ($user->getConfirmationUrl()) {
            return new RedirectResponse($user->getConfirmationUrl() . "/profile.php?confirmed=true");
        } else {
            return new RedirectResponse($this->container->get('router')->generate('fos_user_registration_confirmed'));
        }
    }

    private function getFormErrors(\Symfony\Component\Form\Form $form, \Symfony\Component\Translation\Translator $translator, &$errors = array()) {
        foreach ($form->getErrors() as $error) {
            $template = $error->getMessageTemplate();
            $vars = $form->createView()->getVars();
            if ($vars['id'] == $form->getName()) {
                $vars['id'] = 'global';
            }
            $errors[] = array($vars['id'], $translator->trans($template, array(), 'validators')); // $error->getMessageTemplate();
        }
        
        if ($form->hasChildren()) {
            foreach ($form->getChildren() as $child) {
                if (!$child->isValid()) {
                    $this->getFormErrors($child, $translator, $errors);
                }
            }
        }
        return $errors;
    }

}