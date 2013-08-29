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
namespace SansPapier\BooksMartBundle\Controller;

use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Component\DependencyInjection\ContainerAware;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use SansPapier\BooksMartBundle\Classes\SolrManager;

/**
 * Controller that manages the book sheet data.
 * 
 */
class BookDataController extends ContainerAware {

    
    /**
     * @Route("/sheet_{pProductId}.{_format}",  name="sheet", defaults={"_format" = "json"})
     * @View()
     */
    public function bookSheetAction($pProductId) {
        
        $session = $this->container->get('request')->getSession();
        
        //If country hasnt been set yet in session
        if (is_null($session->get('country'))) {
            $country = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
            if(!$country)
                $country = 'FR';
            
            $session->set('country', $country);
        }
        else
            $country = $session->get('country');
        
        if (is_null($session->get('defaultCountry'))) {
            $session->set('defaultCountry', 'FR');
            $defaultCountry = 'FR';
        }
        else
            $defaultCountry = $session->get('defaultCountry');
        
        $configCatalogCore = array('adapteroptions' => array(
        'host' => $this->container->getParameter('sans_papier_books_mart.solr.host'),
        'port' => $this->container->getParameter('sans_papier_books_mart.solr.port'),
        'path' => $this->container->getParameter('sans_papier_books_mart.solr.path'),
        'core' => $this->container->getParameter('sans_papier_books_mart.solr.core_catalog'))
        );
        
        $client = new \Solarium_Client($configCatalogCore);
        
        // get a select query instance
        $query = $client->createSelect();
        $query->setRows(1);
        //specify to Solr the default search field
        $query->setQueryDefaultField('product_id'); 
        //specify to Solr the field that have to appear in the resultset
        $query->setFields(array('author_firstname', 'author_lastname', 'author_id', 'author_searchable', 'publisher_id', 'publisher_name', 'publisher_logo','genre_name', 'product_id', 'related_products', 'title', 'WO_EUR_TTC_c', $country.'_EUR_TTC_c', $defaultCountry.'_EUR_TTC_c', 'format_id', 'format_name', 'description', 'file_size', 'nb_pages', 'publishing_date', 'isbn', 'book_id', 'is_package', 'package_description', 'extract_url'));
        //specify to Solr the string to evaluate
        $query->setQuery($pProductId);
        //this executes the query and returns the result
        $resultset = $client->select($query);
        
        $document = $resultset->getDocuments(); 

        $authorId = $document[0]['author_id'][0];
        $bookId = $document[0]['book_id'];
        $author_searchable = $document[0]['author_searchable'][0];
        
        $sameAuthorResult = $this->bookSheetSameAuthor($client, $authorId, $bookId);
        if($bookId != NULL)
            $sameBookResult = $this->bookSheetOtherEditors($client, $bookId, $document[0]['publisher_id']);
        else
            $sameBookResult = "";
        
        $result = array('status' => TRUE,
                        'host' => 'http://'.$this->container->get('request')->getHost(),
                        'result' => $resultset,
                        'sameAuthor' => $sameAuthorResult,
                        'otherEditors' => $sameBookResult,
                        'country' => $country,
                        'defaultCountry' => $defaultCountry,
                        'author_searchable' => $author_searchable);
        

        return $result;
    }
    
    private function bookSheetSameAuthor($client, $pAuthorId, $pBookId) {
        // get a select query instance
        $query = $client->createSelect();
        $query->setRows(20);
        //specify to Solr the field that have to appear in the resultset
        $query->setFields(array('book_id', 'product_id', 'publisher_id', 'title'));
        //get the books from the same author excepted products from the same book
        $query->createFilterQuery('differentBookId')->setQuery("-book_id:" . $pBookId);
        $query->setQuery("author_id:" . $pAuthorId);
        //this executes the query and returns the result
        $resultset = $client->select($query);
        if(count($resultset))
            $resultset = $this->dedupRS($resultset);
        
        return $resultset;
    }
    
    private function bookSheetOtherEditors($client, $pBookId, $pPublisherId) {
        // get a select query instance
        $query = $client->createSelect();
        $query->setRows(20);
        //specify to Solr the default search field
        $query->setQueryDefaultField('book_id'); 
        //specify to Solr the field that have to appear in the resultset
        $query->setFields(array('book_id', 'product_id', 'related_products', 'publisher_id', 'title', 'publisher_name'));
        //get the products from the same book excepted with same product_id
        $query->createFilterQuery('differentPublisherId')->setQuery("-publisher_id:" . $pPublisherId);
        $query->setQuery("book_id:" . $pBookId);
        //this executes the query and returns the result
        $resultset = $client->select($query);
        if(count($resultset))
            $resultset = $this->dedupRSEditor($resultset);
        
        return $resultset;
    }

    private function dedupRS($pRs)
    {
        $tabRef = array();
        $newRs = array();
        foreach($pRs as $solrDoc)
        {
            $fields = $solrDoc->getFields();
            if(in_array($fields['book_id'], $tabRef, false) == false)
            {
                $newRs[] = $fields;
                $tabRef[] = $fields['book_id'];
            }
        }
        return $newRs;
    }
    
    private function dedupRSEditor($pRs)
    {
        $tabRef = array();
        $newRs = array();
        foreach($pRs as $solrDoc)
        {
            $fields = $solrDoc->getFields();
            if(in_array('related_products', $fields, false) == true)
            {
                foreach($fields['related_products'] as $relatedProduct)
                {
                    if(in_array($relatedProduct, $tabRef, false) == false)
                    {
                        $newRs[] = $fields;
                        $tabRef[] = $fields['product_id'];
                    }
                }
            }
            else
            {
                $newRs[] = $fields;
                $tabRef[] = $fields['product_id'];
            }
            
        }
        return $newRs;
    }
}