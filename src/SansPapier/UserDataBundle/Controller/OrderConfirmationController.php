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

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Controller\Annotations\View;
use Knp\Bundle\SnappyBundle;
use Knp\Snappy\Pdf;
use SansPapier\ShopBundle\Entity\Operation;
use SansPapier\ShopBundle\Entity\Transaction;
use SansPapier\UserDataBundle\Entity\Preference;
use SansPapier\UserDataBundle\Entity\ProductOrderedUnique;
use SansPapier\UserDataBundle\Model;

/**
 * @Route("/order_confirmation")
 */
class OrderConfirmationController extends Controller {

    /**
     * 
     * @Route("/generatePDF_{_orderId}.{_format}", name="sanspapier_generatePDF", defaults={"_orderId" = "", "_format" = "html"})
     * @View()
     */
    public function generatePDFAction($_orderId) {

        $token = $this->container->get('security.context')->getToken();
        $user = $token->getUser();
        $userEm = $this->container->get('doctrine')->getEntityManager("user");
        $userRepo = $userEm->getRepository('SansPapierUserDataBundle:ProductShelf');
        $userPrefRepo = $userEm->getRepository('SansPapierUserDataBundle:Preference');

        $shopEm = $this->container->get('doctrine')->getEntityManager("shop");
        $opeRepo = $shopEm->getRepository('SansPapierShopBundle:Operation');
        $transRepo = $shopEm->getRepository('SansPapierShopBundle:Transaction');

        $shopOperation = $opeRepo->findOneBy(array('user_id_fk' => $user->getUserId(), 'dilicom_transaction_id' => $_orderId));
        if ($shopOperation == NULL)
            return "ERROR MESSAGE";

        else {
            $id_op = $shopOperation->getOperationId();

            $order = array();

            $date = $shopOperation->getTransactionAt();
            $formatDate = $date->format('d-m-Y H:i:s');
            $explodeDate = explode(" ", $formatDate);
            $order['order_date'] = $explodeDate[0];
            $order['order_time'] = $explodeDate[1];
            $order['total_price'] = $shopOperation->getTotalPrice();

            $productsShelf = $userRepo->findBy(array('user' => $user->getUserId(), 'operation_id_fk' => $id_op));
            $order_product_imageArray = array();
            $order_product_detailsArray = array();
            $order_product_uPriceArray = array();
            $authorName = "";
            
            foreach ($productsShelf as $key2 => $document) {

                $shopTransaction = $transRepo->findOneBy(array('user_id_fk' => $user->getUserId(), 'transaction_id' => $document->getTransactionIdFk()));
                $products[$key2]['product'] = $this->getProductById($document->getProductIdSolr());

                if ($products[$key2]['product'][0]['author_firstname'])
                    $authorName = $products[$key2]['product'][0]['author_firstname'][0] . " " . $products[$key2]['product'][0]['author_lastname'][0];
                else
                    $authorName = $products[$key2]['product'][0]['author_lastname'][0];
                
                $order_product_imageArray[] = $products[$key2]['product'][0]['publisher_id'] . "/" . $products[$key2]['product'][0]['product_id'] . "/" . $products[$key2]['product'][0]['product_id'] . "_fc_E.jpg";
                $order_product_detailsArray[$key2]['title'] = $products[$key2]['product'][0]['title'];
                $order_product_detailsArray[$key2]['authorName'] = $authorName;
                $order_product_uPriceArray[] = $shopTransaction->getPrice();
            }

            $userInfos = $userPrefRepo->findOneBy(array('user' => $user->getUserId()));
            $order['user']['name'] = $userInfos->getFirstname() . " " . $userInfos->getLastname();
            $order['user']['mail'] = $user->getEmail();


            $html = $this->renderView('SansPapierUserDataBundle:OrderConfirmation:pdfGenerator.html.twig', array(
                'order_num' => $_orderId,
                'order_time' => $order['order_time'],
                'order_date' => $order['order_date'],
                'user_name' => $order['user']['name'],
                'user_mail' => $order['user']['mail'],
                'total_produits' => $order['total_price'],
                'reduction' => "0",
                'total_price' => $order['total_price'],
                'order_product_imageArray' => $order_product_imageArray,
                'order_product_detailsArray' => $order_product_detailsArray,
                'order_product_uPriceArray' => $order_product_uPriceArray
                    ));

            return new Response(
                            $this->get('knp_snappy.pdf')->getOutputFromHtml($html),
                            200,
                            array(
                                'Content-Type' => 'application/pdf',
                                'Content-Disposition' => 'attachment; filename="commande_sanspapier_' . $_orderId . '.pdf"',
                                'cache-control' => 'must-revalidate',
                                'encoding' => 'UTF-8'
                            )
            );
        }
    }

    /**
     * 
     * @Route("/sendConfirmationMail_{_orderId}.{_format}", name="sanspapier_sendConfirmationMail", defaults={"_orderId" = "", "_format" = "html"})
     * @View()
     */
    public function sendConfirmationMailAction($_orderId) {

        $token = $this->container->get('security.context')->getToken();
        $user = $token->getUser();
        $userEm = $this->container->get('doctrine')->getEntityManager("user");
        $userRepo = $userEm->getRepository('SansPapierUserDataBundle:ProductShelf');
        $userPrefRepo = $userEm->getRepository('SansPapierUserDataBundle:Preference');

        $shopEm = $this->container->get('doctrine')->getEntityManager("shop");
        $opeRepo = $shopEm->getRepository('SansPapierShopBundle:Operation');
        $transRepo = $shopEm->getRepository('SansPapierShopBundle:Transaction');

        $shopOperation = $opeRepo->findOneBy(array('user_id_fk' => $user->getUserId(), 'dilicom_transaction_id' => $_orderId));

        if ($shopOperation == NULL)
            return "ERROR MESSAGE";

        else {
            $id_op = $shopOperation->getOperationId();

            $order = array();

            $date = $shopOperation->getTransactionAt();
            $formatDate = $date->format('d-m-Y H:i:s');
            $explodeDate = explode(" ", $formatDate);
            $order['order_date'] = $explodeDate[0];
            $order['order_time'] = $explodeDate[1];
            $order['total_price'] = $shopOperation->getTotalPrice();

            $productsShelf = $userRepo->findBy(array('user' => $user->getUserId(), 'operation_id_fk' => $id_op));
            $order_product_imageArray = array();
            $order_product_detailsArray = array();
            $order_product_uPriceArray = array();
            $authorName = "";

            foreach ($productsShelf as $key2 => $document) {

                $shopTransaction = $transRepo->findOneBy(array('user_id_fk' => $user->getUserId(), 'transaction_id' => $document->getTransactionIdFk()));
                $products[$key2]['product'] = $this->getProductById($document->getProductIdSolr());
                
                if ($products[$key2]['product'][0]['author_firstname'])
                    $authorName = $products[$key2]['product'][0]['author_firstname'][0] . " " . $products[$key2]['product'][0]['author_lastname'][0];
                else
                    $authorName = $products[$key2]['product'][0]['author_lastname'][0];

                $order_product_imageArray[] = $products[$key2]['product'][0]['publisher_id'] . "/" . $products[$key2]['product'][0]['product_id'] . "/" . $products[$key2]['product'][0]['product_id'] . "_fc_E.jpg";
                $order_product_detailsArray[$key2]['title'] = $products[$key2]['product'][0]['title'];
                $order_product_detailsArray[$key2]['authorName'] = $authorName;
                $order_product_uPriceArray[] = $shopTransaction->getPrice();
            }

            $userInfos = $userPrefRepo->findOneBy(array('user' => $user->getUserId()));
            $order['user']['name'] = $userInfos->getFirstname() . " " . $userInfos->getLastname();
            $order['user']['mail'] = $user->getEmail();

            $html = $this->renderView('SansPapierUserDataBundle:OrderConfirmation:sendConfirmationMail.html.twig', array(
                'order_num' => $_orderId,
                'order_time' => $order['order_time'],
                'order_date' => $order['order_date'],
                'user_name' => $order['user']['name'],
                'user_mail' => $order['user']['mail'],
                'total_produits' => $order['total_price'],
                'reduction' => "0",
                'total_price' => $order['total_price'],
                'order_product_imageArray' => $order_product_imageArray,
                'order_product_detailsArray' => $order_product_detailsArray,
                'order_product_uPriceArray' => $order_product_uPriceArray
                    ));
            $subject = "Confirmation de votre commande " . $_orderId . " chez sanspapier.com";

            $from = "info@sanspapier.com";

            $to = $order['user']['mail'];

            $this->get('mail_helper')->sendEmail($from, $to, $html, $subject);
        }
    }

    /**
     * 
     * @Route("/sendErrorMail_{_idOP}.{_format}", name="sanspapier_sendErrorMail", defaults={"_idOP" = "", "_format" = "html"})
     * @View()
     */
    public function sendErrorMailAction($_idOP) {

        $token = $this->container->get('security.context')->getToken();
        $user = $token->getUser();
        $userEm = $this->container->get('doctrine')->getEntityManager("user");
        $userPrefRepo = $userEm->getRepository('SansPapierUserDataBundle:Preference');

        $shopEm = $this->container->get('doctrine')->getEntityManager("shop");
        $opeRepo = $shopEm->getRepository('SansPapierShopBundle:Operation');
        $transRepo = $shopEm->getRepository('SansPapierShopBundle:Transaction');

        $shopOperation = $opeRepo->findOneBy(array('user_id_fk' => $user->getUserId(), 'operation_id' => $_idOP));

        if ($shopOperation == NULL)
            return "ERROR MESSAGE";

        else {

            $order = array();

            $date = $shopOperation->getTransactionAt();
            $formatDate = $date->format('d-m-Y H:i:s');
            $explodeDate = explode(" ", $formatDate);
            $order['order_date'] = $explodeDate[0];
            $order['order_time'] = $explodeDate[1];

            $productsShelf = $transRepo->findBy(array('user_id_fk' => $user->getUserId(), 'operation' => $_idOP));
            $productsString = "";

            foreach ($productsShelf as $key2 => $document) {
                $products[$key2]['product'] = $this->getProductById($document->getProductIdSolr());
                $isbn = $products[$key2]['product'][0]['isbn'];

                $productsString = $productsString . "ID=" . $document->getProductIdSolr() . " ISBN=" . $isbn . " ++++ ";
            }

            $userInfos = $userPrefRepo->findOneBy(array('user' => $user->getUserId()));
            $order['user']['id'] = $user->getUserId();
            $order['user']['name'] = $userInfos->getFirstname() . " " . $userInfos->getLastname();
            $order['user']['mail'] = $user->getEmail();


            $html = "ID CLIENT : " . $order['user']['id'] . " //// NOM CLIENT : " . $order['user']['name'] . " //// MAIL CLIENT : " . $order['user']['mail'] . " //// OPERATION ID : " . $_idOP . " //// DATE : " . $order['order_date'] . " //// TIME : " . $order['order_time'] . " //// PRODUCTS : " . $productsString;


            $subject = "ERREUR DILICOM, operation " . $_idOP;

            $from = "info@sanspapier.com";
            $to = "benoit@sanspapier.com";

            $this->get('mail_helper')->sendEmail($from, $to, $html, $subject);
        }
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
        $query->setFields(array('author_firstname', 'author_lastname', 'author_id', 'publisher_id', 'publisher_name', 'product_id', 'title', 'EUR_c', 'format_name', 'file_size', 'publishing_date', 'isbn'));
        //specify to Solr the string to evaluate
        $query->setQuery($id_product);
        //this executes the query and returns the result
        $resultset = $client->select($query);

        $result = $resultset->getDocuments();

        return $result;
    }
    
}

?>
