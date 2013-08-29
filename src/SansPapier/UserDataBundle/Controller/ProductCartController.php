<?php
/*  Copyright (C) 2013 DELABY Benoit

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

use \Solarium_Client;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Controller\Annotations\View;
use SansPapier\UserDataBundle\Entity\ProductCart;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * @Route("/cart");
 * @author nunja
 */
class ProductCartController extends ContainerAware {

    private $logger;
    
    public function __construct() {
        // create a log channel
        $this->logger = new Logger('product_cart');
        $this->logger->pushHandler(new StreamHandler('../app/sp_logs/dilicom.log', Logger::INFO));
    }
    
    /**
     * @Route("/add_to_cart_{_id}.{_format}",  name="sanspapier_add_to_cart", defaults={"_format" = "json"})
     * @View()
     */
    public function addToCartAction($_id) {
        // cart is all session
        $session = $this->container->get('request')->getSession();
        $cart = $this->getCart($session);
        $res = $cart->addUnique($_id, $this->container->getParameter('external_shop_id.sanspapier'));
        $nbItems = $cart->getCountProducts();
        if ($res) {
            return array("status" => TRUE, "message" => "product id: " . $_id . " added to the cart", "numOfItems" => $nbItems);
        } else {
            return array("status" => FALSE, "message" => "product id: " . $_id . " already in the cart", "numOfItems" => 0);
        }
    }

    /**
     * @Route("/count_cart.{_format}",  name="sanspapier_count_cart", defaults={"_format" = "json"})
     * @View()
     * @return type 
     */
    public function getCountCartAction() {
        // cart is all session
        $session = $this->container->get('request')->getSession();
        $cart = $this->getCart($session);
        $nbItems = $cart->getCountProducts();
        return $nbItems;
    }
    
    /**
     * @Route("/get_cart_shop.{_format}",  name="sanspapier_get_cart_shop", defaults={"_format" = "json"})
     * @View()
     * returns all the products in the cart for the shop display (checks dilicom availability)
     */
    public function getProductCartShopAction() {
        $configCatalogCore = $this->getSolrCatalogConfig();
        $session = $this->container->get('request')->getSession();
        $country = $session->get('country');
        // cart is all session
        $session = $this->container->get('request')->getSession();
        $cart = $this->getCart($session);
        $products = $cart->getProducts($configCatalogCore, $country);
        
        if (count($products['products'])) {
            //Check disponibility of products via dilicom
            $params = array();
            $params['glnReseller'] = str_replace('"', '', $this->container->getParameter('sans_papier_shop.dilicom.gln'));
            $params['passwordReseller'] = $this->container->getParameter('sans_papier_shop.dilicom.password');

            foreach($products['products'] as $key => $product)
            {
                $params['checkAvailabilityLines[' . $key . '].ean13'] = $product['isbn'];

                if(isset($product['WO_EUR_TTC_c']))
                    $params['checkAvailabilityLines[' . $key . '].unitPrice'] = $this->convertPrice($product['WO_EUR_TTC_c']);
                elseif(isset($product[$country.'_EUR_TTC_c']))
                    $params['checkAvailabilityLines[' . $key . '].unitPrice'] = $this->convertPrice($product[$country.'_EUR_TTC_c']);

                $params['checkAvailabilityLines[' . $key . '].glnDistributor'] = $product['supplier_id'];
            }
            // responses management
            $response = $this->doAuthGet($params['glnReseller'], $params['passwordReseller'], http_build_query($params), $this->container->getParameter('sans_papier_shop.dilicom.availability_url'));        
            $json = json_decode($response, true);
            $idInError = array();
            
            if($json['returnStatus'] == "OK") {
                $status = 'ok';
                $this->logger->addInfo('Cart CheckAvailability OK');
            }
            else {
                $this->logger->addInfo('Cart CheckAvailability NOT OK');
                foreach($json['checkAvailabilityResponseLines'] as $responseLine) {
                    //Error for this product
                    if($responseLine['returnStatus'] != "OK") {
                        $this->logger->addInfo('Error on the recuperation of ean '.$responseLine["ean13"].' for distributor '.$responseLine["glnDistributor"].' with code '.$responseLine["returnStatus"].'('.$responseLine["returnMessage"].')');
                        $status = 'warning';
                        $idInError[] = $responseLine['product_id'];
                        foreach($products['products'] as $product) {
                            $identifier = $product['isbn'];
                            if($identifier == $responseLine["ean13"]) {
                                //Flags the product as error
                                $idInError[] = $product['product_id'];
                                //Decreases the cart total price
                                if(isset($product['WO_EUR_TTC_c']))
                                    $products['totalPrice'] -= $product['WO_EUR_TTC_c'];
                                elseif(isset($product[$country.'_EUR_TTC_c']))
                                    $products['totalPrice'] -= $product[$country.'_EUR_TTC_c'];
                                $this->deleteFromCartAction($product['product_id']);
                            }
                        }
                    }
                }
            }
            
            return array("status" => $status,  
                         "data" => array("total_price" => $products['totalPrice'],
                                         "currency" => $this->container->getParameter('sans_papier_user_data.solr.currency'),
                                         "products" => $products['products'],
                                         "country" => $country),
                         "errors" => $idInError);
        } else
            return array('status' => "ko", 'message' => "no products in the cart ");
    }

    /**
     * @Route("/get_cart.{_format}",  name="sanspapier_get_cart", defaults={"_format" = "json"})
     * @View()
     * returns all the products in the cart, by selection ids on the Solar Index.
     */
    public function getProductCartAction() {
        $configCatalogCore = $this->getSolrCatalogConfig();
        $session = $this->container->get('request')->getSession();
        $country = $session->get('country');
        // cart is all session
        $session = $this->container->get('request')->getSession();
        $cart = $this->getCart($session);
        $products = $cart->getProducts($configCatalogCore, $country);
        if (count($products['products'])) {
            return array("status" => TRUE,  
                         "data" => array("total_price" => $products['totalPrice'],
                                         "currency" => $this->container->getParameter('sans_papier_user_data.solr.currency'),
                                         "products" => $products['products'],
                                         "country" => $country));
        } else
            return array('status' => FALSE, 'message' => "no products in the cart ");
    }

    /**
     * @Route("/delete_from_cart_{_id}.{_format}",  name="sanspapier_delete_from_cart", defaults={"_format" = "json"})
     * @View()
     * @param type $id
     * @return type 
     */
    public function deleteFromCartAction($_id) {
        // cart is all session
        $session = $this->container->get('request')->getSession();
        $cart = $this->getCart($session);
        $res = $cart->remove($_id);
        $nbItems = $cart->getCountProducts();
        if ($res) {
            return array("status" => TRUE, "message" => "product id: " . $_id . " deleted from the cart", "numOfItems" => $nbItems);
        }
        return array("status" => FALSE, "message" => "product id: " . $_id . " not in the cart", "numOfItems" => 0);
    }

    /**
     * Action that clears all the cart.
     * @Route("/clear_cart.{_format}",  name="sanspapier_clear_cart", defaults={"_format" = "json"})
     * @View()
     */
    public function clearCartAction() {
        // cart is all session
        $session = $this->container->get('request')->getSession();
        $cart = $this->getCart($session);
        $cart->clear();
        return array("status" => TRUE, "message" => "product cart is cleared");
    }

    private function getCart($session) {
        if (!$session->get('cart')) {
            $session->set('cart', new ProductCart());
        }
        return $session->get('cart');
    }

    /**
     * @Route("/get_formats_{_id}.{_format}",  name="sanspapier_get_formats", defaults={"_format" = "json"})
     * @View()
     * @param type $id
     * @return type 
     */
    public function getFormatAction($_id) {
        $configCatalogCore = $this->getSolrCatalogConfig();
        $client = new \Solarium_Client($configCatalogCore);
        $query = $client->createSelect();
        $query->setQuery($_id);
        $query->setQueryDefaultField('product_id');
        
        $currency = $this->container->getParameter('sans_papier_user_data.solr.currency').'_c';
        
        $query->setFields(array('format_name', 'product_id', 'title', 'author_firstname', 'author_lastname', $currency));

        $resultset = $client->select($query);

        $docs = $resultset->getDocuments();
        
        $formats = array();
        $product_ids = array();
        $prices = array();
        
        foreach($docs as $doc){
            $product_ids = $doc->product_id;
            $formats = $doc->format_name;
            $prices = $doc->$currency;
        }
        
        $return[] = $product_ids;
        $return[] = $formats;
        $return[] = $prices;
        
        return $return;
    }
    
    /**
     * @Route("/set_cart_from_external_website.{_format}",  name="set_cart_from_external_website", defaults={"_format" = "json"})
     * @Method("post")
     * @View()
     * returns all the products in the cart, by selection ids on the Solar Index.
     */
    public function setCartFromExternalWebsiteAction() {
        $request = $this->container->get('request');
        if ('POST' === $request->getMethod() && $request->isXmlHttpRequest() && $_POST["data"]) {
            $session = $this->container->get('request')->getSession();
            $cart = $this->getCart($session);
            $isbnToDecode = $_POST["data"];
            $isbnDecoded = base64_decode($isbnToDecode);
            if(!$isbnDecoded)
                return false;
            
            $isbnDecodedArray = split('\.', $isbnDecoded);
            $nbIsbn = count($isbnDecodedArray)-1;
            $isbnQuery = '';
            foreach($isbnDecodedArray as $key => $isbn) {
                if($key < $nbIsbn)
                    $isbnQuery .= base_convert($isbn, 36, 10).' OR ';
                else
                    $isbnQuery .= base_convert($isbn, 36, 10);
            }
            $configCatalogCore = $this->getSolrCatalogConfig();
            $client = new \Solarium_Client($configCatalogCore);
            $query = $client->createSelect();
            $query->setQuery($isbnQuery);
            $query->setQueryDefaultField('isbn');
            $query->setFields(array('product_id'));
            $resultset = $client->select($query);
            $docs = $resultset->getDocuments();
            foreach($docs as $doc){
                $cart->addUnique($doc->product_id, $this->container->getParameter('external_shop_id.numeriklivres'));
            }
        }
    }
    
    private function doAuthGet($user, $pass, $data, $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . $data);
        curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $pass);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: close'));
        $result = curl_exec($ch);
        $this->logger->addInfo("Log for cURL on ".$url);
        $this->logger->addInfo("HTTP Code: ".curl_getinfo($ch, CURLINFO_HTTP_CODE));
        $this->logger->addInfo("Execution time: ".curl_getinfo($ch, CURLINFO_TOTAL_TIME));
        return $result;
    }
    
    private function convertPrice($f_price) {
        return sprintf("%03s", round($f_price * 100));
    }
    
    private function getSolrCatalogConfig() {
        $configCatalogCore = array('adapteroptions' => array(
                'host' => $this->container->getParameter('sans_papier_user_data.solr.host'),
                'port' => $this->container->getParameter('sans_papier_user_data.solr.port'),
                'path' => $this->container->getParameter('sans_papier_user_data.solr.path'),
                'core' => $this->container->getParameter('sans_papier_user_data.solr.core_catalog'))
        );
        return $configCatalogCore;
    }
}

?>
