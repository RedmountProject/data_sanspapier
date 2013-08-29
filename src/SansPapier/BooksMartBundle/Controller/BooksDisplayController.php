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
 * Controller that manages the book search.
 * 
 */
class BooksDisplayController extends ContainerAware {

    
    /**
     * @Route("/customDisplay.{_format}",  name="customDisplay", defaults={"_format" = "json"})
     * @View()
     */
    public function customDisplayAction() {
        $securityContext = $this->container->get('security.context');
        $token = $securityContext->getToken();
        $user = $token->getUser();

        if ($user) {
            $res = array();
            $preference = $user->getPreference();
            $publishers = $preference->getPublishers();
            $genres = $preference->getGenres();

            $pref_id = $preference->getPreferenceId();
            $searches = $this->container->get('doctrine')->getEntityManager('user')->getRepository('SansPapierUserDataBundle:UserSearch')->findBy(array("preference" => $pref_id));

            $res['publishers'] = count($publishers);
            $res['genres'] = count($genres);
            $res['searches'] = count($searches);

            return $res;
        } else {
            return $this->getError("Not logged in ...");
        }
    }

    /**
     * @Route("/customEd.{_format}/{_nb}/{_filters}",  name="customEd", defaults={"_filters" = "1000|1|1|1|1","_nb" = "5","_format" = "json"})
     * @View()
     */
    public function customEdAction($_filters) {
        // get user
        $securityContext = $this->container->get('security.context');
        $token = $securityContext->getToken();
        $user = $token->getUser();

        // process the filters
        $filter_arr = explode("|", $_filters);

        $session = $this->container->get('request')->getSession();
        $session->set('customPublishersFirstTime', false);
        $session->set('customPublishersLastFilters', array($filter_arr[0], $filter_arr[1], $filter_arr[2], $filter_arr[3], $filter_arr[4]));

        $country = $session->get('country');
        $defaultCountry = $session->get('defaultCountry');

        if (count($filter_arr) !== 5)
            return $this->getError("Corrupted filters");

        if ($user) {
            $res = array();
            // get the customization preferences.
            $publishers = $user->getPreference()->getPublishers();
            foreach ($publishers as $i => $publisher) {
                $res_arr = $this->getCustomPublishers($publisher, $filter_arr, $country, $defaultCountry);
                if (count($res_arr)) {
                    $res['publishers'][$i] = array($publisher->getName(), $res_arr, $country, $defaultCountry);
                }
            }
            $res['country'] = $country;
            $res['defaultCountry'] = $defaultCountry;
            return $res;
        } else {
            return $this->getError("Not logged in ...");
        }
    }

    /**
     * @Route("/customGenres.{_format}/{_nb}/{_filters}",  name="customGenres", defaults={"_filters" = "1000|1|1|1|1","_nb" = "5","_format" = "json"})
     * @View()
     */
    public function customGenresAction($_filters) {
        // get user
        $securityContext = $this->container->get('security.context');
        $token = $securityContext->getToken();
        $user = $token->getUser();

        // process the filters
        $filter_arr = explode("|", $_filters);

        $session = $this->container->get('request')->getSession();
        $session->set('customGenresFirstTime', false);
        $session->set('customGenresLastFilters', array($filter_arr[0], $filter_arr[1], $filter_arr[2], $filter_arr[3], $filter_arr[4]));

        $country = $session->get('country');
        $defaultCountry = $session->get('defaultCountry');

        if (count($filter_arr) !== 5)
            return $this->getError("Corrupted filters");

        if ($user) {
            $res = array();
            // get the customization preferences.
            $genres = $user->getPreference()->getGenres();
            foreach ($genres as $i => $genre) {
                $res_arr = $this->getCustomGenres($genre, $filter_arr, $country, $defaultCountry);
                if (count($res_arr)) {
                    $res['genres'][$i] = array($genre->getName(), $res_arr);
                }
            }
            $res['country'] = $country;
            $res['defaultCountry'] = $defaultCountry;
            return $res;
        } else {
            return $this->getError("Not logged in ...");
        }
    }

    /**
     * @Route("/customKeywords.{_format}/{_nb}/{_filters}",  name="customKeywords", defaults={"_filters" = "1000|1|1|1|1","_nb" = "5","_format" = "json"})
     * @View()
     */
    public function customKeywordsAction($_filters) {
        // get user
        $securityContext = $this->container->get('security.context');
        $token = $securityContext->getToken();
        $user = $token->getUser();

        // process the filters
        $filter_arr = explode("|", $_filters);

        $session = $this->container->get('request')->getSession();
        $session->set('customKeywordsFirstTime', false);
        $session->set('customKeywordsLastFilters', array($filter_arr[0], $filter_arr[1], $filter_arr[2], $filter_arr[3], $filter_arr[4]));

        if (count($filter_arr) !== 5)
            return $this->getError("Corrupted filters");

        if ($user) {
            $res = array();
            // get the customization preferences.
            $pref_id = $user->getPreference()->getPreferenceId();
            $searches = $this->container->get('doctrine')->getEntityManager('user')->getRepository('SansPapierUserDataBundle:UserSearch')->findBy(array("preference" => $pref_id));
            foreach ($searches as $search) {
                $res['searches'][] = $search->getQuery();
            }
            return $res;
        } else {
            return $this->getError("Not logged in ...");
        }
    }

    /**
     * @Route("/columns.{_format}/{_nb}/{_filters}",  name="columns", defaults={"_filters" = "1000|1|1|1|1","_nb" = "5","_format" = "json"})
     * @View()
     */
    public function columnsAction($_nb, $_filters) {
        // process the filters
        $filter_arr = explode("|", $_filters);
        if (count($filter_arr) !== 5)
            return $this->getError("Corrupted filters");

        $session = $this->container->get('request')->getSession();
        $session->set('customALaUneFirstTime', false);
        $session->set('customALaUneLastFilters', array($filter_arr[0], $filter_arr[1], $filter_arr[2], $filter_arr[3], $filter_arr[4]));

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

        //Defines the genres to display according to the day number
        $genreName = array();
        $genreName[1] = 'Fantastique & SF';
        $genreName[2] = 'Romans';
        $genreName[3] = 'Aventures';
        $genreName[4] = 'Dictionnaires';
        $genreName[5] = 'Historique';
        $genreName[6] = 'Poésie';
        $genreName[7] = 'Humour';
        $genreName[8] = 'Documents - Essais';
        $genreName[9] = 'Revues';
        $genreName[10] = 'Jeunesse';
        $genreName[11] = 'Nouvelles - Contes';
        $genreName[12] = 'Pratique';
        $genreName[13] = 'Théâtre';
        $genreName[14] = 'Policier & Mystère';
        $genreName[15] = 'Chansons';
        $selecContent = array();
        $dayNumber = date("j");
        while($dayNumber > 7)
            $dayNumber = $dayNumber-7;
        
        for($i = 0; $i<5; $i++) {
            $selecContent[$i]['genre_id'] = $this->generateGenreFromDayNumber($i, $dayNumber);
            $selecContent[$i]['genre_name'] = $genreName[$selecContent[$i]['genre_id']];
        }

        $catContent = array();
        for ($i = 0; $i < count($selecContent); $i++) {
            if (count($selecContent[$i])) {
                $sel = $this->getCatContentByGenre($selecContent[$i], $dayNumber, $filter_arr, $country, $defaultCountry);
                if ($sel)
                    $catContent[] = $sel;
            }
            else
                $catContent[] = array();
        }

        if (count($catContent)) {
            return array("host" => 'http://' . $this->container->get('request')->getHost(), "status" => TRUE, "data" => $catContent, "country" => $country, "defaultCountry" => $defaultCountry);
        }
        return array("status" => FALSE, "message" => "No results.");
//        
//        //// GET SELECTIONS IDS ACCORDING TO COLUMNS NUMBERS
//        $selecContent = array();
//        for ($i = 1; $i <= $_nb; $i++) {
//            $selecContent[] = $this->getSelectionId($i);
//        }
//
//        $catContent = array();
//        for ($i = 0; $i < count($selecContent); $i++) {
//            if (count($selecContent[$i])) {
//                $sel = $this->getCatContent($selecContent[$i], $filter_arr, $country, $defaultCountry);
//                if ($sel)
//                    $catContent[] = $sel;
//            }
//            else
//                $catContent[] = array();
//        }
//
//        if (count($catContent)) {
//            return array("host" => 'http://' . $this->container->get('request')->getHost(), "status" => TRUE, "data" => $catContent, "country" => $country, "defaultCountry" => $defaultCountry);
//        }
//        return array("status" => FALSE, "message" => "No results.");
    }
    
    private function generateGenreFromDayNumber($colNumber, $dayNumber) {
        $returnValue = $dayNumber + $colNumber*2;
        return $returnValue;
    }
    
    private function getCatContentByGenre($selecContent, $dayNumber, $filter_arr, $country, $defaultCountry) {
        $configCatalogCore = $this->getCatalogConfig();

        $price = $filter_arr[0];
        $books = array();
        $client = new \Solarium_Client($configCatalogCore);

        // get a select query instance
        $query = $client->createSelect();

        //specify to Solr the default search field
        $query->setQueryDefaultField('genre_id');
        //specify to Solr the field that have to appear in the resultset
        $query->setFields(array('book_id', 'author_firstname', 'author_lastname', 'author_id' ,'author_searchable', 'publisher_name', 'publisher_id', 'genre_name', 'product_id', 'product_rank', 'title', 'WO_EUR_TTC_c', $country . '_EUR_TTC_c', $defaultCountry . '_EUR_TTC_c', 'format_id', 'format_name', 'description', 'selection_id', 'publishing_date', 'nb_pages', 'file_size', 'isbn'));
        //specify to Solr the string to evaluate
        $query->createFilterQuery('genre_id')->setQuery("genre_id:" . $selecContent['genre_id']);
        $query->createFilterQuery('price1')->setQuery("WO_EUR_TTC_c:[0.00,EUR TO " . $price . ".00,EUR]");
        $query->createFilterQuery('price2')->setQuery($country . "_EUR_TTC_c:[0.00,EUR TO " . $price . ".00,EUR]");
        $query->createFilterQuery('price3')->setQuery($defaultCountry . "_EUR_TTC_c:[0.00,EUR TO " . $price . ".00,EUR]");
        $query->setRows(1000);
        $qr_str = $this->processFilters($filter_arr);

        if ($qr_str !== "")
            $query->createFilterQuery('format')->setQuery("format_id:(" . $qr_str . ")");
        $resultsetTemp = $client->select($query);
        $resultset = $this->DedupByBookId($resultsetTemp);
        $res = array();
        $res[] = $selecContent['genre_name'];
        $nbDocs = count($resultset);
        $dayNumber -= 1;
        switch($nbDocs) {
            case 0:
                $res[] = "";
                break;
            case ($nbDocs < 20):
                foreach ($resultset as $document) {
                    $books[] = $document;
                }
                $res[] = $books;
                break;
            case ($nbDocs < 75):
                $testValue = $dayNumber;
                $arrayChunked = array_chunk($resultset, 15);
                if(array_key_exists($dayNumber, $arrayChunked)) {
                    $workingArray = $arrayChunked[$dayNumber];
                } else {
                    $workingArray = $arrayChunked[0];
                }
                foreach ($workingArray as $document) {
                    $books[] = $document;
                }
                $res[] = $books;
                break;
            default:
                $testValue = floor(count($resultset)/7);
                $arrayChunked = array_chunk($resultset, $testValue);
                $workingArray = $arrayChunked[$dayNumber];
                $cptChunck = 0;
                foreach ($workingArray as $document) {
                    $cptChunck++;
                    $books[] = $document;
                    if($cptChunck > 15)
                        break;
                }
                $res[] = $books;
                break;
        }
        //tabard
        return $res;
    }

    private function getSelectionId($idCol) {
        //récup du editoSelectionId dans editoSelection where idcolumn=$pIdColumn DOCTRINE
        $configEditoCore = $this->getEditoConfig();

        $client = new \Solarium_Client($configEditoCore);

        // get a select query instance
        $query = $client->createSelect();
        $query->setRows(1);
        //specify to Solr the field that have to appear in the resultset
        $query->setFields(array('selection_id', 'selection_name', 'begin_date', 'column_id'));
        $query->createFilterQuery('begin')->setQuery("begin_date:[* TO NOW]");
        $query->createFilterQuery('status')->setQuery("status:1");
        $query->addSort('begin_date', \Solarium_Query_Select::SORT_DESC);
        $query->setQuery('column_id:' . $idCol);
        
        //this executes the query and returns the result
        $resultset = $client->select($query);
        
        $res = array();
        foreach ($resultset as $document) {
            $res[] = $document;
        }
        return $res;
    }

    private function getCatContent($selecContent, $filter_arr, $country, $defaultCountry) {
        $configCatalogCore = $this->getCatalogConfig();

        $price = $filter_arr[0];
        $books = array();
        $client = new \Solarium_Client($configCatalogCore);

        // get a select query instance
        $query = $client->createSelect();

        //specify to Solr the default search field
        $query->setQueryDefaultField('selection_id');
        //specify to Solr the field that have to appear in the resultset
        $query->setFields(array('author_firstname', 'author_lastname', 'author_id' ,'author_searchable', 'publisher_name', 'publisher_id', 'genre_name', 'product_id', 'product_rank', 'title', 'WO_EUR_TTC_c', $country . '_EUR_TTC_c', $defaultCountry . '_EUR_TTC_c', 'format_id', 'format_name', 'description', 'selection_id', 'publishing_date', 'nb_pages', 'file_size', 'isbn'));
        //specify to Solr the string to evaluate
        $query->createFilterQuery('selection_id')->setQuery("selection_id:" . $selecContent[0]['selection_id']);
        $query->createFilterQuery('price1')->setQuery("WO_EUR_TTC_c:[0.00,EUR TO " . $price . ".00,EUR]");
        $query->createFilterQuery('price2')->setQuery($country . "_EUR_TTC_c:[0.00,EUR TO " . $price . ".00,EUR]");
        $query->createFilterQuery('price3')->setQuery($defaultCountry . "_EUR_TTC_c:[0.00,EUR TO " . $price . ".00,EUR]");

        $qr_str = $this->processFilters($filter_arr);

        if ($qr_str !== "")
            $query->createFilterQuery('format')->setQuery("format_id:(" . $qr_str . ")"); //$query->setQuery($qr_str);
            
        //this executes the query and returns the result
        $resultset = $client->select($query);

        $res = array();
        $res[] = $selecContent[0]['selection_name'];
        if (count($resultset)) {
            foreach ($resultset as $document) {
                $i = 0;
                foreach ($document['selection_id'] as $selectId) {
                    if ($selectId == $selecContent[0]['selection_id']) {
                        $rank = (int) $document['product_rank'][$i] - 1;
                        $books[$rank] = $document;
                        break;
                    }
                    $i++;
                }
            }
            ksort($books);
            $res[] = $books;
        }
        else
            $res[] = "";
        return $res;
    }

    private function getCustomGenres($genre, $filter_arr, $country, $defaultCountry) {
        $configCatalogCore = $this->getCatalogConfig();
        // get filters
        $price = $filter_arr[0];
        $client = new \Solarium_Client($configCatalogCore);

        // get a select query instance
        $query = $client->createSelect();

        //specify to Solr the default search field
        $query->setQueryDefaultField('product_id');
        //specify to Solr the field that have to appear in the resultset
        $query->setFields(array('author_firstname', 'author_lastname', 'author_id', 'author_searchable', 'publisher_name', 'publisher_id', 'genre_name', 'genre_id', 'product_id', 'title', 'WO_EUR_TTC_c', $country . '_EUR_TTC_c', $defaultCountry . '_EUR_TTC_c', 'format_id', 'format_name', 'description', 'selection_id', 'publishing_date', 'nb_pages', 'file_size', 'isbn'));
        //specify to Solr the string to evaluate

        $query->createFilterQuery('genre_id')->setQuery("genre_id:" . $genre->getGenreId());

        $query->createFilterQuery('price1')->setQuery("WO_EUR_TTC_c:[0.00,EUR TO " . $price . ".00,EUR]");
        $query->createFilterQuery('price2')->setQuery($country . "_EUR_TTC_c:[0.00,EUR TO " . $price . ".00,EUR]");
        $query->createFilterQuery('price3')->setQuery($defaultCountry . "_EUR_TTC_c:[0.00,EUR TO " . $price . ".00,EUR]");
        $qr_str = $this->processFilters($filter_arr);
        if ($qr_str !== "")
            $query->createFilterQuery('format')->setQuery("format_id:(" . $qr_str . ")");


        $groupComponent = $query->getGrouping();
        $groupComponent->addField('title');
        $resultset = $client->select($query);

        $res = $resultset->getGrouping()->getGroup("title");
        return $this->flattenSolrGroupedRes($res);
    }

    private function getCustomPublishers($publisher, $filter_arr, $country, $defaultCountry) {
        $configCatalogCore = $this->getCatalogConfig();
        // get filters
        $price = $filter_arr[0];
        $client = new \Solarium_Client($configCatalogCore);

        // get a select query instance
        $query = $client->createSelect();
        //specify to Solr the default search field
        $query->setQueryDefaultField('product_id');
        //specify to Solr the field that have to appear in the resultset
        $query->setFields(array('author_firstname', 'author_lastname', 'publisher_name', 'publisher_id',  'author_id' ,'author_searchable', 'genre_name', 'genre_id', 'product_id', 'title', 'WO_EUR_TTC_c', $country . '_EUR_TTC_c', $defaultCountry . '_EUR_TTC_c', 'format_id', 'format_name', 'description', 'selection_id', 'publishing_date', 'nb_pages', 'file_size', 'isbn'));
        //specify to Solr the string to evaluate

        $query->createFilterQuery('publisher_id')->setQuery("publisher_id:" . $publisher->getPublisherId());

        $query->createFilterQuery('price1')->setQuery("WO_EUR_TTC_c:[0.00,EUR TO " . $price . ".00,EUR]");
        $query->createFilterQuery('price2')->setQuery($country . "_EUR_TTC_c:[0.00,EUR TO " . $price . ".00,EUR]");
        $query->createFilterQuery('price3')->setQuery($defaultCountry . "_EUR_TTC_c:[0.00,EUR TO " . $price . ".00,EUR]");
        $qr_str = $this->processFilters($filter_arr);
        if ($qr_str !== "")
            $query->createFilterQuery('format')->setQuery("format_id:(" . $qr_str . ")");


        $groupComponent = $query->getGrouping();
        $groupComponent->addField('title');
        $resultset = $client->select($query);


        $res = $resultset->getGrouping()->getGroup("title");
        return $this->flattenSolrGroupedRes($res);
    }

    /**
     * @Route("/get_formats_{_id}.{_format}", name="sanspapier_get_formats", defaults={"_format" = "json"})
     * @View()
     * @param type $id
     * @return type
     */
    public function getFormatAction($_id) {
        $session = $this->container->get('request')->getSession();
        $country = $session->get('country');
        $defaultCountry = $session->get('defaultCountry');
        $configCatalogCore = array('adapteroptions' => array(
                'host' => $this->container->getParameter('sans_papier_books_mart.solr.host'),
                'port' => $this->container->getParameter('sans_papier_books_mart.solr.port'),
                'path' => $this->container->getParameter('sans_papier_books_mart.solr.path'),
                'core' => $this->container->getParameter('sans_papier_books_mart.solr.core_catalog'))
        );

        $client = new \Solarium_Client($configCatalogCore);
        $query = $client->createSelect();
        $query->setQuery($_id);
        $query->setQueryDefaultField('product_id');

        //$currency = $this->container->getParameter('sans_papier_user_data.solr.currency') . '_c';
        $query->setFields(array('format_name', 'product_id', 'related_products', 'publisher_id', 'is_package', 'WO_EUR_TTC_c', $country . '_EUR_TTC_c', $defaultCountry . '_EUR_TTC_c'));
        //$query->setFields(array('format_name', 'product_id', 'related_products', 'publisher_id', 'is_package', $currency));
        $resultset = $client->select($query);
        $document = $resultset->getDocuments();
        $returnData = array('document' => $document[0],
            'country' => $country,
            'defaultCountry' => $defaultCountry);
        return $returnData;
    }

    private function getError($message) {
        return array("status" => FALSE, "message" => $message);
    }

    private function getEditoConfig() {
        return array('adapteroptions' => array(
                'host' => $this->container->getParameter('sans_papier_books_mart.solr.host'),
                'port' => $this->container->getParameter('sans_papier_books_mart.solr.port'),
                'path' => $this->container->getParameter('sans_papier_books_mart.solr.path'),
                'core' => $this->container->getParameter('sans_papier_books_mart.solr.core_edito'))
        );
    }

    private function getCatalogConfig() {
        return array('adapteroptions' => array(
                'host' => $this->container->getParameter('sans_papier_books_mart.solr.host'),
                'port' => $this->container->getParameter('sans_papier_books_mart.solr.port'),
                'path' => $this->container->getParameter('sans_papier_books_mart.solr.path'),
                'core' => $this->container->getParameter('sans_papier_books_mart.solr.core_catalog'))
        );
    }

    public static function processFilters($filter_arr) {
        // get filters
        $filter_arr;
        $epub = $filter_arr[1];
        $pdf = $filter_arr[2];
        $mobile = $filter_arr[3];
        $drm = $filter_arr[4];

        $filters = array();

        if (!($epub == "1" && $pdf == "1" && $mobile == "1" && $drm == "1")) {
            if ($epub == "1") {
                if ($drm == "0")
                    $filters[] = "4_1 OR 4_3";
                else
                    $filters[] = "4_*";
            }
            if ($pdf == "1") {
                if ($drm == "0")
                    $filters[] = "6_1 OR 6_3";
                else
                    $filters[] = "6_*";
            }
            if ($mobile == "1") {
                if ($drm == "0")
                    $filters[] = "7_1 OR 7_3";
                else
                    $filters[] = "7_*";
            }
        }

        $qr_str = "";
        $i = 1;
        foreach ($filters as $filter) {
            if ($i < count($filters))
                $qr_str .= $filter . " OR ";
            else
                $qr_str .= $filter;
            $i++;
        }
        return $qr_str;
    }

    private function flattenSolrGroupedRes($res) {
        $flat = array();
        foreach ($res as $entry) {
            $docs = $entry->getDocuments();
            $flat[] = $docs[0];
        }
        return $flat;
    }
    
    private function DedupByBookId($documents) {

        $tabRef = array();
        $newRs = array();
        foreach($documents as $solrDoc)
        {
            $fields = $solrDoc->getFields();
            if(in_array($fields['book_id'], $tabRef, false) == false)
            {
                $newRs[] = $solrDoc;
                $tabRef[] = $fields['book_id'];
            }
        }
        return $newRs;
    }

}
