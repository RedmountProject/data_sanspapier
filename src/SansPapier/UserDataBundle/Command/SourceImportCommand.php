<?php

namespace SansPapier\UserDataBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SansPapier\UserDataBundle\Entity\Genre;
use SansPapier\UserDataBundle\Entity\Publisher;

/**
 * Description of GetSourceDataCommand
 *
 * @author nunja
 */
class SourceImportCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this->setName('sanspapier:source:import')
     ->setDescription('Import genres and editors fixtures from the sans papier source database.');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $em = $this->getContainer()->get('doctrine')->getEntityManager('user');
    $output->writeln("Starting to import from source database... ");
    $connection = $this->getContainer()->get("doctrine.dbal.source_connection");

    // IF EMPTY IN FRONT
    $query = $em->createQuery('SELECT COUNT(g.genre_id) FROM SansPapierUserDataBundle:Genre g');
    $g_count = $query->getSingleScalarResult();
    $query = $em->createQuery('SELECT COUNT(p.publisher_id) FROM SansPapierUserDataBundle:Publisher p');
    $p_count = $query->getSingleScalarResult();

    if (!$g_count && !$p_count)
    {
      $output->writeln("front database is empty, bulk load of all source data ... ");
      $publishers = $connection->fetchAll('SELECT * FROM publisher');
      $genres = $connection->fetchAll('SELECT * FROM genre where genre_id>0');
      $output->writeln("importing " . count($publishers) . " publisher(s) ...");
      $output->writeln("importing " . count($genres) . " genre(s) ...");

      foreach ($genres as $genre)
      {
        $user_genre = new Genre();
        $user_genre->setGenreId($genre['genre_id']);
        $user_genre->setName($genre['name']);
        $em->persist($user_genre);
      }

      foreach ($publishers as $publisher)
      {
        $user_pub = new Publisher();
        $user_pub->setPublisherId($publisher['publisher_id']);
        $user_pub->setName($publisher['name']);
        $user_pub->setExternalIdExt($publisher['external_id']);
        $user_pub->setWebsite($publisher['website']);
        $em->persist($user_pub);
      }

      $em->flush();
    } else
    {
      $updated = 0;
      $added = 0;

      $output->writeln("front database is filled with data, making a diff operation ... ");
      $publishers = $connection->fetchAll('SELECT * FROM publisher');
      foreach ($publishers as $publisher)
      {
        $fk_id = $publisher['publisher_id'];
        $user_publisher = $em->getRepository('SansPapierUserDataBundle:Publisher')->findOneBy(array("publisher_id" => $fk_id));
        if (!count($user_publisher))
        {
          $user_publisher = new Publisher();
          $user_publisher->setPublisherId($publisher['publisher_id']);
          $user_publisher->setName($publisher['name']);
          $user_publisher->setExternalIdExt($publisher['external_id']);
          $user_publisher->setWebsite($publisher['website']);
          $em->persist($user_publisher);
          $added++;
          
        } else
        {
          if ($publisher['name'] !== $user_publisher->getName())
          {
            $user_publisher->setName($publisher['name']);
            $updated++;
            $em->persist($user_publisher);
          }
          
          if ($publisher['website'] !== $user_publisher->getWebsite())
          {
            $user_publisher->setWebsite($publisher['website']);
            $updated++;
            $em->persist($user_publisher);
          }
          
          if ($publisher['external_id'] !== $user_publisher->getExternalIdExt())
          {
            $user_publisher->setExternalIdExt($publisher['external_id']);
            $updated++;
            $em->persist($user_publisher);
          }
        }
      }

      $genres = $connection->fetchAll('SELECT * FROM genre where genre_id>0');
      foreach ($genres as $genre)
      {
        $fk_id = $genre['genre_id'];
        $user_genre = $em->getRepository('SansPapierUserDataBundle:Genre')->findOneBy(array("genre_id" => $fk_id));
        if (!count($user_genre))
        {
          $user_genre = new Genre();
          $user_genre->setGenreId($genre['genre_id']);
          $user_genre->setName($genre['name']);
          $em->persist($user_genre);
          $added++;
          
        } else
        {
          if ($genre['name'] !== $user_genre->getName())
          {
            $user_genre->setName($genre['name']);
            $updated++;
            $em->persist($user_genre);
          }
        }
      }
      
      $output->writeln("front database updated: ".$added. " add operations, " .$updated. " updated operations.");
    }
  }

}

?>
