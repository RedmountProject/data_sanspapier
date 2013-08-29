<?php

namespace SansPapier\UserDataBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use SansPapier\ShopBundle\Entity\Operation;
use SansPapier\ShopBundle\Entity\Transaction;
use SansPapier\UserDataBundle\Entity\ProductShelf;
use SansPapier\UserDataBundle\Entity\ProductLink;

class injectFreePackCommand extends ContainerAwareCommand {

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output); //initialize parent class method
        $output->writeln('['.date("H:i:s").'] '.'Starting initialization...');
        $this->emShop = $this->getContainer()->get('doctrine')->getEntityManager('shop');
        $this->emUser = $this->getContainer()->get('doctrine')->getEntityManager('user');
        $output->writeln('['.date("H:i:s").'] '.'Initialization completed');
    }
    
    protected function configure()
    {
        $this->setName('sanspapier:injectFreePack')
             ->addArgument('userId', InputArgument::REQUIRED, 'Select the userId')
             ->addArgument('packs', InputArgument::REQUIRED, 'Select the packs to add to user account (1: jeunesse / 
2: classique / 3: sf / 4: poesie / 5: polar) dash-separated:');
    }
    
  protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('['.date("H:i:s").'] '.'Starting execution...');
        $connection = $this->getContainer()->get("doctrine.dbal.source_connection");
        $userRepo = $this->emUser->getRepository('SansPapierUserDataBundle:User');
        $userId = $input->getArgument('userId');
        $user = $userRepo->find($userId);
        
        if(!$user)
        {
            return false;
        }
        
        $packs = $input->getArgument('packs');

        if(!$packs)
        {
            return false;
        }

        $packsArray = explode('-',$packs);
        $productIds = array();
        foreach($packsArray as $pack)
        {
            $productIds[$pack] = array();
            switch($pack)
            {
                case '1':
                    array_push($productIds[$pack],61954,61955,61956,61957,61958);
                    break;
                case '2':
                    array_push($productIds[$pack],61959,61960,61961,61962,61963);
                    break;
                case '3':
                    array_push($productIds[$pack],61964,61965,61966,61967,61968);
                    break;
                case '4':
                    array_push($productIds[$pack],61969,61970,61971,61972,61973);
                    break;
                case '5':
                    array_push($productIds[$pack],61974,61975,61976,61977,61978);
                    break;
                default:
                    break;
            }
        }
        
        foreach($productIds as $key=>$packContent)
        {
            //Setting the operation and transactions
            $operation = new Operation();
            $orderId = $this->generateSDLOrderId($key);
            $transactionId = $this->generateTransactionId();
            $operation->setFromWebsite('www.sanspapier.com');
            foreach($packContent as $product)
            {
                $tr = new Transaction();
                $tr->setExternalId('9999999999999');
                $tr->setDistributorId('4');
                $tr->setPrice('0.00');
                $tr->setProductIdSolr($product);
                $tr->setUserIdFk($userId);
                $tr->setCreditCardIdFk("null");
                $tr->setTransactionAt(new \DateTime('now'));
                $operation->addTransaction($tr);
            }
            $operation->setTotalPrice('0.00');
            $operation->setUserIdFk($userId);
            $operation->setStatus(3);
            $operation->setTransactionAt(new \DateTime('now'));
            $operation->setMean("BYPASS SDL-2013");
            $operation->setSocgenTransactionId($transactionId);
            $operation->setDilicomTransactionId($orderId);
            $this->emShop->persist($operation);
            $this->emShop->flush();

            //Getting the links for each transaction
            foreach($packContent as $product)
            {
                $shelf = new ProductShelf();
                $shelf->setTransactionAt(new \DateTime('now'));
                $shelf->setUser($user);

                $transaction = $this->emShop->getRepository('SansPapierShopBundle:Transaction')->findOneBy(array("operation" => $operation->getOperationId(), "product_id_solr" => $product));
                $shelf->setOperationIdFk($operation->getOperationId());
                $shelf->setTransactionIdFk($transaction->getTransactionId());
                $shelf->setProductIdSolr($transaction->getProductIdSolr());

                $links = $connection->fetchAll('SELECT resource_id_fk, resource_url FROM product_resource where product_id_fk = '.$product);
                foreach ($links as $link)
                {
                    $lnk = new ProductLink();
                    $lnk->setUrl($link['resource_url']);
                    switch($link['resource_id_fk'])
                    {
                        case 9919:
                            $lnk->setFormatDescription('EPUB');
                            $lnk->setFormatId('E101');
                            break;
                        case 9931:
                            $lnk->setFormatDescription('PDF');
                            $lnk->setFormatId('E107');
                            break;
                    }
                    $shelf->addProductLink($lnk);
                }
                $this->emUser->persist($shelf);
            }
        }
        
        $this->emUser->flush();
        echo "\n".'['.date("H:i:s").'] '.'Execution completed';
    }


  private function generateTransactionId() {
    $transaction_id = time() * rand(1, 19);
    $transaction_id = substr($transaction_id, -6, 6);
    return $transaction_id;
  }

   private function generateSDLOrderId($pPackId) {
    switch($pPackId)
    {
        case '1':
            return 'SDL-JEUNESSE';
            break;
        case '2':
            return 'SDL-CLASSIQUE';
            break;
        case '3':
            return 'SDL-FANTASTIQUE';
            break;
        case '4':
            return 'SDL-POESIE';
            break;
        case '5':
            return 'SDL-POLAR';
            break;
        default:
            break;
    }
  }
}

?>
