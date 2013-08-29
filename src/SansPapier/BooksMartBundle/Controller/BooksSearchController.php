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

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations\View;

/**
 * Controller that manages the book search.
 * 
 */
class BooksSearchController extends ContainerAware {

    public static function stripAccents($pString) {
        $result = mb_strtolower($pString, 'UTF-8');
        $result = str_replace(array('à', 'â', 'ä', 'á', 'ã', 'å',
            'î', 'ï', 'ì', 'í',
            'ô', 'ö', 'ò', 'ó', 'õ', 'ø',
            'ù', 'û', 'ü', 'ú',
            'é', 'è', 'ê', 'ë',
            'ç', 'ÿ', 'ñ', 'ḧ', 'ĥ',
                ), array('a', 'a', 'a', 'a', 'a', 'a',
            'i', 'i', 'i', 'i',
            'o', 'o', 'o', 'o', 'o', 'o',
            'u', 'u', 'u', 'u',
            'e', 'e', 'e', 'e',
            'c', 'y', 'n', 'h', 'h',
                ), $result);
        return $result;
    }

    /**
     * Main entry point to the sreach engine
     * 
     * @Route("/search.{_format}/{_query}/{_filters}",  name="sanspapier_send_unitex", defaults={"_format" = "json"})
     * @View()
     */
    public function searchAction($_query, $_filters) {
        // secure the $query string

        $session = $this->container->get('request')->getSession();

        //If country hasnt been set yet in session
        if (is_null($session->get('country'))) {
            $country = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
            if (!$country)
                $country = 'FR';

            $session->set('country', $country);
        } else {
            $country = $session->get('country');
        }
        if (is_null($session->get('defaultCountry'))) {
            $session->set('defaultCountry', 'FR');
            $defaultCountry = 'FR';
        } else {
            $defaultCountry = $session->get('defaultCountry');
        }
        $country = $session->get('country');
        $defaultCountry = $session->get('defaultCountry');

        $safe_query = $_query; //= BooksSearchController::stripAccents($_query);

        if (empty($safe_query)) {
            return $this->getError("Bad or empty query");
        }

        // process the filters
        $filter_arr = explode("|", $_filters);
        if (count($filter_arr) !== 6) {
            return $this->getError("Corrupted filters");
        }

        $price = $filter_arr[0];
        $epub = $filter_arr[1];
        $pdf = $filter_arr[2];
        $mobile = $filter_arr[3];
        $drm = $filter_arr[4];
        $type = $filter_arr[5];

        // get the unitex params
        $host = $this->container->getParameter('sans_papier_books_mart.unitex.host');
        $port = $this->container->getParameter('sans_papier_books_mart.unitex.port');

        // get socket
        $socket = stream_socket_client("$host:$port", $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
        if (!$socket) {
            $msg = ($this->container->get('kernel')->getEnvironment() == "dev") ? 'Socket Error, Host down. Dev: ' . socket_strerror(socket_last_error()) : 'Socket Error, Host down.';
            return $this->getError($msg);
        }

        $safe_query = $price . "_" . $epub . $pdf . $mobile . $drm . "_" . $safe_query . "_" . $country . "_" . $defaultCountry . "_WO_" . $type . "\n\r";
        $id = 0;
        $sockets = array();

        if ($socket == true) {
            fwrite($socket, $safe_query);
            $sockets[$id] = $socket;
            $id+=1;
        } else {
            $status[$id] = "failed," . $errno . " " . $errstr;
        }

        $buf = "";
        while (count($sockets)) {
            $read = $sockets;
            $write = NULL;
            $except = NULL;
            $n = stream_select($read, $write, $except, 0);
            if ($n > 0) {
                foreach ($read as $r) {
                    $id = array_search($r, $sockets);
                    $buf = "";
                    while (!feof($r)) {
                        $buf .= fread($r, 8192);
                    }

                    fclose($r);
                    unset($sockets[$id]);
                }
            }
        }

        if ($buf != "") {
            $status = 0;
            $arrResult = array();
            $behavior = NULL;
            $explode = explode("sp_bibli_delim", $buf);
            if (count($explode) != 2) {
                $explode = explode("sp_genre_delim", $buf);
                if (count($explode) < 2) {
                    $explode = explode("sp_publi_delim", $buf);
                    if (count($explode) != 2) {
                        $explode = explode("sp_booksheet_delim", $buf);
                        if (count($explode) == 1) {
                            if (strpos($explode[0], "book_id") == false) {
                                $behavior = "booksheet";
                            } else {
                                $behavior = "target";
                            }
                        } else {
                            $behavior = "booksheet_target";
                        }
                    } else {
                        $behavior = "Editeur";
                    }
                } else {
                    $behavior = "Genre";
                }
            } else {
                if (strpos($explode[0], 'author_nonSearchable'))
                    $behavior = "Bibliographie_alone";
                else {
                    $behavior = "Bibliographie";
                }
            }

            $result = json_decode($explode[0], true);
            $arrResult[] = $result;

            if (isset($explode[1])) {
                $secondResult = json_decode($explode[1], true);
                $arrResult[] = $secondResult;
            }
            else
                $arrResult[] = NULL;

            $arrResult[] = $behavior;
            $arrResult[] = $country;
            $arrResult[] = $defaultCountry;

            return $arrResult;
        } else {
            return -1; //TODO
        }
    }

    /**
     * Main entry point to the sreach engine
     * 
     * @Route("/KeywordSearch.{_format}/{_query}/{_filters}",  name="sanspapier_keyword_pref", defaults={"_format" = "json"})
     * @View()
     */
    public function KeywordsAction($_query, $_filters) {

        $session = $this->container->get('request')->getSession();
        $country = $session->get('country');
        $defaultCountry = $session->get('defaultCountry');

        // process the filters
        $filters = $this->genFilters($_filters);
        $client = $this->getSolrCatalogConnector();
        $QueryString = $this->genKeywordQueryString($_query, "AND");

        // get a select query instance
        $query = $client->createSelect();

        $query->setRows(10);
        //specify to Solr the field that have to appear in the resultset
        $query->setFields(array('author_firstname', 'author_lastname', 'author_id', 'author_searchable', 'publisher_id', 'publisher_name', 'publisher_logo', 'genre_name', 'product_id', 'related_products', 'title', 'WO_EUR_TTC_c', $country . '_EUR_TTC_c', $defaultCountry . '_EUR_TTC_c', 'format_id', 'format_name', 'description', 'file_size', 'nb_pages', 'publishing_date', 'isbn', 'book_id', 'is_package', 'package_description'));
        //specify to Solr the string to evaluate
        $query->setQuery("keywords:" . $QueryString);
        $hl = $query->getHighlighting();
        $hl->setFields('keywords');
        $hl->setSimplePrefix(' ');
        $hl->setSimplePostfix(' ');

        if ($filters !== "") {

            //$currency = $this->container->getParameter('sans_papier_books_mart.solr.currency');
            $fq_price1 = $query->createFilterQuery('price1')->setQuery("WO_EUR_TTC_c:[0.00,EUR TO " . $filters[0] . ".00,EUR]");
            $fq_price2 = $query->createFilterQuery('price2')->setQuery($country . "_EUR_TTC_c:[0.00,EUR TO " . $filters[0] . ".00,EUR]");
            $fq_price3 = $query->createFilterQuery('price3')->setQuery($defaultCountry . "_EUR_TTC_c:[0.00,EUR TO " . $filters[0] . ".00,EUR]");

            if ($filters[1] !== "") {
                $fq_format = $query->createFilterQuery('format')->setQuery("format_id:" . $filters[1]);
                $query->addFilterQueries(array($fq_price1, $fq_price2, $fq_price3, $fq_format));
            } else {
                $query->addFilterQueries(array($fq_price1, $fq_price2, $fq_price3));
            }
        }

        $resultset = $client->select($query);

        $documents = $this->SortDocumentsByWeight($resultset);
        if (!$documents)
            $documents = 'No Result';

        $data = array();
        $data['documents'] = $documents;
        $data['country'] = $country;
        $data['defaultCountry'] = $defaultCountry;

        return $data;
    }

    private function SortDocumentsByWeight($resultset) {
        $documents = $this->parseDocuments($resultset);

        if (!count($documents)) {
            return null;
        }

        $documents = $this->sort($documents);

        $documents = $this->DedupByBookId($documents);

        return $documents;
    }

    private function parseDocuments($resultset) {

        $documents = $resultset->getDocuments();

        if (!count($documents)) {
            return null;
        }

        $highlighting = $resultset->getHighlighting();

        $books = array();
        $i = 0;

        foreach ($documents as $document) {
            $sum = 0;
            $counter = array();
            $highlightedDoc = $highlighting->getResult($document->product_id);

            if ($highlightedDoc) {
                //browse the higligted field that have been matched and extract the keyword balance value
                foreach ($highlightedDoc as $highlight) {
                    $kw = implode(' (...) ', $highlight);
                    $tabTemp = explode('|', $kw);
                    $counter[] = end($tabTemp);
                }
                if (count($counter)) {
                    $sum = array_sum($counter);
                    unset($counter);
                }
            }
            $books[$i]['doc'] = $document;
            $books[$i]['sum'] = $sum;
            $books[$i]['match'] = count($highlightedDoc);
            $i++;
        }

        return $books;
    }

    private function sort($books) {

        $TempList = $books;
        unset($books);
        $books = array();

        $TempList = $this->sortListByMatch($TempList);
        $List = array();
        $TempMatchNumber = $TempList[0]['match'];



        for ($i = 0; $i < count($TempList); $i++) {

            $CurrentBook = $TempList[$i];
            if ($CurrentBook['match'] == $TempMatchNumber) {
                $List[] = $CurrentBook;
            } else {
                $List = $this->sortListBySum($List);
                foreach ($List as $doc) {
                    $books[] = $doc['doc'];
                }
                $List = array();
                $TempMatchNumber = $CurrentBook['match'];
            }
        }

        $List = $this->sortListBySum($List);
        if (count($List)) {
            foreach ($List as $doc) {
                $books[] = $doc['doc'];
            }
        }
        return $books;
    }

    private function compareBySum($key) {
        return function ($a, $b) use ($key) {
                    if ($a[$key] == $b[$key]) {
                        return 0;
                    }
                    return ($a[$key] > $b[$key]) ? -1 : 1;
                };
    }

    private function sortListBySum($List) {
        usort($List, $this->compareBySum('sum'));
        return $List;
    }

    private function sortListByMatch($List) {
        usort($List, $this->compareBySum('match'));
        return $List;
    }

    private function genKeywordQueryString($_query, $operator) {

        $splitted_query = explode(" ", $_query);
        $QueryString = "";
        foreach ($splitted_query as $i => $keyword) {
            $keyword = strtolower($keyword);
            $QueryString .= " (" . $keyword . " OR ";
            $QueryString .= $keyword . ".N OR ";
            $QueryString .= $keyword . ".A ) ";

            if ($i != count($splitted_query) - 1) {
                $QueryString .= $operator;
            }
        }

        return $QueryString;
    }

    private function genFilters($_filters) {
        $filter_arr = explode("|", $_filters);
        $price = $filter_arr[0];
        return array($price, BooksDisplayController::processFilters($filter_arr));
    }

    private function getSolrCatalogConnector() {
        $configCatalogCore = array('adapteroptions' => array(
                'host' => $this->container->getParameter('sans_papier_books_mart.solr.host'),
                'port' => $this->container->getParameter('sans_papier_books_mart.solr.port'),
                'path' => $this->container->getParameter('sans_papier_books_mart.solr.path'),
                'core' => $this->container->getParameter('sans_papier_books_mart.solr.core_catalog'))
        );

        return new \Solarium_Client($configCatalogCore);
    }

    private function getError($message) {
        if ($this->container->get('kernel')->getEnvironment() == "dev") {
            throw new NotFoundHttpException($message);
        }
        return array("status" => FALSE, "message" => $message);
    }

    private function DedupByBookId($documents) {

        $TempQuery = array();
        $temp_book_id = 0;

        foreach ($documents as $document) {
            $temp_book_id = $document->book_id;
            $TempQuery[] = $temp_book_id;
        }

        $TempList = array();
        $TempQuery = array_unique($TempQuery);


        foreach ($TempQuery as $TempDoc) {
            foreach ($documents as $solrDoc) {
                $doc_book_id = $solrDoc->book_id;
                if ($doc_book_id == $TempDoc) {
                    $TempList[] = $solrDoc;
                    break;
                }
            }
        }

        return $TempList;
    }

}
