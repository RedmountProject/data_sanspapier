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
namespace SansPapier\BooksMartBundle\Command;

use \DOMDocument;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BooksMapCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this->setName('sanspapier:sitemap')
     ->setDescription('Task to make an indexed sitemap')
     ->addArgument('host', InputArgument::REQUIRED, 'Enter host for sitemap');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $host = $input->getArgument('host');
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
    $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">";

    $xml .= "<url><loc>http://" . $host . "/search.php#!main</loc><lastmod>" . date("Y-m-d") . "</lastmod><changefreq>daily</changefreq></url>";

    $output->writeln("Gathering all the books to make a sitemap for " . $host);
    $configCatalogCore = array('adapteroptions' => array(
        'host' => $this->getContainer()->getParameter('sans_papier_books_mart.solr.host'),
        'port' => $this->getContainer()->getParameter('sans_papier_books_mart.solr.port'),
        'path' => $this->getContainer()->getParameter('sans_papier_books_mart.solr.path'),
        'core' => $this->getContainer()->getParameter('sans_papier_books_mart.solr.core_catalog'))
    );

    $client = new \Solarium_Client($configCatalogCore);

    // get a select query instance
    $query = $client->createSelect();
    $query->setRows(5000);
    $query->setFields(array('product_id', 'title', 'last_modif'));
    //specify to Solr the string to evaluate
    $resultset = $client->select($query);

    $docs = $resultset->getDocuments();
    $dateModif = '';
    foreach ($docs as $doc)
    {
        $dateModif = $doc->last_modif;
        $xml .= "<url>";
        $xml .= "<loc>http://" . $host . "/search.php#!booksheet_". $doc->product_id ."</loc>";
        if($dateModif != '')
            $xml .= "<lastmod>" . $dateModif . "</lastmod>";
        $xml .= "<changefreq>monthly</changefreq>";
        $xml .= "</url>";
    }
    
    $xml .= "</urlset>";
    $sitemap = new DOMDocument();
    $sitemap->formatOutput = true;
    $sitemap->loadXML($xml);
    $path = $this->getContainer()->get('kernel')->getRootDir() . "/../../front_sanspapier/html4/";
    $sitemap->save($path."sitemap.xml");
  }

}

?>
