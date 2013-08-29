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
namespace SansPapier\UserDataBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use SansPapier\ShopBundle\Entity\Transaction;
use SansPapier\UserDataBundle\Classes\CartProduct;

/**
 * A Class that holds the custommer cart;
 *
 * @author nunja
 */
class ProductCart
{

  /**
   * Holds the products (product_id_solr)
   * @var type ArrayCollection
   */
  private $products;

  public function __construct() {
    $this->products = new ArrayCollection;
  }
  
  public function clear() {
    $this->products->clear();
  }

  /**
   * returns the product IDs;
   */
  public function getProductIds() {
      $productIdsArray = array();
      foreach($this->products as $product) {
          $productIdsArray[] = $product->getId();
      }
      return $productIdsArray;
  }
  
  public function getCountProducts() {
      return count($this->products);
  }

  public function remove($id) {
      foreach($this->products as $product) {
          if($product->getId() == $id) {
              $this->products->removeElement($product);
              return true;
          }
      }
      return false;
  }
  
  public function findProduct($id) {
      foreach($this->products as $product) {
          if($product->getId() == $id) {
              return $product;
          }
      }
      return null;
  }

  public function addUnique($id, $origin) {
    if ($this->products->isEmpty()) {
        $this->setNewCartProduct($id, $origin);
        return true;
    } else {
        foreach($this->products as $product) {
            if($product->getId() == $id)
                return false;
        }
        $this->setNewCartProduct($id, $origin);
        return true;
    }
  }
  
  private function setNewCartProduct($pId, $pOrigin) {
      $product = new CartProduct();
      $product->setId($pId);
      $product->setProductOrigin($pOrigin);
      $this->products->add($product);
  }
  
  public function createTransactions($configCatalogCore, $currency, $user_id, $country) {
    $products = $this->getProducts($configCatalogCore, $country);
    $res = array();
    $str = '';
    $formatName = '';
    if (count($products)) {
      foreach ($products['products'] as $product) {
        $cartProduct = $this->findProduct($product['product_id']);
        if(isset($product['WO_EUR_TTC_c'])) {
            $str = $product['WO_EUR_TTC_c'];
        }
        elseif(isset($product[$country.'_EUR_TTC_c'])) {
            $str = $product[$country.'_EUR_TTC_c'];
        }
        
        $arr = explode(",", $str);
        $price = floatval($arr[0]);
        $tr = new Transaction();
        $tr->setExternalId($product['isbn']);
        $tr->setDistributorId($product['supplier_id']);
        $tr->setPrice($price);
        $tr->setProductIdSolr($product['product_id']);
        $tr->setUserIdFk($user_id);
        $tr->setCreditCardIdFk("null");
        $tr->setTransactionAt(new \DateTime('now'));
        $tr->setOriginShopId($cartProduct->getProductOrigin());
        if(count($product['format_name']) == 1) {
            $format_name = explode('_', $product['format_name']);
            $tr->setFormatName($format_name[0]);
        }
        $res[] = $tr;
      }
      return $res;
    }
  }
  
  public function getTotalPrice($configCatalogCore, $country) {
    $products = $this->getProducts($configCatalogCore, $country);
    return $products['totalPrice'];
  }
  
  public function getProducts($configCatalogCore, $country) {
    $client = new \Solarium_Client($configCatalogCore);
    $ids = $this->getProductIds();
    
    // build query string
    $query_str = "";
    $i = 1;
    foreach ($ids as $id) {
      if ($i == count($ids))
        $query_str .= $id;
      else
        $query_str .= $id . " OR ";
      
      $i++;
    }

    // get a select query instance
    $query = $client->createSelect();
    // override the default row limit of 10 by setting rows to 30
    $query->setRows(20);
    //specify to Solr the default search field
    $query->setQueryDefaultField('product_id');
    //specify to Solr the field that have to appear in the resultset
    $query->setFields(array('supplier_id','isbn','author_firstname', 'author_lastname', 'publisher_name', 'publisher_id','genre_name', 'product_id', 'product_rank', 'title', 'WO_EUR_TTC_c', $country.'_EUR_TTC_c', 'format_id', 'format_name', 'is_package', 'package_description'));
    //specify to Solr the string to evaluate
    $query->setQuery($query_str);
    //this executes the query and returns the result
    $resultset = $client->select($query);
    $documents = $resultset->getDocuments();
    $res = array();
    $res['products'] = array();
    $totalPrice = 0;
    
    foreach ($documents as $document) {
      $res['products'][] = $document;
      if(isset($document['WO_EUR_TTC_c']))
          $totalPrice += $document['WO_EUR_TTC_c'];
      elseif(isset($document[$country.'_EUR_TTC_c']))
          $totalPrice += $document[$country.'_EUR_TTC_c'];
    }
    $res['totalPrice'] = $totalPrice;
    return $res;
  }
}

?>
