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
 * Controller that manages the book list data.
 * 
 */
class BookListController extends ContainerAware {

    
    /**
     * @Route("/getBookList.{_format}",  name="booklist", defaults={"_format" = "json"})
     * @View()
     */
    public function getBookListAction() {
        
        //Controller of book listing
        $configCatalogCore = array('adapteroptions' => array(
        'host' => $this->container->getParameter('sans_papier_books_mart.solr.host'),
        'port' => $this->container->getParameter('sans_papier_books_mart.solr.port'),
        'path' => $this->container->getParameter('sans_papier_books_mart.solr.path'),
        'core' => $this->container->getParameter('sans_papier_books_mart.solr.core_catalog'))
        );
        
        $client = new \Solarium_Client($configCatalogCore);
        
        // get a select query instance
        $query = $client->createSelect();
        $query->setRows(10);
        //specify to Solr the default search field
        //specify to Solr the field that have to appear in the resultset
        $query->setFields(array('author_firstname', 'author_lastname', 'author_id', 'publisher_name', 'product_id', 'title', 'format_name', 'isbn', 'book_id', 'is_package', 'package_description'));
        //specify to Solr the string to evaluate
        $query->setQuery("*:*");
        //this executes the query and returns the result
        $resultset = $client->select($query);
        $result = $resultset->getDocuments();  

        return $result;
    }
}