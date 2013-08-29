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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Controller\Annotations\View;
use SansPapier\UserDataBundle\Form\Type\UserPreferencesFormType;
use SansPapier\UserDataBundle\Form\Type\CustomizationFormType;
use SansPapier\UserDataBundle\Entity\UserSearch;

/**
 * UserPreferencesController delivers forms and manage user preferences.
 * @Route("/preferences") 
 * @author nunja
 */
class UserPreferencesController extends ContainerAware {

    /**
     * Form provider only 
     * @Route("/customization_form")
     * @Template()
     */
    public function customizationFormAction() {
        $form = $this->container->get('form.factory')->create(new CustomizationFormType());

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * Form provider only 
     * @Route("/form")
     * @Template()
     */
    public function formAction() {
        // get user
        $securityContext = $this->container->get('security.context');
        $token = $securityContext->getToken();
        $user = $token->getUser();
        $form = $this->container->get('form.factory')->create(new UserPreferencesFormType(), $user);
        return array('form' => $form->createView());
    }

    /**
     * Tells if the user has completed its references
     * @Route("/is_complete.{_format}", name="sanspapier_userpreferences_is_complete", defaults={"_format" = "json"})
     * @View()
     */
    public function isUserCompleteAction() {
        // get user
        $securityContext = $this->container->get('security.context');
        $token = $securityContext->getToken();
        $user = $token->getUser();
        if (!$user->isPreferenceComplete()) {
            return array("status" => FALSE, "message" => "User's prefs missing information");
        }
        return array("status" => TRUE, "message" => "User's preferences are complete enough to order products");
    }

    /**
     * @Route("/get_publishers.{_format}", name="sanspapier_userpreferences_get_publishers", defaults={"_format" = "json"})
     * @View()
     */
    public function getPublishersAction() {
        $em = $this->container->get('doctrine')->getEntityManager('user');
        //DQL
        $query = $em->createQuery('SELECT p.name FROM SansPapierUserDataBundle:Publisher p');
        $res = $query->getResult();
        if ($res) {
            return array("status" => TRUE, "data" => $res);
        } else {
            return array("status" => FALSE, "message" => "No publishers in base");
        }
    }

    /**
     * @Route("/get_genres.{_format}", name="sanspapier_userpreferences_get_genres", defaults={"_format" = "json"})
     * @View()
     */
    public function getGenresAction() {
        $em = $this->container->get('doctrine')->getEntityManager('user');
        // DQL
        $query = $em->createQuery('SELECT g.name FROM SansPapierUserDataBundle:Genre g');
        $res = $query->getResult();
        if ($res) {
            //array_shift($res); // first one unwanted
            return array("status" => TRUE, "data" => $res);
        } else {
            return array("status" => FALSE, "message" => "No genres in base");
        }
    }

    /**
     * @Route("/get_customization.{_format}", name="sanspapier_userpreferences_customization_get", defaults={"_format" = "json"})
     * @View()
     */
    public function getCustomizationAction() {
        // get user
        $securityContext = $this->container->get('security.context');
        $token = $securityContext->getToken();
        $user = $token->getUser();
        $res = array();
        $preference = $user->getPreference();
        foreach ($preference->getPublishers() as $pub) {
            $res["publishers"][] = $pub->getName();
        }
        foreach ($preference->getGenres() as $gen) {
            $res["genres"][] = $gen->getName();
        }
        $pref_id = $preference->getPreferenceId();
        $user_searches = $this->container->get('doctrine')->getEntityManager('user')->getRepository('SansPapierUserDataBundle:UserSearch')->findBy(array("preference" => $pref_id));
        foreach ($user_searches as $sea) {
            $res["searches"][] = $sea->getQuery();
        }
        $result['status'] = TRUE;
        $result['data'] = $res;
        return $result;
    }

    /**
     * @Route("/update_customization.{_format}", name="sanspapier_userpreferences_customization_update", defaults={"_format" = "json"})
     * @View()
     */
    public function updateCustomizationAction() {
        $request = $this->container->get('request');
        if ('POST' === $request->getMethod() && $request->isXmlHttpRequest()) {
            // get user
            $securityContext = $this->container->get('security.context');
            $token = $securityContext->getToken();
            $user = $token->getUser();
            $em = $this->container->get('doctrine')->getEntityManager('user');
            $all = $request->request->all();

            $preference = $user->getPreference();

            $publishers = array();
            $genres = array();


            $searches = $em->getRepository('SansPapierUserDataBundle:UserSearch')->findBy(array("preference" => $preference->getPreferenceId()));
            foreach ($searches as $s) {
                $em->remove($s);
            }

            foreach ($all['sanspapier_userdatabundle_customizationtype'] as $key => $val) {
                if ($val !== "") {
                    if (preg_match("/publisher/", $key)) {
                        $res = $em->getRepository('SansPapierUserDataBundle:Publisher')->findOneBy(array("name" => $val));
                        if( $res !== null ){
                            $publishers[] = $res;
                        }
                    }
                    if (preg_match("/genre/", $key)) {
                        $res =  $em->getRepository('SansPapierUserDataBundle:Genre')->findOneBy(array("name" => $val));
                        if($res !== null){
                             $genres[] = $res;
                        }
                    }
                    if (preg_match("/search/", $key)) {

                        $search = new UserSearch();
                        $search->setQuery($val);
                        $search->setPreference($preference);
                        $em->persist($search);
                    }
                }
            }

            $preference->addPublisherArray($publishers);
            $preference->addGenreArray($genres);

            $em->persist($user);
            $em->flush();

            $result['status'] = TRUE;
            $result['message'] = "";
            return $result;
        } else {
            throw new AccessDeniedException("nothing to see");
        }
    }

    /**
     * @Route("/update.{_format}", name="sanspapier_userpreferences_update", defaults={"_format" = "json"})
     * @View()
     */
    public function updateUserPreferencesAction() {
        // get user
        $securityContext = $this->container->get('security.context');
        $token = $securityContext->getToken();
        $user = $token->getUser();

        $form = $this->container->get('form.factory')->create(new UserPreferencesFormType(), $user);
        $request = $this->container->get('request');

        if ('POST' === $request->getMethod() && $request->isXmlHttpRequest()) {
            $form->bindRequest($request);
            $updatedUser = $form->getData();

            $result = array();

            // fucking validation;
            if ($form->isValid()) {
                // valid so updated, we need to persist now
                $em = $this->container->get('doctrine')->getEntityManager('user');
                $em->persist($updatedUser);
                $em->flush();

                $result['status'] = TRUE;
                $result['message'] = "";
                return $result;
            } else {
                // validation errors occured;here is a super function that helps a lot to map
                $valid_errors = $this->getFormErrors($form, $this->container->get('translator'));
                // construct json form response
                $result['status'] = FALSE;
                $result['message'] = "validation error";
                $result['valid_errors'] = $valid_errors;

                return $result;
            }
        }
        // 404 because we should only access here with POST Method and XmlHttp Request
        throw new NotFoundHttpException('No Access here');
    }

    private function getFormErrors(\Symfony\Component\Form\Form $form, \Symfony\Component\Translation\Translator $translator, &$errors = array()) {
        foreach ($form->getErrors() as $key => $error) {
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

