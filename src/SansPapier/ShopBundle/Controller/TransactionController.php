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
namespace SansPapier\ShopBundle\Controller;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations\View;
use SansPapier\ShopBundle\Entity\Operation;
use SansPapier\ShopBundle\Entity\Transaction;
use SansPapier\UserDataBundle\Entity\ProductShelf;
use SansPapier\UserDataBundle\Entity\ProductLink;
use SansPapier\UserDataBundle\Entity\ProductOrderedUnique;

/**
 * @Route("/secure");
 * @author nunja 
 */
class TransactionController extends ContainerAware {

    private $logger;
    private $emUser;
    private $emShop;

    public function __construct() {
        // create a log channel
        $this->logger = new Logger('name');
        $this->logger->pushHandler(new StreamHandler('../app/sp_logs/socgen.log', Logger::INFO));
    }

    /**
     * @Route("/checkId.{_format}",  name="sanspapier_secure_checkId", defaults={"_format" = "html"})
     * @View()
     */
    public function getStatutByIdOperationAction() {

        $session = $this->container->get('request')->getSession();    //get back the operation thnx to the transaction id;

        if ($session->has('id_op')) {
            $id_op = $session->get('id_op');
        } else {
            return(-1);
        }

        $em = $this->container->get('doctrine')->getEntityManager("shop");
        $repo = $em->getRepository('SansPapierShopBundle:Operation');
        $operation = $repo->find($id_op);
        if (( is_object($operation) || $operation instanceof \SansPapier\ShopBundle\Entity\Operation)) {
            $operationStatus = $operation->getStatus();
            $counter = 0;
            while($operationStatus == 2 && $counter<10)
            {
                sleep(1);
                $em->getUnitOfWork()->clear();
                $operation = $repo->find($id_op);
                $operationStatus = $operation->getStatus();
                $counter++;
                
            }
            
            return $operation->getStatus();
        }
        return(-1);
    }
    
    /**
     * @Route("/decide_buy_process.{_format}/{_from}",  name="sanspapier_decide_buy_process", defaults={"_format" = "json"})
     * @View()
     */
    public function decideBuyProcessAction($_from) {

        $securityContext = $this->container->get('security.context');
        $token = $securityContext->getToken();
        $user = $token->getUser();
        if (!is_object($user) || !$user instanceof \SansPapier\UserDataBundle\Entity\User) {
            throw new NotFoundHttpException('No Access here');
        }

        if (!$user->isPreferenceComplete()) {
            throw new NotFoundHttpException('Incomplete User');
        }

        // get session for the cart
        $session = $this->container->get('request')->getSession();
        $country = $session->get('country');
        $cart = $session->get('cart');

        if (!$cart) {
            throw new NotFoundHttpException('Nothing in the cart');
        }

        // solr config
        $configCatalogCore = array('adapteroptions' => array(
                'host' => $this->container->getParameter('sans_papier_shop.solr.host'),
                'port' => $this->container->getParameter('sans_papier_shop.solr.port'),
                'path' => $this->container->getParameter('sans_papier_shop.solr.path'),
                'core' => $this->container->getParameter('sans_papier_shop.solr.core_catalog'))
        );
        
        $cart_total_price = $cart->getTotalPrice($configCatalogCore, $country);
        
        $returnData = array();
        if($cart_total_price > 0)
        {
            $returnData['type'] = 1;
            $returnData['data'] = $this->requestAction($_from);
            
            return $returnData;
        }
        else {
            //Cart has no cost : go to dilicom process
            $returnData['type'] = 2;
            $returnData['data'] = $this->requestBypassAction($_from);
            return $returnData;
        }
    }
    
    /**
     * @Route("/bypass_request.{_format}/{_from}",  name="sanspapier_bypass_request", defaults={"_format" = "json"})
     * @View()
     */
    public function requestBypassAction($_from) {
        // get user
        $securityContext = $this->container->get('security.context');
        $token = $securityContext->getToken();
        $user = $token->getUser();
        if (!is_object($user) || !$user instanceof \SansPapier\UserDataBundle\Entity\User) {
            throw new NotFoundHttpException('No Access here');
        }

        if (!$user->isPreferenceComplete()) {
            throw new NotFoundHttpException('Incomplete User');
        }

        // get session for the cart
        $session = $this->container->get('request')->getSession();
        $country = $session->get('country');
        $cart = $session->get('cart');

        if (!$cart) {
            throw new NotFoundHttpException('Nothing in the cart');
        }

        // solr config
        $configCatalogCore = array('adapteroptions' => array(
                'host' => $this->container->getParameter('sans_papier_shop.solr.host'),
                'port' => $this->container->getParameter('sans_papier_shop.solr.port'),
                'path' => $this->container->getParameter('sans_papier_shop.solr.path'),
                'core' => $this->container->getParameter('sans_papier_shop.solr.core_catalog'))
        );
        $currency = $this->container->getParameter('sans_papier_shop.solr.currency');
        $cart_total_price = $cart->getTotalPrice($configCatalogCore, $country);
        
        // create operations and transaction, store it in the session;
        $operation = new Operation();
        $transactionId = $this->generateTransactionId();
        $operation->setFromWebsite($_from);
        $transactions = $cart->createTransactions($configCatalogCore, $currency, $user->getUserId(), $country);
        foreach ($transactions as $transaction) {
            $operation->addTransaction($transaction);
        }
        $operation->setTotalPrice($cart_total_price);
        $operation->setUserIdFk($user->getUserId());
        $operation->setStatus(2);
        $operation->setTransactionAt(new \DateTime('now'));
        $operation->setMean("BYPASS");
        $operation->setSocgenTransactionId($transactionId);
        $em = $this->container->get('doctrine')->getEntityManager("shop");
        $em->persist($operation);
        $em->flush();
        $session->set('id_op', $operation->getOperationId());
        $this->logger->addInfo("################################ ** SOCGEN BYPASS ** ##################################");
        
        if($this->processDistributor($operation)) {
            $this->sendConfirmationMail($operation->getDilicomTransactionId());
        } else {
            $this->sendConfirmationMail($operation->getDilicomTransactionId());
            $this->sendErrorMail($operation->getDilicomTransactionId());
        }
        return $operation->getStatus();
    }
    
    /**
     * @Route("/request.{_format}/{_from}",  name="sanspapier_secure_request", defaults={"_format" = "html"})
     * @View()
     */
    public function requestAction($_from) {
        // get user
        $securityContext = $this->container->get('security.context');
        $token = $securityContext->getToken();
        $user = $token->getUser();
        if (!is_object($user) || !$user instanceof \SansPapier\UserDataBundle\Entity\User) {
            throw new NotFoundHttpException('No Access here');
        }

        if (!$user->isPreferenceComplete()) {
            throw new NotFoundHttpException('Incomplete User');
        }

        // get session for the cart
        $session = $this->container->get('request')->getSession();
        $country = $session->get('country');
        $cart = $session->get('cart');

        if (!$cart) {
            throw new NotFoundHttpException('Nothing in the cart');
        }

        // solr config
        $configCatalogCore = array('adapteroptions' => array(
                'host' => $this->container->getParameter('sans_papier_shop.solr.host'),
                'port' => $this->container->getParameter('sans_papier_shop.solr.port'),
                'path' => $this->container->getParameter('sans_papier_shop.solr.path'),
                'core' => $this->container->getParameter('sans_papier_shop.solr.core_catalog'))
        );
        $params = array();
        $currency = $this->container->getParameter('sans_papier_shop.solr.currency');
        $cart_total_price = $cart->getTotalPrice($configCatalogCore, $country);
        // total price to pay 
        $params['amount'] = $this->convertPrice($cart_total_price);
        //generate an Id operation
        $params['transaction_id'] = $this->generateTransactionId();
        // merchant id 
        $params['merchant_id'] = str_replace('"', '', $this->container->getParameter('sans_papier_shop.socgen.merchant_id'));
        // currency code
        $params['currency_code'] = $this->container->getParameter('sans_papier_shop.socgen.currency_code');
        // merchant country
        $params['merchant_country'] = $this->container->getParameter('sans_papier_shop.socgen.merchant_country');
        
        // responses management
        $params['normal_return_url'] = $params['cancel_return_url'] = $this->container->get('router')->generate('sanspapier_secure_response', array(), TRUE);
        $params['automatic_response_url'] = $this->container->get('router')->generate('sanspapier_secure_aresponse', array(), TRUE); // temp
        $params['language'] = $session->getLocale();
        $params['header_flag'] = 'no';
        
        // create operations and transaction, store it in the session;
        $operation = new Operation();
        $operation->setFromWebsite($_from);
        $transactions = $cart->createTransactions($configCatalogCore, $currency, $user->getUserId(), $country);
        foreach ($transactions as $transaction) {
            $operation->addTransaction($transaction);
        }
        $operation->setTotalPrice($cart_total_price);
        $operation->setUserIdFk($user->getUserId());
        $operation->setStatus(99);
        $operation->setTransactionAt(new \DateTime('now'));
        $operation->setMean("null");
        $operation->setSocgenTransactionId($params['transaction_id']);
        $em = $this->container->get('doctrine')->getEntityManager("shop");
        $em->persist($operation);
        $em->flush();
        $session->set('id_op', $operation->getOperationId());
        $this->logger->addInfo('Asking Socgen For CB Redirection Links');
        $response = $this->doPost($this->container->getParameter('sans_papier_shop.socgen.proxy'), http_build_query($params));
        
        return array($response);
    }

    /**
     * @Route("/response.{_format}",  name="sanspapier_secure_response", defaults={"_format" = "json"})
     * @View()
     */
    public function responseAction() {
        //request
        $request = $this->container->get('request');
        $crypted_data = $request->request->get('DATA');
        $response = $this->doPost($this->container->getParameter('sans_papier_shop.socgen.decoder'), "ENC=" . $crypted_data);
        $resarr = explode("|", $response);
        $params = $this->parseSocGenResponse($resarr);

        //get back the operation thnx to the transaction id;
        $em = $this->container->get('doctrine')->getEntityManager("shop");
        $repo = $em->getRepository('SansPapierShopBundle:Operation');
        $operation = $repo->findOneBy(array('socgen_transaction_id' => $params['transaction_id']));


        if ($operation->getStatus() == 3) { // paiement done and dilicom transaction done
            return new RedirectResponse(
                            $this->container->getParameter('sans_papier_shop.redirect.transaction_success.protocol') .
                            "://" .
                            $operation->getFromWebsite() .
                            $this->container->getParameter('sans_papier_shop.redirect.transaction_success.url')
            );
        }
        return new RedirectResponse(
                        $this->container->getParameter('sans_papier_shop.redirect.transaction_fail.protocol') .
                        "://" .
                        $operation->getFromWebsite() .
                        $this->container->getParameter('sans_papier_shop.redirect.transaction_fail.url')
        );
    }

    /**
     * @Route("/aresponse.{_format}",  name="sanspapier_secure_aresponse", defaults={"_format" = "json"})
     * @View()
     */
    public function aresponseAction() { // 4974934125497800
        
        $session = $this->container->get('request')->getSession();
        $request = $this->container->get('request');
        $this->logger->addInfo("################################# ** SOCGEN LOG ** ###################################");
        $this->logger->addInfo("SocGen server notification from server: " . $request->getClientIp());
        $crypted_data = $request->request->get('DATA');
        $response = $this->doPost($this->container->getParameter('sans_papier_shop.socgen.decoder'), "ENC=" . $crypted_data);
        $resarr = explode("|", $response);
        $params = $this->parseSocGenResponse($resarr);

        //get back the operation thnx to the transaction id;
        $em = $this->container->get('doctrine')->getEntityManager("shop");
        $repo = $em->getRepository('SansPapierShopBundle:Operation');
        $operation = $repo->findOneBy(array('socgen_transaction_id' => $params['transaction_id']));

        if ($params['code'] != 0) { //socgen actif
            $this->logger->addError($params['error']);
            return 0;
        } else {

            $this->logger->addInfo("response code: " . $params['response_code']);
            $this->logger->addInfo("paiement time: " . $params['payment_time']);
            $this->logger->addInfo("paiement date: " . $params['payment_date']);
            $this->logger->addInfo("cvv_response_code: " . $params['cvv_response_code']);
            $this->logger->addInfo("complementary_code: " . $params['complementary_code']);

            // set transaction at
            $operation->setTransactionAt(new \DateTime($params['payment_date'] . $params['payment_time']));

            //complementary code
            //00 OK
            //02 Encours dépassé
            //09 IP du pays inconnue
            //10 IP du pays refusé
            //12 Combi pays/IP carte interdit
            //12 Combi pays/IP carte inconnu
            //Default Transaction refusée
            //Response code
            //00 OK
            //03 Merchant_id incorrect
            //12 Transaction invalide
            //17 Annulation internaute
            //90 Service indispo
            //Default Refus bancaire

            //Payment OK
            if ($params['response_code'] == "00") { 
                $operation->setStatus(2);
                $operation->setMean($params['payment_means']);
                $em->persist($operation);
                $em->flush();
                if($this->processDistributor($operation)) {
                    //TODO: METTRE SEND CONFIRMATION MAIL ICI
                    $this->sendConfirmationMail($operation->getDilicomTransactionId());
                } else {
                    $this->sendConfirmationMail($operation->getDilicomTransactionId());
                    $this->sendErrorMail($operation->getDilicomTransactionId());
                }
                
            } else {
                //Payment KO
                $operation->setStatus(1);
                $session->remove('id_op');
                $em->persist($operation);
                $em->flush();
            }
        }
    }

    /**
     * Call Dilicom webservices and extract download links. it also create the User bookshelf in Database.
     * @param Operation $operation 
     */
    private function processDistributor(Operation $operation) {
        $this->logger->addInfo("################################# ** DILICOM LOG ** ##################################");
        // get user from operation
        $this->emUser = $this->container->get('doctrine')->getEntityManager("user");
        $this->emShop = $this->container->get('doctrine')->getEntityManager("shop");
        $formatEm = $this->emUser->getRepository('SansPapierUserDataBundle:Format');
        $user = $this->emUser->getRepository('SansPapierUserDataBundle:User')->findOneBy(array("user_id" => $operation->getUserIdFk()));
        $order_lines_free_books = array();
        $params = $this->initParamsArray($user->getUserId());
        $nbDilicomOrderLines = 0;
        $nbFreeBooksOrderLines = 0;
        $format = null;
        $productIdSolr = null;
        
        $transactions = $operation->getTransactions();
        foreach ($transactions as $transaction) {
            //Transaction that must be addressed to Dilicom
            if($transaction->getDistributorId() != $params['glnReseller']) {
                $params['orderRequestLines[' . $nbDilicomOrderLines . '].ean13'] = $transaction->getExternalId();
                $params['orderRequestLines[' . $nbDilicomOrderLines . '].glnDistributor'] = $transaction->getDistributorId();
                $params['orderRequestLines[' . $nbDilicomOrderLines . '].quantity'] = '1';
                $params['orderRequestLines[' . $nbDilicomOrderLines . '].unitPrice'] = $this->convertPrice($transaction->getPrice());
                $params['orderRequestLines[' . $nbDilicomOrderLines . '].lineReference'] = $transaction->getTransactionId();
                $this->logger->addInfo("lineReference " . $transaction->getTransactionId());
                $this->logger->addInfo("glnDistributor " . $transaction->getDistributorId());
                $this->logger->addInfo("unitPrice " . $this->convertPrice($transaction->getPrice()));
                $nbDilicomOrderLines++;
            } else {
                //Other transactions (free books from sanspapier)
                $format = $formatEm->findBy(array('name' => $transaction->getFormatName()));
                $productIdSolr = $transaction->getProductIdSolr();
                $order_lines_free_books[$nbFreeBooksOrderLines]['ean13'] = $transaction->getExternalId();
                $order_lines_free_books[$nbFreeBooksOrderLines]['links'][0]['url'] = $this->container->getParameter('sans_papier_shop.free_books.url').'600/'.$productIdSolr.'/'.$productIdSolr.'.'.$format->getName();
                $order_lines_free_books[$nbFreeBooksOrderLines]['links'][0]['format'] = $format->getOnixCode();
                $order_lines_free_books[$nbFreeBooksOrderLines]['links'][0]['formatDescription'] = $format->getName();
                $nbFreeBooksOrderLines++;
            }
        }
        // Processing the array of free books
        if($nbFreeBooksOrderLines > 0) {
            try {
                $this->processOrderLines($order_lines_free_books, $operation);
            } catch (\Exception $e) {
                $this->logger->addInfo('Error in processOrderLines for free books: '.$e->getMessage());
            }
        }
        
        // Processing the JSON response from Dilicom
        if($nbDilicomOrderLines > 0) {
            $response = $this->doAuthGet($params['glnReseller'], $params['passwordReseller'], http_build_query($params), $this->container->getParameter('sans_papier_shop.dilicom.url'));
            $json = json_decode($response, true);
            $this->logger->addInfo("Dilicom return status " . $json['returnStatus']);
            $this->logger->addInfo("Dilicom order detail " . $response);
            
            if ($json['returnStatus'] == "OK") {
                //******************//
                // No Dilicom error //
                //******************//
                $this->finaliseOperation($operation, $params['orderId']);
                $order_lines = $json['orderLines'];
                $this->logger->addInfo('process order lines ...');
                try {
                    $this->processOrderLines($order_lines, $operation);
                } catch (\Exception $e) {
                    $this->logger->addInfo('Error in processOrderLines (first dilicom return status OK): '.$e->getMessage());
                }
            } else {
                //*******************************************************************************//
                // Error while getting dilicom return, try a getOrderDetail() call on dilicom ws //
                //*******************************************************************************//
                $this->logger->addInfo("Dilicom data recup failed, try getOrderDetail");
                $response = $this->doAuthGet($params['glnReseller'], $params['passwordReseller'], http_build_query($params), $this->container->getParameter('sans_papier_shop.dilicom.url_order_detail'));
                $json = json_decode($response, true);
                $this->logger->addInfo("[getOrderDetail] Dilicom return status " . $json['returnStatus']);
                $this->logger->addInfo("[getOrderDetail] Dilicom order detail " . $response);

                if ($json['returnStatus'] == "OK") {
                    // Dilicom Request OK : let's update our operation status
                    $this->finaliseOperation($operation, $params['orderId']);
                    $order_lines = $json['orderLines'];
                    $this->logger->addInfo('[getOrderDetail] process order lines ...');
                    try {
                        $this->processOrderLines($order_lines, $operation);
                    } catch (\Exception $e) {
                        $this->logger->addInfo('Error in processOrderLines (second dilicom return status OK): '.$e->getMessage());
                    }
                } else {
                    $this->logger->addInfo("Provider Error");
                    return false;
                }
            }
        }
        return true;
    }
    
    private function initParamsArray($userId) {
        $params = array();
        $params['glnReseller'] = str_replace('"', '', $this->container->getParameter('sans_papier_shop.dilicom.gln'));
        $params['passwordReseller'] = $this->container->getParameter('sans_papier_shop.dilicom.password');
        $params['orderId'] = $this->generateDilicomOrderId($userId);
        $params['customerId'] = $userId;
        $params['finalBookOwner.identifier'] = $userId;
        $params['finalBookOwner.firstName'] = $user->getPreference()->getFirstName();
        $params['finalBookOwner.lastName'] = $user->getPreference()->getLastName();
        $params['finalBookOwner.email'] = $user->getEmail();
        $params['finalBookOwner.country'] = $user->getPreference()->getBillingAddress()->getCountryCode();
        $params['finalBookOwner.postalCode'] = '92120';
        $params['finalBookOwner.city'] = 'Montrouge';
        $this->logger->addInfo("orderId: " . $params['orderId']);
        return $params;
    }
    
    private function finaliseOperation($pOperation, $pOrderId) {
        $pOperation->setStatus(3);
        $pOperation->setProviderIdFk(0);
        $pOperation->setDilicomTransactionId($pOrderId);
        $this->emShop->persist($pOperation);
        $this->emShop->flush();
    }
    
    private function processOrderLines($pOrderLines, $pOperation) {
        try {
            foreach ($pOrderLines as $line) { //array of all links and books informations gathered from dilicom 
                $shelf = new ProductShelf();
                $shelf->setTransactionAt(new \DateTime('now'));
                $shelf->setUser($user);
                $links = $line['links'];
                $transaction = $this->emShop->getRepository('SansPapierShopBundle:Transaction')->findOneBy(array("operation" => $pOperation->getOperationId(), "external_id" => $line['ean13']));
                $shelf->setOperationIdFk($pOperation->getOperationId());
                $shelf->setTransactionIdFk($transaction->getTransactionId());
                foreach ($links as $link) {
                    $shelf->setProductIdSolr($transaction->getProductIdSolr());
                    $lnk = new ProductLink();
                    $lnk->setUrl($link['url']);
                    $lnk->setFormatDescription($link['formatDescription']);
                    $lnk->setFormatId($link['format']);
                    $shelf->addProductLink($lnk);
                }
                $this->emUser->persist($shelf);
            }
            $this->emUser->flush();
            $this->logger->addInfo("User has been flushed with his shelf");
            $this->addProductOrderedUnique($pOperation->getOperationId());
            $this->logger->addInfo("Product ordered unique managed");
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    private function addProductOrderedUnique($idOP) {
        
        $shopEm = $this->container->get('doctrine')->getEntityManager("shop");
        $userEm = $this->container->get('doctrine')->getEntityManager("user");
        $opeRepo = $shopEm->getRepository('SansPapierShopBundle:Operation');
        $productOrderedRepo = $userEm->getRepository('SansPapierUserDataBundle:ProductOrderedUnique');
        $shopOperation = $opeRepo->findOneBy(array('operation_id' => $idOP));
        if (!$shopOperation)
            return "Error: Operation does not exist";
        else {
            $transactions = $shopOperation->getTransactions();
            $needFlush = false;
            
            foreach ($transactions as $transaction) {
                $productId = $transaction->getProductIdSolr();
                $existProduct = $productOrderedRepo->find($productId);
                if(!$existProduct)
                {
                    $productSolr = $this->getProductById($productId);
                    if($productSolr != null)
                    {
                        $authorFirstName = '';
                        $authorLastName = '';
                        
                        if ($productSolr[0]['author_firstname'])
                            $authorFirstName = $productSolr[0]['author_firstname'][0];
                        if ($productSolr[0]['author_lastname'])
                            $authorLastName = $productSolr[0]['author_lastname'][0];

                        $publisherId = $productSolr[0]['publisher_id'];
                        $publisherName = $productSolr[0]['publisher_name'];
                        $title = $productSolr[0]['title'];

                        $productOrdered = new ProductOrderedUnique($productId, $publisherId, $publisherName, $title, $authorFirstName, $authorLastName);
                        $userEm->persist($productOrdered);
                        $needFlush = true;
                    }
                    else
                        return "Error: Product does not exist anymore";
                }
            }
            if($needFlush)
                $userEm->flush();
        }
    }

    private function parseSocGenResponse($resarr) {
        $ret = array();
        $ret['code'] = $resarr[1];
        $ret['error'] = $resarr[2];
        $ret['merchant_id'] = $resarr[3];
        $ret['merchant_country'] = $resarr[4];
        $ret['amount'] = $resarr[5];
        $ret['transaction_id'] = $resarr[6];
        $ret['payment_means'] = $resarr[7];
        $ret['transmission_date'] = $resarr[8];
        $ret['payment_time'] = $resarr[9];
        $ret['payment_date'] = $resarr[10];
        $ret['response_code'] = $resarr[11];
        $ret['payment_certificate'] = $resarr[12];
        $ret['authorisation_id'] = $resarr[13];
        $ret['currency_code'] = $resarr[14];
        $ret['card_number'] = $resarr[15];
        $ret['cvv_flag'] = $resarr[16];
        $ret['cvv_response_code'] = $resarr[17];
        $ret['bank_response_code'] = $resarr[18];
        $ret['complementary_code'] = $resarr[19];
        $ret['complementary_info'] = $resarr[20];
        $ret['return_context'] = $resarr[21];
        $ret['caddie'] = $resarr[22];
        $ret['receipt_complement'] = $resarr[23];
        $ret['merchant_language'] = $resarr[24];
        $ret['language'] = $resarr[25];
        $ret['customer_id'] = $resarr[26];
        $ret['order_id'] = $resarr[27];
        $ret['customer_email'] = $resarr[28];
        $ret['customer_ip_address'] = $resarr[29];
        $ret['capture_day'] = $resarr[30];
        $ret['capture_mode'] = $resarr[31];
        $ret['data'] = $resarr[32];
        $ret['order_validity'] = $resarr[33];
        $ret['transaction_condition'] = $resarr[34];
        $ret['statement_reference'] = $resarr[35];
        $ret['card_validity'] = $resarr[36];
        $ret['score_value'] = $resarr[37];
        $ret['score_color'] = $resarr[38];
        $ret['score_info'] = $resarr[39];
        $ret['score_threshold'] = $resarr[40];
        $ret['score_profile'] = $resarr[41];
        return $ret;
    }

    private function convertPrice($f_price) {
        return sprintf("%03s", round($f_price * 100));
    }

    private function doAuthGet($user, $pass, $data, $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . $data);
        curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $pass);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: close'));
        $result = curl_exec($ch);
        $this->logger->addInfo("Log for cURL on ".$url);
        $this->logger->addInfo("HTTP Code: ".curl_getinfo($ch, CURLINFO_HTTP_CODE));
        $this->logger->addInfo("Execution time: ".curl_getinfo($ch, CURLINFO_TOTAL_TIME));
        
        return $result;
    }

    /*
     * 
     */

    private function doPost($url, $data, $optional_headers = null) {
        $params = array('http' => array(
                'method' => 'POST',
                'content' => $data
                ));
        if ($optional_headers !== null) {
            $params['http']['header'] = $optional_headers;
        }
        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);
        if (!$fp) {
            throw new \Exception("Problem with $url");
        }
        $response = @stream_get_contents($fp);
        if ($response === false) {
            throw new \Exception("Problem reading data from $url");
        }
        return $response;
    }

    private function generateDilicomOrderId($pUserId) {
        srand($this->make_seed());
        return 'SP' . date('ymd') . (substr(time() * rand(1, 674), -5, 5)) . str_pad(substr($pUserId, -3, 3), 3, "0", STR_PAD_LEFT);
    }

    private function generateTransactionId() {
        $transaction_id = time() * rand(1, 19);
        $transaction_id = substr($transaction_id, -6, 6);
        return $transaction_id;
    }

    private function getProductById($id_product) {
        $configCatalogCore = array('adapteroptions' => array(
                'host' => $this->container->getParameter('sans_papier_user_data.solr.host'),
                'port' => $this->container->getParameter('sans_papier_user_data.solr.port'),
                'path' => $this->container->getParameter('sans_papier_user_data.solr.path'),
                'core' => $this->container->getParameter('sans_papier_user_data.solr.core_catalog'))
        );

        $client = new \Solarium_Client($configCatalogCore);
        $query = $client->createSelect();
        $query->setRows(1);
        $query->setQueryDefaultField('product_id');
        $query->setFields(array('author_firstname', 'author_lastname', 'author_id', 'publisher_id', 'publisher_name', 'product_id', 'title', 'EUR_c', 'format_name', 'file_size', 'publishing_date', 'isbn'));
        $query->setQuery($id_product);
        $resultset = $client->select($query);

        $result = $resultset->getDocuments();

        return $result;
    }
    
    private function convertCivility($entry) {
        switch ($entry) {
            case "Mr":
                return "M";
            case "Mme":
                return "MME";
            case "Mlle":
                return "MLE";
            default:
                return "";
        }
    }

    private function make_seed() {
        list($usec, $sec) = explode(' ', microtime());
        return (float) $sec + ((float) $usec * 100000);
    }
    
    private function sendConfirmationMail($_orderId) {

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

    private function sendErrorMail($_idOP) {

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
    
//    private function getProductById($id_product) {
//        $configCatalogCore = array('adapteroptions' => array(
//                'host' => $this->container->getParameter('sans_papier_user_data.solr.host'),
//                'port' => $this->container->getParameter('sans_papier_user_data.solr.port'),
//                'path' => $this->container->getParameter('sans_papier_user_data.solr.path'),
//                'core' => $this->container->getParameter('sans_papier_user_data.solr.core_catalog'))
//        );
//
//        $client = new \Solarium_Client($configCatalogCore);
//
//        // get a select query instance
//        $query = $client->createSelect();
//        $query->setRows(1);
//        //specify to Solr the default search field
//        $query->setQueryDefaultField('product_id');
//        //specify to Solr the field that have to appear in the resultset
//        $query->setFields(array('author_firstname', 'author_lastname', 'author_id', 'publisher_id', 'publisher_name', 'product_id', 'title', 'EUR_c', 'format_name', 'file_size', 'publishing_date', 'isbn'));
//        //specify to Solr the string to evaluate
//        $query->setQuery($id_product);
//        //this executes the query and returns the result
//        $resultset = $client->select($query);
//
//        $result = $resultset->getDocuments();
//
//        return $result;
//    }
}
?>
