<?php
/*  Copyright (C) 2013 NUNJE Aymeric
    Copyright (C) 2013 BRISOU Amaury

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
namespace SansPapier\MaintenanceBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use PHPExcel;
use PHPExcel_IOFactory;

class GenerateReportMBCommand extends ContainerAwareCommand {

    protected function initialize(InputInterface $input, OutputInterface $output) {
        parent::initialize($input, $output); //initialize parent class method
        $output->writeln('[' . date("H:i:s") . '] ' . 'Starting initialization...');
        $this->emShop = $this->getContainer()->get('doctrine')->getEntityManager('shop');
        $this->emUser = $this->getContainer()->get('doctrine')->getEntityManager('user');
        $output->writeln('[' . date("H:i:s") . '] ' . 'Initialization completed');
        $this->emShop->getConnection()->getConfiguration()->setSQLLogger(null);
        $this->emUser->getConnection()->getConfiguration()->setSQLLogger(null);
    }

    protected function configure() {
        $this->setName('sanspapier:GenerateReportMB')
                ->addArgument('external_website_id', InputArgument::REQUIRED, 'Select the mb to process');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $userRepo = $this->emUser->getRepository('SansPapierUserDataBundle:User');
        $productOrderedRepo = $this->emUser->getRepository('SansPapierUserDataBundle:ProductOrderedUnique');
        $externalWebsiteId = $input->getArgument('external_website_id');
        $dateNow = new \DateTime('now');
        $queryTransactions = $this->emShop->createQuery('SELECT t FROM SansPapierShopBundle:Transaction t JOIN t.operation o WHERE t.origin_shop_id = :externalId AND o.status = 3 AND DATE_DIFF(:dateNow, t.transactionAt) <= 7')
                ->setParameter('externalId', $externalWebsiteId)
                ->setParameter('dateNow', $dateNow);
        $transactions = $queryTransactions->getResult();
        $output->writeln('[' . date("H:i:s") . '] ' . 'Number of transactions: '.count($transactions));
        $objPHPExcel = new \PHPExcel();

        $objPHPExcel->getProperties()->setCreator("sanspapier.com")
                    ->setLastModifiedBy("sanspapier.com")
                    ->setTitle("Export des ventes numeriklirestore")
                    ->setSubject("Export des ventes export des ventes numeriklirestore");
        
        $objPHPExcel->createSheet(NULL, 0);
        $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A1', 'Date commande')
                ->setCellValue('B1', 'Numéro commande')
                ->setCellValue('C1', 'Mail client')
                ->setCellValue('D1', 'Id fichier')
                ->setCellValue('E1', 'Titre')
                ->setCellValue('F1', 'Prix');
        $objPHPExcel->getActiveSheet()->setTitle('Téléchargements payants');
        
        $objPHPExcel->createSheet(NULL, 1);
        $objPHPExcel->setActiveSheetIndex(1)
                ->setCellValue('A1', 'Date commande')
                ->setCellValue('B1', 'Numéro commande')
                ->setCellValue('C1', 'Mail client')
                ->setCellValue('D1', 'Id fichier')
                ->setCellValue('E1', 'Titre')
                ->setCellValue('F1', 'Prix');
        $objPHPExcel->getActiveSheet()->setTitle('Téléchargements gratuits');
        
        $lineNumFree = 1;
        $lineNumPayant = 1;
        $output->writeln('[' . date("H:i:s") . '] Writing the excel file /var/www/data_sanspapier/reports/rapport_ventes_numeriklirestore_'.$dateNow->format('Y-m-d').'.xls');
        foreach($transactions as $transaction) {
            $productId = $transaction->getProductIdSolr();
            $productOrdered = $productOrderedRepo->find($productId);
            if($productOrdered != null) {
                $title = $productOrdered->getTitle();
                $dateCommande = $transaction->getOperation()->getTransactionAt()->format('Y-m-d H:i:s');
                $numCommande = $transaction->getOperation()->getDilicomTransactionId();
                $idUser = $transaction->getOperation()->getUserIdFk();
                $user = $userRepo->find($idUser);
                $userMail = $user->getEmail();
                $price = $transaction->getPrice();
                
                if($price == 0) {
                    $idSheet = 1;
                    $lineNumFree++;
                    $objPHPExcel->setActiveSheetIndex($idSheet)
                            ->setCellValue('A'.$lineNumFree, $dateCommande)
                            ->setCellValue('B'.$lineNumFree, $numCommande)
                            ->setCellValue('C'.$lineNumFree, $userMail)
                            ->setCellValue('D'.$lineNumFree, $productId)
                            ->setCellValue('E'.$lineNumFree, $title)
                            ->setCellValue('F'.$lineNumFree, $price);
                } else {
                    $idSheet = 0;
                    $lineNumPayant++;
                    $objPHPExcel->setActiveSheetIndex($idSheet)
                            ->setCellValue('A'.$lineNumPayant, $dateCommande)
                            ->setCellValue('B'.$lineNumPayant, $numCommande)
                            ->setCellValue('C'.$lineNumPayant, $userMail)
                            ->setCellValue('D'.$lineNumPayant, $productId)
                            ->setCellValue('E'.$lineNumPayant, $title)
                            ->setCellValue('F'.$lineNumPayant, $price);
                }
            } else {
                $output->writeln('[' . date("H:i:s") . '] ' . 'Transaction '.$transaction->getTransactionId().' / product '.$productId.' > sans product_ordered_unique');
            }
        }
        
        $objPHPExcel->setActiveSheetIndex(0);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('/var/www/data_sanspapier/reports/rapport_ventes_numeriklirestore_'.$dateNow->format('Y-m-d').'.xls');
        $output->writeln('[' . date("H:i:s") . '] Sending the report by mail');
        $message = \Swift_Message::newInstance()
                ->setSubject('Rapport de ventes numeriklirestore')    
                ->setFrom(array('info@sanspapier.com' => 'sanspapier.com'))
                ->setTo(array('gayrard.jf@gmail.com' => 'J.F. Gayrard'))
                ->setBody('Bonjour, veuillez trouver ci-joint le rapport des ventes de numeriklirestore de la semaine passée')
                ->attach(\Swift_Attachment::fromPath('/var/www/data_sanspapier/reports/rapport_ventes_numeriklirestore_'.$dateNow->format('Y-m-d').'.xls'));
        
        $this->getContainer()->get('mailer')->send($message);
        $output->writeln('[' . date("H:i:s") . '] Mail sent, process finished');
        exit();
    }
}

?>
