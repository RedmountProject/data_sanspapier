<?php

namespace SansPapier\UserDataBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SansPapier\UserDataBundle\Entity\ProductOrderedUnique;

/**
 * Description of RattrapageProductsOrderedCommand
 */
class RattrapageProductsOrderedCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this->setName('sanspapier:rattrapage:productsOrdered')
     ->setDescription('RattrapageProductsOrdered');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $output->writeln("Init");
    
    $userEm = $this->getContainer()->get('doctrine')->getEntityManager('user');
    $productOrderedRepo = $userEm->getRepository('SansPapierUserDataBundle:ProductOrderedUnique');
    $productShelfRepo = $userEm->getRepository('SansPapierUserDataBundle:ProductShelf');

    $productShelfs = $productShelfRepo->findAll();
    $nbTotal = count($productShelfs);
    
    foreach ($productShelfs as $key => $productShelf) {
        $productId = $productShelf->getProductIdSolr();
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
                $output->writeln("Persist ".$key."/".$nbTotal);
            }
            else
                $output->writeln("Prodct ".$productId." no more in Solr, set it manually");
        }
    }
    $output->writeln("Flush");
    $userEm->flush();
    $output->writeln("Done");
  }
  
  private function getProductById($id_product) {
        $configCatalogCore = array('adapteroptions' => array(
                'host' => $this->getContainer()->getParameter('sans_papier_user_data.solr.host'),
                'port' => $this->getContainer()->getParameter('sans_papier_user_data.solr.port'),
                'path' => $this->getContainer()->getParameter('sans_papier_user_data.solr.path'),
                'core' => $this->getContainer()->getParameter('sans_papier_user_data.solr.core_catalog'))
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
