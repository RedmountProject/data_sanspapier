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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Controller\Annotations\View;
use SansPapier\UserDataBundle\Entity\ProductSelection;

/**
 * Manage logged and Anonymous Users selection
 *  @Route("/selection")
 * @author nunja
 */
class ProductSelectionController extends ContainerAware
{

  /**
   * Action that adds product ID to selection.
   * @Route("/add_to_selection_{_id}.{_format}",  name="sanspapier_add_to_selection", defaults={"_format" = "json"})
   * @View()
   * @param int $_id
   */
  public function addToSelectionAction($_id)
  {
    // get user
    $securityContext = $this->container->get('security.context');
    $token = $securityContext->getToken();
    $user = $token->getUser();

    $session = $this->container->get('request')->getSession();

    if (!is_object($user) || !$user instanceof \SansPapier\UserDataBundle\Entity\User) // SESSION SPACE
    {
      $selection = $session->get('selection');
      // creates the selection array if does not exist
      if (empty($selection))
      {
        $session->set('selection', array());
      }
      // add anonymously in the session.
      if ($this->safeAddToSessionWithoutDuplicates($_id, $session))
      {
        // @ TODO RETURN SELECTION TO LIMIT AJAX QUERIES
        return array("status" => TRUE, "message" => "successfully added product id " . $_id . " to selection SESSION");
      }
    } else // USER SPACE 
    {
      // get the user entity manager
      $em = $this->container->get('doctrine')->getEntityManager('user');
      if ($this->safeAddToUserWithoutDuplicates($_id, $user, $em))
      {
        // added to session without duplicate so we need to add this in base
        // @ TODO RETURN SELECTION TO LIMIT AJAX QUERIES
        return array("status" => TRUE, "message" => "successfully added product id " . $_id . " to selection in USER");
      }
    }
    return array("status" => FALSE, "message" => "nothing added to selection");
  }

  /**
   * Action that remove one product from the selection
   * @Route("/delete_from_selection_{_id}.{_format}",  name="sanspapier_delete_from_selection", defaults={"_format" = "json"})
   * @View()
   */
  public function deleteFromSelectionAction($_id)
  {
    // get user
    $securityContext = $this->container->get('security.context');
    $token = $securityContext->getToken();
    $user = $token->getUser();
    $session = $this->container->get('request')->getSession();

    // user or annon
    if (!is_object($user) || !$user instanceof \SansPapier\UserDataBundle\Entity\User) // SESSION SPACE
    {
      $selections = $session->get('selection');
      if (!empty($selections))
      {
        $search = array_search($_id, $selections);
        if ($search)
        {
          unset($selection[$search]);
          $session->set('selection', $selections);
          return array("status" => TRUE, "message" => "product ". $_id ." has been removed from the selection");
        }
        return array("status" => FALSE, "message" => "product ". $_id ." do not exist in selection");
      }
    } else
    {
      // get the user entity manager
      $em = $this->container->get('doctrine')->getEntityManager('user');
      $repo = $em->getRepository('SansPapierUserDataBundle:ProductSelection');
      $res = $repo->findOneBy(array('product_id_solr' => $_id));
      if (count($res))
      {
        $em->remove($res);
        $em->flush();
        return array("status" => TRUE, "message" => "product ". $_id ." has been removed from the selection");
      }
      return array("status" => FALSE, "message" => "product ". $_id ." do not exist in selection");
    }
  }

  /**
   * Action that clears all the user selection.
   * @Route("/clear_selection.{_format}",  name="sanspapier_clear_selection", defaults={"_format" = "json"})
   * @View()
   */
  public function clearSelectionAction()
  {
    // get user
    $securityContext = $this->container->get('security.context');
    $token = $securityContext->getToken();
    $user = $token->getUser();
    $session = $this->container->get('request')->getSession();

    // user or annon
    if (!is_object($user) || !$user instanceof \SansPapier\UserDataBundle\Entity\User) // SESSION SPACE
    {
      $session->set('selection', array());
      return array("status" => TRUE, "message" => "cleared selection");
    } else
    {
      $user->setProductSelections(new \Doctrine\Common\Collections\ArrayCollection());
      $em = $this->container->get('doctrine')->getEntityManager('user');
      $em->persist($user);
      $em->flush();
      return array("status" => TRUE, "message" => "cleared selection");
    }
  }

  /**
   * Action that get all the user selection.
   * @Route("/get_selection.{_format}",  name="sanspapier_get_selection", defaults={"_format" = "json"})
   * @View()
   */
  public function getSelectionAction()
  {
    // get user
    $securityContext = $this->container->get('security.context');
    $token = $securityContext->getToken();
    $user = $token->getUser();
    $session = $this->container->get('request')->getSession();

    // user or annon
    if (!is_object($user) || !$user instanceof \SansPapier\UserDataBundle\Entity\User) // SESSION SPACE
    {
      $selection = $session->get('selection');
      // creates the selection array if does not exist
      if (empty($selection))
      {
        $session->set('selection', array());
      }
      $products = $this->getProductsFromIdsSolr($session->get('selection'));
      return array("status" => TRUE, "data" => $products);
    } else
    {
      $selections = $user->getProductSelections();
      $ids = array();
      // temp
      foreach ($selections as $selection)
      {
        $ids[] = $selection->getProductIdSolr();
      }
      $products = $this->getProductsFromIdsSolr($ids);
      return array("status" => TRUE, "data" => $products);
    }
  }

  private function safeAddToSessionWithoutDuplicates($id, $session)
  {
    if (preg_match("/^\d+$/", $id))
    {
      if (!in_array($id, $session->get('selection')))
      {
        $arr = $session->get('selection');
        $arr[] = $id;
        $session->set('selection', $arr);
        return TRUE;
      }
    }
    return FALSE;
  }

  private function safeAddToUserWithoutDuplicates($id, \SansPapier\UserDataBundle\Entity\User $user, \Doctrine\ORM\EntityManager $em)
  {
    // get user selection.
    $selections = $user->getProductSelections();
    $selection = new ProductSelection();
    $selection->setProductIdSolr($id);
    if (!count($selections))
    {
      $user->addProductSelection($selection);
      $em->persist($user);
      $em->flush();
      return TRUE;
    } else
    {
      // filter to see if we have a duplicate
      $duplicate = $selections->filter(
       function($entry) use ($id)
       {
         if ($id == $entry->getProductIdSolr())
         {
           return TRUE;
         }
         return FALSE;
       }
      );
      // if we do not have dups let's go
      if (!count($duplicate))
      {
        $user->addProductSelection($selection);
        $em->persist($user);
        $em->flush();
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @param type $ids 
   * Retrieves Products by ID from Solr Index
   */
  private function getProductsFromIdsSolr($ids)
  {
    $configCatalogCore = array('adapteroptions' => array(
        'host' => $this->container->getParameter('sans_papier_user_data.solr.host'),
        'port' => $this->container->getParameter('sans_papier_user_data.solr.port'),
        'path' => $this->container->getParameter('sans_papier_user_data.solr.path'),
        'core' => $this->container->getParameter('sans_papier_user_data.solr.core_catalog'))
    );

    $client = new \Solarium_Client($configCatalogCore);

    // build query string
    $query_str = "";
    foreach ($ids as $key => $id)
    {
      $query_str .= ($key == count($ids) - 1) ? $id : $id . " OR ";
    }

    // get a select query instance
    $query = $client->createSelect();
    // override the default row limit of 10 by setting rows to 30
    $query->setRows(20);
    //specify to Solr the default search field
    $query->setQueryDefaultField('product_id');
    //specify to Solr the field that have to appear in the resultset
    $query->setFields(array('author_firstname', 'author_lastname', 'publisher_name', 'genre_name', 'product_id', 'product_rank', 'title', '*_c', 'format_id', 'format_name', 'back_cover', 'description'));
    //specify to Solr the string to evaluate
    $query->setQuery($query_str);
    //this executes the query and returns the result
    $resultset = $client->select($query);
    $res = array();
    foreach ($resultset as $document)
    {
      $res[] = $document;
    }
    return $res;
  }

}

?>
