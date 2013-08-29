<?php
/*  Copyright (C) 2013 DELABY Benoit
    Copyright (C) 2013 NUNJE Aymeric

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
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations\View;
use SansPapier\ShopBundle\Entity\Operation;
use SansPapier\ShopBundle\Entity\Transaction;

class UserProductsShelfController extends ContainerAware {

    /**
     * 
     * @Route("/getTotalProductsShelf.{_format}", name="sanspapier_getTotalProductsShelf", defaults={"_format" = "json"})
     * @View()
     */
    public function getTotalProductsShelfAction() {

        $token = $this->container->get('security.context')->getToken();
        $user = $token->getUser();

        if (!is_object($user) || !$user instanceof \SansPapier\UserDataBundle\Entity\User) { // SESSION SPACE
            return array("status" => FALSE, "message" => "user is not logged");
        }
        
        $shopEm = $this->container->get('doctrine')->getEntityManager("shop");
        $opeRepo = $shopEm->getRepository('SansPapierShopBundle:Operation');
        $transRepo = $shopEm->getRepository('SansPapierShopBundle:Transaction');

        $shopOperation = $opeRepo->findBy(array('user_id_fk' => $user->getUserId(), 'status' => 3), array('transactionAt' => 'DESC'), NULL, NULL);

        $userEm = $this->container->get('doctrine')->getEntityManager("user");
        $userRepo = $userEm->getRepository('SansPapierUserDataBundle:ProductShelf');

        $orders = array();
        $products = array();
        foreach ($shopOperation as $key => $document) {
            
            $orders[$key]['dilicom_transaction_id'] = $document->getDilicomTransactionId();
            $orders[$key]['order_date'] = $document->getTransactionAt();
            $orders[$key]['total_price'] = $document->getTotalPrice();
            
            $id_op = $document->getOperationId();

            $productsShelf = $userRepo->findBy(array('operation_id_fk' => $id_op));
            $products = array();
            foreach ($productsShelf as $key2 => $productShelf) {
                $shopTransaction = $transRepo->findOneBy(array('user_id_fk' => $user->getUserId(), 'transaction_id' => $productShelf->getTransactionIdFk()));
                $products[$key2]['product'] = $this->getHistoProductById($productShelf->getProductIdSolr());
                $query = $userEm->createQuery('SELECT p.url, p.format_description FROM SansPapierUserDataBundle:ProductLink p WHERE p.product_shelf = :productShelfId');
                $query->setParameter('productShelfId', $productShelf->getProductShelfId());
                $links = $query->getResult();
                $products[$key2]['links'] = $links;
                $products[$key2]['price'] = $shopTransaction->getPrice();
            }

            $orders[$key]['products'] = $products;
        }
        
        return array("status" => TRUE, "message" => "user is logged", "orders" => $orders);
    }

    /**
     * 
     * @Route("/getProductsShelfByOperation.{_format}", name="sanspapier_getProductsShelfByOperation", defaults={"_format" = "json"})
     * @View()
     */
    public function getProductsShelfByOperationAction() {

        $token = $this->container->get('security.context')->getToken();
        $user = $token->getUser();
        $session = $this->container->get('request')->getSession();
        $id_op = $session->get('id_op');
        $userEm = $this->container->get('doctrine')->getEntityManager("user");
        $userRepo = $userEm->getRepository('SansPapierUserDataBundle:ProductShelf');
        $productsShelf = $userRepo->findBy(array('user' => $user->getUserId(), 'operation_id_fk' => $id_op));

        $shopEm = $this->container->get('doctrine')->getEntityManager("shop");
        $opeRepo = $shopEm->getRepository('SansPapierShopBundle:Operation');
        $transRepo = $shopEm->getRepository('SansPapierShopBundle:Transaction');

        $shopOperation = $opeRepo->findOneBy(array('user_id_fk' => $user->getUserId(), 'operation_id' => $id_op));
        $dilicomTrans_id = $shopOperation->getDilicomTransactionId();
        $total_price = $shopOperation->getTotalPrice();

        $products = array();

        foreach ($productsShelf as $key => $document) {
            $shopTransaction = $transRepo->findOneBy(array('user_id_fk' => $user->getUserId(), 'transaction_id' => $document->getTransactionIdFk()));

            $products[$key]['product'] = $this->getProductById($document->getProductIdSolr());
            $products[$key]['links'] = $document->getProductLinks();
            $products[$key]['price'] = $shopTransaction->getPrice();
        }

        if (!is_object($user) || !$user instanceof \SansPapier\UserDataBundle\Entity\User) { // SESSION SPACE
            return array("status" => FALSE, "message" => "user is not logged");
        }

        $session->remove('id_op');

        return array("status" => TRUE, "message" => "user is logged", "products" => $products, "id_op" => $id_op, 'dilicom_transaction_id' => $dilicomTrans_id, 'total_price' => $total_price);
    }

    private function getProductById($id_product) {
        
        $configCatalogCore = array('adapteroptions' => array(
                'host' => $this->container->getParameter('sans_papier_user_data.solr.host'),
                'port' => $this->container->getParameter('sans_papier_user_data.solr.port'),
                'path' => $this->container->getParameter('sans_papier_user_data.solr.path'),
                'core' => $this->container->getParameter('sans_papier_user_data.solr.core_catalog'))
        );

        $client = new \Solarium_Client($configCatalogCore);

        // get a select query instance
        $query = $client->createSelect();
        $query->setRows(1);
        //specify to Solr the default search field
        $query->setQueryDefaultField('product_id');
        //specify to Solr the field that have to appear in the resultset
        $query->setFields(array('author_firstname', 'author_lastname', 'author_id', 'is_package', 'package_description', 'publisher_id', 'publisher_name', 'product_id', 'title', 'EUR_c', 'format_name', 'file_size', 'publishing_date', 'isbn'));
        //specify to Solr the string to evaluate
        $query->setQuery($id_product);
        //this executes the query and returns the result
        $resultset = $client->select($query);

        $result = $resultset->getDocuments();

        return $result;
    }
    
    private function getHistoProductById($id_product) {
        
        $userEm = $this->container->get('doctrine')->getEntityManager("user");
        $productRepo = $userEm->getRepository('SansPapierUserDataBundle:ProductOrderedUnique');
        $product = $productRepo->find($id_product);
        $resultat = array();
        $resultat['product_id'] = $product->getProductId();
        $resultat['publisher_id'] = $product->getPublisherId();
        $resultat['publisher_name'] = $product->getPublisherName();
        $resultat['title'] = $product->getTitle();
        $resultat['author_firstname'] = $product->getAuthorFirstname();
        $resultat['author_lastname'] = $product->getAuthorLastname();
        return $resultat;
    }
}

?>
